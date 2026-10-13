<?php
/**
 * Resultado.php
 *
 * Recebe o formulário do index.php via POST, usa as funções de
 * CalculoEnergia.php para calcular se a bateria aguenta a viagem e
 * sugerir onde recarregar, e devolve o HTML da página de resultado.
 */

require_once __DIR__ . '/CalculoEnergia.php';               // funções de cálculo
require_once __DIR__ . '/backend/data/dados_carros.php';    // $carros, buscarCarroPorId()
require_once __DIR__ . '/backend/data/dados_cidades.php';   // $cidades, $distancias, buscarDistancia()
require_once __DIR__ . '/backend/data/dados_postos.php';    // $postos, buscarPostosPorCidade()


// ------------------------------------------------------------
// RECEBE OS DADOS DO FORMULÁRIO E FAZ O CÁLCULO
// ------------------------------------------------------------

$erro = null;
$carro = null;
$rota = null;
$resultado = null;
$postoSugerido = null;
$paradaIntermediaria = null;
$postoNaOrigem = null;
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

        // Se nem a primeira cidade no caminho tem posto dentro do alcance,
        // a saída é recarregar mais ANTES de sair, na própria cidade de origem.
        if (!$paradaIntermediaria) {
            $postoNaOrigem = melhorPostoDaCidade($cidadeOrigem, $postos);
        }
    }

    salvarHistorico([
        'carro' => $carro['marca'] . ' ' . $carro['modelo'] . ' ' . $carro['versao'],
        'imagem' => $carro['caminho_imagem'] ?? '',
        'origem' => $cidadeOrigem,
        'destino' => $cidadeDestino,
        'distancia_km' => $rota['distancia_km'],
        'situacao' => $resultado['situacao'],
        'data_hora' => date('d/m/Y H:i'),
    ]);

} catch (Exception $e) {
    $erro = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ETRIP — Resultado</title>
    <link rel="stylesheet" href="/css/style.css">
    <script>
        if (localStorage.getItem('etrip-tema') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
</head>

<body>

    <nav class="menu">
        <a href="index.php" class="marca">
            <img src="/img/etrip-logo-completa.png" alt="ETRIP" class="logo-nav">
        </a>
        <ul>
            <li><a href="index.php">Início</a></li>
            <li><a href="index.php#viagem">Nova simulação</a></li>
            <li><a href="historico.php">Histórico</a></li>
        </ul>
        <button type="button" id="toggle-tema" class="botao-tema" aria-label="Alternar modo escuro" title="Alternar modo escuro">🌙</button>
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
                    <?php elseif ($postoNaOrigem): ?>
                        <p class="aviso" style="background:#fff8e1; color:#8a6d00;">
                            Não encontramos nenhuma cidade com posto de recarga dentro do alcance do carro
                            entre <?php echo htmlspecialchars($cidadeOrigem); ?> e
                            <?php echo htmlspecialchars($cidadeDestino); ?>. Recarregue mais antes de sair —
                            há um posto disponível na própria cidade de <?php echo htmlspecialchars($cidadeOrigem); ?>.
                        </p>
                        <div class="campos">
                            <p class="campo">
                                <label>Posto sugerido (antes de sair)</label>
                                <?php echo htmlspecialchars($postoNaOrigem['nome']); ?>
                            </p>
                            <p class="campo">
                                <label>Cidade</label>
                                <?php echo htmlspecialchars($postoNaOrigem['cidade']); ?>
                            </p>
                            <p class="campo">
                                <label>Endereço</label>
                                <span class="endereco-texto"><?php echo htmlspecialchars($postoNaOrigem['endereco']); ?></span>
                                <button type="button" class="btn-copiar" data-endereco="<?php echo htmlspecialchars($postoNaOrigem['endereco']); ?>">Copiar endereço</button>
                            </p>
                            <p class="campo">
                                <label>Conector</label>
                                <?php echo htmlspecialchars($postoNaOrigem['tipo_conector']); ?> — <?php echo $postoNaOrigem['potencia_kw']; ?> kW
                            </p>
                        </div>
                    <?php else: ?>
                        <p class="aviso">
                            Não encontramos nenhuma cidade com posto de recarga dentro do alcance do carro entre
                            <?php echo htmlspecialchars($cidadeOrigem); ?> e <?php echo htmlspecialchars($cidadeDestino); ?>,
                            nem na própria cidade de origem.
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

    <footer class="rodape">
        <img src="/img/etrip-logo-completa.png" alt="ETRIP" class="logo-footer">
    </footer>

    <script src="/js/tema.js"></script>

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