<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}
include('conexao.php');
$id_usuario = $_SESSION['id_usuario'];
$nomeUsuario = $_SESSION['nomeUsuario'];
$primeiroNome = explode(" ", $nomeUsuario)[0];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Simulação Hidrelétrica</title>
  <link rel="stylesheet" href="estilo_simulacao.css">
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

  <div class="layoutSimular">
    <div class="simular">
      <h1>Simule aqui!</h1>
      <p>Insira os parâmetros para a simulação de energia.</p>

      <form id="formSimulacao" method="get">
        <input type="number" step="any" id="vazao" placeholder="Vazão Mássica (m³/s)" required>
        <input type="number" step="any" id="altura" placeholder="Altura da queda d'água (m)" required>
        <input type="number" step="any" id="potTurbina" placeholder="Potência das turbinas (MW)" required>
        <input type="number" step="1" id="qtdTurbinas" placeholder="Quantidade de turbinas" required>
        <input type="number" step="any" id="potGerador" placeholder="Potência do gerador (MW)" required>

        <br>
        <p>Parâmetros opcionais para geração de energia (dia/mês/ano)</p>
        <input type="number" step="any" id="eficiencia" placeholder="Eficiência do sistema (%)">
        <input type="number" step="any" id="horas" placeholder="Duração da operação diária (h/dia)">
        <br>
        <input type="submit" id="simular" value="Simular">
      </form>

      <!-- Tabela de Resultados -->
      <div id="resultados" style="display:none; margin-top:30px;">
        <h3>Resultados da Simulação</h3>
        <table id="tabelaResultados">
          <tbody id="corpoTabela"></tbody>
        </table>

        <div style="margin-top: 20px;">
          <button type="button" id="salvar">Salvar no histórico</button>
          <button type="button" id="exportar">Exportar</button>
          <div id="mensagemSucesso">✅ Simulação salva com sucesso no histórico!</div>
        </div>
      </div>
    </div>
  </div>

  <footer>
    <p>&copy; Todos os direitos reservados. <a href="politica.html">Políticas de privacidade.</a></p>
  </footer>

<script>
document.getElementById("formSimulacao").addEventListener("submit", function(e) {
    e.preventDefault();

    const rho = 1000;
    const g = 9.81;

    const vazao = parseFloat(document.getElementById("vazao").value);
    const altura = parseFloat(document.getElementById("altura").value);
    const potTurbina = parseFloat(document.getElementById("potTurbina").value);
    const qtdTurbinas = parseInt(document.getElementById("qtdTurbinas").value);
    const potGerador = parseFloat(document.getElementById("potGerador").value);
    const eficiencia = parseFloat(document.getElementById("eficiencia").value)/100 || 1;
    const horas = parseFloat(document.getElementById("horas").value) || 0;

    if (isNaN(vazao) || isNaN(altura) || isNaN(potTurbina) || isNaN(qtdTurbinas) || isNaN(potGerador)) {
        alert("Preencha todos os campos obrigatórios!");
        return;
    }

    const resultadoPrincipal = eficiencia * rho * vazao * g * altura * qtdTurbinas / 1e6;
    const geracaoDia = horas > 0 ? resultadoPrincipal * horas : 0;
    const geracaoMes = horas > 0 ? geracaoDia * 30 : 0;
    const geracaoAno = horas > 0 ? geracaoDia * 365 : 0;

    let linhas = `
        <tr><td>Vazão Mássica</td><td>${vazao.toFixed(2)} m³/s</td></tr>
        <tr><td>Altura da queda</td><td>${altura.toFixed(2)} m</td></tr>
        <tr><td>Potência da turbina</td><td>${potTurbina.toFixed(2)} MW</td></tr>
        <tr><td>Quantidade de turbinas</td><td>${qtdTurbinas}</td></tr>
        <tr><td>Potência do gerador</td><td>${potGerador.toFixed(2)} MW</td></tr>
    `;

    if (!isNaN(eficiencia)) linhas += `<tr><td>Eficiência do sistema</td><td>${(eficiencia*100).toFixed(2)} %</td></tr>`;
    if (horas > 0) linhas += `<tr><td>Duração diária</td><td>${horas.toFixed(2)} h/dia</td></tr>`;

    linhas += `<tr><td><b>Geração total:</b></td><td>${resultadoPrincipal.toFixed(2)} MW</td></tr>`;

    if (horas > 0) {
        linhas += `
            <tr><td><b>Geração por hora:</b></td><td>${resultadoPrincipal.toFixed(2)} MWh</td></tr>
            <tr><td><b>Geração diária:</b></td><td>${geracaoDia.toFixed(2)} MWh</td></tr>
            <tr><td><b>Geração por mês:</b></td><td>${geracaoMes.toFixed(2)} MWh</td></tr>
            <tr><td><b>Geração por ano:</b></td><td>${geracaoAno.toFixed(2)} MWh</td></tr>
        `;
    }

    document.getElementById("corpoTabela").innerHTML = linhas;
    document.getElementById("resultados").style.display = "block";

    window.simulacaoAtual = {vazao, altura, potTurbina, qtdTurbinas, potGerador, eficiencia, horas, geracaoDia, geracaoMes, geracaoAno};
});

document.getElementById("salvar").addEventListener("click", function() {
    if (!window.simulacaoAtual) {
        alert("Simule primeiro antes de salvar!");
        return;
    }

    fetch('salvar_simulacao.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(window.simulacaoAtual)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const msg = document.getElementById("mensagemSucesso");
            msg.style.display = "block";
            setTimeout(() => { msg.style.display = "none"; }, 2000);
        } else {
            alert("Erro ao salvar: " + data.message);
        }
    })
    .catch(err => alert("Erro: " + err));
});
</script>

</body>
</html>
