<?php
// Configurações de e-mail para recuperação de senha
// IMPORTANTE: Configure estas constantes para produção

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // 'tls' ou 'ssl'
define('SMTP_USER', 'sistemasisgeh@gmail.com');
define('SMTP_PASS', 'qgrj mnrm lgyq byuo'); // Use senha de app do Gmail
define('SMTP_FROM', 'sistemasisgeh@gmail.com');
define('SMTP_FROM_NAME', 'SiSGEH');
define('SITE_URL', 'http://localhost/siteatual'); // Ajuste para produção

// Função para enviar e-mail de recuperação usando SMTP direto
function enviarEmailRecuperacao($destinatario, $nomeUsuario, $token) {
    $linkRedefinicao = SITE_URL . "/redefinir_senha.php?token=" . $token;
    $assunto = 'Redefinição de senha - SiSGEH';
    $mensagem = "Olá {$nomeUsuario},\r\n\r\n";
    $mensagem .= "Recebemos uma solicitação para redefinir sua senha.\r\n\r\n";
    $mensagem .= "Clique no link abaixo para criar uma nova senha:\r\n";
    $mensagem .= "{$linkRedefinicao}\r\n\r\n";
    $mensagem .= "Este link é válido por 1 hora e pode ser usado apenas uma vez.\r\n";
    $mensagem .= "Caso você não tenha solicitado esta redefinição, ignore este e-mail.\r\n\r\n";
    $mensagem .= "Atenciosamente,\r\nEquipe SiSGEH\r\n";

    return enviarEmailSMTP(
        $destinatario,
        $assunto,
        $mensagem,
        SMTP_FROM,
        SMTP_FROM_NAME
    );
}

function enviarEmailSMTP($para, $assunto, $mensagem, $remetente, $remetenteNome) {
    $smtpHost = SMTP_HOST;
    $smtpPort = SMTP_PORT;
    $timeout = 30;
    $hostname = gethostname() ?: 'localhost';

    $socket = stream_socket_client("tcp://{$smtpHost}:{$smtpPort}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        return false;
    }

    stream_set_timeout($socket, $timeout);
    if (!smtpExpect($socket, '220')) {
        fclose($socket);
        return false;
    }

    fwrite($socket, "EHLO {$hostname}\r\n");
    if (!smtpExpect($socket, '250')) {
        fclose($socket);
        return false;
    }

    fwrite($socket, "STARTTLS\r\n");
    if (!smtpExpect($socket, '220')) {
        fclose($socket);
        return false;
    }

    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        fclose($socket);
        return false;
    }

    fwrite($socket, "EHLO {$hostname}\r\n");
    if (!smtpExpect($socket, '250')) {
        fclose($socket);
        return false;
    }

    fwrite($socket, "AUTH LOGIN\r\n");
    if (!smtpExpect($socket, '334')) {
        fclose($socket);
        return false;
    }

    fwrite($socket, base64_encode(SMTP_USER) . "\r\n");
    if (!smtpExpect($socket, '334')) {
        fclose($socket);
        return false;
    }

    fwrite($socket, base64_encode(SMTP_PASS) . "\r\n");
    if (!smtpExpect($socket, '235')) {
        fclose($socket);
        return false;
    }

    fwrite($socket, "MAIL FROM:<{$remetente}>\r\n");
    if (!smtpExpect($socket, '250')) {
        fclose($socket);
        return false;
    }

    fwrite($socket, "RCPT TO:<{$para}>\r\n");
    if (!smtpExpect($socket, '250')) {
        fclose($socket);
        return false;
    }

    fwrite($socket, "DATA\r\n");
    if (!smtpExpect($socket, '354')) {
        fclose($socket);
        return false;
    }

    $headers = "From: {$remetenteNome} <{$remetente}>\r\n";
    $headers .= "To: {$para}\r\n";
    $headers .= "Subject: {$assunto}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n\r\n";

    fwrite($socket, $headers . $mensagem . "\r\n.\r\n");
    if (!smtpExpect($socket, '250')) {
        fclose($socket);
        return false;
    }

    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    return true;
}

function smtpExpect($socket, $expectedCode) {
    while ($line = fgets($socket, 515)) {
        if (substr($line, 3, 1) === ' ') {
            return strpos($line, $expectedCode) === 0;
        }
    }
    return false;
}
?> 