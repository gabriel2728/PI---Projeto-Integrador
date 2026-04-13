<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}
include 'conexao.php';

$id_usuario = $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_simulacao = intval($_POST['id_simulacao'] ?? 0);

    if ($id_simulacao <= 0) {
        $_SESSION['mensagem_erro'] = 'Simulação inválida.';
        header('Location: historico.php');
        exit();
    }

    // Garantir que a simulação pertence ao usuário
    $stmt = $conn->prepare('SELECT id_simulacao FROM Simulacoes WHERE id_simulacao = ? AND id_usuario = ?');
    $stmt->bind_param('ii', $id_simulacao, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['mensagem_erro'] = 'Simulação não encontrada ou você não tem permissão para excluir.';
        header('Location: historico.php');
        exit();
    }

    // Excluir os resultados associados primeiro para evitar problemas de FK
    $stmt = $conn->prepare('DELETE FROM ResultadoSimulacao WHERE id_simulacao = ?');
    $stmt->bind_param('i', $id_simulacao);
    $stmt->execute();

    $stmt = $conn->prepare('DELETE FROM Simulacoes WHERE id_simulacao = ?');
    $stmt->bind_param('i', $id_simulacao);

    if ($stmt->execute()) {
        $_SESSION['mensagem_sucesso'] = 'Simulação excluída com sucesso.';
    } else {
        $_SESSION['mensagem_erro'] = 'Erro ao excluir simulação: ' . $conn->error;
    }
}

header('Location: historico.php');
exit();
