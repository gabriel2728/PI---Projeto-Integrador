<?php
require_once __DIR__ . '/php/conexao.php';

$sqlFile = __DIR__ . '/migracao_analise_preditiva.sql';
if (!file_exists($sqlFile)) {
    die("Arquivo de migração não encontrado: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    die("Falha ao ler o arquivo de migração.\n");
}

$statements = array_filter(array_map('trim', explode(';', $sql)));
foreach ($statements as $statement) {
    if ($statement === '') {
        continue;
    }
    if (!$conn->query($statement)) {
        die("Erro na execução da migração: " . $conn->error . "\nStatement: " . $statement . "\n");
}
}

echo "Migração aplicada com sucesso!\n";
$conn->close();
?>