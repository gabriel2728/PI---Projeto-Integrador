<?php
// Configurações de e-mail para recuperação de senha
// IMPORTANTE: Configure estas constantes para produção

/*
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // 'tls' ou 'ssl'
define('SMTP_USER', 'seuemail@gmail.com');
define('SMTP_PASS', 'suasenha_app'); // Use senha de app do Gmail
define('SMTP_FROM', 'seuemail@gmail.com');
define('SMTP_FROM_NAME', 'SiSGEH');
define('SITE_URL', 'https://seudominio.com');
*/

// Função para enviar e-mail de recuperação (use PHPMailer em produção)
function enviarEmailRecuperacao($destinatario, $nomeUsuario, $token) {
    $linkRedefinicao = SITE_URL . "/redefinir_senha.php?token=" . $token;

    $assunto = 'Redefinição de senha - SiSGEH';
    $mensagem = "Olá {$nomeUsuario},\n\n";
    $mensagem .= "Recebemos uma solicitação para redefinir sua senha.\n\n";
    $mensagem .= "Clique no link abaixo para criar uma nova senha:\n";
    $mensagem .= "{$linkRedefinicao}\n\n";
    $mensagem .= "Este link é válido por 1 hora e pode ser usado apenas uma vez.\n";
    $mensagem .= "Caso você não tenha solicitado esta redefinição, ignore este e-mail.\n\n";
    $mensagem .= "Atenciosamente,\nEquipe SiSGEH\n";

    $headers = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM . '>' . "\r\n";
    $headers .= 'Reply-To: ' . SMTP_FROM . "\r\n";
    $headers .= 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";

    return mail($destinatario, $assunto, $mensagem, $headers);
}
?>