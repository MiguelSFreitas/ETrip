<?php
/**
 * historico.php
 *
 * Mostra as simulações salvas em historico.json como cards (imagem do
 * carro à esquerda, informações no meio, botão de excluir à direita).
 * Não tem login: qualquer um que acessar a página vê e pode apagar
 * itens do histórico — é um recurso simples, não uma área privada.
 */

$arquivoHistorico = __DIR__ . '/historico.json';

function lerHistorico($arquivo) {
    if (!file_exists($arquivo)) {
        return [];
    }
    $dados = json_decode(file_get_contents($arquivo), true);
    return is_array($dados) ? $dados : [];
}

function salvarHistoricoCompleto($arquivo, $historico) {
    file_put_contents($arquivo, json_encode($historico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Processa exclusão, se o botão de lixeira de algum item foi clicado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_id'])) {
    $historico = lerHistorico($arquivoHistorico);
    $historico = array_values(array_filter($historico, function ($item) {
        return $item['id'] !== $_POST['excluir_id'];
    }));
    salvarHistoricoCompleto($arquivoHistorico, $historico);

    // Redireciona pra evitar reenvio do POST se a pessoa atualizar a página (F5)
    header('Location: historico.php');
    exit;
}

$historico = lerHistorico($arquivoHistorico);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Histórico — ETRIP</title>
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
            <li><a href="index.php#viagem">Calcular viagem</a></li>
            <li><a href="historico.php" class="ativo">Histórico</a></li>
        </ul>
        <button type="button" id="toggle-tema" class="botao-tema" aria-label="Alternar modo escuro" title="Alternar modo escuro">🌙</button>
    </nav>

    <main class="camadas" style="margin-top: 110px;">

        <section class="camada">
            <header class="camada-topo">
                <span class="passo">🕒</span>
                <div>
                    <h2>Histórico de simulações</h2>
                    <p>Últimas viagens calculadas no ETRIP.</p>
                </div>
            </header>
        </section>

        <?php if (empty($historico)): ?>

            <section class="camada" style="text-align:center;">
                <p>Nenhuma simulação feita ainda.</p>
                <div class="acao">
                    <a href="index.php#viagem" class="hero-botao" style="display:inline-block;">Fazer a primeira simulação</a>
                </div>
            </section>

        <?php else: ?>

            <div class="grade-historico">
                <?php foreach ($historico as $item): ?>
                    <div class="historico-card">

                        <?php if (!empty($item['imagem'])): ?>
                            <img src="/<?php echo htmlspecialchars($item['imagem']); ?>" alt="<?php echo htmlspecialchars($item['carro']); ?>" class="historico-img">
                        <?php else: ?>
                            <div class="historico-img historico-img-vazia" aria-hidden="true">🚗</div>
                        <?php endif; ?>

                        <div class="historico-info">
                            <span class="historico-carro"><?php echo htmlspecialchars($item['carro']); ?></span>
                            <span class="historico-rota">
                                <?php echo htmlspecialchars($item['origem']); ?> → <?php echo htmlspecialchars($item['destino']); ?>
                            </span>
                            <span class="historico-detalhe">
                                <?php echo $item['distancia_km']; ?> km · <?php echo htmlspecialchars($item['data_hora']); ?>
                            </span>
                        </div>

                        <form method="post" action="historico.php" class="historico-excluir-form">
                            <input type="hidden" name="excluir_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                            <button type="submit" class="botao-excluir" aria-label="Excluir esta simulação" title="Excluir">
                                🗑
                            </button>
                        </form>

                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </main>

    <footer class="rodape">
        <img src="/img/etrip-logo-completa.png" alt="ETRIP" class="logo-footer">
    </footer>

    <script src="/js/tema.js"></script>

</body>

</html>