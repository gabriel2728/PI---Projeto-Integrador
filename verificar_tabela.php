<?php
include 'conexao.php';

$result = mysqli_query($conn, 'SHOW TABLES LIKE "UsuarioConfiguracoes"');
if (mysqli_num_rows($result) > 0) {
    echo '✅ Tabela UsuarioConfiguracoes encontrada no banco de dados!' . PHP_EOL;

    // Mostrar estrutura da tabela
    $result = mysqli_query($conn, 'DESCRIBE UsuarioConfiguracoes');
    echo '📋 Estrutura da tabela:' . PHP_EOL;
    while ($row = mysqli_fetch_assoc($result)) {
        echo '- ' . $row['Field'] . ': ' . $row['Type'] . PHP_EOL;
    }
} else {
    echo '❌ Tabela não encontrada.' . PHP_EOL;
}
?>