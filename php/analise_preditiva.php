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

// Valores padrão para o modelo quando a tabela não existir
$dadosHistoricos = [
    ['data_registro' => '2023-01-01', 'pluviosidade_mm' => 50, 'potencia_mw' => 20],
    ['data_registro' => '2023-02-01', 'pluviosidade_mm' => 100, 'potencia_mw' => 40],
    ['data_registro' => '2023-03-01', 'pluviosidade_mm' => 150, 'potencia_mw' => 60],
    ['data_registro' => '2023-04-01', 'pluviosidade_mm' => 200, 'potencia_mw' => 80],
];
$useDatasetDefault = true;

// Verifica se existe tabela de dados históricos
$result = $conn->query("SHOW TABLES LIKE 'DadosHistoricos'");
if ($result && $result->num_rows > 0) {
    $stmt = $conn->prepare('SELECT data_registro, pluviosidade_mm, potencia_mw FROM DadosHistoricos ORDER BY data_registro ASC');
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        $dadosHistoricos = [];
        while ($row = $res->fetch_assoc()) {
            $dadosHistoricos[] = [
                'data_registro' => $row['data_registro'],
                'pluviosidade_mm' => floatval($row['pluviosidade_mm']),
                'potencia_mw' => floatval($row['potencia_mw']),
            ];
        }
        $stmt->close();
        if (count($dadosHistoricos) > 0) {
            $useDatasetDefault = false;
        }
    }
}

$periodo = $_POST['periodo'] ?? '';
$pluviosidade_mm = isset($_POST['pluviosidade_mm']) ? floatval($_POST['pluviosidade_mm']) : null;
$resultado = null;
$modeloA = null;
$modeloB = null;
$mensagemErro = null;

if ($pluviosidade_mm !== null) {
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
        }
    }
}

$labels = json_encode(array_map(fn($item) => $item['pluviosidade_mm'], $dadosHistoricos));
$values = json_encode(array_map(fn($item) => $item['potencia_mw'], $dadosHistoricos));
$linhaRegressao = '[]';
if ($modeloA !== null && $modeloB !== null) {
    $linha = array_map(fn($valor) => round($modeloA * $valor + $modeloB, 2), array_column($dadosHistoricos, 'pluviosidade_mm'));
    $linhaRegressao = json_encode($linha);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análise Preditiva - SiSGEH</title>
    <link rel="stylesheet" href="../css/components/header.css">
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
    	<div class="intro">
        	<div class="mensagem-pequena">
            		<h2>Bem-vindo, <?= htmlspecialchars($nomeUsuario) ?>!</h2>
            			<p>Use dados históricos de pluviosidade para prever a potência estimada em MW.</p>
        	</div>
        	<a class="botao-cinza" href="inicio.php">← Voltar ao Dashboard</a>
    	</div>

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
            		<div <div class="mensagem-pequena">
                		<h3>Resultado da Previsão</h3>
            		</div>
            		
                		<a href="dados_historicos.php" class="botao-azul">Gerenciar dados históricos</a>
            		
        	</div>

        	<?php if ($mensagemErro): ?>
            	<div class="alerta erro"><?= htmlspecialchars($mensagemErro) ?></div>
        	<?php elseif ($resultado !== null): ?>
            	<div class="resultado-card">
                	<p>Pluviosidade informada: <strong><?= htmlspecialchars(number_format($pluviosidade_mm, 2, ',', '.')) ?> mm</strong></p>
                	<p>Potência estimada: <strong><?= htmlspecialchars(number_format($resultado, 2, ',', '.')) ?> MW</strong></p>
                	<p>Modelo utilizado: <strong>Regressão Linear Simples</strong></p>
                	<p>Equação do modelo: <strong>y = <?= number_format($modeloA, 4, ',', '.') ?>x + <?= number_format($modeloB, 4, ',', '.') ?></strong></p>
            	</div>
        	<?php else: ?>
            	<div class="resultado-card">
                	<p>Informe o valor de pluviosidade e clique em <strong>Gerar Previsão</strong>.</p>
            	</div>
        	<?php endif; ?>
    	</section>

    	<section class="grafico-area">
        	<h3>Gráfico: Pluviosidade x Potência Gerada</h3>
        	<canvas id="graficoPreditivo"></canvas>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labels = <?= $labels ?>;
const valores = <?= $values ?>;
const linhaRegressao = <?= $linhaRegressao ?>;

const ctx = document.getElementById('graficoPreditivo').getContext('2d');
new Chart(ctx, {
    type: 'scatter',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Dados Históricos',
                data: labels.map((x, index) => ({ x, y: valores[index] })),
                backgroundColor: '#2f80ed',
                borderColor: '#2f80ed',
                pointRadius: 6,
                showLine: false,
            },
            {
                label: 'Linha de Regressão',
                data: labels.map((x, index) => ({ x, y: linhaRegressao[index] || 0 })),
                type: 'line',
                borderColor: '#eb5757',
                borderWidth: 2,
                fill: false,
                pointRadius: 0,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Pluviosidade (mm)'
                }
            },
            y: {
                title: {
                    display: true,
                    text: 'Potência (MW)'
                }
            }
        },
        plugins: {
            legend: {
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return `(${context.parsed.x}, ${context.parsed.y})`;
                    }
                }
            }
        }
    }
});
</script>
</body>
</html>
