<?php
// Espera que a pagina que faz o include ja tenha definido $paginaAtiva
// com um dos valores: simulacao, historico, analise_preditiva, estacao, configuracoes
$paginaAtiva = $paginaAtiva ?? '';

$itensMenu = [
    ['chave' => 'simulacao', 'icone' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-droplet-fill" viewBox="0 0 16 16">
  <path d="M8 16a6 6 0 0 0 6-6c0-1.655-1.122-2.904-2.432-4.362C10.254 4.176 8.75 2.503 8 0c0 0-6 5.686-6 10a6 6 0 0 0 6 6M6.646 4.646l.708.708c-.29.29-1.128 1.311-1.907 2.87l-.894-.448c.82-1.641 1.717-2.753 2.093-3.13"/>
</svg>', 'texto' => 'Simulação', 'url' => 'simulacao.php'],

    ['chave' => 'historico', 'icone' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock-fill" viewBox="0 0 16 16">
  <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
</svg>', 'texto' => 'Histórico', 'url' => 'historico.php'],

    ['chave' => 'analise_preditiva', 'icone' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard-data-fill" viewBox="0 0 16 16">
  <path d="M6.5 0A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0zm3 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5z"/>
  <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1A2.5 2.5 0 0 1 9.5 5h-3A2.5 2.5 0 0 1 4 2.5zM10 8a1 1 0 1 1 2 0v5a1 1 0 1 1-2 0zm-6 4a1 1 0 1 1 2 0v1a1 1 0 1 1-2 0zm4-3a1 1 0 0 1 1 1v3a1 1 0 1 1-2 0v-3a1 1 0 0 1 1-1"/>
</svg>', 'texto' => 'Análise Preditiva', 'url' => 'analise_preditiva.php'],

    ['chave' => 'estacao', 'icone' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bar-chart-line-fill" viewBox="0 0 16 16">
  <path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1z"/>
</svg>', 'texto' => 'Estações', 'url' => 'estacao.php'],

    ['chave' => 'configuracoes', 'icone' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear-fill" viewBox="0 0 16 16">
  <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z"/>
</svg>', 'texto' => 'Configurações', 'url' => 'configuracoes.php'],
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
                <div classe="icone-container">
                    <span class="icone"><?= $item['icone'] ?></span>
                </div>

                <div class="texto-container">
                    <span class="texto"><?= htmlspecialchars($item['texto']) ?></span>
                </div>
            </a>
        <?php endforeach; ?>

                    <div class="sidebar-sair">
                        <form method="post" action="logout.php">
                        <button type="submit">

                            <div class="icone-container">
                                <span class="icone"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-door-open-fill" viewBox="0 0 16 16">
  <path d="M1.5 15a.5.5 0 0 0 0 1h13a.5.5 0 0 0 0-1H13V2.5A1.5 1.5 0 0 0 11.5 1H11V.5a.5.5 0 0 0-.57-.495l-7 1A.5.5 0 0 0 3 1.5V15zM11 2h.5a.5.5 0 0 1 .5.5V15h-1zm-2.5 8c-.276 0-.5-.448-.5-1s.224-1 .5-1 .5.448.5 1-.224 1-.5 1"/>
</svg></span>
                            </div>

                            <div class="texto-container">
                                <span class="texto">Sair</span>
                            </div>
                        
                        </button>
                        </form>
                    </div>

    </nav>



    <div class="sidebar-logo">
        <span> <img src="../images/logo.png" alt="Logo" class="logo"></span>
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
