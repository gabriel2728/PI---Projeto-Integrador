<?php
session_start();
include('error_handler.php');
include('seguranca.php');
include('conexao.php');

iniciarSessaoSegura();

function responderCadastro($titulo, $mensagem, $tipo = 'erro', $href = '../cadastro.php', $textoLink = 'Voltar ao cadastro') {
    $classe = $tipo === 'sucesso' ? 'sucesso' : 'erro';
    $tituloSeguro = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
    $mensagemSeguro = nl2br(htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'));
    $hrefSeguro = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
    $textoLinkSeguro = htmlspecialchars($textoLink, ENT_QUOTES, 'UTF-8');

    echo "<!DOCTYPE html>
<html lang='pt-br'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$tituloSeguro} - SiSGEH</title>
    <style>
        body {
            align-items: center;
            background: #f4f8fb;
            color: #1f2933;
            display: flex;
            font-family: Arial, sans-serif;
            justify-content: center;
            margin: 0;
            min-height: 100vh;
            padding: 24px;
        }

        .painel {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
            max-width: 520px;
            padding: 32px;
            text-align: center;
            width: 100%;
        }

        h1 {
            color: #9b1c1c;
            font-size: 26px;
            margin: 0 0 16px;
        }

        .sucesso h1 {
            color: #176b3a;
        }

        p {
            font-size: 16px;
            line-height: 1.5;
            margin: 0 0 24px;
        }

        a {
            background: #0f766e;
            border-radius: 6px;
            color: #fff;
            display: inline-block;
            font-weight: bold;
            padding: 12px 18px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <main class='painel {$classe}'>
        <h1>{$tituloSeguro}</h1>
        <p>{$mensagemSeguro}</p>
        <a href='{$hrefSeguro}'>{$textoLinkSeguro}</a>
    </main>
</body>
</html>";
    exit;
}

if (!rateLimitCheck('cadastro', 10, 3600)) {
    http_response_code(429);
    responderCadastro('Muitas tentativas', 'Muitas tentativas de cadastro. Tente novamente mais tarde.');
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrf_token_post = sanitizeInput($_POST['csrf_token'] ?? '');
    if (!verificarTokenCSRF($csrf_token_post)) {
        logTentativaSuspeita('csrf_fail_cadastro', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'desconhecido']);
        responderCadastro('Cadastro nao enviado', 'Token de seguranca invalido. Abra a tela de cadastro novamente e tente de novo.');
    }

    $nome = sanitizeInput($_POST['nomeUsuario'] ?? '');
    $telefone = sanitizeInput($_POST['telefoneUsuario'] ?? '');
    $email = sanitizeInput($_POST['emailUsuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';
    $erros = [];

    if (empty($nome)) {
        $erros[] = 'Nome e obrigatorio.';
        logTentativaSuspeita('nome_vazio', ['email' => $email]);
    } elseif (!validarNome($nome)) {
        $erros[] = 'Nome deve conter apenas letras, espacos, pontos, hifens e apostrofos (2-50 caracteres).';
        logTentativaSuspeita('nome_invalido', ['nome' => $nome, 'email' => $email]);
    }

    if (empty($email)) {
        $erros[] = 'E-mail e obrigatorio.';
        logTentativaSuspeita('email_vazio', ['nome' => $nome]);
    } elseif (!validarEmail($email)) {
        $erros[] = 'E-mail invalido.';
        logTentativaSuspeita('email_invalido', ['email' => $email]);
    }

    if (!empty($telefone) && !validarTelefone($telefone)) {
        $erros[] = 'Telefone invalido. Use 10 ou 11 numeros com DDD.';
        logTentativaSuspeita('telefone_invalido', ['telefone' => $telefone, 'email' => $email]);
    }

    if (empty($senha)) {
        $erros[] = 'Senha e obrigatoria.';
    } elseif (!validarSenha($senha)) {
        $erros[] = 'Senha deve ter no minimo 8 caracteres, incluindo letra maiuscula, minuscula e numero.';
        logTentativaSuspeita('senha_fraca', ['email' => $email]);
    }

    if (empty($confirmar)) {
        $erros[] = 'Confirmacao de senha e obrigatoria.';
    } elseif ($senha !== $confirmar) {
        $erros[] = 'As senhas nao coincidem.';
        logTentativaSuspeita('senha_nao_coincide', ['email' => $email]);
    }

    if (!empty($erros)) {
        responderCadastro('Erro no cadastro', implode("\n", $erros));
    }

    $sql_check = "SELECT id_usuario, nomeUsuario, emailConfirmado FROM Usuario WHERE emailUsuario = ?";
    $stmt_check = $conn->prepare($sql_check);
    if (!$stmt_check) {
        logTentativaSuspeita('erro_sql_check', ['email' => $email]);
        responderCadastro('Erro no sistema', 'Nao foi possivel verificar o e-mail agora. Tente novamente.');
    }

    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $resultado_check = $stmt_check->get_result();

    if ($resultado_check->num_rows > 0) {
        $usuario_existente = $resultado_check->fetch_assoc();
        $stmt_check->close();

        if ((int)$usuario_existente['emailConfirmado'] === 0) {
            $novoToken = bin2hex(random_bytes(32));
            $novaExpiracao = date('Y-m-d H:i:s', strtotime('+1 day'));
            $senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);
            $sql_update_token = "UPDATE Usuario SET nomeUsuario = ?, telefoneUsuario = ?, senha = ?, confirmacaoToken = ?, emailConfirmacaoExpiracao = ? WHERE id_usuario = ?";
            $stmt_update_token = $conn->prepare($sql_update_token);

            if (!$stmt_update_token) {
                logTentativaSuspeita('erro_sql_update_token_confirmacao', ['email' => $email]);
                responderCadastro('Erro no sistema', 'Nao foi possivel atualizar o cadastro pendente. Tente novamente.');
            }

            $stmt_update_token->bind_param("sssssi", $nome, $telefone, $senhaCriptografada, $novoToken, $novaExpiracao, $usuario_existente['id_usuario']);

            if ($stmt_update_token->execute()) {
                include 'config_email.php';

                if (enviarEmailConfirmacao($email, $nome, $novoToken)) {
                    $stmt_update_token->close();
                    responderCadastro('Link reenviado', 'Este e-mail ja tinha um cadastro pendente. Enviamos um novo link de confirmacao.', 'sucesso', '../index.html', 'Ir para o inicio');
                }

                logTentativaSuspeita('erro_reenvio_confirmacao', ['email' => $email]);
                $stmt_update_token->close();
                responderCadastro('Cadastro pendente atualizado', 'Nao foi possivel reenviar o e-mail de confirmacao agora. Verifique a configuracao SMTP e tente novamente.');
            }

            logTentativaSuspeita('erro_execute_update_token_confirmacao', ['email' => $email]);
            $stmt_update_token->close();
            responderCadastro('Erro no cadastro', 'Erro ao atualizar cadastro pendente. Tente novamente.');
        }

        logTentativaSuspeita('email_duplicado', ['email' => $email]);
        responderCadastro('E-mail ja cadastrado', 'Este e-mail ja esta cadastrado. Use outro e-mail ou faca login.', 'erro', 'login.php', 'Ir para login');
    }

    $stmt_check->close();

    $senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);
    $confirmacaoToken = bin2hex(random_bytes(32));
    $expiracaoToken = date('Y-m-d H:i:s', strtotime('+1 day'));

    $sql = "INSERT INTO Usuario (nomeUsuario, telefoneUsuario, emailUsuario, senha, emailConfirmado, confirmacaoToken, emailConfirmacaoExpiracao) VALUES (?, ?, ?, ?, 0, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        logTentativaSuspeita('erro_sql_insert', ['email' => $email]);
        responderCadastro('Erro no sistema', 'Nao foi possivel preparar o cadastro. Tente novamente.');
    }

    $stmt->bind_param("ssssss", $nome, $telefone, $email, $senhaCriptografada, $confirmacaoToken, $expiracaoToken);

    if ($stmt->execute()) {
        include 'config_email.php';

        if (enviarEmailConfirmacao($email, $nome, $confirmacaoToken)) {
            $stmt->close();
            responderCadastro('Cadastro realizado', 'Verifique seu e-mail e confirme sua conta antes de entrar.', 'sucesso', '../index.html', 'Ir para o inicio');
        }

        logTentativaSuspeita('erro_envio_confirmacao', ['email' => $email]);
        $stmt->close();
        responderCadastro('Cadastro realizado', 'A conta foi criada, mas nao foi possivel enviar o e-mail de confirmacao. Verifique a configuracao SMTP e tente reenviar criando a conta novamente com o mesmo e-mail.');
    }

    logTentativaSuspeita('erro_execute', ['email' => $email, 'error' => $conn->error]);
    $stmt->close();
    responderCadastro('Erro no cadastro', 'Erro ao cadastrar usuario. Tente novamente.');
}

header('Location: ../cadastro.php');
exit;
?>
