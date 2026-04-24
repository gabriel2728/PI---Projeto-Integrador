<?php
session_start();

// Funções de segurança
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) &&
           preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email);
}

function logTentativaSuspeita($acao, $dados) {
    $log = date('Y-m-d H:i:s') . " - [LOGIN] IP: " . $_SERVER['REMOTE_ADDR'] . " - Ação: $acao - Dados: " . json_encode($dados) . "\n";
    @file_put_contents(__DIR__ . '/logs/seguranca.log', $log, FILE_APPEND | LOCK_EX);
}

// Rate limiting para login (máximo 5 tentativas por 15 minutos)
$rate_limit_key = "login_attempt_" . $_SERVER['REMOTE_ADDR'];
if (!isset($_SESSION[$rate_limit_key])) {
    $_SESSION[$rate_limit_key] = ['count' => 0, 'time' => time(), 'locked' => false];
}

$rate_data = $_SESSION[$rate_limit_key];

// Verifica se está bloqueado
if ($rate_data['locked'] && (time() - $rate_data['lock_time'] < 900)) { // 15 minutos
    http_response_code(429);
    echo "<script>alert('Muitas tentativas de login. Tente novamente em 15 minutos.'); window.history.back();</script>";
    exit;
}

// Reset após 15 minutos
if (time() - $rate_data['time'] > 900) {
    $rate_data = ['count' => 0, 'time' => time(), 'locked' => false];
} elseif ($rate_data['count'] >= 5) {
    // Bloqueia por 15 minutos
    $rate_data['locked'] = true;
    $rate_data['lock_time'] = time();
    $_SESSION[$rate_limit_key] = $rate_data;
    logTentativaSuspeita('bloqueio_por_brute_force', ['ip' => $_SERVER['REMOTE_ADDR']]);
    http_response_code(429);
    echo "<script>alert('Muitas tentativas de login. Tente novamente em 15 minutos.'); window.history.back();</script>";
    exit;
}

if (isset($_POST['entrar'])) {
    include('conexao.php'); // arquivo com sua conexão MySQL

    // Sanitização de entrada
    $email = sanitizeInput($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    // Validações básicas
    if (empty($email) || empty($senha)) {
        echo "<script>alert('E-mail e senha são obrigatórios!'); window.history.back();</script>";
        logTentativaSuspeita('campos_vazios', ['email' => $email]);
        exit;
    }

    if (!validarEmail($email)) {
        echo "<script>alert('E-mail inválido!'); window.history.back();</script>";
        logTentativaSuspeita('email_invalido', ['email' => $email]);
        exit;
    }

    // Verifica se o e-mail existe no banco usando Prepared Statement
    $sql = "SELECT id_usuario, nomeUsuario, senha FROM Usuario WHERE emailUsuario = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo "<script>alert('Erro no sistema. Tente novamente.'); window.history.back();</script>";
        exit;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

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
            // Senha incorreta - incrementa tentativa falha
            $rate_data['count']++;
            $_SESSION[$rate_limit_key] = $rate_data;
            
            logTentativaSuspeita('senha_incorreta', ['email' => $email, 'tentativa' => $rate_data['count']]);
            echo "<script>alert('Senha incorreta!'); window.history.back();</script>";
        }
    } else {
        // Email não encontrado - incrementa tentativa falha
        $rate_data['count']++;
        $_SESSION[$rate_limit_key] = $rate_data;
        
        logTentativaSuspeita('email_nao_encontrado', ['email' => $email, 'tentativa' => $rate_data['count']]);
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
	        <a href="../cadastro.html" > Criar conta </a>
        </div>

        <img src="../logo.png" class="logo">
        <div class="layoutEntrar">
        
            <h1>Área do Usuário</h1>
		    <p>Identifique-se</p>

            <form action="login.php" method="POST">
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