<?php
/**
 * Sistema Centralizado de Tratamento de Erros
 * Inclua este arquivo no início de cada arquivo PHP para tratamento global de erros
 */

// Detectar ambiente (desenvolvimento = com display_errors, produção = sem)
$isDevelopment = (getenv('APP_ENV') === 'development' || getenv('APP_ENV') === false);

// Configurar exibição de erros
if ($isDevelopment) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
}

// Definir o nível de log
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Handler customizado para erros
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
    $uri = $_SERVER['REQUEST_URI'] ?? 'CLI';
    
    // Classificar o tipo de erro
    $errorType = match($errno) {
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_NOTICE => 'NOTICE',
        E_PARSE => 'PARSE',
        E_DEPRECATED => 'DEPRECATED',
        E_STRICT => 'STRICT',
        default => 'UNKNOWN'
    };
    
    // Log detalhado para arquivo
    $logMessage = "[$timestamp] [$errorType] ";
    $logMessage .= "IP: $ip | Method: $method | URI: $uri\n";
    $logMessage .= "Error: $errstr in $errfile (line $errline)\n";
    $logMessage .= "---\n";
    
    @file_put_contents(__DIR__ . '/logs/php_errors.log', $logMessage, FILE_APPEND | LOCK_EX);
    
    // Em produção, não exibir erro detalhado
    if (!$isDevelopment) {
        // Mostrar mensagem genérica
        if ($errno === E_ERROR || $errno === E_PARSE) {
            header('Content-Type: text/html; charset=UTF-8');
            echo '<h1>Erro no Sistema</h1>';
            echo '<p>Ocorreu um erro durante o processamento. Tente novamente mais tarde.</p>';
            exit;
        }
    }
    
    return true; // Deixar o PHP lidar com a exibição (se configurado)
});

// Handler customizado para exceções
set_exception_handler(function($exception) {
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    
    // Log detalhado para arquivo
    $logMessage = "[$timestamp] [EXCEPTION] ";
    $logMessage .= "IP: $ip | Exception: " . get_class($exception) . "\n";
    $logMessage .= "Message: " . $exception->getMessage() . "\n";
    $logMessage .= "File: " . $exception->getFile() . " (line " . $exception->getLine() . ")\n";
    $logMessage .= "Trace:\n" . $exception->getTraceAsString() . "\n";
    $logMessage .= "---\n";
    
    @file_put_contents(__DIR__ . '/logs/php_errors.log', $logMessage, FILE_APPEND | LOCK_EX);
    
    // Em produção, não exibir trace completo
    if (!$isDevelopment) {
        header('Content-Type: text/html; charset=UTF-8');
        echo '<h1>Erro no Sistema</h1>';
        echo '<p>Ocorreu um erro inesperado. Contate o administrador se o problema persistir.</p>';
    } else {
        // Em desenvolvimento, mostrar erro
        header('Content-Type: text/html; charset=UTF-8');
        echo '<h1>Exceção não Capturada</h1>';
        echo '<pre>';
        echo 'Exception: ' . get_class($exception) . "\n";
        echo 'Message: ' . $exception->getMessage() . "\n";
        echo 'File: ' . $exception->getFile() . ':' . $exception->getLine() . "\n\n";
        echo 'Trace:' . "\n";
        echo $exception->getTraceAsString();
        echo '</pre>';
    }
    
    exit;
});

// Handler para shutdown (pega fatais não capturados)
register_shutdown_function(function() {
    $error = error_get_last();
    
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        
        $logMessage = "[$timestamp] [FATAL] ";
        $logMessage .= "IP: $ip | ";
        $logMessage .= "Message: " . $error['message'] . " in " . $error['file'] . " (line " . $error['line'] . ")\n";
        $logMessage .= "---\n";
        
        @file_put_contents(__DIR__ . '/logs/php_errors.log', $logMessage, FILE_APPEND | LOCK_EX);
        
        // Em produção, exibir erro genérico
        if (!($GLOBALS['isDevelopment'] ?? false)) {
            header('Content-Type: text/html; charset=UTF-8');
            echo '<h1>Erro no Sistema</h1>';
            echo '<p>Ocorreu um erro crítico. Contate o administrador.</p>';
        }
    }
});

// Função auxiliar para log personalizado
function logCustom($level, $message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $logMessage = "[$timestamp] [$level] IP: $ip | Message: $message";
    
    if (!empty($context)) {
        $logMessage .= " | Context: " . json_encode($context);
    }
    
    $logMessage .= "\n";
    
    @file_put_contents(__DIR__ . '/logs/php_errors.log', $logMessage, FILE_APPEND | LOCK_EX);
}

?>
