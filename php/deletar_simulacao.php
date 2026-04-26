<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}
include('error_handler.php');
include 'conexao.php';
include 'seguranca.php';

$id_usuario = $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF token
    $csrf_token = sanitizeInput($_POST['csrf_token'] ?? '');
    if (!verificarTokenCSRF($csrf_token)) {
        logTentativaSuspeita('csrf_fail_delete_simulation', ['id_usuario' => $id_usuario]);
        $_SESSION['mensagem_erro'] = 'Token de segurança inválido. Ação não permitida.';
        header('Location: historico.php');
        exit();
    }
    
    // Rate limiting: máximo 5 deletions por hora por usuário
    if (!rateLimitCheck("deletar_simulacao_{$id_usuario}", 5, 3600)) {
        logTentativaSuspeita('rate_limit_delete_simulation', ['id_usuario' => $id_usuario]);
        $_SESSION['mensagem_erro'] = 'Limite de exclusões por hora excedido.';
        header('Location: historico.php');
        exit();
    }
    
    $id_simulacao = intval($_POST['id_simulacao'] ?? 0);

    if ($id_simulacao <= 0) {
        logTentativaSuspeita('invalid_simulation_id_delete', ['id_usuario' => $id_usuario, 'id' => $id_simulacao]);
        $_SESSION['mensagem_erro'] = 'Simulação inválida.';
        header('Location: historico.php');
        exit();
    }

    // Garantir que a simulação pertence ao usuário
    $stmt = $conn->prepare('SELECT id_simulacao FROM Simulacoes WHERE id_simulacao = ? AND id_usuario = ?');
    $stmt->bind_param('ii', $id_simulacao, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {\n        logTentativaSuspeita('unauthorized_delete_attempt', ['id_usuario' => $id_usuario, 'id_simulacao' => $id_simulacao]);\n        $_SESSION['mensagem_erro'] = 'Simulação não encontrada ou você não tem permissão para excluir.';\n        header('Location: historico.php');\n        exit();\n    }\n\n    // Excluir os resultados associados primeiro para evitar problemas de FK\n    $stmt = $conn->prepare('DELETE FROM ResultadoSimulacao WHERE id_simulacao = ?');\n    $stmt->bind_param('i', $id_simulacao);\n    $stmt->execute();\n\n    $stmt = $conn->prepare('DELETE FROM Simulacoes WHERE id_simulacao = ?');\n    $stmt->bind_param('i', $id_simulacao);\n\n    if ($stmt->execute()) {\n        logAuditoria('simulacao_excluida', $id_usuario, ['id_simulacao' => $id_simulacao]);\n        $_SESSION['mensagem_sucesso'] = 'Simulação excluída com sucesso.';\n    } else {\n        logTentativaSuspeita('delete_simulation_error', ['id_usuario' => $id_usuario, 'id_simulacao' => $id_simulacao]);\n        $_SESSION['mensagem_erro'] = 'Erro ao excluir simulação. Tente novamente.';\n    }\n}

header('Location: historico.php');
exit();
