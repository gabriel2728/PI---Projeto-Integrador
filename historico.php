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
<link rel="stylesheet" type="text/css" href="estilo_historico.css"> 

</head>
<body>

<header>
    <div class="caixa_de_texto">
        <input type="text" class="search-text" placeholder="Pesquisar...">
    </div>
    <h2 class="sisgeh"> SiSGEH </h2>
    <div class="links">
      <a href="inicio.php" class="link_home">
        <img src="icon_home.png" alt="Voltar a Home" class="home">
      </a>
      <a href="configuracoes.php" class="link_config">
        <img src="icon_config.png" alt="Configurações" class="config">
      </a>
    </div>
</header>

<div class="layoutHistorico">
    <div class="historico">
        <div class="mensagem">
            <h1>Bem-vindo ao seu histórico!</h1>
            <p>Clique em “Simulação” para ver os detalhes ou em “Exportar” para baixar.</p>
        </div>

        <table id="tabelaHistorico">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Hora</th>
                    <th>Simulação</th>
                    <th>Exportar</th>
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
                </tr>
                <tr class="detalhes" id="detalhes-<?= $sim['id_simulacao'] ?>" style="display:none;">
                    <td colspan="4">
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

// Exportação (PDF/CSV pode ser implementada aqui)
function exportSimulacao(id) {
    alert('Aqui você pode gerar o PDF ou CSV da simulação ID ' + id);
}
</script>

</body>
</html>
