<?php
// Carregar variáveis de ambiente do arquivo .env
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        die("Erro: arquivo .env não encontrado em " . $filePath);
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar comentários
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse a variável
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remover aspas se existirem
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            $_ENV[$key] = $value;
        }
    }
}

// Carregar arquivo .env da raiz do projeto
$envPath = dirname(dirname(__FILE__)) . '/.env';
loadEnv($envPath);

// Obter credenciais das variáveis de ambiente
$servidor = $_ENV['DB_HOST'] ?? 'localhost';
$usuario = $_ENV['DB_USER'] ?? 'root';
$senha = $_ENV['DB_PASS'] ?? '';
$banco = $_ENV['DB_NAME'] ?? 'SistemaHidreletrico';

$conn = new mysqli($servidor, $usuario, $senha, $banco);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Definir charset UTF-8
$conn->set_charset("utf8mb4");
?>
