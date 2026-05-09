<?php
session_start();
include('php/error_handler.php');
include('php/seguranca.php');

$mensagem_sucesso = '';
$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting para recuperação de senha (máximo 3 tentativas por 30 minutos)
    if (!rateLimitCheck('recuperar_senha', 3, 1800)) {
        $mensagem_erro = 'Muitas tentativas. Tente novamente em 30 minutos.';
    } else {
        $email = sanitizeInput(trim($_POST['email'] ?? ''));

        if (empty($email) || !validarEmail($email)) {
            $mensagem_erro = 'Digite um e-mail válido.';
        } else {
            include 'php/conexao.php';

            $sql = 'SELECT id_usuario, nomeUsuario FROM Usuario WHERE emailUsuario = ?';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                logTentativaSuspeita('erro_sql_recuperar_senha', ['email' => $email]);
                $mensagem_erro = 'Erro no sistema. Tente novamente.';
            } else {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $resultado = $stmt->get_result();

                if ($resultado->num_rows > 0) {
                    $usuario = $resultado->fetch_assoc();

                    // Gerar token único e seguro
                    $token = bin2hex(random_bytes(32));
                    $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token válido por 1 hora

                    // Salvar token no banco
                    $sqlToken = 'INSERT INTO RecuperacaoSenha (id_usuario, token, data_expiracao) VALUES (?, ?, ?)';
                    $stmtToken = $conn->prepare($sqlToken);
                    $stmtToken->bind_param('iss', $usuario['id_usuario'], $token, $expiracao);

                    if ($stmtToken->execute()) {
                        // Enviar e-mail de recuperação
                        include 'php/config_email.php';
                        
                        if (enviarEmailRecuperacao($email, $usuario['nomeUsuario'], $token)) {
                            $mensagem_sucesso = 'Se existe uma conta vinculada a este e-mail, enviamos um link para redefinir sua senha. Verifique sua caixa de entrada e spam.';
                            logAuditoria('recuperacao_senha_enviada', ['email' => $email]);
                        } else {
                            $mensagem_erro = 'Erro ao enviar o e-mail. Tente novamente mais tarde.';
                            logTentativaSuspeita('erro_envio_email', ['email' => $email]);
                        }
                    } else {
                        $mensagem_erro = 'Erro ao gerar link de recuperação. Tente novamente.';
                        logTentativaSuspeita('erro_token_recuperacao', ['email' => $email]);
                    }
                } else {
                    // Mesmo se não existir, mostrar mensagem genérica por segurança
                    $mensagem_sucesso = 'Se existe uma conta vinculada a este e-mail, enviamos um link para redefinir sua senha. Verifique sua caixa de entrada e spam.';
                    logTentativaSuspeita('email_nao_encontrado_recuperacao', ['email' => $email]);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar senha - SiSGEH</title>
<link rel="stylesheet" href="css/components/header.css">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/components/botoes.css">
<link rel="stylesheet" type="text/css" href="css/recuperar_senha.css"> 
</head>
<body>

<header>
    <div class="caixa_de_texto">
            <input type="text" class="search-text" placeholder="Pesquisar...">
    </div>

    <h1 class="sisgeh">SiSGEH</h1>

    <nav class="links">
        <ul>
            <li>
                <a href="sobre.html" class="sobre">Sobre</a>

                <a href="index.html" class="link_home">
                    <img src="images/home.png" alt="Voltar para a Home" class="home">
                </a>
            </li>
        </ul>
    </nav>
</header>

<div class="layout">
    <section class="recuperar_senha">
        <div class="mensagem-pequena">
            <h2>Recuperar senha</h2>
            <p>Informe o e-mail associado à sua conta. Enviaremos um link seguro para redefinir sua senha.</p>
        </div>

        <?php if ($mensagem_sucesso): ?>
            <div class="status-mensagem sucesso"><?php echo $mensagem_sucesso; ?></div>
        <?php endif; ?>
        <?php if ($mensagem_erro): ?>
            <div class="status-mensagem erro"><?= htmlspecialchars($mensagem_erro) ?></div>
        <?php endif; ?>

        <form action="recuperar_senha.php" method="POST">
                <label for="email">DIGITE SEU E-MAIL:</label>
                <input type="email" id="email" name="email" placeholder="seu@exemplo.com" maxlength="35" required>
                <input type="submit" value="Enviar" class="botao-generico">
        </form>

        <div class="mensagem-pequena">
            <p class="info">Após clicar no link enviado, você poderá definir uma nova senha segura.</p>
        </div>
    </section>
</div>

<footer>
    <p>&copy; Todos os direitos reservados. <a href="politica.html">Políticas de privacidade.</a></p>
</footer>

</body>
</html>
