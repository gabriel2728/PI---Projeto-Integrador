<?php
// Limpa qualquer echo acidental que quebre o JSON
ob_start();
session_start();
include('conexao.php');
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

    // 3️⃣ Extrai os valores
    $vazao = $data['vazao'];
    $altura = $data['altura'];
    $potTurbina = $data['potTurbina'];
    $qtdTurbinas = $data['qtdTurbinas'];
    $potGerador = $data['potGerador'];
    $eficiencia = $data['eficiencia'] ?? 1; // Se não enviado, assume 1
    $horas = $data['horas'] ?? 0;           // Se não enviado, assume 0

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
