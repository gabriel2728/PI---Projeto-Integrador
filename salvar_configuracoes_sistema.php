<?php
session_start();
include 'conexao.php';

// Verificar se usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Salvar configurações do sistema
    $tema = $_POST['tema'] ?? 'claro';
    $notificacoes_email = isset($_POST['notificacoes_email']) ? 1 : 0;
    $notificacoes_sistema = isset($_POST['notificacoes_sistema']) ? 1 : 0;
    $notificacoes_simulacao = isset($_POST['notificacoes_simulacao']) ? 1 : 0;
    $notificacoes_relatorios = isset($_POST['notificacoes_relatorios']) ? 1 : 0;

    // Verificar se já existe configuração para este usuário
    $sql_check = "SELECT id_config FROM UsuarioConfiguracoes WHERE id_usuario = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $id_usuario);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // Atualizar configuração existente
        $sql = "UPDATE UsuarioConfiguracoes SET
                tema = ?,
                notificacoes_email = ?,
                notificacoes_sistema = ?,
                notificacoes_simulacao = ?,
                notificacoes_relatorios = ?
                WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siiiii", $tema, $notificacoes_email, $notificacoes_sistema,
                         $notificacoes_simulacao, $notificacoes_relatorios, $id_usuario);
    } else {
        // Inserir nova configuração
        $sql = "INSERT INTO UsuarioConfiguracoes
                (id_usuario, tema, notificacoes_email, notificacoes_sistema,
                 notificacoes_simulacao, notificacoes_relatorios)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isiiii", $id_usuario, $tema, $notificacoes_email,
                         $notificacoes_sistema, $notificacoes_simulacao, $notificacoes_relatorios);
    }

    if ($stmt->execute()) {
        $_SESSION['mensagem_sucesso'] = 'Configurações salvas com sucesso!';
    } else {
        $_SESSION['mensagem_erro'] = 'Erro ao salvar configurações: ' . $conn->error;
    }

    header('Location: configuracoes_sistema.php');
    exit();
}

// Carregar configurações atuais
$sql = "SELECT * FROM UsuarioConfiguracoes WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

$configuracoes = [
    'tema' => 'claro',
    'notificacoes_email' => 1,
    'notificacoes_sistema' => 1,
    'notificacoes_simulacao' => 1,
    'notificacoes_relatorios' => 1
];

if ($result->num_rows > 0) {
    $configuracoes = $result->fetch_assoc();
}
?>