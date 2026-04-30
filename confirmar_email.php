<?php
session_start();
include('php/error_handler.php');
include('php/seguranca.php');
include('php/conexao.php');

$titulo = 'Confirmacao de e-mail';
$mensagem = 'Link de confirmacao invalido.';
$tipo = 'erro';

$token = sanitizeInput($_GET['token'] ?? '');

if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
    logTentativaSuspeita('token_confirmacao_invalido', ['token_len' => strlen($token)]);
} else {
    $sql = "SELECT id_usuario, nomeUsuario, emailUsuario, emailConfirmado, emailConfirmacaoExpiracao
            FROM Usuario
            WHERE confirmacaoToken = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();

            if ((int)$usuario['emailConfirmado'] === 1) {
                $mensagem = 'Seu e-mail ja estava confirmado. Voce ja pode entrar no sistema.';
                $tipo = 'sucesso';
            } elseif (strtotime($usuario['emailConfirmacaoExpiracao']) < time()) {
                $mensagem = 'Este link de confirmacao expirou. Tente criar a conta novamente para receber um novo link.';
                logTentativaSuspeita('token_confirmacao_expirado', ['id_usuario' => $usuario['id_usuario']]);
            } else {
                $sqlUpdate = "UPDATE Usuario
                              SET emailConfirmado = 1,
                                  confirmacaoToken = NULL,
                                  emailConfirmacaoExpiracao = NULL
                              WHERE id_usuario = ?";
                $stmtUpdate = $conn->prepare($sqlUpdate);

                if ($stmtUpdate) {
                    $stmtUpdate->bind_param('i', $usuario['id_usuario']);

                    if ($stmtUpdate->execute()) {
                        $mensagem = 'E-mail confirmado com sucesso! Agora voce ja pode entrar na sua conta.';
                        $tipo = 'sucesso';
                        logAuditoria('email_confirmado', $usuario['id_usuario'], ['email' => $usuario['emailUsuario']]);
                    } else {
                        $mensagem = 'Nao foi possivel confirmar seu e-mail agora. Tente novamente.';
                        logTentativaSuspeita('erro_update_confirmacao_email', ['id_usuario' => $usuario['id_usuario']]);
                    }

                    $stmtUpdate->close();
                } else {
                    $mensagem = 'Erro no sistema. Tente novamente mais tarde.';
                    logTentativaSuspeita('erro_prepare_update_confirmacao_email', ['id_usuario' => $usuario['id_usuario']]);
                }
            }
        } else {
            logTentativaSuspeita('token_confirmacao_nao_encontrado', ['token_len' => strlen($token)]);
        }

        $stmt->close();
    } else {
        $mensagem = 'Erro no sistema. Tente novamente mais tarde.';
        logTentativaSuspeita('erro_prepare_confirmacao_email', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'desconhecido']);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo); ?> - SiSGEH</title>
    <style>
        body {
            align-items: center;
            background: #f4f8fb;
            color: #1f2933;
            display: flex;
            font-family: Arial, sans-serif;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 24px;
        }

        .painel {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
            max-width: 460px;
            padding: 32px;
            text-align: center;
            width: 100%;
        }

        h1 {
            font-size: 26px;
            margin: 0 0 16px;
        }

        p {
            font-size: 16px;
            line-height: 1.5;
            margin: 0 0 24px;
        }

        .sucesso h1 {
            color: #176b3a;
        }

        .erro h1 {
            color: #9b1c1c;
        }

        a {
            background: #0f766e;
            border-radius: 6px;
            color: #ffffff;
            display: inline-block;
            font-weight: bold;
            padding: 12px 18px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <main class="painel <?php echo htmlspecialchars($tipo); ?>">
        <h1><?php echo $tipo === 'sucesso' ? 'Confirmado!' : 'Nao foi possivel confirmar'; ?></h1>
        <p><?php echo htmlspecialchars($mensagem); ?></p>
        <a href="php/login.php">Ir para login</a>
    </main>
</body>
</html>
