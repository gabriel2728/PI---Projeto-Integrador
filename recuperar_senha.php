<?php
session_start();

$mensagem_sucesso = '';
$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem_erro = 'Digite um e-mail válido.';
    } else {
        include 'php/conexao.php';

        $sql = 'SELECT id_usuario, nomeUsuario FROM Usuario WHERE emailUsuario = ?';
        $stmt = $conn->prepare($sql);
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
                // Link para redefinição (em produção, seria enviado por e-mail)
                $linkRedefinicao = "http://localhost/siteatual/redefinir_senha.php?token=" . $token;

                $mensagem_sucesso = "✅ Link de recuperação enviado!<br><br>";
                $mensagem_sucesso .= "<strong>Para desenvolvimento:</strong><br>";
                $mensagem_sucesso .= "<a href='{$linkRedefinicao}' target='_blank' style='color: #007bff;'>Clique aqui para redefinir sua senha</a><br><br>";
                $mensagem_sucesso .= "<em>Em produção, este link seria enviado por e-mail.</em><br>";
                $mensagem_sucesso .= "<strong>Link expira em 1 hora.</strong>";

                // Código para envio por e-mail (descomente em produção)
                /*
                $assunto = 'Redefinição de senha - SiSGEH';
                $mensagemEmail = "Olá {$usuario['nomeUsuario']},\n\n";
                $mensagemEmail .= "Recebemos uma solicitação para redefinir sua senha.\n\n";
                $mensagemEmail .= "Clique no link abaixo para criar uma nova senha:\n";
                $mensagemEmail .= "{$linkRedefinicao}\n\n";
                $mensagemEmail .= "Este link é válido por 1 hora e pode ser usado apenas uma vez.\n";
                $mensagemEmail .= "Caso você não tenha solicitado esta redefinição, ignore este e-mail.\n\n";
                $mensagemEmail .= "Atenciosamente,\nEquipe SiSGEH\n";

                $headers = 'From: no-reply@seudominio.com' . "\r\n";
                $headers .= 'Reply-To: no-reply@seudominio.com' . "\r\n";
                $headers .= 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";

                if (mail($email, $assunto, $mensagemEmail, $headers)) {
                    $mensagem_sucesso = 'Se existe uma conta vinculada a este e-mail, enviamos um link para redefinir sua senha. Verifique sua caixa de entrada e spam.';
                } else {
                    $mensagem_erro = 'Erro ao enviar o e-mail. Tente novamente mais tarde.';
                }
                */
            } else {
                $mensagem_erro = 'Erro ao gerar link de recuperação. Tente novamente.';
            }
        } else {
            // Mesmo se não existir, mostrar mensagem genérica por segurança
            $mensagem_sucesso = 'Se existe uma conta vinculada a este e-mail, enviamos um link para redefinir sua senha. Verifique sua caixa de entrada e spam.';
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
<link rel="stylesheet" type="text/css" href="css/estilo_recuperar_senha.css"> 
</head>
<body>

<header>
    <div class="caixa_de_texto">
        <input type="text" class="search-text" placeholder="Pesquisar...">
    </div>
    <h2 class="sisgeh"> SiSGEH </h2>
    <div class="links">
      <a href="sobre.html" class="sobre"> Sobre </a>
      <a href="index.html" class="link_home">
        <img src="icon_home.png" alt="Voltar a Home" class="home">
      </a>
    </div>
</header>

 <img src="logo.png" class="logo">

<div class="layoutRecuperar_Senha">
    <div class="recuperar_senha">
        <div class="mensagem">
            <h1>Recuperar senha</h1>
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
                <input type="submit" value="Enviar">
        </form>
        <p class="info">Após clicar no link enviado, você poderá definir uma nova senha segura.<br>
        <small><em>Nota: Para produção, configure um servidor SMTP em config_email.php</em></small></p>
    </div>
</div>

<footer>
    <p>&copy; Todos os direitos reservados. <a href="politica.html">Políticas de privacidade.</a></p>
</footer>

</body>
</html>