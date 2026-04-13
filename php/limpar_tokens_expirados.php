<?php
include 'conexao.php';

// Limpar tokens expirados ou usados há mais de 24 horas
$sql = "DELETE FROM RecuperacaoSenha
        WHERE data_expiracao < NOW()
        OR (usado = TRUE AND data_criacao < DATE_SUB(NOW(), INTERVAL 24 HOUR))";

if ($conn->query($sql) === TRUE) {
    echo "✅ Limpeza de tokens realizada com sucesso!\n";
    echo "Tokens removidos: " . $conn->affected_rows . "\n";
} else {
    echo "❌ Erro na limpeza: " . $conn->error . "\n";
}

$conn->close();
?>