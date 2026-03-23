#!/usr/bin/env php
<?php
/**
 * Script de Teste e Debug da Exportação
 * Verifica se os formatos PDF, CSV, XLSX estão funcionando
 */

echo "\n";
echo "========================================\n";
echo "🧪 Teste de Exportação - Sistema SiSGEH\n";
echo "========================================\n";
echo "\n";

// 1. Verificar se exportacao.php existe
echo "1️⃣  Verificando arquivo exportacao.php...\n";
if (file_exists('exportacao.php')) {
    $tamanho = filesize('exportacao.php');
    echo "   ✅ Encontrado ($tamanho bytes)\n";
} else {
    echo "   ❌ NÃO ENCONTRADO\n";
    exit(1);
}

// 2. Verificar sintaxe PHP
echo "\n2️⃣  Verificando sintaxe PHP...\n";
$output = shell_exec('php -l exportacao.php 2>&1');
if (strpos($output, 'No syntax errors') !== false) {
    echo "   ✅ Sem erros de sintaxe\n";
} else {
    echo "   ❌ Erro de sintaxe:\n";
    echo "   " . $output . "\n";
    exit(1);
}

// 3. Verificar se ZipArchive está disponível
echo "\n3️⃣  Verificando extensão ZipArchive...\n";
if (extension_loaded('zip')) {
    echo "   ✅ ZipArchive disponível\n";
} else {
    echo "   ⚠️  ZipArchive NÃO disponível (XLSX pode não funcionar)\n";
}

// 4. Verificar permissões /tmp
echo "\n4️⃣  Verificando permissões em /tmp...\n";
$tmpdir = sys_get_temp_dir();
if (is_writable($tmpdir)) {
    echo "   ✅ /tmp (ou equivalente) é gravável\n";
} else {
    echo "   ❌ /tmp (ou equivalente) NÃO é gravável\n";
}

// 5. Verificar se simulacao.php tem modal
echo "\n5️⃣  Verificando simulacao.php...\n";
if (file_exists('simulacao.php')) {
    $conteudo = file_get_contents('simulacao.php');
    if (strpos($conteudo, 'modalExportacao') !== false) {
        echo "   ✅ Modal de exportação presente\n";
    } else {
        echo "   ❌ Modal de exportação NÃO presente\n";
    }
} else {
    echo "   ❌ simulacao.php NÃO ENCONTRADO\n";
}

// 6. Verificar se historico.php tem modal
echo "\n6️⃣  Verificando historico.php...\n";
if (file_exists('historico.php')) {
    $conteudo = file_get_contents('historico.php');
    if (strpos($conteudo, 'modalExportacao') !== false) {
        echo "   ✅ Modal de exportação presente\n";
    } else {
        echo "   ❌ Modal de exportação NÃO presente\n";
    }
} else {
    echo "   ❌ historico.php NÃO ENCONTRADO\n";
}

// 7. Verificar FPDF
echo "\n7️⃣  Verificando biblioteca FPDF...\n";
if (file_exists('fpdf186/fpdf.php')) {
    echo "   ✅ FPDF encontrado\n";
} else {
    echo "   ❌ FPDF NÃO encontrado\n";
}

echo "\n";
echo "========================================\n";
echo "✅ Verificação Completa!\n";
echo "========================================\n";
echo "\n";

echo "📝 Para testar manualmente:\n";
echo "   1. Acesse: http://localhost/siteatual/simulacao.php\n";
echo "   2. Preencha e clique 'Simular'\n";
echo "   3. Clique 'Exportar' e escolha um formato\n";
echo "\n";

echo "Ou teste no histórico:\n";
echo "   1. Acesse: http://localhost/siteatual/historico.php\n";
echo "   2. Clique 'Exportar' em uma simulação\n";
echo "   3. Escolha um formato\n";
echo "\n";
?>
