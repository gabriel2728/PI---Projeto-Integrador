# SiSGEH - Sistema de Simulação de Geração de Energia Hidrelétrica

[![PHP](https://img.shields.io/badge/PHP-7.0+-blue.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Sistema web para simulação e cálculo de geração de energia elétrica em hidrelétricas, desenvolvido como Projeto Integrador do 3º semestre de Gestão da Tecnologia da Informação - Fatec Franco da Rocha.

## 📋 Sobre o Projeto

O SiSGEH (Sistema de Simulação de Geração de Energia Hidrelétrica) é uma aplicação web que permite aos usuários simular cenários de geração de energia elétrica em hidrelétricas. O sistema calcula a potência gerada com base em parâmetros físicos como vazão mássica, altura da queda d'água, potência das turbinas e outros fatores ambientais.

### 🎯 Funcionalidades Principais

- **Simulação de Cenários Hidrelétricos**: Cálculo preciso da geração de energia usando a equação P = ρ⋅g⋅h⋅Q⋅η
- **Histórico de Simulações**: Armazenamento e visualização de simulações anteriores
- **Exportação de Dados**: Suporte para exportação em múltiplos formatos (CSV, PDF, XLSX)
- **Interface Responsiva**: Compatível com desktop e dispositivos móveis
- **Sistema de Usuários**: Cadastro, login e gerenciamento de perfis
- **Configurações Personalizáveis**: Tema claro/escuro e preferências de notificação

## 🏗️ Arquitetura do Sistema

### Tecnologias Utilizadas

- **Backend**: PHP 7.0+ com MySQLi
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Banco de Dados**: MySQL 5.7+
- **Bibliotecas**:
  - FPDF (geração de PDFs)
  - Chart.js (gráficos interativos)
  - ZipArchive (geração de XLSX)

### Estrutura de Banco de Dados

```sql
-- Tabelas principais
Usuario (id, nome, email, senha, telefone)
Simulacoes (id, id_usuario, vazao, altura, potTurbina, qtdTurbinas, potGerador, eficiencia, horas, data_simulacao)
ResultadoSimulacao (id, id_simulacao, geracao_diaria, geracao_mensal, geracao_anual, geracao_principal)
```

## 🚀 Instalação e Configuração

### Pré-requisitos

- Servidor web (Apache/Nginx)
- PHP 7.0 ou superior
- MySQL 5.7 ou superior
- Extensões PHP: mysqli, zip, mbstring

### Passos de Instalação

1. **Clone o repositório:**
   ```bash
   git clone https://github.com/gabriel2728/PI---Projeto-Integrador.git
   cd PI---Projeto-Integrador
   ```

2. **Configure o banco de dados:**
   - Importe o arquivo `sistemaHidrico.sql` no MySQL
   - Atualize as credenciais em `conexao.php`

3. **Configure o servidor web:**
   - Copie os arquivos para o diretório raiz do servidor (ex: `htdocs/`)
   - Certifique-se de que as permissões estão corretas

4. **Acesse o sistema:**
   - Abra o navegador e acesse: `http://localhost/`
   - Faça o cadastro ou login

## 📖 Como Usar

### 1. Cadastro/Login
- Acesse a página inicial
- Clique em "Cadastrar" para criar uma conta
- Ou faça login com credenciais existentes

### 2. Realizar Simulação
- Na página "Simulação", preencha os parâmetros:
  - Vazão mássica (m³/s)
  - Altura da queda (m)
  - Potência da turbina (MW)
  - Quantidade de turbinas
  - Potência do gerador (MW)
  - Eficiência (%)
  - Horas de operação/dia

### 3. Visualizar Resultados
- Os resultados são calculados automaticamente
- Visualize geração diária, mensal e anual
- Exporte os dados nos formatos desejados

### 4. Histórico
- Acesse "Histórico" para ver simulações anteriores
- Clique em uma simulação para ver detalhes
- Exporte simulações salvas

### 5. Configurações
- Acesse "Configurações" para alterar dados pessoais
- Modifique nome, email, senha e telefone

## 📊 Funcionalidades Técnicas

### Cálculo de Energia
O sistema utiliza a equação fundamental da potência hidráulica:

```
P = ρ × g × h × Q × η
```

Onde:
- **P**: Potência (Watts)
- **ρ**: Densidade da água (1000 kg/m³)
- **g**: Aceleração da gravidade (9.81 m/s²)
- **h**: Altura da queda (metros)
- **Q**: Vazão mássica (m³/s)
- **η**: Eficiência do sistema (decimal)

### Exportação de Dados

#### CSV
- Formato: Separado por ponto e vírgula (;)
- Codificação: UTF-8 com BOM
- Compatível com Excel e LibreOffice

#### PDF
- Gerado com biblioteca FPDF
- Layout profissional com cabeçalhos
- Inclui data/hora da simulação

#### XLSX
- Formato Excel moderno (.xlsx)
- Gerado nativamente com ZipArchive
- Sem dependências externas

## 🧪 Testes

### Testes de Funcionalidade
- [x] Cadastro e login de usuários
- [x] Cálculo de simulações
- [x] Armazenamento no banco
- [x] Exportação CSV
- [x] Exportação PDF (em ajustes)
- [x] Exportação XLSX (em ajustes)
- [x] Interface responsiva

### Testes de Performance
- Tempo de resposta: < 2 segundos
- Cálculos simultâneos: Suportado
- Memória utilizada: Otimizada

## 📁 Estrutura do Projeto

```
c:\xampp\htdocs\siteatual\
├── 📄 index.html              # Página inicial
├── 📄 login.php               # Autenticação
├── 📄 cadastro.php            # Registro de usuários
├── 📄 inicio.php              # Dashboard principal
├── 📄 simulacao.php           # Página de simulação
├── 📄 historico.php           # Histórico de simulações
├── 📄 configuracoes.php       # Configurações do usuário
├── 📄 exportacao.php          # Sistema de exportação
├── 📄 conexao.php             # Conexão com banco
├── 📄 logout.php              # Logout do sistema
├── 📁 fpdf186/                # Biblioteca FPDF
├── 📁 fonts/                  # Fontes customizadas
├── 📁 estilo_*.css            # Arquivos de estilo
├── 📄 sistemaHidrico.sql      # Script do banco
└── 📄 README.md               # Este arquivo
```

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 🔐 Sistema de Recuperação de Senha Seguro

### Como Funciona

1. **Solicitação**: Usuário informa e-mail
2. **Token Seguro**: Sistema gera token único e temporário (64 caracteres hex)
3. **Link por E-mail**: Link seguro enviado (em produção) ou exibido (desenvolvimento)
4. **Redefinição**: Usuário clica no link e define nova senha
5. **Expiração**: Tokens expiram em 1 hora e são únicos por uso

### Segurança Implementada

- ✅ **Tokens únicos** gerados com `random_bytes(32)` (256 bits de entropia)
- ✅ **Expiração automática** (1 hora do momento da criação)
- ✅ **Uso único** (token invalidado após primeira utilização)
- ✅ **Verificação de propriedade** (só dono do e-mail pode usar o token)
- ✅ **Limpeza automática** de tokens expirados/usados
- ✅ **Proteção contra timing attacks** (mensagens genéricas)

### Arquivos Relacionados

- `recuperar_senha.php` - Página de solicitação de recuperação
- `redefinir_senha.php` - Página de redefinição via token
- `php/config_email.php` - Configurações de e-mail
- `php/limpar_tokens_expirados.php` - Script de limpeza
- `sistemaHidrico.sql` - Tabela `RecuperacaoSenha`

### Para Produção

1. Configure SMTP em `php/config_email.php`:
   ```php
   define('SMTP_HOST', 'smtp.gmail.com');
   define('SMTP_PORT', 587);
   define('SMTP_USER', 'seuemail@gmail.com');
   define('SMTP_PASS', 'senha_app_gmail');
   define('SITE_URL', 'https://seudominio.com');
   ```

2. Descomente código de envio por e-mail em `recuperar_senha.php`

3. Configure limpeza periódica (cron job):
   ```bash
   php limpar_tokens_expirados.php
   ```

### Desenvolvimento

Durante desenvolvimento, o link de redefinição aparece na tela para facilitar testes. Em produção, é enviado por e-mail seguro.

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

## 👥 Equipe

- **Gabriel** - Desenvolvimento Full-stack
- **Equipe Fatec Franco da Rocha** - Orientação e supervisão

## 📞 Contato

- **Instituição**: Fatec Franco da Rocha
- **Curso**: Gestão da Tecnologia da Informação
- **Período**: 3º Semestre
- **Projeto**: Projeto Integrador

## �️ Status Atual do Desenvolvimento

### ✅ O que já está implementado e funcionando
- Sistema de cadastro/login de usuário
- Simulação de geração de energia (cálculo hidráulico) e armazenamento em banco
- Visualização de histórico de simulações e exportação CSV
- Exportação PDF e XLSX (base implementada; precisar de ajustes estéticos)
- Perfis de conta (status do usuário, segurança de senha)
- Página de **Configurações** refatorada:
  - `configuracoes.php`: menu principal `Perfil` / `Sistema`
  - `configuracoes_perfil.php`: edição de nome/email/senha + exibição de dados (nomeUsuario, emailUsuario, telefoneUsuario)
  - `configuracoes_sistema.php`: tema (`claro`/`escuro`) e notificações (email/sistema/simulação/relatórios)
- Tabela nova no banco `UsuarioConfiguracoes` com campos de tema e notificações
- CSS consolidado em `estilo_configuracao.css`
- Remoção de scripts expostos no HTML (fix de não renderizar JS como texto)

### 🔧 O que falta / próximo passo
- Ajustar layout visual de dados do usuário em `configuracoes_perfil.php` (dentro da caixa de configuração)
- Finalizar exportação PDF e XLSX com formatação completa
- Adicionar modelos de sessão persistente + tratamento de idioma (UTF-8 completo)
- Revisar e aplicar testes automatizados (PHPUnit) para acesso ao DB e rotas
- Melhorar tratamento de erros no frontend (alertas mais amigáveis/Toast UI)

### 📍 Onde paramos (ponto atual)
- Ambiente funcional com simulação e gerenciamento de conta
- Refatoração e divisão das telas de configuração concluída
- Pendência essencial: UX final para dados pessoais na página de perfil

## �🙏 Agradecimentos

- Professores da Fatec Franco da Rocha
- Comunidade de desenvolvimento PHP
- Bibliotecas open source utilizadas

---

**Status do Projeto**: Em desenvolvimento ativo para apresentação na banca do 3º semestre.

