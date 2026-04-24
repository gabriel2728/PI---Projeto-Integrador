<?php
/**
 * Arquivo de funções de segurança reutilizáveis
 * Inclua este arquivo no início de qualquer script que precise de validação
 * 
 * Uso: include('seguranca.php');
 */

// ============ SANITIZAÇÃO ============

/**
 * Sanitiza entrada para prevenir XSS
 */
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitiza entrada preservando quebras de linha (para textareas)
 */
function sanitizeTextarea($input) {
    $input = strip_tags($input, '<br><p><strong><em><u>');
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

// ============ VALIDAÇÃO ============

/**
 * Valida nome (apenas letras, espaços, hífens e apóstrofos)
 */
function validarNome($nome) {
    return preg_match('/^[a-zA-ZÀ-ÿ\s\-\']{2,50}$/u', $nome);
}

/**
 * Valida email com regex rigoroso
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) &&
           preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email) &&
           strlen($email) <= 100;
}

/**
 * Valida telefone (aceita vários formatos)
 */
function validarTelefone($telefone) {
    $telefone_limpo = preg_replace('/[^0-9]/', '', $telefone);
    return preg_match('/^\d{10,11}$/', $telefone_limpo);
}

/**
 * Valida senha forte
 * Requerimentos: 8+ caracteres, maiúscula, minúscula, número
 */
function validarSenha($senha) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/', $senha) &&
           strlen($senha) <= 255;
}

/**
 * Valida CPF (formato básico)
 */
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return preg_match('/^\d{11}$/', $cpf);
}

/**
 * Valida número (inteiro ou float)
 */
function validarNumero($numero, $tipo = 'float') {
    if ($tipo === 'int') {
        return preg_match('/^-?\d+$/', $numero);
    }
    return is_numeric($numero) && ($numero != '');
}

/**
 * Valida URL
 */
function validarURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Valida data no formato YYYY-MM-DD
 */
function validarData($data) {
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) && strtotime($data) !== false;
}

/**
 * Valida IPv4
 */
function validarIPv4($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
}

// ============ LOGGING E AUDITORIA ============

/**
 * Registra tentativas suspeitas de segurança
 */
function logTentativaSuspeita($acao, $dados = [], $arquivo = 'seguranca.log') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'desconhecido';
    $timestamp = date('Y-m-d H:i:s');
    
    $log = "[{$timestamp}] IP: {$ip} | Ação: {$acao} | Dados: " . json_encode($dados) . " | UA: {$user_agent}\n";
    
    @file_put_contents(__DIR__ . '/logs/' . $arquivo, $log, FILE_APPEND | LOCK_EX);
}

/**
 * Registra ações bem-sucedidas na auditoria
 */
function logAuditoria($acao, $usuario_id = null, $detalhes = []) {
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
    
    $log = "[{$timestamp}] Usuário ID: " . ($usuario_id ?? 'anonimo') . " | IP: {$ip} | Ação: {$acao}";
    if (!empty($detalhes)) {
        $log .= " | Detalhes: " . json_encode($detalhes);
    }
    $log .= "\n";
    
    @file_put_contents(__DIR__ . '/logs/auditoria.log', $log, FILE_APPEND | LOCK_EX);
}

// ============ RATE LIMITING ============

/**
 * Verifica rate limiting por IP
 * 
 * $limite = máximo de tentativas
 * $janela = tempo em segundos
 * Exemplo: rateLimitCheck('login', 5, 900) = máximo 5 tentativas em 15 minutos
 */
function rateLimitCheck($chave, $limite = 5, $janela = 900) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $rate_limit_key = "rate_limit_{$chave}_{$ip}";
    
    if (!isset($_SESSION[$rate_limit_key])) {
        $_SESSION[$rate_limit_key] = ['count' => 0, 'time' => time(), 'bloqueado' => false];
    }

    $rate_data = $_SESSION[$rate_limit_key];

    // Verifica se está bloqueado
    if ($rate_data['bloqueado'] && (time() - $rate_data['block_time'] < $janela)) {
        return false; // Bloqueado
    }

    // Reset após janela de tempo
    if (time() - $rate_data['time'] > $janela) {
        $rate_data = ['count' => 0, 'time' => time(), 'bloqueado' => false];
    }

    // Incrementa contador
    $rate_data['count']++;

    // Bloqueia se exceder limite
    if ($rate_data['count'] > $limite) {
        $rate_data['bloqueado'] = true;
        $rate_data['block_time'] = time();
        logTentativaSuspeita("rate_limit_excedido_{$chave}", ['ip' => $ip, 'tentativas' => $rate_data['count']]);
    }

    $_SESSION[$rate_limit_key] = $rate_data;
    return true; // Permitido
}

/**
 * Incrementa tentativa de rate limit
 */
function rateLimitIncrement($chave) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $rate_limit_key = "rate_limit_{$chave}_{$ip}";
    
    if (isset($_SESSION[$rate_limit_key])) {
        $_SESSION[$rate_limit_key]['count']++;
    }
}

/**
 * Reseta rate limit (após login bem-sucedido, por exemplo)
 */
function rateLimitReset($chave) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $rate_limit_key = "rate_limit_{$chave}_{$ip}";
    unset($_SESSION[$rate_limit_key]);
}

// ============ PROTEÇÃO CSRF ============

/**
 * Gera token CSRF
 */
function gerarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica token CSRF
 */
function verificarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

/**
 * Campo oculto HTML para formulário com CSRF token
 */
function campoCSRF() {
    $token = gerarTokenCSRF();
    return "<input type='hidden' name='csrf_token' value='{$token}'>";
}

// ============ HEADERS DE SEGURANÇA ============

/**
 * Define headers de segurança padrão
 */
function definirHeadersSeguranca() {
    // Previne clickjacking
    header("X-Frame-Options: DENY");
    
    // Previne MIME type sniffing
    header("X-Content-Type-Options: nosniff");
    
    // XSS protection
    header("X-XSS-Protection: 1; mode=block");
    
    // Política de referrer
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    // Content Security Policy (básica)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
}

// ============ PROTEÇÃO DE AUTENTICAÇÃO ============

/**
 * Verifica se usuário está logado
 */
function verificarAutenticacao() {
    if (!isset($_SESSION['id_usuario'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Verifica se existe sessão ativa
 */
function verificarSessao() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['id_usuario']);
}

/**
 * Faz logout seguro
 */
function fazerLogout() {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

// ============ CRIPTOGRAFIA ============

/**
 * Criptografa string (pode ser usada para dados sensíveis)
 * NOTA: Use password_hash para senhas!
 */
function criptografarDado($dado) {
    $chave = hash('sha256', 'chave_secreta_do_projeto', true); // MUDE ISSO!
    return base64_encode(openssl_encrypt($dado, 'AES-256-CBC', $chave, OPENSSL_RAW_DATA, substr($chave, 0, 16)));
}

/**
 * Descriptografa string
 */
function descriptografarDado($dado_criptografado) {
    $chave = hash('sha256', 'chave_secreta_do_projeto', true); // MUDE ISSO!
    return openssl_decrypt(base64_decode($dado_criptografado), 'AES-256-CBC', $chave, OPENSSL_RAW_DATA, substr($chave, 0, 16));
}

?>
