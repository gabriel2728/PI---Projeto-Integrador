<?php
session_start();
include('error_handler.php');
include('seguranca.php');
include('conexao.php');

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

$nomeUsuario = $_SESSION['nomeUsuario'];

$conn->query("CREATE TABLE IF NOT EXISTS DadosEstacao (
    id_dado INT AUTO_INCREMENT PRIMARY KEY,
    estacao VARCHAR(100) NOT NULL,
    mes_ano DATE NOT NULL,
    temp_min_media DECIMAL(5,2) NULL,
    temp_max_media DECIMAL(5,2) NOT NULL,
    umid_min_media DECIMAL(5,2) NOT NULL,
    umid_max_media DECIMAL(5,2) NOT NULL,
    precipitacao_mm DECIMAL(10,2) NOT NULL,
    fonte VARCHAR(500) NULL,
    INDEX idx_estacao_data (estacao, mes_ano)
)");

$result = $conn->query('SELECT estacao, mes_ano, temp_min_media, temp_max_media, umid_min_media, umid_max_media, precipitacao_mm, fonte FROM DadosEstacao ORDER BY estacao, mes_ano ASC');

$dadosPorEstacao = [];
$fontesPorEstacao = [];
while ($row = $result->fetch_assoc()) {
    $estacao = $row['estacao'];
    $dadosPorEstacao[$estacao][] = $row;
    if (!empty($row['fonte'])) {
        $fontesPorEstacao[$estacao][$row['fonte']] = true;
    }
}

$nomesEstacoes = array_keys($dadosPorEstacao);

// Labels de mês (ex.: "1/2023") e versão ISO ("2023-01") para filtro, a partir da primeira estação com dados
$labels = [];
$labelsIso = [];
if (!empty($nomesEstacoes)) {
    foreach ($dadosPorEstacao[$nomesEstacoes[0]] as $row) {
        $labels[] = (int) date('n', strtotime($row['mes_ano'])) . '/' . date('Y', strtotime($row['mes_ano']));
        $labelsIso[] = date('Y-m', strtotime($row['mes_ano']));
    }
}
$mesMinIso = $labelsIso[0] ?? null;
$mesMaxIso = end($labelsIso) ?: null;

$coresEstacao = [
    'Bauru - Água Parada' => '#2f80ed',
    'Ourinhos' => '#16a34a',
    'Franca' => '#f59e0b',
];

$payload = [
    'labels' => $labels,
    'labelsIso' => $labelsIso,
    'estacoes' => [],
];
foreach ($dadosPorEstacao as $estacao => $linhas) {
    $payload['estacoes'][] = [
        'nome' => $estacao,
        'cor' => $coresEstacao[$estacao] ?? '#6b7280',
        'tempMin' => array_map(fn($l) => $l['temp_min_media'] !== null ? (float) $l['temp_min_media'] : null, $linhas),
        'tempMax' => array_map(fn($l) => (float) $l['temp_max_media'], $linhas),
        'umidMin' => array_map(fn($l) => (float) $l['umid_min_media'], $linhas),
        'umidMax' => array_map(fn($l) => (float) $l['umid_max_media'], $linhas),
        'precip' => array_map(fn($l) => (float) $l['precipitacao_mm'], $linhas),
    ];
}

$dadosGraficoJs = json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$paginaAtiva = 'estacao';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estações - SiSGEH</title>
    <link rel="stylesheet" href="../css/components/sidebar.css">
    <link rel="stylesheet" href="../css/analise_preditiva.css">
    <link rel="stylesheet" href="../css/estacao.css">
    <link rel="stylesheet" href="../css/components/botoes.css">
</head>
<body>
<?php include('includes/sidebar.php'); ?>

