<?php
/**
 * CalculoEnergia.php
 *
 * Recebe o formulário do index.php via POST, calcula se a bateria
 * aguenta a viagem e sugere onde recarregar. Já devolve o HTML da
 * página de resultado — sem fetch/JSON.
 *
 * IMPORTANTE: o cálculo nunca assume que o carro pode chegar ao
 * destino "descarregado". Se a viagem gasta mais energia do que a
 * bateria TEM (mesmo sem reserva nenhuma), o sistema procura uma
 * parada numa cidade intermediária, dentro do alcance real do carro
 * — nunca sugere recarregar só depois de já ter chegado ao destino
 * se o carro não teria energia pra chegar lá.
 */

require_once __DIR__ . '/backend/data/dados_carros.php';   // $carros, buscarCarroPorId()
require_once __DIR__ . '/backend/data/dados_cidades.php';  // $cidades, $distancias, buscarDistancia()
require_once __DIR__ . '/backend/data/dados_postos.php';   // $postos, buscarPostosPorCidade()


// ------------------------------------------------------------
// 1. FUNÇÕES DE CÁLCULO
// ------------------------------------------------------------

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


// ------------------------------------------------------------
// 2. RECEBE OS DADOS DO FORMULÁRIO E FAZ O CÁLCULO
// ------------------------------------------------------------

$erro = null;
$carro = null;
$rota = null;
$resultado = null;
$postoSugerido = null;
$paradaIntermediaria = null;
$cidadeOrigem = null;
$cidadeDestino = null;

// Valor fixo por enquanto — TODO: Paulo vai pesquisar o valor real da tarifa de energia no ES
$tarifaReaisKwh = 0.75;

