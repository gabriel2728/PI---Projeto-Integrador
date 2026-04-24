<?php
// Inclui o arquivo de conexão
include('conexao.php');

// Funções de validação e sanitização
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validarNome($nome) {
    return preg_match('/^[a-zA-ZÀ-ÿ\s\-\']{2,50}$/u', $nome);
}

function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) &&
           preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email) &&
           strlen($email) <= 100;
}

function validarTelefone($telefone) {
    // Aceita formatos: (11)99999-9999, 11 99999-9999, 11999999999, etc
    $telefone_limpo = preg_replace('/[^0-9]/', '', $telefone);
    return preg_match('/^\d{10,11}$/', $telefone_limpo);
}

function validarSenha($senha) {
    // Mínimo 8 caracteres, letra maiúscula, minúscula e número
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/', $senha) &&
           strlen($senha) <= 255;
}

function logTentativaSuspeita($acao, $dados) {
    $log = date('Y-m-d H:i:s') . " - Ação suspeita em cadastro: $acao - Dados: " . json_encode($dados) . "\n";
    @file_put_contents(__DIR__ . '/logs/seguranca.log', $log, FILE_APPEND | LOCK_EX);
}

// Rate limiting para cadastro
$rate_limit_key = "cadastro_attempt_" . $_SERVER['REMOTE_ADDR'];
if (!isset($_SESSION[$rate_limit_key])) {
    $_SESSION[$rate_limit_key] = ['count' => 0, 'time' => time()];
}

$rate_data = $_SESSION[$rate_limit_key];
if (time() - $rate_data['time'] > 3600) { // Reset após 1 hora
    $rate_data = ['count' => 0, 'time' => time()];
}

if ($rate_data['count'] >= 10) { // Máximo 10 tentativas por hora
    http_response_code(429);
    echo "<script>alert('Muitas tentativas de cadastro. Tente novamente mais tarde.'); window.history.back();</script>";
    exit;
}

$rate_data['count']++;
$_SESSION[$rate_limit_key] = $rate_data;

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    session_start();

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

    // Usa Prepared Statement (SQL Injection Protection)
    $sql = "INSERT INTO Usuario (nomeUsuario, telefoneUsuario, emailUsuario, senha) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        logTentativaSuspeita('erro_sql_insert', ['email' => $email]);
        echo "<script>alert('Erro no sistema. Tente novamente.'); window.history.back();</script>";
        exit;
    }

    $stmt->bind_param("ssss", $nome, $telefone, $email, $senhaCriptografada);
    
    if ($stmt->execute()) {
        $id_usuario = $stmt->insert_id;
        $_SESSION['id_usuario'] = $id_usuario;
        $_SESSION['nomeUsuario'] = $nome;

        echo "<script>alert('Usuário cadastrado com sucesso!'); window.location.href='inicio.php';</script>";
        exit;
    } else {
        logTentativaSuspeita('erro_execute', ['email' => $email, 'error' => $conn->error]);
        echo "<script>alert('Erro ao cadastrar usuário. Tente novamente.'); window.history.back();</script>";
        exit;
    }

    $stmt->close();
}
?>
