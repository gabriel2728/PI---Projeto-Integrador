<?php
// Script de teste HTTPS
session_start();

// Verifica se está usando HTTPS
$protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'HTTPS' : 'HTTP';
$host = $_SERVER['HTTP_HOST'];
$uri = $_SERVER['REQUEST_URI'];

echo "<!DOCTYPE html>
<html lang='pt-br'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Teste HTTPS - SiSGEH</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .status { padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    </style>
</head>
<body>
    <h1>🔒 Teste de Configuração HTTPS</h1>

    <div class='status " . ($protocolo === 'HTTPS' ? 'success' : 'warning') . "'>
        <h3>Protocolo Atual: $protocolo</h3>
        <p><strong>URL:</strong> $protocolo://$host$uri</p>
        " . ($protocolo === 'HTTPS' ? '<p>✅ HTTPS está funcionando!</p>' : '<p>⚠️ Ainda está usando HTTP. Certificado SSL pode não estar configurado.</p>') . "
    </div>

    <div class='status info'>
        <h3>Informações do Servidor</h3>
        <p><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>
        <p><strong>SSL/TLS:</strong> " . (isset($_SERVER['SSL_PROTOCOL']) ? $_SERVER['SSL_PROTOCOL'] : 'Não disponível') . "</p>
        <p><strong>Cipher:</strong> " . (isset($_SERVER['SSL_CIPHER']) ? $_SERVER['SSL_CIPHER'] : 'Não disponível') . "</p>
    </div>

    <div class='status info'>
        <h3>Headers de Segurança</h3>
        <p><strong>X-Frame-Options:</strong> " . (isset($_SERVER['HTTP_X_FRAME_OPTIONS']) ? $_SERVER['HTTP_X_FRAME_OPTIONS'] : 'Não definido') . "</p>
        <p><strong>X-Content-Type-Options:</strong> " . (isset($_SERVER['HTTP_X_CONTENT_TYPE_OPTIONS']) ? $_SERVER['HTTP_X_CONTENT_TYPE_OPTIONS'] : 'Não definido') . "</p>
        <p><strong>X-XSS-Protection:</strong> " . (isset($_SERVER['HTTP_X_XSS_PROTECTION']) ? $_SERVER['HTTP_X_XSS_PROTECTION'] : 'Não definido') . "</p>
        <p><strong>Strict-Transport-Security:</strong> " . (isset($_SERVER['HTTP_STRICT_TRANSPORT_SECURITY']) ? $_SERVER['HTTP_STRICT_TRANSPORT_SECURITY'] : 'Não definido') . "</p>
    </div>

    <h3>Testes de Funcionalidade</h3>
    <ul>
        <li><a href='index.html'>🏠 Página Inicial</a></li>
        <li><a href='php/login.php'>🔐 Login</a></li>
        <li><a href='php/configuracoes_perfil.php'>⚙️ Configurações</a></li>
    </ul>

    <p><small>Para testar HTTPS, acesse: <code>https://localhost/siteatual/teste_https.php</code></small></p>
</body>
</html>";
?>