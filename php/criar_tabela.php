<?php
// Script para criar a tabela UsuarioConfiguracoes
include('conexao.php');

$sql = "CREATE TABLE IF NOT EXISTS UsuarioConfiguracoes (
    id_config INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    tema ENUM('claro', 'escuro') DEFAULT 'claro',
    notificacoes_email BOOLEAN DEFAULT true,
    notificacoes_sistema BOOLEAN DEFAULT true,
    notificacoes_simulacao BOOLEAN DEFAULT true,
    notificacoes_relatorios BOOLEAN DEFAULT true,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
)";

if (mysqli_query($conn, $sql)) {
    echo "✅ Tabela UsuarioConfiguracoes criada com sucesso!\n";

    // Verificar estrutura da tabela
    $result = mysqli_query($conn, "DESCRIBE UsuarioConfiguracoes");
    echo "\n📋 Estrutura da tabela:\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
} else {
    echo "❌ Erro ao criar tabela: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
?>