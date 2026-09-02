<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}include('error_handler.php');include('conexao.php');
include('seguranca.php');

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

// Gera token CSRF
$csrf_token = gerarTokenCSRF();

// Configuração da paginação
$itens_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Conta total de simulações para paginação
$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM Simulacoes WHERE id_usuario = ?");
$stmt_count->bind_param("i", $id_usuario);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_simulacoes = $result_count->fetch_assoc()['total'];
$stmt_count->close();

$total_paginas = ceil($total_simulacoes / $itens_por_pagina);

$stmt_analise = $conn->prepare("SELECT id_analise_preditiva, id_simulacao, periodo, data_calculo, pluviosidade_informada, potencia_estimada, modelo, equacao FROM AnalisePreditiva WHERE id_usuario = ? AND status = 'concluido' ORDER BY data_calculo DESC");
$stmt_analise->bind_param("i", $id_usuario);
$stmt_analise->execute();
$result_analise = $stmt_analise->get_result();
$analises_preditivas = $result_analise->fetch_all(MYSQLI_ASSOC);
$stmt_analise->close();

// Pega histórico do usuário com paginação
$stmt = $conn->prepare("
    SELECT s.id_simulacao, s.data_simulacao, s.vazao, s.altura, s.potTurbina, s.qtdTurbinas, s.potGerador, s.eficiencia, s.horas,
           r.geracao_principal, r.geracao_diaria, r.geracao_mensal, r.geracao_anual
    FROM Simulacoes s
    LEFT JOIN ResultadoSimulacao r ON r.id_simulacao = s.id_simulacao
    WHERE s.id_usuario = ?
    ORDER BY s.data_simulacao DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $id_usuario, $itens_por_pagina, $offset);
$stmt->execute();
$result = $stmt->get_result();
$simulacoes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Simulações</title>
    <link rel="stylesheet" href="../css/components/header.css"> 
    <link rel="stylesheet" type="text/css" href="../css/style.css">
    <link rel="stylesheet" href="../css/historico.css?v=20260511-layout">
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
		</li>

		<li>
      			<a href="configuracoes.php" class="link_config">
        			<img src="../images/gear.png" alt="Configurações" class="config">
    			</a>
		</li>
	</ul>
    </nav>
</header>

