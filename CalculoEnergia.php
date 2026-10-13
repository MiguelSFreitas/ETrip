<?php
/**
 * CalculoEnergia.php
 *
 * Só as funções de cálculo do ETRIP — nenhuma delas lê $_POST nem
 * imprime HTML. É "a calculadora": recebe números, devolve números
 * (ou lança Exception se a entrada for inválida).
 *
 * Quem recebe o formulário e monta a página de resultado é o
 * Resultado.php, que inclui este arquivo.
 *
 * IMPORTANTE: o cálculo nunca assume que o carro pode chegar ao
 * destino "descarregado". Se a viagem gasta mais energia do que a
 * bateria TEM (mesmo sem reserva nenhuma), o sistema procura uma
 * parada numa cidade intermediária, dentro do alcance real do carro
 * — nunca sugere recarregar só depois de já ter chegado ao destino
 * se o carro não teria energia pra chegar lá.
 */

function calcularBateriaDisponivel($capacidadeBateriaKwh, $percentualSaida) {
    return $capacidadeBateriaKwh * ($percentualSaida / 100);
}

function calcularBateriaReserva($capacidadeBateriaKwh, $percentualChegadaDesejado) {
    return $capacidadeBateriaKwh * ($percentualChegadaDesejado / 100);
}

function calcularEnergiaNecessaria($distanciaKm, $consumoMedioKwhKm) {
    return $distanciaKm * $consumoMedioKwhKm;
}

function calcularCustoEstimado($energiaNecessariaKwh, $tarifaReaisKwh) {
    return round($energiaNecessariaKwh * $tarifaReaisKwh, 2);
}

function calcularTempoRecargaMinutos($energiaFaltanteKwh, $potenciaCarregadorKw) {
    if ($potenciaCarregadorKw <= 0) {
        return 0;
    }
    return round(($energiaFaltanteKwh / $potenciaCarregadorKw) * 60, 0);
}

/**
 * Calcula a viagem considerando 3 situações possíveis:
 *
 * 1. 'ok'                -> aguenta chegar com a reserva desejada ou mais
 * 2. 'chega_sem_reserva' -> o carro CONSEGUE chegar fisicamente ao destino
 *                           (não fica sem bateria no meio do caminho), só
 *                           que chega abaixo da reserva que a pessoa queria
 * 3. 'precisa_parar'     -> o carro NÃO tem energia nem pra chegar ao
 *                           destino — precisa parar ANTES de chegar lá
 *
 * Lança Exception em caso de entrada inválida.
 */
function calcularViagem($capacidadeBateriaKwh, $consumoMedioKwhKm, $percentualSaida, $percentualChegadaDesejado, $distanciaKm) {
    if ($percentualSaida < 0 || $percentualSaida > 100) {
        throw new Exception('Percentual de saída inválido. Deve estar entre 0 e 100.');
    }
    if ($percentualChegadaDesejado < 0 || $percentualChegadaDesejado > 100) {
        throw new Exception('Percentual de chegada inválido. Deve estar entre 0 e 100.');
    }
    if ($percentualSaida <= $percentualChegadaDesejado) {
        throw new Exception('O percentual de bateria ao sair deve ser maior que o percentual desejado na chegada.');
    }
    if ($distanciaKm <= 0) {
        throw new Exception('Distância inválida.');
    }

    $bateriaDisponivelKwh = calcularBateriaDisponivel($capacidadeBateriaKwh, $percentualSaida); // tudo que tem, sem reserva
    $bateriaReservaKwh    = calcularBateriaReserva($capacidadeBateriaKwh, $percentualChegadaDesejado);
    $bateriaUtilizavelKwh = $bateriaDisponivelKwh - $bateriaReservaKwh; // o que pode gastar respeitando a reserva
    $energiaNecessariaKwh = calcularEnergiaNecessaria($distanciaKm, $consumoMedioKwhKm);

    // Até onde o carro consegue ir de verdade, mesmo descarregando tudo (sem reserva nenhuma)
    $distanciaMaximaKm = $consumoMedioKwhKm > 0 ? $bateriaDisponivelKwh / $consumoMedioKwhKm : 0;

    if ($energiaNecessariaKwh <= $bateriaUtilizavelKwh) {
        $situacao = 'ok';
    } elseif ($distanciaKm <= $distanciaMaximaKm) {
        // Chega no destino, mas abaixo da reserva desejada — ainda assim CHEGA
        $situacao = 'chega_sem_reserva';
    } else {
        // Não teria energia nem pra chegar — precisa parar antes
        $situacao = 'precisa_parar';
    }

    $energiaFaltanteKwh = $situacao === 'ok'
        ? 0
        : round($energiaNecessariaKwh - $bateriaUtilizavelKwh, 2);

    return [
        'situacao'                  => $situacao,
        'bateria_disponivel_kwh'    => round($bateriaDisponivelKwh, 2),
        'bateria_reserva_kwh'       => round($bateriaReservaKwh, 2),
        'bateria_utilizavel_kwh'    => round($bateriaUtilizavelKwh, 2),
        'energia_necessaria_kwh'    => round($energiaNecessariaKwh, 2),
        'energia_faltante_kwh'      => $energiaFaltanteKwh,
        'distancia_maxima_km'       => round($distanciaMaximaKm, 1),
    ];
}

