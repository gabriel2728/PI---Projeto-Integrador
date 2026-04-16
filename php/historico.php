<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}
include('conexao.php');

$id_usuario = $_SESSION['id_usuario'];
$nomeUsuario = $_SESSION['nomeUsuario'];

// Pega histórico do usuário
$stmt = $conn->prepare("
    SELECT s.id_simulacao, s.data_simulacao, s.vazao, s.altura, s.potTurbina, s.qtdTurbinas, s.potGerador, s.eficiencia, s.horas,
           r.geracao_principal, r.geracao_diaria, r.geracao_mensal, r.geracao_anual
    FROM Simulacoes s
    LEFT JOIN ResultadoSimulacao r ON r.id_simulacao = s.id_simulacao
    WHERE s.id_usuario = ?
    ORDER BY s.data_simulacao DESC
");
$stmt->bind_param("i", $id_usuario);
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
<link rel="stylesheet" type="text/css" href="css/estilo_historico.css"> 

</head>
<body>

<header>
    <div class="caixa_de_texto">
        <input type="text" class="search-text" placeholder="Pesquisar...">
    </div>
    <h2 class="sisgeh"> SiSGEH </h2>
    <div class="links">
      <a href="inicio.php" class="link_home">
        <img src="../icon_home.png" alt="Voltar a Home" class="home">
      </a>
      <a href="configuracoes.php" class="link_config">
        <img src="../icon_config.png" alt="Configurações" class="config">
      </a>
    </div>
</header>

<div class="layoutHistorico">
    <div class="historico">
        <div class="mensagem">
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
                    <td><button class="btnDetalhes" data-id="<?= $sim['id_simulacao'] ?>">Simulação</button></td>
                    <td><button onclick="exportSimulacao(<?= $sim['id_simulacao'] ?>)">Exportar</button></td>
                    <td><button class="btnExcluir" onclick="excluirSimulacao(<?= $sim['id_simulacao'] ?>)">Excluir</button></td>
                </tr>
                <tr class="detalhes" id="detalhes-<?= $sim['id_simulacao'] ?>" style="display:none;">
                    <td colspan="5">
                        <table class="tabelaExpandida">
                            <tr><td>Vazão Mássica</td><td><?= $sim['vazao'] ?> m³/s</td></tr>
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

<footer>
    <p>&copy; Todos os direitos reservados. <a href="politica.html">Políticas de privacidade.</a></p>
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

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'id_simulacao';
    input.value = id;
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
}

function realizarExportacao(formato, id) {
    // Para simulações salvas, vamos usar GET direto
    const url = `exportacao.php?tipo=salvo&id=${id}&formato=${formato}`;
    window.location.href = url;
}
</script>

</body>
</html>
