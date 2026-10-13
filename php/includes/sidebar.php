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
<!-- Botao de 3 riscos: abre o menu. Fica FORA da sidebar porque ela
     sai da tela quando fechada -->
<button type="button" class="sidebar-abrir" id="sidebarAbrir" aria-label="Abrir menu">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5"/>
</svg>
</button>

<!-- Botao de alto contraste: fica sempre visivel no topo, do lado
     do botao do menu (ou dentro do menu, quando ele esta aberto) -->
<button type="button" class="botao-contraste" id="botaoContraste" aria-label="Alto contraste" aria-pressed="false" title="Alto contraste">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-circle-half" viewBox="0 0 16 16">
  <path d="M8 15A7 7 0 1 0 8 1zm0 1A8 8 0 1 1 8 0a8 8 0 0 1 0 16"/>
</svg>
</button>

<aside class="sidebar">
    <div class="sidebar-topo">
        <!-- Seta: fecha o menu -->
        <button type="button" class="sidebar-fechar" id="sidebarFechar" aria-label="Fechar menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
</svg>
        </button>
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
    // No computador o menu comeca aberto (classe sidebar-fechada esconde);
    // no celular comeca fechado (classe sidebar-aberta mostra).
    // A escolha do computador fica salva para valer em todas as paginas.
    var CHAVE = 'sisgeh-sidebar-fechada';
    var corpo = document.body;
    var celular = window.matchMedia('(max-width: 768px)');

    try {
        if (localStorage.getItem(CHAVE) === '1') {
            corpo.classList.add('sidebar-fechada');
        }
    } catch (e) {}

    function salvar(fechada) {
        try { localStorage.setItem(CHAVE, fechada ? '1' : '0'); } catch (e) {}
    }

    document.getElementById('sidebarAbrir').addEventListener('click', function () {
        if (celular.matches) {
            corpo.classList.add('sidebar-aberta');
        } else {
            corpo.classList.remove('sidebar-fechada');
            salvar(false);
        }
    });

    // ===== Alto contraste =====
    // Liga a classe "alto-contraste" no <html>; as cores ficam em
    // css/components/contraste.css. A escolha fica salva no navegador.
    var CHAVE_CONTRASTE = 'sisgeh-alto-contraste';
    var raiz = document.documentElement;
    var botaoContraste = document.getElementById('botaoContraste');

    function aplicarContraste(ativo) {
        raiz.classList.toggle('alto-contraste', ativo);
        botaoContraste.setAttribute('aria-pressed', ativo ? 'true' : 'false');
        atualizarGraficos();
    }

    // Graficos (Chart.js) sao desenhados em canvas e nao pegam o CSS,
    // entao troca a cor dos textos e linhas deles na mao
    function atualizarGraficos() {
        if (!window.Chart) {
            return;
        }
        var ativo = raiz.classList.contains('alto-contraste');
        Chart.defaults.color = ativo ? '#ffffff' : '#666666';
        Chart.defaults.borderColor = ativo ? 'rgba(255,255,255,0.4)' : 'rgba(0,0,0,0.1)';
        Object.values(Chart.instances).forEach(function (grafico) {
            grafico.options.color = Chart.defaults.color;
            Object.values(grafico.options.scales || {}).forEach(function (eixo) {
                if (eixo.ticks) { eixo.ticks.color = Chart.defaults.color; }
                if (eixo.title) { eixo.title.color = Chart.defaults.color; }
                if (eixo.grid) { eixo.grid.color = Chart.defaults.borderColor; }
            });
            grafico.update();
        });
    }

    try {
        if (localStorage.getItem(CHAVE_CONTRASTE) === '1') {
            aplicarContraste(true);
        }
    } catch (e) {}

    // Os graficos sao criados depois deste script, entao ajusta de novo
    // quando a pagina terminar de carregar
    document.addEventListener('DOMContentLoaded', atualizarGraficos);

    botaoContraste.addEventListener('click', function () {
        var ativo = !raiz.classList.contains('alto-contraste');
        aplicarContraste(ativo);
        try { localStorage.setItem(CHAVE_CONTRASTE, ativo ? '1' : '0'); } catch (e) {}
    });

    document.getElementById('sidebarFechar').addEventListener('click', function () {
        if (celular.matches) {
            corpo.classList.remove('sidebar-aberta');
        } else {
            corpo.classList.add('sidebar-fechada');
            salvar(true);
        }
    });
})();
</script>