try {
    $carroId = isset($_POST['carro_id']) && $_POST['carro_id'] !== '' ? (int) $_POST['carro_id'] : null;
    $cidadeOrigem = $_POST['origem'] ?? '';
    $cidadeDestino = $_POST['destino'] ?? '';
    $percentualSaida = isset($_POST['percentual_saida']) ? (float) $_POST['percentual_saida'] : null;
    $percentualChegadaDesejado = isset($_POST['percentual_chegada']) ? (float) $_POST['percentual_chegada'] : null;

    if (!$carroId || !$cidadeOrigem || !$cidadeDestino || $percentualSaida === null || $percentualChegadaDesejado === null) {
        throw new Exception('Preencha todos os campos antes de calcular.');
    }

    $carro = buscarCarroOuFalhar($carroId, $carros);
    $rota  = buscarRotaOuFalhar($cidadeOrigem, $cidadeDestino, $distancias);

    $resultado = calcularViagem(
        $carro['bateria_kwh'],
        $carro['consumo_kwh_km'],
        $percentualSaida,
        $percentualChegadaDesejado,
        $rota['distancia_km']
    );

    $resultado['custo_estimado'] = calcularCustoEstimado($resultado['energia_necessaria_kwh'], $tarifaReaisKwh);

    if ($resultado['situacao'] === 'chega_sem_reserva') {
        // O carro CHEGA ao destino, só que abaixo da reserva desejada.
        // Faz sentido sugerir recarregar lá.
        $postoSugerido = melhorPostoDaCidade($cidadeDestino, $postos);
        if (!$postoSugerido) {
            $postoSugerido = melhorPostoDaCidade($cidadeOrigem, $postos);
        }
        if ($postoSugerido) {
            $resultado['tempo_recarga_min'] = calcularTempoRecargaMinutos(
                $resultado['energia_faltante_kwh'],
                $postoSugerido['potencia_kw']
            );
        }
    } elseif ($resultado['situacao'] === 'precisa_parar') {
        // O carro NÃO teria energia pra chegar ao destino.
        // Precisa de parada antes, dentro do alcance real dele.
        $paradaIntermediaria = encontrarParadaViavel(
            $cidadeOrigem,
            $cidadeDestino,
            $resultado['distancia_maxima_km'],
            $cidades,
            $distancias,
            $postos
        );
    }

} catch (Exception $e) {
    $erro = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E_TRIP — Resultado</title>
    <link rel="stylesheet" href="/css/style.css">
</head>

<body>

    <nav class="menu">
        <a href="index.php" class="marca">E_TRIP</a>
        <ul>
            <li><a href="index.php">Início</a></li>
            <li><a href="index.php#viagem">Nova simulação</a></li>
        </ul>
    </nav>

    <main class="camadas" style="margin-top: 110px;">

        <?php if ($erro): ?>

            <section class="camada">
                <p id="aviso" class="aviso" role="alert"><?php echo htmlspecialchars($erro); ?></p>
                <div class="acao">
                    <a href="index.php" class="hero-botao" style="display:inline-block; text-align:center;">Voltar e tentar de novo</a>
                </div>
            </section>

        <?php else: ?>

            <section class="camada">
                <header class="camada-topo">
                    <span class="passo">✓</span>
                    <div>
                        <h2>Resumo da viagem</h2>
                        <p><?php echo htmlspecialchars($carro['marca'] . ' ' . $carro['modelo'] . ' ' . $carro['versao']); ?></p>
                    </div>
                </header>

                <?php if (!empty($carro['caminho_imagem'])): ?>
                    <img
                        src="/<?php echo htmlspecialchars($carro['caminho_imagem']); ?>"
                        alt="<?php echo htmlspecialchars($carro['marca'] . ' ' . $carro['modelo'] . ' ' . $carro['versao']); ?>"
                        style="width:100%; max-width:360px; display:block; margin:0 auto 20px; border-radius:16px;"
                    >
                <?php endif; ?>

                <div class="campos">
                    <p class="campo">
                        <label>Origem</label>
                        <?php echo htmlspecialchars($cidadeOrigem); ?>
                    </p>
                    <p class="campo">
                        <label>Destino</label>
                        <?php echo htmlspecialchars($cidadeDestino); ?>
                    </p>
                    <p class="campo">
                        <label>Distância</label>
                        <?php echo $rota['distancia_km']; ?> km
                    </p>
                    <p class="campo">
                        <label>Tempo estimado de viagem</label>
                        <?php echo $rota['tempo_min']; ?> minutos
                    </p>
                </div>
            </section>

            <section class="camada" id="rota">
                <header class="camada-topo">
                    <span class="passo">🔋</span>
                    <div>
                        <h2>Bateria</h2>
                        <p>Com base nas preferências que você definiu.</p>
                    </div>
                </header>

                <div class="bateria" aria-hidden="true" style="margin: 0 auto 24px; max-width: 220px;">
                    <span class="celula"></span>
                    <span class="celula"></span>
                    <span class="celula"></span>
                    <span class="celula"></span>
                    <span class="celula"></span>
                </div>

                <div class="campos">
                    <p class="campo">
                        <label>Energia necessária para a viagem</label>
                        <?php echo $resultado['energia_necessaria_kwh']; ?> kWh
                    </p>
                    <p class="campo">
                        <label>Alcance real do carro (sem reserva)</label>
                        <?php echo $resultado['distancia_maxima_km']; ?> km
                    </p>
                </div>
            </section>

            <?php if ($resultado['situacao'] === 'ok'): ?>

                <section class="camada" id="bateria">
                    <header class="camada-topo">
                        <span class="passo">🚗</span>
                        <div>
                            <h2>Sua bateria aguenta!</h2>
                            <p>Você chega com a reserva de bateria que definiu, sem precisar recarregar.</p>
                        </div>
                    </header>
                </section>

            <?php elseif ($resultado['situacao'] === 'chega_sem_reserva'): ?>

                <section class="camada" id="bateria">
                    <header class="camada-topo">
                        <span class="passo">⚡</span>
                        <div>
                            <h2>Você chega, mas abaixo da reserva desejada</h2>
                            <p>O carro consegue chegar ao destino, só que com menos bateria do que você gostaria. Recomendamos recarregar ao chegar.</p>
                        </div>
                    </header>

                    <?php if ($postoSugerido): ?>
                        <div class="campos">
                            <p class="campo">
                                <label>Posto sugerido</label>
                                <?php echo htmlspecialchars($postoSugerido['nome']); ?>
                            </p>
                            <p class="campo">
                                <label>Cidade</label>
                                <?php echo htmlspecialchars($postoSugerido['cidade']); ?>
                            </p>
                            <p class="campo">
                                <label>Endereço</label>
                                <span class="endereco-texto"><?php echo htmlspecialchars($postoSugerido['endereco']); ?></span>
                                <button type="button" class="btn-copiar" data-endereco="<?php echo htmlspecialchars($postoSugerido['endereco']); ?>">Copiar endereço</button>
                            </p>
                            <p class="campo">
                                <label>Conector</label>
                                <?php echo htmlspecialchars($postoSugerido['tipo_conector']); ?> — <?php echo $postoSugerido['potencia_kw']; ?> kW
                            </p>
                            <p class="campo">
                                <label>Tempo estimado de recarga</label>
                                <?php echo $resultado['tempo_recarga_min']; ?> minutos
                            </p>
                        </div>
                    <?php else: ?>
                        <p class="aviso">Nenhum posto de recarga cadastrado nesta rota.</p>
                    <?php endif; ?>
                </section>

            <?php else: // situacao === 'precisa_parar' ?>

                <section class="camada" id="bateria">
                    <header class="camada-topo">
                        <span class="passo">⚠️</span>
                        <div>
                            <h2>O carro não chega ao destino sem parar antes</h2>
                            <p>Com a bateria que você tem, o carro ficaria sem energia no meio do caminho. É necessário recarregar antes de chegar ao destino.</p>
                        </div>
                    </header>

                    <?php if ($paradaIntermediaria): ?>
                        <div class="campos">
                            <p class="campo">
                                <label>Parar em</label>
                                <?php echo htmlspecialchars($paradaIntermediaria['cidade']); ?>
                                (a <?php echo $paradaIntermediaria['distancia_km']; ?> km da origem)
                            </p>
                            <p class="campo">
                                <label>Posto sugerido</label>
                                <?php echo htmlspecialchars($paradaIntermediaria['posto']['nome']); ?>
                            </p>
                            <p class="campo">
                                <label>Endereço</label>
                                <span class="endereco-texto"><?php echo htmlspecialchars($paradaIntermediaria['posto']['endereco']); ?></span>
                                <button type="button" class="btn-copiar" data-endereco="<?php echo htmlspecialchars($paradaIntermediaria['posto']['endereco']); ?>">Copiar endereço</button>
                            </p>
                            <p class="campo">
                                <label>Conector</label>
                                <?php echo htmlspecialchars($paradaIntermediaria['posto']['tipo_conector']); ?> — <?php echo $paradaIntermediaria['posto']['potencia_kw']; ?> kW
                            </p>
                        </div>
                        <p class="aviso" style="background:#fff8e1; color:#8a6d00;">
                            Esta é a melhor parada dentro do alcance do carro. Depois de recarregar lá, faça uma nova simulação para o restante do trajeto até <?php echo htmlspecialchars($cidadeDestino); ?>.
                        </p>
                    <?php else: ?>
                        <p class="aviso">
                            Não encontramos nenhuma cidade com posto de recarga dentro do alcance do carro entre
                            <?php echo htmlspecialchars($cidadeOrigem); ?> e <?php echo htmlspecialchars($cidadeDestino); ?>.
                            Essa viagem não é recomendada com a bateria atual.
                        </p>
                    <?php endif; ?>
                </section>

            <?php endif; ?>

            <section class="camada">
                <header class="camada-topo">
                    <span class="passo">R$</span>
                    <div>
                        <h2>Custo estimado</h2>
                        <p>R$ <?php echo $resultado['custo_estimado']; ?></p>
                    </div>
                </header>
            </section>

            <div class="acao">
                <a href="index.php" class="hero-botao" style="display:block; text-align:center;">Fazer nova simulação</a>
            </div>

        <?php endif; ?>

    </main>

    <footer class="rodape">E_TRIP</footer>

    <script>
        document.querySelectorAll('.btn-copiar').forEach(function (botao) {
            botao.addEventListener('click', function () {
                const endereco = botao.getAttribute('data-endereco');
                navigator.clipboard.writeText(endereco).then(function () {
                    const textoOriginal = botao.textContent;
                    botao.textContent = 'Copiado!';
                    setTimeout(function () {
                        botao.textContent = textoOriginal;
                    }, 2000);
                }).catch(function () {
                    alert('Não foi possível copiar automaticamente. Endereço: ' + endereco);
                });
            });
        });
    </script>

</body>

</html>