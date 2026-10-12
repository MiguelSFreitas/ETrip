<?php
/**
 * index.php
 *
 * O PHP gera:
 * - as opções de MARCA (via listarMarcas)
 * - as opções de ORIGEM/DESTINO (via $cidades)
 * - os dados completos dos carros em JSON, pro JS usar na cascata
 *   marca -> modelo -> versao (selects.js lê esse JSON, nunca busca
 *   nada separado — o dado já vem pronto do PHP).
 */
require_once __DIR__ . '/backend/data/dados_carros.php';   // gera $carros, listarMarcas(), listarModelosPorMarca()
require_once __DIR__ . '/backend/data/dados_cidades.php';  // gera $cidades
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ETRIP</title>
    <link rel="stylesheet" href="/css/style.css">
    <script>
        // Aplica o tema salvo ANTES da página desenhar, pra evitar o "flash"
        // de tela clara aparecendo por um instante antes do escuro carregar.
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
            <li><a href="index.php" class="ativo">Início</a></li>
            <li><a href="#viagem">Calcular viagem</a></li>
            <li><a href="historico.php">Histórico</a></li>
            <li><a href="#">Contato</a></li>
        </ul>
        <button type="button" id="toggle-tema" class="botao-tema" aria-label="Alternar modo escuro" title="Alternar modo escuro">🌙</button>
    </nav>

    <header class="hero">
        <div class="hero-texto">
            <img src="/img/etrip-logo-completa.png" alt="ETRIP" class="logo-hero">
            <p>Escolha seu carro elétrico, a rota e a bateria que você tem.
                A gente calcula se você chega ou se precisa parar para carregar.</p>
            <a href="#viagem" class="hero-botao">Planejar viagem</a>
        </div>

        <div class="hero-visual" aria-hidden="true">
            <div class="bateria">
                <span class="celula"></span>
                <span class="celula"></span>
                <span class="celula"></span>
                <span class="celula"></span>
                <span class="celula"></span>
            </div>
            <div class="estrada"><span class="carro"></span></div>
        </div>
    </header>

    <main class="camadas" id="viagem">

        <form method="post" action="CalculoEnergia.php" id="formulario"></form>

        <section class="camada" id="veiculo">
            <header class="camada-topo">
                <span class="passo">1</span>
                <div>
                    <h2>Seu carro</h2>
                    <p>O consumo muda de modelo para modelo.</p>
                </div>
            </header>

            <div class="campos">
                <p class="campo">
                    <label for="marca">Marca</label>
                    <select name="marca" id="marca" form="formulario">
                        <option value="">Selecione a marca</option>
                        <?php foreach (listarMarcas($carros) as $marca): ?>
                            <option value="<?php echo htmlspecialchars($marca); ?>">
                                <?php echo htmlspecialchars($marca); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <p class="campo oculto" id="bloco_modelo">
                    <label for="modelo">Modelo</label>
                    <select name="modelo" id="modelo" form="formulario">
                        <option value="">Selecione o modelo</option>
                    </select>
                </p>

                <p class="campo oculto" id="bloco_versao">
                    <label for="versao">Versão</label>
                    <select name="versao" id="versao" form="formulario">
                        <option value="">Selecione a versão</option>
                    </select>
                </p>
            </div>

            <!-- Preenchido pelo selects.js assim que a versão for escolhida.
                 É o único campo de carro que o CalculoEnergia.php realmente usa. -->
            <input type="hidden" name="carro_id" id="carro_id" form="formulario" required>
        </section>

        <section class="camada" id="rota">
            <header class="camada-topo">
                <span class="passo">2</span>
                <div>
                    <h2>Sua rota</h2>
                    <p>De onde você sai e para onde vai.</p>
                </div>
            </header>

            <div class="campos">
                <p class="campo">
                    <label for="origem">Origem</label>
                    <select name="origem" id="origem" form="formulario" required>
                        <option value="">Selecione a origem</option>
                        <?php foreach ($cidades as $cidade): ?>
                            <option value="<?php echo htmlspecialchars($cidade); ?>">
                                <?php echo htmlspecialchars($cidade); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <p class="campo">
                    <label for="destino">Destino</label>
                    <select name="destino" id="destino" form="formulario" required>
                        <option value="">Selecione o destino</option>
                        <?php foreach ($cidades as $cidade): ?>
                            <option value="<?php echo htmlspecialchars($cidade); ?>">
                                <?php echo htmlspecialchars($cidade); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>
            </div>
        </section>

        <section class="camada" id="bateria">
            <header class="camada-topo">
                <span class="passo">3</span>
                <div>
                    <h2>Sua bateria</h2>
                    <p>Quanto você tem ao sair e quanto quer ter ao chegar.</p>
                </div>
            </header>

            <div class="campos">
                <p class="campo">
                    <label for="percentual_saida">Bateria ao sair: <span id="valor_saida">80%</span></label>
                    <input type="range" name="percentual_saida" id="percentual_saida" form="formulario" min="0" max="100" value="80">
                </p>

                <p class="campo">
                    <label for="percentual_chegada">Bateria desejada na chegada: <span id="valor_chegada">20%</span></label>
                    <input type="range" name="percentual_chegada" id="percentual_chegada" form="formulario" min="0" max="100" value="20">
                </p>
            </div>

            <p id="aviso" class="aviso" role="alert"></p>
        </section>

        <div class="acao">
            <button type="submit" form="formulario" id="btn-calcular">Calcular viagem</button>
        </div>

    </main>

    <footer class="rodape">
        <img src="/img/etrip-logo-completa.png" alt="ETRIP" class="logo-footer">
    </footer>

    <!-- Dados completos dos carros, gerados pelo PHP, pro selects.js filtrar
         modelo/versão sem precisar buscar nada separado. -->
    <script>
        const carrosDisponiveis = <?php echo json_encode($carros, JSON_UNESCAPED_UNICODE); ?>;
    </script>

    <script src="/js/selects.js"></script>
    <script src="/js/sliders.js"></script>
    <script src="/js/tema.js"></script>
    <script>
        document.querySelectorAll('input[type="range"]').forEach(function(r) {
            var pintar = function() {
                r.style.setProperty('--p', r.value + '%');
            };
            r.addEventListener('input', pintar);
            pintar();
        });
    </script>

    <script>
        // Mostra "Calculando..." no botão assim que o formulário é enviado.
        // Como o PHP recarrega a página pra mostrar o resultado, isso evita
        // a sensação de que o site travou durante o pequeno delay.
        document.getElementById('formulario').addEventListener('submit', function () {
            const botao = document.getElementById('btn-calcular');
            botao.disabled = true;
            botao.textContent = 'Calculando...';
        });
    </script>

</body>

</html>