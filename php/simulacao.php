<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}
include('error_handler.php');
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
    <link rel="stylesheet" href="../css/components/header.css"> 
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/components/botoes.css">
    <link rel="stylesheet" href="../css/components/tabela.css">
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

    <div class="layout">
        <section class="simular">
            <div class="mensagem-pequena">
                <h2>Simule aqui!</h2>
                <p>Insira os parâmetros para a simulação de energia.</p>
            </div>

            <form id="formSimulacao" method="get">
                <input type="number" step="any" id="vazao" placeholder="Vazão Mássica (m³/s)" required>
                <input type="number" step="any" id="altura" placeholder="Altura da queda d'água (m)" required>
                <input type="number" step="any" id="potTurbina" placeholder="Potência das turbinas (MW)" required>
                <input type="number" step="1" id="qtdTurbinas" placeholder="Quantidade de turbinas" required>
                <input type="number" step="any" id="potGerador" placeholder="Potência do gerador (MW)" required>
                <br>
                <div class="mensagem-pequena">
                    <p>Parâmetros opcionais para geração de energia (dia/mês/ano)</p>
                </div>
                <input type="number" step="any" id="eficiencia" placeholder="Eficiência do sistema (%)">
                <input type="number" step="any" id="horas" placeholder="Duração da operação diária (h/dia)">
                <br>
                <input type="submit" id="simular" value="Simular" class="botao-cinza">
            </form>

            <!-- Tabela de Resultados -->
            <div id="resultados" style="display:none; margin-top:30px;">
                <div class="mensagem-pequena">
                    <h2>Resultados da Simulação</h2>
                </div>
                <table id="tabelaResultados">
                    <tbody id="corpoTabela"></tbody>
                </table>

                    <button type="button" id="salvar" class="botao-generico">Salvar no histórico</button>
                    <button type="button" id="exportar" class="botao-generico">Exportar</button>

                <div id="mensagemSucesso">✅ Simulação salva com sucesso no histórico!</div>
            </div>
            
        
    

            <!-- Modal de Exportação -->
            <div id="modalExportacao" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.4);">
                <div class="modal-content" style="background-color:#fefefe; margin:15% auto; padding:20px; border:1px solid #888; width:400px; border-radius:8px; box-shadow:0 4px 8px rgba(0,0,0,0.2);">
                    <span class="close" id="closeModal" style="color:#aaa; float:right; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>

                    <div class="mensagem-pequena">
                        <h2 style="text-align:center; color:#333;">Escolha o formato de exportação</h2>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:20px; justify-content:center;">
                        <button type="button" id="exportPDF" style="padding:12px 24px; background-color:#ff6b6b; color:white; border:none; border-radius:5px; cursor:pointer; font-size:16px;">📄 PDF</button>
                        <button type="button" id="exportCSV" style="padding:12px 24px; background-color:#4ecdc4; color:white; border:none; border-radius:5px; cursor:pointer; font-size:16px;">📊 CSV</button>
                        <button type="button" id="exportXLSX" style="padding:12px 24px; background-color:#45b7d1; color:white; border:none; border-radius:5px; cursor:pointer; font-size:16px;">📈 XLSX</button>
                    </div>
                </div>
            </div>

        </section>
    </div>
</main>

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

// Modal de Exportação
const modal = document.getElementById("modalExportacao");
const closeBtn = document.getElementById("closeModal");

document.getElementById("exportar").addEventListener("click", function() {
    if (!window.simulacaoAtual) {
        alert("Simule primeiro antes de exportar!");
        return;
    }
    modal.style.display = "block";
});

closeBtn.addEventListener("click", function() {
    modal.style.display = "none";
});

window.addEventListener("click", function(event) {
    if (event.target === modal) {
        modal.style.display = "none";
    }
});

document.getElementById("exportPDF").addEventListener("click", function() {
    exportarSimulacao('pdf');
    modal.style.display = "none";
});

document.getElementById("exportCSV").addEventListener("click", function() {
    exportarSimulacao('csv');
    modal.style.display = "none";
});

document.getElementById("exportXLSX").addEventListener("click", function() {
    exportarSimulacao('xlsx');
    modal.style.display = "none";
});

function exportarSimulacao(formato) {
    if (!window.simulacaoAtual) {
        alert("Nenhuma simulação para exportar!");
        return;
    }

    fetch('exportacao.php?tipo=novo&formato=' + formato, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(window.simulacaoAtual)
    })
    .then(response => {
        if (!response.ok) throw new Error('Erro na exportação');
        return response.blob();
    })
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        
        const timestamp = new Date().toISOString().slice(0,19).replace(/:/g, '-');
        const nomeArquivo = `Simulacao_${timestamp}`;
        
        switch(formato) {
            case 'pdf':
                a.download = nomeArquivo + '.pdf';
                break;
            case 'csv':
                a.download = nomeArquivo + '.csv';
                break;
            case 'xlsx':
                a.download = nomeArquivo + '.xlsx';
                break;
        }
        
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    })
    .catch(err => {
        alert("Erro ao exportar: " + err.message);
    });
}
</script>

</body>
</html>