<main>
<div class="layout">
    <section class="historico">
        <!-- Token CSRF para proteção de formulários -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        
        <div class="mensagem-pequena">
            <h1>Bem-vindo ao seu histórico!</h1>
            <p>Clique em “Simulação” para ver os detalhes ou em “Exportar” para baixar.</p>
        </div>

        <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
            <div class="status-mensagem sucesso">
                <?= htmlspecialchars($_SESSION['mensagem_sucesso']) ?>
                <?php unset($_SESSION['mensagem_sucesso']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['mensagem_erro'])): ?>
            <div class="status-mensagem erro">
                <?= htmlspecialchars($_SESSION['mensagem_erro']) ?>
                <?php unset($_SESSION['mensagem_erro']); ?>
            </div>
        <?php endif; ?>

        <div class="tabelaContainer">
                <table id="tabelaHistorico">
                        <thead>
                            <tr>
                                    <th>Data</th>
                                    <th>Hora</th>
                                    <th>Simulação</th>
                                    <th>Exportar</th>
                                    <th>Excluir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($simulacoes as $sim): 
                                $dataHora = new DateTime($sim['data_simulacao']);
                            ?>
                            <tr>
                                    <td><?= $dataHora->format('d/m/Y') ?></td>
                                    <td><?= $dataHora->format('H:i') ?></td>
                                    <td><button class="btn-azul btnDetalhes" data-id="<?= $sim['id_simulacao'] ?>">Simulação</button></td>
                                    <td><button class="btn-azul" onclick="exportSimulacao(<?= $sim['id_simulacao'] ?>)">Exportar</button></td>
                                    <td><button class="btn-vermelho" onclick="excluirSimulacao(<?= $sim['id_simulacao'] ?>)">Excluir</button></td>
                                    
                            </tr>
                            <tr class="detalhes" id="detalhes-<?= $sim['id_simulacao'] ?>" style="display:none;">
                                    <td colspan="5">
                                        <table class="tabelaExpandida">
                                                <tr><td>Vazão Volumétrica</td><td><?= $sim['vazao'] ?> m³/s</td></tr>
                                                <tr><td>Altura da Queda</td><td><?= $sim['altura'] ?> m</td></tr>
                                                <tr><td>Potência Turbina</td><td><?= $sim['potTurbina'] ?> MW</td></tr>
                                                <tr><td>Qtd. Turbinas</td><td><?= $sim['qtdTurbinas'] ?></td></tr>
                                                <tr><td>Potência Gerador</td><td><?= $sim['potGerador'] ?> MW</td></tr>
                                                <tr><td>Eficiência</td><td><?= $sim['eficiencia']*100 ?> %</td></tr>
                                                <tr><td>Horas operação/dia</td><td><?= $sim['horas'] ?></td></tr>
                                                <tr><td><b>Potência média (MW)</b></td><td><?= $sim['geracao_principal'] ?> MW</td></tr>
                                                <tr><td><b>Geração diária (MWh/dia)</b></td><td><?= $sim['geracao_diaria'] ?> MWh</td></tr>
                                                <tr><td><b>Geração mensal (MWh/mês)</b></td><td><?= $sim['geracao_mensal'] ?> MWh</td></tr>
                                                <tr><td><b>Geração anual (MWh/ano)</b></td><td><?= $sim['geracao_anual'] ?> MWh</td></tr>
                                        </table>
                                    </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                </table>
        </div>	

        <div class="tabelaContainer secao-analise-preditiva">
            <div class="cabecalho-secao">
                <h2>Análises preditivas salvas</h2>
            </div>
            <table id="tabelaAnalisesPreditivas">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Origem</th>
                        <th>Período</th>
                        <th>Pluviosidade</th>
                        <th>Potência estimada</th>
                        <th>Modelo</th>
                        <th>Exportar</th>
                        <th>Excluir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($analises_preditivas)): ?>
                        <tr>
                            <td colspan="8">Nenhuma análise preditiva salva ainda.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($analises_preditivas as $analise): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($analise['data_calculo'])) ?></td>
                                <td><?= !empty($analise['id_simulacao']) ? '<span class="badge-simulacao">Sim. #' . intval($analise['id_simulacao']) . '</span>' : '<span class="badge-manual">Manual</span>' ?></td>
                                <td><?= htmlspecialchars($analise['periodo'] ?: '-') ?></td>
                                <td><?= number_format(floatval($analise['pluviosidade_informada']), 2, ',', '.') ?> mm</td>
                                <td><?= number_format(floatval($analise['potencia_estimada']), 2, ',', '.') ?> MW</td>
                                <td><?= htmlspecialchars($analise['modelo']) ?></td>
                                <td><button class="btn-azul btn-exportar-analise" onclick="exportAnalisePreditiva(<?= $analise['id_analise_preditiva'] ?>)">Exportar CSV</button></td>
                                <td><button class="btn-vermelho" onclick="excluirAnalisePreditiva(<?= $analise['id_analise_preditiva'] ?>)">Excluir</button></td>
                            </tr>
                            <tr class="linha-equacao-analise">
                                <td colspan="8">
                                    <strong>Equação:</strong> <?= htmlspecialchars($analise['equacao'] ?: '-') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Controles de Paginação -->
        <?php if ($total_paginas > 1): ?>
        <div class="paginacao" style="margin-top: 20px; text-align: center;">
            <?php if ($pagina_atual > 1): ?>
                <a href="?pagina=<?= $pagina_atual - 1 ?>" class="btn-pagina">« Anterior</a>
            <?php endif; ?>

            <?php
            $inicio = max(1, $pagina_atual - 2);
            $fim = min($total_paginas, $pagina_atual + 2);

            if ($inicio > 1): ?>
                <a href="?pagina=1" class="btn-pagina">1</a>
                <?php if ($inicio > 2): ?>
                    <span class="paginacao-dots">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $inicio; $i <= $fim; $i++): ?>
                <?php if ($i == $pagina_atual): ?>
                    <span class="btn-pagina atual"><?= $i ?></span>
                <?php else: ?>
                    <a href="?pagina=<?= $i ?>" class="btn-pagina"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($fim < $total_paginas): ?>
                <?php if ($fim < $total_paginas - 1): ?>
                    <span class="paginacao-dots">...</span>
                <?php endif; ?>
                <a href="?pagina=<?= $total_paginas ?>" class="btn-pagina"><?= $total_paginas ?></a>
            <?php endif; ?>

            <?php if ($pagina_atual < $total_paginas): ?>
                <a href="?pagina=<?= $pagina_atual + 1 ?>" class="btn-pagina">Próximo »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="info-paginacao" style="margin-top: 10px; text-align: center; color: #666; ">
            Mostrando <?= count($simulacoes) ?> de <?= $total_simulacoes ?> simulações
            (Página <?= $pagina_atual ?> de <?= $total_paginas ?>)
        </div>
    </section>
