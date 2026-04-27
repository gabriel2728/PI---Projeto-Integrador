<?php
session_start();
include('error_handler.php');
include('seguranca.php');
include 'conexao.php';

// Verificar se usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Gerar CSRF token se não existir
$csrf_token = gerarTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF token
    $csrf_token_post = sanitizeInput($_POST['csrf_token'] ?? '');
    if (!verificarTokenCSRF($csrf_token_post)) {
        logTentativaSuspeita('csrf_fail_config_perfil', ['ip' => $_SERVER['REMOTE_ADDR']]);
        $_SESSION['mensagem_erro'] = 'Token de segurança inválido. Tente novamente.';
        header('Location: configuracoes_perfil.php');
        exit;
    }

    // Rate limiting para alterações de perfil (máximo 5 alterações por hora)
    if (!rateLimitCheck('config_perfil', 5, 3600)) {
        $_SESSION['mensagem_erro'] = 'Muitas alterações realizadas recentemente. Tente novamente em uma hora.';
        header('Location: configuracoes_perfil.php');
        exit;
    }

    $tipo = sanitizeInput($_POST['tipo'] ?? '');

    switch ($tipo) {
        case 'nome':
            $novo_nome = sanitizeInput($_POST['nome'] ?? '');

            if (empty($novo_nome)) {
                $_SESSION['mensagem_erro'] = 'Nome não pode estar vazio.';
                logTentativaSuspeita('nome_vazio', ['nome' => $novo_nome]);
            } elseif (!validarNome($novo_nome)) {
                $_SESSION['mensagem_erro'] = 'Nome deve conter apenas letras, espaços, hífens e apóstrofos (2-50 caracteres).';
                logTentativaSuspeita('nome_invalido', ['nome' => $novo_nome]);
            } elseif (strlen($novo_nome) < 2 || strlen($novo_nome) > 50) {
                $_SESSION['mensagem_erro'] = 'Nome deve ter entre 2 e 50 caracteres.';
            } else {
                $sql = "UPDATE Usuario SET nomeUsuario = ? WHERE id_usuario = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $novo_nome, $id_usuario);
                if ($stmt->execute()) {
                    $_SESSION['mensagem_sucesso'] = 'Nome alterado com sucesso!';
                    $_SESSION['nomeUsuario'] = $novo_nome; // Atualizar sessão
                } else {
                    $_SESSION['mensagem_erro'] = 'Erro ao alterar nome. Tente novamente.';
                    error_log("Erro SQL nome: " . $conn->error);
                }
            }
            break;

        case 'email':
            $novo_email = sanitizeInput($_POST['email'] ?? '');
            $confirmar_email = sanitizeInput($_POST['confirmar_email'] ?? '');

            if (empty($novo_email) || empty($confirmar_email)) {
                $_SESSION['mensagem_erro'] = 'Preencha ambos os campos de e-mail.';
                logTentativaSuspeita('email_vazio', ['email1' => $novo_email, 'email2' => $confirmar_email]);
            } elseif ($novo_email !== $confirmar_email) {
                $_SESSION['mensagem_erro'] = 'Os e-mails não coincidem.';
                logTentativaSuspeita('email_nao_coincide', ['email1' => $novo_email, 'email2' => $confirmar_email]);
            } elseif (!validarEmail($novo_email)) {
                $_SESSION['mensagem_erro'] = 'E-mail inválido. Use um formato válido (exemplo@dominio.com).';
                logTentativaSuspeita('email_invalido', ['email' => $novo_email]);
            } elseif (strlen($novo_email) > 100) {
                $_SESSION['mensagem_erro'] = 'E-mail muito longo (máximo 100 caracteres).';
            } else {
                // Verificar se e-mail já existe
                $sql_check = "SELECT id_usuario FROM Usuario WHERE emailUsuario = ? AND id_usuario != ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("si", $novo_email, $id_usuario);
                $stmt_check->execute();
                if ($stmt_check->get_result()->num_rows > 0) {
                    $_SESSION['mensagem_erro'] = 'Este e-mail já está em uso por outra conta.';
                    logTentativaSuspeita('email_duplicado', ['email' => $novo_email]);
                } else {
                    $sql = "UPDATE Usuario SET emailUsuario = ? WHERE id_usuario = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $novo_email, $id_usuario);
                    if ($stmt->execute()) {
                        $_SESSION['mensagem_sucesso'] = 'E-mail alterado com sucesso!';
                    } else {
                        $_SESSION['mensagem_erro'] = 'Erro ao alterar e-mail. Tente novamente.';
                        error_log("Erro SQL email: " . $conn->error);
                    }
                }
            }
            break;

        case 'senha':
            $nova_senha = $_POST['senha'] ?? '';
            $confirmar_senha = $_POST['confirmar_senha'] ?? '';

            if (empty($nova_senha) || empty($confirmar_senha)) {
                $_SESSION['mensagem_erro'] = 'Preencha ambos os campos de senha.';
                logTentativaSuspeita('senha_vazia', ['senha1_len' => strlen($nova_senha), 'senha2_len' => strlen($confirmar_senha)]);
            } elseif ($nova_senha !== $confirmar_senha) {
                $_SESSION['mensagem_erro'] = 'As senhas não coincidem.';
                logTentativaSuspeita('senha_nao_coincide', ['senha1_len' => strlen($nova_senha), 'senha2_len' => strlen($confirmar_senha)]);
            } elseif (!validarSenha($nova_senha)) {
                $_SESSION['mensagem_erro'] = 'A senha deve ter pelo menos 8 caracteres, incluindo uma letra maiúscula, uma minúscula e um número.';
                logTentativaSuspeita('senha_fraca', ['senha_len' => strlen($nova_senha)]);
            } elseif (strlen($nova_senha) > 255) {
                $_SESSION['mensagem_erro'] = 'Senha muito longa (máximo 255 caracteres).';
            } else {
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $sql = "UPDATE Usuario SET senha = ? WHERE id_usuario = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $senha_hash, $id_usuario);
                if ($stmt->execute()) {
                    $_SESSION['mensagem_sucesso'] = 'Senha alterada com sucesso!';
                } else {
                    $_SESSION['mensagem_erro'] = 'Erro ao alterar senha. Tente novamente.';
                    error_log("Erro SQL senha: " . $conn->error);
                }
            }
            break;

        default:
            $_SESSION['mensagem_erro'] = 'Tipo de alteração inválido.';
            logTentativaSuspeita('tipo_invalido', ['tipo' => $tipo]);
    }

    header('Location: configuracoes_perfil.php');
    exit();
} else {
    // Método HTTP inválido
    logTentativaSuspeita('metodo_invalido', ['method' => $_SERVER['REQUEST_METHOD']]);
    header('Location: configuracoes_perfil.php');
    exit();
}
?>