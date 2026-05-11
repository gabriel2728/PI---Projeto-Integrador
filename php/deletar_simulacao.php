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
    $csrf_token = sanitizeInput($_POST['csrf_token'] ?? '');
    if (!verificarTokenCSRF($csrf_token)) {
        logTentativaSuspeita('csrf_fail_delete_simulation', ['id_usuario' => $id_usuario]);
        $_SESSION['mensagem_erro'] = 'Token de seguranca invalido. Acao nao permitida.';
        header('Location: historico.php');
        exit();
    }

    if (!rateLimitCheck("deletar_simulacao_{$id_usuario}", 5, 3600)) {
        logTentativaSuspeita('rate_limit_delete_simulation', ['id_usuario' => $id_usuario]);
        $_SESSION['mensagem_erro'] = 'Limite de exclusoes por hora excedido.';
        header('Location: historico.php');
        exit();
    }

    $id_simulacao = intval($_POST['id_simulacao'] ?? 0);

    if ($id_simulacao <= 0) {
        logTentativaSuspeita('invalid_simulation_id_delete', ['id_usuario' => $id_usuario, 'id' => $id_simulacao]);
        $_SESSION['mensagem_erro'] = 'Simulacao invalida.';
        header('Location: historico.php');
        exit();
    }

    $stmt = $conn->prepare('SELECT id_simulacao FROM Simulacoes WHERE id_simulacao = ? AND id_usuario = ?');
    $stmt->bind_param('ii', $id_simulacao, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        logTentativaSuspeita('unauthorized_delete_attempt', ['id_usuario' => $id_usuario, 'id_simulacao' => $id_simulacao]);
        $_SESSION['mensagem_erro'] = 'Simulacao nao encontrada ou voce nao tem permissao para excluir.';
        header('Location: historico.php');
        exit();
    }

    $stmt = $conn->prepare('DELETE FROM ResultadoSimulacao WHERE id_simulacao = ?');
    $stmt->bind_param('i', $id_simulacao);
    $stmt->execute();

    $stmt = $conn->prepare('DELETE FROM Simulacoes WHERE id_simulacao = ?');
    $stmt->bind_param('i', $id_simulacao);

    if ($stmt->execute()) {
        logAuditoria('simulacao_excluida', $id_usuario, ['id_simulacao' => $id_simulacao]);
        $_SESSION['mensagem_sucesso'] = 'Simulacao excluida com sucesso.';
    } else {
        logTentativaSuspeita('delete_simulation_error', ['id_usuario' => $id_usuario, 'id_simulacao' => $id_simulacao]);
        $_SESSION['mensagem_erro'] = 'Erro ao excluir simulacao. Tente novamente.';
    }
}

header('Location: historico.php');
exit();
