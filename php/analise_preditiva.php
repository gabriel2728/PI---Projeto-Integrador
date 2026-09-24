<?php
session_start();
include('error_handler.php');
include('seguranca.php');
include('conexao.php');

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$nomeUsuario = $_SESSION['nomeUsuario'];

$conn->query("CREATE TABLE IF NOT EXISTS AnalisePreditiva (
    id_analise_preditiva INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_simulacao INT NULL,
    periodo VARCHAR(50) NULL,
    data_calculo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pluviosidade_informada DECIMAL(10,2) NOT NULL,
    potencia_estimada DECIMAL(10,2) NOT NULL,
    modelo VARCHAR(100) NOT NULL DEFAULT 'Regressão Linear Simples',
    equacao VARCHAR(100) NULL,
    status ENUM('concluido', 'pendente', 'erro') NOT NULL DEFAULT 'concluido',
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario) ON DELETE CASCADE,
    INDEX idx_usuario_data (id_usuario, data_calculo)
)");

if ($conn->error) {
    $conn->close();
    die('Erro ao criar tabela AnalisePreditiva: ' . $conn->error);
}

$colunasAnalise = [];
$colsResult = $conn->query("SHOW COLUMNS FROM AnalisePreditiva");
if ($colsResult) {
    while ($coluna = $colsResult->fetch_assoc()) {
        $colunasAnalise[] = $coluna['Field'];
    }
    if (!in_array('id_simulacao', $colunasAnalise, true)) {
        $conn->query("ALTER TABLE AnalisePreditiva ADD COLUMN id_simulacao INT NULL AFTER id_usuario");
    }
    if (!in_array('periodo', $colunasAnalise, true)) {
        $conn->query("ALTER TABLE AnalisePreditiva ADD COLUMN periodo VARCHAR(50) NULL AFTER id_simulacao");
    }
    if (!in_array('status', $colunasAnalise, true)) {
        $conn->query("ALTER TABLE AnalisePreditiva ADD COLUMN status ENUM('concluido', 'pendente', 'erro') NOT NULL DEFAULT 'concluido' AFTER equacao");
    }
}

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
unset($_SESSION['mensagem_sucesso']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar_analise') {
    $periodo = sanitizeInput($_POST['periodo'] ?? '');
    $pluviosidadeInformada = isset($_POST['pluviosidade_informada']) ? floatval($_POST['pluviosidade_informada']) : 0;
    $potenciaEstimada = isset($_POST['potencia_estimada']) ? floatval($_POST['potencia_estimada']) : 0;
    $modelo = sanitizeInput($_POST['modelo'] ?? 'Regressão Linear Simples');
    $equacao = sanitizeInput($_POST['equacao'] ?? '');

    if ($pluviosidadeInformada > 0 && $potenciaEstimada >= 0) {
        $stmt = $conn->prepare("INSERT INTO AnalisePreditiva (id_usuario, id_simulacao, periodo, pluviosidade_informada, potencia_estimada, modelo, equacao, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'concluido')");
        $idSimulacao = isset($_POST['id_simulacao']) && $_POST['id_simulacao'] !== '' ? intval($_POST['id_simulacao']) : null;
        $stmt->bind_param("iisddss", $id_usuario, $idSimulacao, $periodo, $pluviosidadeInformada, $potenciaEstimada, $modelo, $equacao);
        if ($stmt->execute()) {
            $_SESSION['mensagem_sucesso'] = 'Análise preditiva salva com sucesso no histórico.';
            $stmt->close();
            header('Location: analise_preditiva.php');
            exit;
        }
        $stmt->close();
    }

    $_SESSION['mensagem_erro'] = 'Não foi possível salvar a análise no histórico.';
    header('Location: analise_preditiva.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'descartar_analise') {
    $_SESSION['mensagem_sucesso'] = 'Cálculo descartado.';
    header('Location: analise_preditiva.php');
    exit;
}

// Valores padrão para o modelo quando a tabela não existir ou estiver vazia
$dadosHistoricosPadrao = [
    ['data_registro' => '2023-01-01', 'pluviosidade_mm' => 50, 'potencia_mw' => 20],
    ['data_registro' => '2023-02-01', 'pluviosidade_mm' => 100, 'potencia_mw' => 40],
    ['data_registro' => '2023-03-01', 'pluviosidade_mm' => 150, 'potencia_mw' => 60],
    ['data_registro' => '2023-04-01', 'pluviosidade_mm' => 200, 'potencia_mw' => 80],
];
$dadosHistoricos = $dadosHistoricosPadrao;
$useDatasetDefault = true;

// Verifica se existe tabela de dados históricos com registros reais
$result = $conn->query("SHOW TABLES LIKE 'DadosHistoricos'");
if ($result && $result->num_rows > 0) {
    $stmt = $conn->prepare('SELECT data_registro, pluviosidade_mm, potencia_mw, fonte FROM DadosHistoricos ORDER BY data_registro ASC');
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        $dadosReais = [];
        while ($row = $res->fetch_assoc()) {
            $dadosReais[] = [
                'data_registro' => $row['data_registro'],
                'pluviosidade_mm' => floatval($row['pluviosidade_mm']),
                'potencia_mw' => floatval($row['potencia_mw']),
                'fonte' => $row['fonte'],
            ];
        }
        $stmt->close();
        // Só troca para o dataset real se houver ao menos um registro;
        // caso contrário mantém o padrão de demonstração (e o rótulo continua correto).
        if (count($dadosReais) > 0) {
            $dadosHistoricos = $dadosReais;
            $useDatasetDefault = false;
        }
    }
}

