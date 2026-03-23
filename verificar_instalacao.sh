#!/usr/bin/env bash
# Script de verificação do sistema de exportação

echo "================================"
echo "🔍 Verificação do Sistema SiSGEH"
echo "================================"
echo ""

# Diretório do projeto
cd "c:\xampp\htdocs\siteatual" || exit 1

echo "✅ Arquivos Criados:"
echo ""

# Verifica exportacao.php
if [ -f "exportacao.php" ]; then
    SIZE=$(stat -c%s "exportacao.php" 2>/dev/null || stat -f%z "exportacao.php" 2>/dev/null || echo "?")
    echo "  📄 exportacao.php ($(SIZE) bytes)"
else
    echo "  ❌ exportacao.php - NÃO ENCONTRADO"
fi

# Verifica simulacao.php atualizado
if [ -f "simulacao.php" ]; then
    if grep -q "modalExportacao" "simulacao.php"; then
        echo "  ✨ simulacao.php (com modal de exportação)"
    else
        echo "  ⚠️  simulacao.php - sem modal"
    fi
else
    echo "  ❌ simulacao.php - NÃO ENCONTRADO"
fi

# Verifica historico.php atualizado
if [ -f "historico.php" ]; then
    if grep -q "modalExportacao" "historico.php"; then
        echo "  ✨ historico.php (com modal de exportação)"
    else
        echo "  ⚠️  historico.php - sem modal"
    fi
else
    echo "  ❌ historico.php - NÃO ENCONTRADO"
fi

# Verifica teste
if [ -f "teste_exportacao.html" ]; then
    echo "  🧪 teste_exportacao.html"
else
    echo "  ❌ teste_exportacao.html - NÃO ENCONTRADO"
fi

# Verifica documentação
if [ -f "RESUMO_IMPLEMENTACAO.md" ]; then
    echo "  📋 RESUMO_IMPLEMENTACAO.md"
else
    echo "  ❌ RESUMO_IMPLEMENTACAO.md - NÃO ENCONTRADO"
fi

echo ""
echo "✅ Verificação de Sintaxe PHP:"
echo ""

# Verifica sintaxe
if php -l exportacao.php > /dev/null 2>&1; then
    echo "  ✓ exportacao.php - Sintaxe OK"
else
    echo "  ✗ exportacao.php - Erro de sintaxe"
fi

if php -l simulacao.php > /dev/null 2>&1; then
    echo "  ✓ simulacao.php - Sintaxe OK"
else
    echo "  ✗ simulacao.php - Erro de sintaxe"
fi

if php -l historico.php > /dev/null 2>&1; then
    echo "  ✓ historico.php - Sintaxe OK"
else
    echo "  ✗ historico.php - Erro de sintaxe"
fi

echo ""
echo "✅ Requisitos:"
echo ""
echo "  📦 Extensão ZIP: "
if php -m | grep -i zip > /dev/null; then
    echo "    ✓ Habilitada"
else
    echo "    ⚠️  Não encontrada (necessária para XLSX)"
fi

echo ""
echo "================================"
echo "🎉 Verificação Concluída!"
echo "================================"
echo ""
echo "Para testar o sistema:"
echo "1. Acesse: http://localhost/siteatual/teste_exportacao.html"
echo "2. Click nos botões de teste"
echo ""
echo "Para usar:"
echo "1. Acesse: http://localhost/siteatual/simulacao.php (quando logado)"
echo "2. Preencha a simulação"
echo "3. Clique 'Exportar' e escolha o formato"
echo ""
