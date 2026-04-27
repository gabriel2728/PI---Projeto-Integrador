<?php
session_start();
include('error_handler.php');
include('seguranca.php');
include('conexao.php');

iniciarSessaoSegura();

// Verifica rate limiting para cadastro
if (!rateLimitCheck('cadastro', 10, 3600)) { // 10 tentativas por hora
    http_response_code(429);
    echo "<script>alert('Muitas tentativas de cadastro. Tente novamente mais tarde.'); window.history.back();</script>";
    exit;
}

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar CSRF token
    $csrf_token_post = sanitizeInput($_POST['csrf_token'] ?? '');
    if (!verificarTokenCSRF($csrf_token_post)) {
        logTentativaSuspeita('csrf_fail_cadastro', ['ip' => $_SERVER['REMOTE_ADDR']]);
        echo "<script>alert('Token de segurança inválido. Tente novamente.'); window.history.back();</script>";
        exit;
    }

    // Sanitização de entrada
    $nome = sanitizeInput($_POST['nomeUsuario'] ?? '');
    $telefone = sanitizeInput($_POST['telefoneUsuario'] ?? '');
    $email = sanitizeInput($_POST['emailUsuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';

    // Array de erros
    $erros = [];

    // Validações
    if (empty($nome)) {
        $erros[] = 'Nome é obrigatório.';
        logTentativaSuspeita('nome_vazio', ['email' => $email]);
    } elseif (!validarNome($nome)) {
        $erros[] = 'Nome deve conter apenas letras, espaços, hífens e apóstrofos (2-50 caracteres).';
        logTentativaSuspeita('nome_invalido', ['nome' => $nome, 'email' => $email]);
    }

    if (empty($email)) {
        $erros[] = 'E-mail é obrigatório.';
        logTentativaSuspeita('email_vazio', ['nome' => $nome]);
    } elseif (!validarEmail($email)) {
        $erros[] = 'E-mail inválido.';
        logTentativaSuspeita('email_invalido', ['email' => $email]);
    }

    if (!empty($telefone) && !validarTelefone($telefone)) {
        $erros[] = 'Telefone inválido. Use formato: (11)99999-9999 ou 11 99999-9999.';
        logTentativaSuspeita('telefone_invalido', ['telefone' => $telefone, 'email' => $email]);
    }

    if (empty($senha)) {
        $erros[] = 'Senha é obrigatória.';
    } elseif (!validarSenha($senha)) {
        $erros[] = 'Senha deve ter no mínimo 8 caracteres, incluindo letra maiúscula, minúscula e número.';
        logTentativaSuspeita('senha_fraca', ['email' => $email]);
    }

    if (empty($confirmar)) {
        $erros[] = 'Confirmação de senha é obrigatória.';
    } elseif ($senha !== $confirmar) {
        $erros[] = 'As senhas não coincidem.';
        logTentativaSuspeita('senha_nao_coincide', ['email' => $email]);
    }

    // Se houver erros, exibe e retorna
    if (!empty($erros)) {
        $erro_msg = implode("\n", $erros);
        echo "<script>alert('Erro no cadastro:\\n\\n$erro_msg'); window.history.back();</script>";
        exit;
    }

    // Verifica se email já existe
    $sql_check = "SELECT id_usuario FROM Usuario WHERE emailUsuario = ?";
    $stmt_check = $conn->prepare($sql_check);
    if (!$stmt_check) {
        logTentativaSuspeita('erro_sql_check', ['email' => $email]);
        echo "<script>alert('Erro no sistema. Tente novamente.'); window.history.back();</script>";
        exit;
    }

    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        echo "<script>alert('Este e-mail já está cadastrado. Use outro e-mail ou faça login.'); window.history.back();</script>";
        logTentativaSuspeita('email_duplicado', ['email' => $email]);
        $stmt_check->close();
        exit;
    }
    $stmt_check->close();

    // Criptografa a senha
    $senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);
    $confirmacaoToken = bin2hex(random_bytes(32));
    $expiracaoToken = date('Y-m-d H:i:s', strtotime('+1 day'));

    // Usa Prepared Statement (SQL Injection Protection)
    $sql = "INSERT INTO Usuario (nomeUsuario, telefoneUsuario, emailUsuario, senha, emailConfirmado, confirmacaoToken, emailConfirmacaoExpiracao) VALUES (?, ?, ?, ?, 0, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        logTentativaSuspeita('erro_sql_insert', ['email' => $email]);
        echo "<script>alert('Erro no sistema. Tente novamente.'); window.history.back();</script>";
        exit;
    }

    $stmt->bind_param("ssssss", $nome, $telefone, $email, $senhaCriptografada, $confirmacaoToken, $expiracaoToken);
    
    if ($stmt->execute()) {
        $id_usuario = $stmt->insert_id;
        include 'config_email.php';

        if (enviarEmailConfirmacao($email, $nome, $confirmacaoToken)) {
            echo "<script>alert('Cadastro realizado! Verifique seu e-mail e confirme sua conta antes de entrar.'); window.location.href='../index.html';</script>";
        } else {
            logTentativaSuspeita('erro_envio_confirmacao', ['email' => $email]);
            echo "<script>alert('Cadastro realizado, mas não foi possível enviar o e-mail de confirmação. Contate o administrador.'); window.location.href='../index.html';</script>";
        }
        exit;
    } else {
        logTentativaSuspeita('erro_execute', ['email' => $email, 'error' => $conn->error]);
        echo "<script>alert('Erro ao cadastrar usuário. Tente novamente.'); window.history.back();</script>";
        exit;
    }

    $stmt->close();
}
?>