$periodo = $_POST['periodo'] ?? '';
$idSimulacaoContexto = isset($_GET['id_simulacao']) && $_GET['id_simulacao'] !== '' ? intval($_GET['id_simulacao']) : null;
$pluviosidade_mm = isset($_POST['pluviosidade_mm']) ? floatval($_POST['pluviosidade_mm']) : null;
$resultado = null;
$modeloA = null;
$modeloB = null;
$mensagemErro = null;
$foraIntervalo = false;

if ($pluviosidade_mm !== null) {
    if ($pluviosidade_mm <= 0) {
        $mensagemErro = 'A pluviosidade informada deve ser maior que zero.';
    } else {
        $x = array_column($dadosHistoricos, 'pluviosidade_mm');
        $y = array_column($dadosHistoricos, 'potencia_mw');
        $n = count($x);

        if ($n === 0) {
            $mensagemErro = 'Não há dados históricos suficientes para gerar a previsão.';
        } else {
            $somaX = array_sum($x);
            $somaY = array_sum($y);
            $somaX2 = array_sum(array_map(fn($valor) => $valor * $valor, $x));
            $somaXY = array_sum(array_map(fn($valor, $valorY) => $valor * $valorY, $x, $y));
            $denominador = ($n * $somaX2) - ($somaX * $somaX);

            if (abs($denominador) < 1e-9) {
                $mensagemErro = 'Não é possível calcular a regressão linear com os dados atuais.';
            } else {
                $modeloA = ($n * $somaXY - $somaX * $somaY) / $denominador;
                $modeloB = ($somaY - $modeloA * $somaX) / $n;
                $resultado = $modeloA * $pluviosidade_mm + $modeloB;
                $foraIntervalo = $pluviosidade_mm < min($x) || $pluviosidade_mm > max($x);
            }
        }
    }
}

$equacaoTexto = ($modeloA !== null && $modeloB !== null)
    ? 'y = ' . number_format($modeloA, 4, ',', '.') . 'x + ' . number_format($modeloB, 4, ',', '.')
    : null;

// Fontes documentadas por linha (ex.: dados reais semeados no sistema) permitem
// mostrar a origem de verdade, em vez do rótulo genérico "manual".
$fontesDocumentadas = array_values(array_unique(array_filter(array_map(
    fn($item) => trim($item['fonte'] ?? ''),
    $dadosHistoricos
))));
$registrosSemFonte = count(array_filter(
    $dadosHistoricos,
    fn($item) => trim($item['fonte'] ?? '') === ''
));