function buscarCarroOuFalhar($carroId, $carros) {
    $carro = buscarCarroPorId($carroId, $carros);
    if (!$carro) {
        throw new Exception('Carro não encontrado. Volte e selecione marca, modelo e versão novamente.');
    }
    return $carro;
}

function buscarRotaOuFalhar($origem, $destino, $distancias) {
    if ($origem === $destino) {
        throw new Exception('A cidade de origem e destino não podem ser iguais.');
    }
    $rota = buscarDistancia($origem, $destino, $distancias);
    if (!$rota) {
        throw new Exception('Essa rota ainda não está cadastrada no sistema.');
    }
    return $rota;
}

/**
 * Escolhe o posto de maior potência numa cidade. Retorna null se não
 * houver nenhum posto cadastrado ali.
 */
function melhorPostoDaCidade($cidade, $postos) {
    $postosCidade = buscarPostosPorCidade($cidade, $postos);
    if (empty($postosCidade)) {
        return null;
    }
    usort($postosCidade, fn($a, $b) => $b['potencia_kw'] <=> $a['potencia_kw']);
    return $postosCidade[0];
}

/**
 * Retorna as cidades entre origem e destino, na ordem do trajeto
 * (lista $cidades já está organizada norte -> sul pela BR-101).
 */
function cidadesIntermediarias($cidadeOrigem, $cidadeDestino, $cidades) {
    $idxOrigem = array_search($cidadeOrigem, $cidades);
    $idxDestino = array_search($cidadeDestino, $cidades);
    if ($idxOrigem === false || $idxDestino === false) {
        return [];
    }
    if ($idxOrigem < $idxDestino) {
        return array_slice($cidades, $idxOrigem + 1, $idxDestino - $idxOrigem - 1);
    }
    return array_reverse(array_slice($cidades, $idxDestino + 1, $idxOrigem - $idxDestino - 1));
}

/**
 * Procura, entre as cidades intermediárias, a mais distante da origem
 * que ainda esteja dentro do alcance do carro E que tenha posto de
 * recarga cadastrado. É a melhor parada possível: avança o máximo que
 * dá antes de precisar recarregar.
 */
function encontrarParadaViavel($cidadeOrigem, $cidadeDestino, $distanciaMaximaKm, $cidades, $distancias, $postos) {
    $intermediarias = cidadesIntermediarias($cidadeOrigem, $cidadeDestino, $cidades);
    $melhorParada = null;

    foreach ($intermediarias as $cidade) {
        $rotaParcial = buscarDistancia($cidadeOrigem, $cidade, $distancias);
        if (!$rotaParcial || $rotaParcial['distancia_km'] > $distanciaMaximaKm) {
            continue; // fora do alcance do carro a partir da origem
        }
        $posto = melhorPostoDaCidade($cidade, $postos);
        if ($posto) {
            $melhorParada = [
                'cidade' => $cidade,
                'distancia_km' => $rotaParcial['distancia_km'],
                'posto' => $posto,
            ];
        }
    }

    return $melhorParada;
}

/**
 * Salva um registro no histórico de simulações (historico.json), sem
 * precisar de login. Mantém só as últimas $limite simulações, mais
 * recente primeiro.
 */
function salvarHistorico($registro, $limite = 20) {
    $arquivo = __DIR__ . '/historico.json';
    $historico = file_exists($arquivo) ? json_decode(file_get_contents($arquivo), true) : [];
    if (!is_array($historico)) {
        $historico = [];
    }
    $registro['id'] = uniqid('sim_', true); // id único, usado pra poder apagar o item depois
    array_unshift($historico, $registro);
    $historico = array_slice($historico, 0, $limite);
    file_put_contents($arquivo, json_encode($historico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}