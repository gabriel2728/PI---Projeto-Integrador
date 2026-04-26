<?php
session_start();
include('error_handler.php');
include('seguranca.php');
include('conexao.php');

// Gerar CSRF token se não existir
$csrf_token = gerarTokenCSRF();

if (isset($_POST['entrar'])) {
    // Verificar CSRF token
    $csrf_token_post = sanitizeInput($_POST['csrf_token'] ?? '');
    if (!verificarTokenCSRF($csrf_token_post)) {
        logTentativaSuspeita('csrf_fail_login', ['ip' => $_SERVER['REMOTE_ADDR']]);
        echo "<script>alert('Token de segurança inválido. Tente novamente.'); window.history.back();</script>";
        exit;
    }
    
    // Rate limiting para login (máximo 5 tentativas por 15 minutos)
    if (!rateLimitCheck('login', 5, 900)) {
        http_response_code(429);
        echo "<script>alert('Muitas tentativas de login. Tente novamente em 15 minutos.'); window.history.back();</script>";
        exit;
    }

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();

        // Verifica senha criptografada
        if (password_verify($senha, $usuario['senha'])) {
            // Reseta rate limiting em login bem-sucedido
            unset($_SESSION[$rate_limit_key]);

            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nomeUsuario'] = $usuario['nomeUsuario'];

            // Log de sucesso
            $log = date('Y-m-d H:i:s') . " - [LOGIN_SUCESSO] Usuário ID: " . $usuario['id_usuario'] . " - Email: " . $email . "\n";
            @file_put_contents(__DIR__ . '/logs/auditoria.log', $log, FILE_APPEND | LOCK_EX);

            header("Location: inicio.php");
            exit;
        } else {
            // Senha incorreta
            rateLimitIncrement('login');
            logTentativaSuspeita('senha_incorreta', ['email' => $email]);
            echo "<script>alert('Senha incorreta!'); window.history.back();</script>";
        }
    } else {
        // Email não encontrado
        rateLimitIncrement('login');
        logTentativaSuspeita('email_nao_encontrado', ['email' => $email]);
        echo "<script>alert('E-mail não encontrado!'); window.history.back();</script>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../css/header.css"> 
    <link rel="stylesheet" href="../css/estilo_login.css">
</head>
<body>

    <header> 
        <div class="caixa_de_texto">
            <input type="text" class="search-text" placeholder="Pesquisar...">
        </div>
        <h2 class="sisgeh"> SiSGEH </h2>

        <div class="links">

            <a href="../sobre.html" class="sobre"> Sobre </a>
            <a href="../index.html" class="link_home">
                 <img src="../home.png" alt="Voltar a Home" class="home"> 
            </a>
        </div>
    </header>

        <div class="lateral">
            <h1> Seja Bem-Vindo! </h1>
	        <p> Novo por aqui? </p>
            <br>
	        <a href="../cadastro.php" > Criar conta </a>
        </div>

        <img src="../logo.png" class="logo">
        <div class="layoutEntrar">
        
            <h1>Área do Usuário</h1>
		    <p>Identifique-se</p>

            <form action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <label for="email">E-MAIL</label>
                <input type="email" name="email" placeholder="E-mail" maxlength="50" required>

                <br>

                <label for="senha">SENHA</label>
                <input type="password" name="senha" placeholder="Senha" minlength="8" maxlength="20" required>
                <br>

                <input type="submit" name="entrar" value="Entrar" style="align-self: center;">
            </form>

               <a href="../recuperar_senha.php" class="esqueci_senha">Esqueci minha senha</a>
        </div>

</body>
</html>