<main class="container com-sidebar">
    <div class="layout">
        <div class="intro intro-principal">
            <div class="mensagem-pequena">
                <h2>Estações Meteorológicas</h2>
                <p class="descricao-analise">Temperatura, umidade do ar e precipitação registradas em 3 estações, de janeiro/2023 a agosto/2026.</p>
            </div>
        </div>

        <?php if (empty($nomesEstacoes)): ?>
            <section class="painel-resultado">
                <div class="resultado-card">
                    <p>Nenhum dado de estação cadastrado ainda.</p>
                </div>
            </section>
        <?php else: ?>

            <section class="painel-entrada">
                <div class="mensagem-pequena">
                    <h3>Filtrar gráficos</h3>
                </div>

                <div class="filtro-estacoes">
                    <?php foreach ($nomesEstacoes as $estacao): ?>
                        <label class="filtro-estacao-item">
                            <input type="checkbox" class="filtro-estacao-checkbox" value="<?= htmlspecialchars($estacao) ?>" checked>
                            <span style="color: <?= htmlspecialchars($coresEstacao[$estacao] ?? '#6b7280') ?>">●</span>
                            <?= htmlspecialchars($estacao) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="filtro-periodo">
                    <label for="filtroMesInicio">De</label>
                    <input type="month" id="filtroMesInicio" min="<?= htmlspecialchars($mesMinIso) ?>" max="<?= htmlspecialchars($mesMaxIso) ?>" value="<?= htmlspecialchars($mesMinIso) ?>">

                    <label for="filtroMesFim">Até</label>
                    <input type="month" id="filtroMesFim" min="<?= htmlspecialchars($mesMinIso) ?>" max="<?= htmlspecialchars($mesMaxIso) ?>" value="<?= htmlspecialchars($mesMaxIso) ?>">
                </div>

                <button type="button" id="btnGerarGrafico" class="botao-generico">📊 Gerar gráfico</button>
                <p class="nota" id="filtroErro" style="display:none; color:#991b1b;"></p>
            </section>

            <section class="grafico-area">
                <h3>Temperatura (°C)</h3>
                <div class="grafico-wrapper">
                    <canvas id="graficoTemperatura"></canvas>
                </div>
            </section>

            <section class="grafico-area">
                <h3>Precipitação (mm)</h3>
                <div class="grafico-wrapper">
                    <canvas id="graficoPrecipitacao"></canvas>
                </div>
            </section>

            <section class="grafico-area">
                <h3>Umidade do ar (%)</h3>
                <div class="grafico-wrapper">
                    <canvas id="graficoUmidade"></canvas>
                </div>
            </section>

            <?php foreach ($nomesEstacoes as $estacao): ?>
                <section class="dados-historicos">
                    <h3>Dados de <?= htmlspecialchars($estacao) ?></h3>

                    <?php foreach (array_keys($fontesPorEstacao[$estacao] ?? []) as $fonteTexto): ?>
                        <p class="nota">Origem: <?= htmlspecialchars($fonteTexto) ?></p>
                    <?php endforeach; ?>

                    <table>
                        <thead>
                            <tr>
                                <th>Mês/Ano</th>
                                <th>Temp. Mín. (°C)</th>
                                <th>Temp. Máx. (°C)</th>
                                <th>Umid. Mín. (%)</th>
                                <th>Umid. Máx. (%)</th>
                                <th>Precipitação (mm)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dadosPorEstacao[$estacao] as $linha): ?>
                                <tr>
                                    <td><?= (int) date('n', strtotime($linha['mes_ano'])) . '/' . date('Y', strtotime($linha['mes_ano'])) ?></td>
                                    <td><?= $linha['temp_min_media'] !== null ? htmlspecialchars(number_format($linha['temp_min_media'], 2, ',', '.')) : '<span class="nota">sem dado</span>' ?></td>
                                    <td><?= htmlspecialchars(number_format($linha['temp_max_media'], 2, ',', '.')) ?></td>
                                    <td><?= htmlspecialchars(number_format($linha['umid_min_media'], 2, ',', '.')) ?></td>
                                    <td><?= htmlspecialchars(number_format($linha['umid_max_media'], 2, ',', '.')) ?></td>
                                    <td><?= htmlspecialchars(number_format($linha['precipitacao_mm'], 2, ',', '.')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>
</main>

<footer class="com-sidebar">
    <p>&copy; Todos os direitos reservados. <a href="../politica.html">Políticas de privacidade.</a></p>
</footer>

<script src="../js/lib/chart.umd.min.js"></script>
<script>
(function () {
    const dados = <?= $dadosGraficoJs ?>;
    if (!dados.estacoes || dados.estacoes.length === 0) {
        return;
    }

    let chartTemperatura = null;
    let chartPrecipitacao = null;
    let chartUmidade = null;

    function linhasTemperaturaOuUmidade(estacoesFiltradas, campoMin, campoMax) {
        const datasets = [];
        estacoesFiltradas.forEach(est => {
            datasets.push({
                label: 'Máxima ' + est.nome,
                data: est[campoMax],
                borderColor: est.cor,
                backgroundColor: est.cor,
                borderWidth: 2,
                pointRadius: 0,
                fill: false,
                tension: 0.15,
            });
            datasets.push({
                label: 'Mínima ' + est.nome,
                data: est[campoMin],
                borderColor: est.cor,
                backgroundColor: est.cor,
                borderWidth: 2,
                borderDash: [6, 4],
                pointRadius: 0,
                fill: false,
                tension: 0.15,
                spanGaps: false,
            });
        });
        return datasets;
    }

    // Recorta cada série de dados para o intervalo de índices [inicio, fim] (inclusive)
    function fatiarEstacao(est, inicio, fim) {
        return {
            nome: est.nome,
            cor: est.cor,
            tempMin: est.tempMin.slice(inicio, fim + 1),
            tempMax: est.tempMax.slice(inicio, fim + 1),
            umidMin: est.umidMin.slice(inicio, fim + 1),
            umidMax: est.umidMax.slice(inicio, fim + 1),
            precip: est.precip.slice(inicio, fim + 1),
        };
    }

    function renderGraficos(estacoesSelecionadas, mesInicioIso, mesFimIso) {
        let idxInicio = dados.labelsIso.indexOf(mesInicioIso);
        let idxFim = dados.labelsIso.indexOf(mesFimIso);
        if (idxInicio === -1) idxInicio = 0;
        if (idxFim === -1) idxFim = dados.labelsIso.length - 1;

        const labelsFiltrados = dados.labels.slice(idxInicio, idxFim + 1);
        const estacoesFiltradas = dados.estacoes
            .filter(est => estacoesSelecionadas.includes(est.nome))
            .map(est => fatiarEstacao(est, idxInicio, idxFim));

        if (chartTemperatura) chartTemperatura.destroy();
        if (chartPrecipitacao) chartPrecipitacao.destroy();
        if (chartUmidade) chartUmidade.destroy();

        chartTemperatura = new Chart(document.getElementById('graficoTemperatura').getContext('2d'), {
            type: 'line',
            data: { labels: labelsFiltrados, datasets: linhasTemperaturaOuUmidade(estacoesFiltradas, 'tempMin', 'tempMax') },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    x: { ticks: { maxRotation: 90, minRotation: 45 } },
                    y: { title: { display: true, text: 'Temperatura (°C)' } }
                }
            }
        });

        chartPrecipitacao = new Chart(document.getElementById('graficoPrecipitacao').getContext('2d'), {
            type: 'bar',
            data: {
                labels: labelsFiltrados,
                datasets: estacoesFiltradas.map(est => ({
                    label: est.nome,
                    data: est.precip,
                    backgroundColor: est.cor,
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    x: { ticks: { maxRotation: 90, minRotation: 45 } },
                    y: { title: { display: true, text: 'Precipitação (mm)' } }
                }
            }
        });

        chartUmidade = new Chart(document.getElementById('graficoUmidade').getContext('2d'), {
            type: 'line',
            data: { labels: labelsFiltrados, datasets: linhasTemperaturaOuUmidade(estacoesFiltradas, 'umidMin', 'umidMax') },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    x: { ticks: { maxRotation: 90, minRotation: 45 } },
                    y: { title: { display: true, text: 'Umidade do ar (%)' } }
                }
            }
        });
    }

    function todasEstacoesSelecionadas() {
        return dados.estacoes.map(est => est.nome);
    }

    // Gráfico inicial: todas as estações, período completo
    renderGraficos(todasEstacoesSelecionadas(), dados.labelsIso[0], dados.labelsIso[dados.labelsIso.length - 1]);

    const btnGerar = document.getElementById('btnGerarGrafico');
    if (btnGerar) {
        btnGerar.addEventListener('click', function () {
            const erroEl = document.getElementById('filtroErro');
            erroEl.style.display = 'none';

            const mesInicioIso = document.getElementById('filtroMesInicio').value;
            const mesFimIso = document.getElementById('filtroMesFim').value;
            const estacoesSelecionadas = Array.from(document.querySelectorAll('.filtro-estacao-checkbox:checked')).map(cb => cb.value);

            if (!mesInicioIso || !mesFimIso) {
                erroEl.textContent = 'Selecione o mês inicial e o mês final.';
                erroEl.style.display = 'block';
                return;
            }
            if (mesInicioIso > mesFimIso) {
                erroEl.textContent = 'O mês inicial não pode ser depois do mês final.';
                erroEl.style.display = 'block';
                return;
            }
            if (estacoesSelecionadas.length === 0) {
                erroEl.textContent = 'Selecione ao menos uma estação.';
                erroEl.style.display = 'block';
                return;
            }

            renderGraficos(estacoesSelecionadas, mesInicioIso, mesFimIso);
        });
    }
})();
</script>
<script src="../js/pesquisa.js"></script>
</body>
</html>
