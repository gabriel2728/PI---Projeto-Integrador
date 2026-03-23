# Guia de Exportação de Simulações

## 📊 Novos Formatos Disponíveis

Seu sistema agora suporta exportação em **3 formatos**:

### 1. **PDF** 📄
- Gera um relatório profissional em PDF
- Contém todos os parâmetros da simulação e resultados
- Biblioteca: FPDF (já instalada)

### 2. **CSV** 📊  
- Formato de arquivo de dados estruturado
- Compatível com Excel, Google Sheets e outras ferramentas
- Utiliza `;` como separador (padrão brasileiro)
- Inclui BOM UTF-8 para caracteres especiais

### 3. **XLSX** 📈
- Planilha Excel moderna (.xlsx)
- Gerado usando ZipArchive (sem dependências externas)
- Estrutura XML válida segundo padrões ECMA-376

---

## 🚀 Como Usar

### Na Página de Simulação (`simulacao.php`)

1. Preencha os parâmetros de simulação
2. Clique em **"Simular"** para ver os resultados
3. Clique em **"Exportar"** para abrir o modal
4. Escolha o formato desejado (PDF, CSV ou XLSX)
5. O arquivo será baixado automaticamente

**Nota:** Você pode exportar mesmo sem salvar a simulação no histórico.

### No Histórico (`historico.php`)

1. Clique em **"Exportar"** ao lado da simulação
2. Escolha o formato desejado (PDF, CSV ou XLSX)
3. A simulação salva será exportada com os resultados calculados

---

## 📁 Arquivos Modificados/Criados

- ✅ **exportacao.php** - Novo arquivo principal de exportação
- ✅ **simulacao.php** - Atualizado com modal e botões de exportação
- ✅ **historico.php** - Atualizado com modal e botões de exportação

---

## 🔧 Estrutura da Exportação

### Todos os formatos contêm:

1. **Dados de Entrada:**
   - Vazão Mássica (m³/s)
   - Altura da Queda (m)
   - Potência da Turbina (MW)
   - Quantidade de Turbinas
   - Potência do Gerador (MW)
   - Eficiência do Sistema (%)
   - Horas de Operação/dia

2. **Resultados:**
   - Geração Total (MW)
   - Geração Diária (MWh/dia) - se horas informadas
   - Geração Mensal (MWh/mês) - se horas informadas
   - Geração Anual (MWh/ano) - se horas informadas

---

## 💡 Dicas

- **CSV**: Ideal para importar dados em ferramentas de análise
- **XLSX**: Recomendado para compartilhar com Excel
- **PDF**: Melhor para imprimir e arquivar

---

## ℹ️ Informações Técnicas

### Simulações Novas (não salvas):
- Arquivo gerado com timestamp: `Simulacao_YYYY-MM-DD_HH-MM-SS.{ext}`
- Contém os dados com os cálculos realizados

### Simulações Salvas (do histórico):
- Arquivo gerado com ID da simulação: `Simulacao_ID_YYYY-MM-DD_HH-MM-SS.{ext}`
- Recupera dados do banco de dados

---

**Versão:** 1.0  
**Data de Criação:** 2024  
**Suporte:** Todos os navegadores modernos