if ($useDatasetDefault) {
    $origemDadosRotulo = 'demonstração';
    $origemDadosTexto = 'Os dados exibidos são dados de demonstração e não representam medições reais de uma usina. A previsão serve apenas para demonstrar o funcionamento do modelo.';
} elseif (count($fontesDocumentadas) > 0) {
    $origemDadosRotulo = 'real (fonte documentada)';
    $origemDadosTexto = implode(' ', $fontesDocumentadas);
    if ($registrosSemFonte > 0) {
        $origemDadosTexto .= " Atenção: {$registrosSemFonte} registro(s) deste conjunto não têm fonte documentada (podem ter sido inseridos manualmente sem indicação de origem).";
    }
} else {
    $origemDadosRotulo = 'manual';
    $origemDadosTexto = 'Os dados de treinamento foram inseridos manualmente e podem não representar uma situação real de operação.';
}

// Payload único para o gráfico: o JS reaproveita os coeficientes calculados aqui,
// sem recalcular a regressão, para não divergir do resultado exibido na página.
$dadosGraficoJs = json_encode([
    'resultadoValido' => $resultado !== null,
    'dadosTreinamento' => array_map(
        fn($item) => ['x' => $item['pluviosidade_mm'], 'y' => $item['potencia_mw']],
        $dadosHistoricos
    ),
    'coeficienteAngular' => $modeloA,
    'coeficienteLinear' => $modeloB,
    'pluviosidadeInformada' => $pluviosidade_mm,
    'potenciaEstimada' => $resultado,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análise Preditiva - SiSGEH</title>
    <link rel="stylesheet" href="../css/components/header.css">
    <link rel="stylesheet" href="../css/historico.css?v=20260511-layout">
    <link rel="stylesheet" href="../css/analise_preditiva.css">
    <link rel="stylesheet" href="../css/components/botoes.css">
</head>
<body>
<header>
     <div class="caixa_de_texto">
      <input type="text" class="search-text" placeholder="Pesquisar...">
    </div>

    <h1 class="sisgeh"> SiSGEH </h1>

    <nav class="links">
        <ul>
            <li>
                <a href="inicio.php" class="link_home">
                    <img src="../images/home.png" alt="Voltar a Home" class="home">
                </a>

                <a href="configuracoes.php" class="link_config">
                    <img src="../images/gear.png" alt="Configurações" class="config">
                </a>
            </li>
        </ul>
    </nav>
</header>

<main class="container">
   <div class="layout">
        <div class="intro intro-principal">
            <div class="mensagem-pequena">
                <h2>Bem-vindo, <?= htmlspecialchars($nomeUsuario) ?>!</h2>
                <p class="descricao-analise">Use registros históricos de chuva para estimar a potência gerada em MW.</p>
            </div>
        </div>

        <?php if ($mensagemSucesso): ?>
            <div class="resultado-card sucesso"><?= htmlspecialchars($mensagemSucesso) ?></div>
        <?php endif; ?>

    	<section class="painel-entrada">
		<div class="mensagem-pequena">
        		<h3>Dados para Análise Preditiva</h3>
		</div>
        	<form method="POST" action="analise_preditiva.php">
            		<label for="periodo">Período de análise</label>
            		<input type="text" id="periodo" name="periodo" placeholder="Ex: Março/2024" value="<?= htmlspecialchars($periodo) ?>">

            		<label for="pluviosidade_mm">Pluviosidade média esperada (mm)</label>
            		<input type="number" step="0.01" id="pluviosidade_mm" name="pluviosidade_mm" placeholder="180" value="<?= $pluviosidade_mm ?? '' ?>" required>

            		<button type="submit" class="botao-generico">🔍 Gerar Previsão</button>
        	</form>
    	</section>

    	<section class="painel-resultado">
            <div class="intro">
                <div class="mensagem-pequena">
                    <h3>Resultado da Previsão</h3>
                </div>

                <a href="dados_historicos.php" class="botao-cinza">Gerenciar dados históricos</a>
            </div>

            <?php if (isset($_SESSION['mensagem_erro'])): ?>
                <div class="alerta erro"><?= htmlspecialchars($_SESSION['mensagem_erro']) ?></div>
                <?php unset($_SESSION['mensagem_erro']); ?>
            <?php elseif ($mensagemErro): ?>
                <div class="alerta erro"><?= htmlspecialchars($mensagemErro) ?></div>
            <?php elseif ($resultado !== null): ?>
                <div class="resultado-card">
                    <p>Pluviosidade informada: <strong><?= htmlspecialchars(number_format($pluviosidade_mm, 2, ',', '.')) ?> mm</strong></p>
                    <p>Potência estimada: <strong><?= htmlspecialchars(number_format($resultado, 2, ',', '.')) ?> MW</strong></p>
                    <p>Modelo utilizado: <strong>Regressão Linear Simples</strong></p>
                    <p>Equação do modelo: <strong><?= htmlspecialchars($equacaoTexto) ?></strong></p>
                </div>

                <?php if ($foraIntervalo): ?>
                    <div class="alerta aviso">
                        A pluviosidade informada está fora do intervalo dos dados usados no treinamento. O resultado é uma extrapolação e possui maior incerteza.
                    </div>
                <?php endif; ?>

                <div class="acoes-analise">
                    <form method="POST" action="analise_preditiva.php" class="form-acao-analise">
                        <input type="hidden" name="acao" value="salvar_analise">
                        <input type="hidden" name="id_simulacao" value="<?= htmlspecialchars((string) ($idSimulacaoContexto ?? '')) ?>">
                        <input type="hidden" name="periodo" value="<?= htmlspecialchars($periodo) ?>">
                        <input type="hidden" name="pluviosidade_informada" value="<?= htmlspecialchars((string) $pluviosidade_mm) ?>">
                        <input type="hidden" name="potencia_estimada" value="<?= htmlspecialchars((string) $resultado) ?>">
                        <input type="hidden" name="modelo" value="Regressão Linear Simples">
                        <input type="hidden" name="equacao" value="<?= htmlspecialchars($equacaoTexto) ?>">
                        <button type="submit" class="botao-generico">💾 Salvar no histórico</button>
                    </form>

                    <form method="POST" action="analise_preditiva.php" class="form-acao-analise">
                        <input type="hidden" name="acao" value="descartar_analise">
                        <button type="submit" class="botao-cinza">🗑️ Descartar</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="resultado-card">
                    <p>Informe o valor de pluviosidade e clique em <strong>Gerar Previsão</strong>.</p>
                </div>
            <?php endif; ?>
    	</section>



    	<section class="grafico-area">
        	<h3>Pluviosidade x Potência Estimada</h3>

        	<?php if ($resultado !== null): ?>
            		<span class="origem-dados-badge">Origem dos dados: <?= htmlspecialchars($origemDadosRotulo) ?></span>

            		<div class="grafico-wrapper">
                		<canvas id="graficoPreditivo"></canvas>
            		</div>

            		<div class="info-modelo">
                		<p><strong>Modelo:</strong> Regressão Linear Simples</p>
                		<p><strong>Registros utilizados:</strong> <?= count($dadosHistoricos) ?></p>
                		<p><strong>Equação:</strong> <?= htmlspecialchars($equacaoTexto) ?></p>
                		<p><strong>Origem dos dados:</strong> <?= htmlspecialchars($origemDadosTexto) ?></p>
                		<p class="aviso"><strong>Aviso:</strong> esta estimativa não representa uma garantia de potência real de uma usina.</p>
            		</div>
        	<?php else: ?>
            		<p class="resultado-card">Informe a pluviosidade e gere uma previsão para visualizar o gráfico.</p>
        	<?php endif; ?>
    	</section>



    	<section class="dados-historicos">
        	<h3>Dados Históricos Utilizados</h3>
        	<?php if ($useDatasetDefault): ?>
            	<p class="nota">Tabela <strong>DadosHistoricos</strong> não encontrada. Usando conjunto padrão fictício para demonstração.</p>
        	<?php endif; ?>
        	<table>
            		<thead>
                		<tr>
                    			<th>Data</th>
                    			<th>Pluviosidade (mm)</th>
                    			<th>Potência (MW)</th>
                		</tr>
            		</thead>
            		<tbody>
                		<?php foreach ($dadosHistoricos as $item): ?>
                    		<tr>
                        		<td><?= htmlspecialchars($item['data_registro']) ?></td>
                        		<td><?= htmlspecialchars(number_format($item['pluviosidade_mm'], 2, ',', '.')) ?></td>
                        		<td><?= htmlspecialchars(number_format($item['potencia_mw'], 2, ',', '.')) ?></td>
                    		</tr>
                		<?php endforeach; ?>
            		</tbody>
        	</table>
    	</section>
   </div>
</main>

<script src="../js/lib/chart.umd.min.js"></script>
<script>
(function () {
    // Payload gerado pelo PHP: contém os mesmos coeficientes usados no cálculo
    // exibido na página, para o gráfico nunca divergir do resultado mostrado.
    const dadosGrafico = <?= $dadosGraficoJs ?>;

    if (!dadosGrafico || !dadosGrafico.resultadoValido) {
        return;
    }

    const canvasEl = document.getElementById('graficoPreditivo');
    if (!canvasEl) {
        return;
    }

    const a = dadosGrafico.coeficienteAngular;
    const b = dadosGrafico.coeficienteLinear;

    const treinamento = (dadosGrafico.dadosTreinamento || [])
        .filter(p => Number.isFinite(p.x) && Number.isFinite(p.y));

    const previsao = {
        x: dadosGrafico.pluviosidadeInformada,
        y: dadosGrafico.potenciaEstimada
    };

    if (!Number.isFinite(previsao.x) || !Number.isFinite(previsao.y)) {
        return;
    }

    let linhaRegressao = [];
    if (Number.isFinite(a) && Number.isFinite(b)) {
        const xs = treinamento.map(p => p.x).concat([previsao.x]);
        const xMin = Math.min(...xs);
        const xMax = Math.max(...xs);
        linhaRegressao = [
            { x: xMin, y: a * xMin + b },
            { x: xMax, y: a * xMax + b }
        ];
    }

    const ctx = canvasEl.getContext('2d');
    new Chart(ctx, {
        type: 'scatter',
        data: {
            datasets: [
                {
                    label: 'Dados de treinamento',
                    data: treinamento,
                    backgroundColor: '#2f80ed',
                    borderColor: '#2f80ed',
                    pointRadius: 6,
                    showLine: false
                },
                {
                    type: 'line',
                    label: 'Linha de regressão',
                    data: linhaRegressao,
                    borderColor: '#eb5757',
                    backgroundColor: '#eb5757',
                    borderWidth: 2,
                    fill: false,
                    pointRadius: 0,
                    tension: 0
                },
                {
                    label: 'Previsão atual',
                    data: [previsao],
                    backgroundColor: '#16a34a',
                    borderColor: '#15803d',
                    pointRadius: 9,
                    pointHoverRadius: 11,
                    showLine: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Pluviosidade x Potência Estimada'
                },
                legend: {
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const ponto = context.raw;
                            if (context.dataset.label === 'Previsão atual') {
                                return `Previsão informada pelo usuário: ${ponto.x} mm → ${ponto.y} MW`;
                            }
                            return `${context.dataset.label}: (${ponto.x}, ${ponto.y})`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: { display: true, text: 'Pluviosidade (mm)' }
                },
                y: {
                    title: { display: true, text: 'Potência estimada (MW)' }
                }
            }
        }
    });
})();
</script>
<script src="../js/pesquisa.js"></script>
</body>
</html>