</div>

<!-- Modal de Exportação -->
    <div id="modalExportacao" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.4);">
        <div class="modal-content" style="background-color:#fefefe; margin:15% auto; padding:20px; border:1px solid #888; width:400px; border-radius:8px; box-shadow:0 4px 8px rgba(0,0,0,0.2);">
            <span class="close" id="closeModal" style="color:#aaa; float:right; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
            <h2 style="text-align:center; color:#333;">Escolha o formato de exportação</h2>

            <div style="display:flex; gap:10px; margin-top:20px; justify-content:center;">
                <button type="button" id="exportPDF" style="padding:12px 24px; background-color:#ff6b6b; color:white; border:none; border-radius:5px; cursor:pointer; font-size:16px;">📄 PDF</button>
                <button type="button" id="exportCSV" style="padding:12px 24px; background-color:#4ecdc4; color:white; border:none; border-radius:5px; cursor:pointer; font-size:16px;">📊 CSV</button>
                <button type="button" id="exportXLSX" style="padding:12px 24px; background-color:#45b7d1; color:white; border:none; border-radius:5px; cursor:pointer; font-size:16px;">📈 XLSX</button>
            </div>
        </div>
  </div>
</main>

<footer>
    <p>&copy; Todos os direitos reservados. <a href="../politica.html">Políticas de privacidade.</a></p>
</footer>

<script>
// Alterna a exibição dos detalhes
document.querySelectorAll('.btnDetalhes').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const linha = document.getElementById('detalhes-' + id);
        linha.style.display = (linha.style.display === 'none') ? 'table-row' : 'none';
    });
});

// Modal de Exportação
const modal = document.getElementById("modalExportacao");
const closeBtn = document.getElementById("closeModal");
let idSimulacaoAtual = null;

function abrirModalExportacao(id) {
    idSimulacaoAtual = id;
    modal.style.display = "block";
}

function exportSimulacao(id) {
    abrirModalExportacao(id);
}

closeBtn.addEventListener("click", function() {
    modal.style.display = "none";
});

window.addEventListener("click", function(event) {
    if (event.target === modal) {
        modal.style.display = "none";
    }
});

document.getElementById("exportPDF").addEventListener("click", function() {
    if (idSimulacaoAtual) {
        realizarExportacao('pdf', idSimulacaoAtual);
        modal.style.display = "none";
    }
});

document.getElementById("exportCSV").addEventListener("click", function() {
    if (idSimulacaoAtual) {
        realizarExportacao('csv', idSimulacaoAtual);
        modal.style.display = "none";
    }
});

document.getElementById("exportXLSX").addEventListener("click", function() {
    if (idSimulacaoAtual) {
        realizarExportacao('xlsx', idSimulacaoAtual);
        modal.style.display = "none";
    }
});

function excluirSimulacao(id) {
    if (!confirm('Tem certeza de que deseja excluir esta simulação? Esta ação não pode ser desfeita.')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'deletar_simulacao.php';
    form.style.display = 'none';

    const inputId = document.createElement('input');
    inputId.type = 'hidden';
    inputId.name = 'id_simulacao';
    inputId.value = id;
    form.appendChild(inputId);
    
    // Adicionar CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = document.querySelector('input[name="csrf_token"]')?.value || '';
    form.appendChild(csrfInput);

    document.body.appendChild(form);
    form.submit();
}

function excluirAnalisePreditiva(id) {
    if (!confirm('Tem certeza de que deseja excluir esta análise preditiva? Esta ação não pode ser desfeita.')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'deletar_simulacao.php';
    form.style.display = 'none';

    const inputId = document.createElement('input');
    inputId.type = 'hidden';
    inputId.name = 'id_analise_preditiva';
    inputId.value = id;
    form.appendChild(inputId);

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = document.querySelector('input[name="csrf_token"]')?.value || '';
    form.appendChild(csrfInput);

    document.body.appendChild(form);
    form.submit();
}

function realizarExportacao(formato, id) {
    // Para simulações salvas, vamos usar GET direto
    const url = `exportacao.php?tipo=salvo&id=${id}&formato=${formato}`;
    window.location.href = url;
}

function exportAnalisePreditiva(id) {
    const url = `exportacao.php?tipo=analise&id=${id}&formato=csv`;
    window.location.href = url;
}
</script>

<script src="../js/pesquisa.js"></script>
</body>
</html>
