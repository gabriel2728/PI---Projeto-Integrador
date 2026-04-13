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
    $tipo = $_POST['tipo'] ?? '';

    switch ($tipo) {
        case 'nome':
            $novo_nome = trim($_POST['nome'] ?? '');
            if (empty($novo_nome)) {
                $_SESSION['mensagem_erro'] = 'Nome não pode estar vazio.';
            } else {
                $sql = "UPDATE Usuario SET nomeUsuario = ? WHERE id_usuario = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $novo_nome, $id_usuario);
                if ($stmt->execute()) {
                    $_SESSION['mensagem_sucesso'] = 'Nome alterado com sucesso!';
                    $_SESSION['nome_usuario'] = $novo_nome; // Atualizar sessão
                } else {
                    $_SESSION['mensagem_erro'] = 'Erro ao alterar nome: ' . $conn->error;
                }
            }
            break;

        case 'email':
            $novo_email = trim($_POST['email'] ?? '');
            $confirmar_email = trim($_POST['confirmar_email'] ?? '');

            if (empty($novo_email) || empty($confirmar_email)) {
                $_SESSION['mensagem_erro'] = 'Preencha ambos os campos de e-mail.';
            } elseif ($novo_email !== $confirmar_email) {
                $_SESSION['mensagem_erro'] = 'Os e-mails não coincidem.';
            } elseif (!filter_var($novo_email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['mensagem_erro'] = 'E-mail inválido.';
            } else {
                // Verificar se e-mail já existe
                $sql_check = "SELECT id_usuario FROM Usuario WHERE emailUsuario = ? AND id_usuario != ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("si", $novo_email, $id_usuario);
                $stmt_check->execute();
                if ($stmt_check->get_result()->num_rows > 0) {
                    $_SESSION['mensagem_erro'] = 'Este e-mail já está em uso.';
                } else {
                    $sql = "UPDATE Usuario SET emailUsuario = ? WHERE id_usuario = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $novo_email, $id_usuario);
                    if ($stmt->execute()) {
                        $_SESSION['mensagem_sucesso'] = 'E-mail alterado com sucesso!';
                    } else {
                        $_SESSION['mensagem_erro'] = 'Erro ao alterar e-mail: ' . $conn->error;
                    }
                }
            }
            break;

        case 'senha':
            $nova_senha = $_POST['senha'] ?? '';
            $confirmar_senha = $_POST['confirmar_senha'] ?? '';

            if (empty($nova_senha) || empty($confirmar_senha)) {
                $_SESSION['mensagem_erro'] = 'Preencha ambos os campos de senha.';
            } elseif ($nova_senha !== $confirmar_senha) {
                $_SESSION['mensagem_erro'] = 'As senhas não coincidem.';
            } elseif (strlen($nova_senha) < 6) {
                $_SESSION['mensagem_erro'] = 'A senha deve ter pelo menos 6 caracteres.';
            } else {
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $sql = "UPDATE Usuario SET senha = ? WHERE id_usuario = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $senha_hash, $id_usuario);
                if ($stmt->execute()) {
                    $_SESSION['mensagem_sucesso'] = 'Senha alterada com sucesso!';
                } else {
                    $_SESSION['mensagem_erro'] = 'Erro ao alterar senha: ' . $conn->error;
                }
            }
            break;

        default:
            $_SESSION['mensagem_erro'] = 'Tipo de alteração inválido.';
    }

    header('Location: configuracoes_perfil.php');
    exit();
}
?>