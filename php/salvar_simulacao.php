<?php
// Limpa qualquer echo acidental que quebre o JSON
ob_start();
session_start();
include('error_handler.php');
include('conexao.php');
include('seguranca.php');
header('Content-Type: application/json');

try {
    // 1️⃣ Verifica se o usuário está logado
    if (!isset($_SESSION['id_usuario'])) {
        throw new Exception("Usuário não logado");
    }

    $id_usuario = $_SESSION['id_usuario'];

    // 2️⃣ Recebe os dados JSON enviados pelo fetch
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception("Dados inválidos ou não enviados");
    }

    // 3️⃣ Extrai e VALIDA os valores
    $vazao = $data['vazao'] ?? null;
    $altura = $data['altura'] ?? null;
    $potTurbina = $data['potTurbina'] ?? null;
    $qtdTurbinas = $data['qtdTurbinas'] ?? null;
    $potGerador = $data['potGerador'] ?? null;
    $eficiencia = $data['eficiencia'] ?? 1;
    $horas = $data['horas'] ?? 0;
    
    // Validar que todos os campos obrigatórios estão presentes
    if ($vazao === null || $altura === null || $potTurbina === null || $qtdTurbinas === null || $potGerador === null) {
        throw new Exception("Campos obrigatórios faltando");
    }
    
    // Validar tipos de dados numéricos
    if (!validarNumero($vazao) || !validarNumero($altura) || !validarNumero($potTurbina) || !validarNumero($potGerador)) {
        throw new Exception("Parâmetros numéricos inválidos");
    }
    
    if (!validarNumero($qtdTurbinas, 'int') || $qtdTurbinas <= 0 || $qtdTurbinas > 100) {
        throw new Exception("Quantidade de turbinas inválida");
    }
    
    // Validar intervalos realistas
    if ($vazao < 0 || $vazao > 10000 || $altura < 0 || $altura > 1000 || $potTurbina < 0 || $potTurbina > 1000 || $potGerador < 0 || $potGerador > 1000) {
        throw new Exception("Valores fora do intervalo realista");
    }
    
    if ($eficiencia < 0 || $eficiencia > 1 || $horas < 0 || $horas > 24) {
        throw new Exception("Eficiência ou horas inválidas");
    }
    
    // Rate limiting: máximo 20 simulações por hora por usuário
    if (!rateLimitCheck("salvar_simulacao_{$id_usuario}", 20, 3600)) {
        logTentativaSuspeita('rate_limit_simulacao_excedido', ['id_usuario' => $id_usuario]);
        throw new Exception("Limite de simulações por hora excedido");
    }
    
    // Converter para float/int
    $vazao = floatval($vazao);
    $altura = floatval($altura);
    $potTurbina = floatval($potTurbina);
    $qtdTurbinas = intval($qtdTurbinas);
    $potGerador = floatval($potGerador);
    $eficiencia = floatval($eficiencia);
    $horas = floatval($horas);

    // 4️⃣ Calcula geração de energia
    $rho = 1000;  // kg/m³
    $g = 9.81;    // m/s²
    $resultadoPrincipal = $eficiencia * $rho * $vazao * $g * $altura * $qtdTurbinas / 1e6;
    
    // Se horas > 0, calcula adicionais, senão mantém 0
    $geracaoDia = $horas > 0 ? $resultadoPrincipal * $horas : 0;
    $geracaoMes = $horas > 0 ? $geracaoDia * 30 : 0;
    $geracaoAno = $horas > 0 ? $geracaoDia * 365 : 0;

    // 5️⃣ Insere a simulação na tabela Simulacoes
    $stmt = $conn->prepare("INSERT INTO Simulacoes (id_usuario, vazao, altura, potTurbina, qtdTurbinas, potGerador, eficiencia, horas) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) throw new Exception("Erro na preparação da query: " . $conn->error);

    $stmt->bind_param("idddiddi", $id_usuario, $vazao, $altura, $potTurbina, $qtdTurbinas, $potGerador, $eficiencia, $horas);
    $stmt->execute();
    $id_simulacao = $stmt->insert_id; // pega o ID da simulação inserida
    $stmt->close();

    // 6️⃣ Insere os resultados na tabela ResultadoSimulacao (incluindo resultado principal)
    $stmt2 = $conn->prepare("INSERT INTO ResultadoSimulacao (id_simulacao, geracao_principal, geracao_diaria, geracao_mensal, geracao_anual) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt2) throw new Exception("Erro na preparação da query: " . $conn->error);

    $stmt2->bind_param("idddd", $id_simulacao, $resultadoPrincipal, $geracaoDia, $geracaoMes, $geracaoAno);
    $stmt2->execute();
    $stmt2->close();

    // 7️⃣ Retorna sucesso
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // 8️⃣ Retorna erro em JSON
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
