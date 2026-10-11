CREATE DATABASE SistemaHidreletrico;

USE SistemaHidreletrico;

CREATE TABLE Usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nomeUsuario VARCHAR(100) NOT NULL,
    telefoneUsuario VARCHAR(15) NOT NULL,
    emailUsuario VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    emailConfirmado TINYINT(1) NOT NULL DEFAULT 0,
    confirmacaoToken VARCHAR(64),
    emailConfirmacaoExpiracao TIMESTAMP NULL,
    dados_historicos_iniciados TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE UsuarioConfiguracoes (
    id_config INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL UNIQUE,
    tema ENUM('claro', 'escuro') DEFAULT 'claro',
    notificacoes_email TINYINT(1) DEFAULT 1,
    notificacoes_sistema TINYINT(1) DEFAULT 1,
    notificacoes_simulacao TINYINT(1) DEFAULT 1,
    notificacoes_relatorios TINYINT(1) DEFAULT 1,

    FOREIGN KEY (id_usuario)
        REFERENCES Usuario(id_usuario)
);

CREATE TABLE RecuperacaoSenha (
    id_recuperacao INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    data_expiracao TIMESTAMP NOT NULL,
    usado TINYINT(1) DEFAULT 0,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_usuario)
        REFERENCES Usuario(id_usuario)
        ON DELETE CASCADE
);


-- =====================================================
-- SiSGEH - Modelo Físico (MySQL)
-- Parte 2: Simulação e resultados
-- =====================================================
CREATE TABLE Simulacoes (
    id_simulacao INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    vazao DOUBLE NOT NULL,
    altura DOUBLE NOT NULL,
    potTurbina DOUBLE NOT NULL,
    qtdTurbinas INT NOT NULL,
    potGerador DOUBLE NOT NULL,
    eficiencia DOUBLE,
    horas DOUBLE,
    data_simulacao DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_usuario)
        REFERENCES Usuario(id_usuario)
);

CREATE TABLE ResultadoSimulacao (
    id_resultado INT AUTO_INCREMENT PRIMARY KEY,
    id_simulacao INT NOT NULL UNIQUE,
    geracao_principal DOUBLE NOT NULL,
    geracao_diaria DOUBLE,
    geracao_mensal DOUBLE,
    geracao_anual DOUBLE,

    FOREIGN KEY (id_simulacao)
        REFERENCES Simulacoes(id_simulacao)
);


-- =====================================================
-- SiSGEH - Modelo Físico (MySQL)
-- Parte 3: Análise preditiva e dados meteorológicos
-- =====================================================

CREATE TABLE AnalisePreditiva (
    id_analise_preditiva INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_simulacao INT,
    periodo VARCHAR(50),
    data_calculo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pluviosidade_informada DECIMAL(10,2) NOT NULL,
    potencia_estimada DECIMAL(10,2) NOT NULL,
    modelo VARCHAR(100) NOT NULL DEFAULT 'Regressão Linear Simples',
    equacao VARCHAR(100),
    status ENUM('concluido', 'pendente', 'erro') NOT NULL DEFAULT 'concluido',

    FOREIGN KEY (id_usuario)
        REFERENCES Usuario(id_usuario)
        ON DELETE CASCADE,
    FOREIGN KEY (id_simulacao)
        REFERENCES Simulacoes(id_simulacao)
        ON DELETE CASCADE
);

CREATE TABLE DadosHistoricos (
    id_dado INT AUTO_INCREMENT PRIMARY KEY,
    data_registro DATE NOT NULL,
    pluviosidade_mm DECIMAL(10,2) NOT NULL,
    potencia_mw DECIMAL(10,2) NOT NULL,
    fonte VARCHAR(500),
    id_usuario INT,

    FOREIGN KEY (id_usuario)
        REFERENCES Usuario(id_usuario)
        ON DELETE CASCADE
);

CREATE TABLE DadosEstacao (
    id_dado INT AUTO_INCREMENT PRIMARY KEY,
    estacao VARCHAR(100) NOT NULL,
    mes_ano DATE NOT NULL,
    temp_min_media DECIMAL(5,2),
    temp_max_media DECIMAL(5,2) NOT NULL,
    umid_min_media DECIMAL(5,2) NOT NULL,
    umid_max_media DECIMAL(5,2) NOT NULL,
    precipitacao_mm DECIMAL(10,2) NOT NULL,
    fonte VARCHAR(500)
);
