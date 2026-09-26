<?php
// Espera que a pagina que faz o include ja tenha definido $paginaAtiva
// com um dos valores: simulacao, historico, analise_preditiva, estacao, configuracoes
$paginaAtiva = $paginaAtiva ?? '';

$itensMenu = [
    ['chave' => 'simulacao', 'icone' => '💧', 'texto' => 'Simulação', 'url' => 'simulacao.php'],
    ['chave' => 'historico', 'icone' => '🕒', 'texto' => 'Histórico', 'url' => 'historico.php'],
    ['chave' => 'analise_preditiva', 'icone' => '📊', 'texto' => 'Análise Preditiva', 'url' => 'analise_preditiva.php'],
    ['chave' => 'estacao', 'icone' => '🌦️', 'texto' => 'Estações', 'url' => 'estacao.php'],
    ['chave' => 'configuracoes', 'icone' => '⚙️', 'texto' => 'Configurações', 'url' => 'configuracoes.php'],
];
?>
<button type="button" class="sidebar-toggle-mobile" id="sidebarToggleMobile" aria-label="Abrir menu">☰</button>

<aside class="sidebar">
    <div class="sidebar-topo">
        <div class="caixa_de_texto">
            <input type="text" class="search-text" placeholder="Pesquisar...">
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($itensMenu as $item): ?>
            <a href="<?= htmlspecialchars($item['url']) ?>" class="<?= $paginaAtiva === $item['chave'] ? 'ativo' : '' ?>">
                <span class="icone"><?= $item['icone'] ?></span>
                <?= htmlspecialchars($item['texto']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-sair">
        <form method="post" action="logout.php">
            <button type="submit">
                <span class="icone">📤</span>
                Sair
            </button>
        </form>
    </div>

    <div class="sidebar-logo">
        <span>💧</span>
    </div>
</aside>

<script>
(function () {
    var botao = document.getElementById('sidebarToggleMobile');
    if (botao) {
        botao.addEventListener('click', function () {
            document.body.classList.toggle('sidebar-aberta');
        });
    }
})();
</script>
