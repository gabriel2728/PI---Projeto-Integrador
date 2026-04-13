<?php
include 'conexao.php';

// Criar tabela de recuperação de senha se não existir
$sql = "CREATE TABLE IF NOT EXISTS RecuperacaoSenha (
    id_recuperacao INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    data_expiracao TIMESTAMP NOT NULL,
    usado BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_usuario_expiracao (id_usuario, data_expiracao)
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Tabela RecuperacaoSenha criada com sucesso!\n";
} else {
    echo "❌ Erro ao criar tabela: " . $conn->error . "\n";
}

$conn->close();
?>