/* Modelo Físico - SiSGEH (MySQL) */

CREATE TABLE usuario (
    id_usuario int NOT NULL AUTO_INCREMENT,
    nomeUsuario varchar(100) NOT NULL,
    telefoneUsuario varchar(15) NOT NULL,
    emailUsuario varchar(100) NOT NULL,
    senha varchar(255) NOT NULL,
    emailConfirmado tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 = não confirmado, 1 = confirmado',
    confirmacaoToken varchar(64) DEFAULT NULL COMMENT 'Token único para confirmação de email',
    emailConfirmacaoExpiracao timestamp NULL DEFAULT NULL COMMENT 'Data de expiração do token de confirmação',
    dados_historicos_iniciados tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (id_usuario),
    UNIQUE KEY emailUsuario (emailUsuario)
);

CREATE TABLE usuarioconfiguracoes (
    id_config int NOT NULL AUTO_INCREMENT,
    id_usuario int NOT NULL,
    tema enum('claro','escuro') DEFAULT 'claro',
    notificacoes_email tinyint(1) DEFAULT '1',
    notificacoes_sistema tinyint(1) DEFAULT '1',
    notificacoes_simulacao tinyint(1) DEFAULT '1',
    notificacoes_relatorios tinyint(1) DEFAULT '1',
    PRIMARY KEY (id_config),
    UNIQUE KEY uk_config_usuario (id_usuario)
);

CREATE TABLE recuperacaosenha (
    id_recuperacao int NOT NULL AUTO_INCREMENT,
    id_usuario int NOT NULL,
    token varchar(255) NOT NULL,
    data_expiracao timestamp NOT NULL,
    usado tinyint(1) DEFAULT '0',
    data_criacao timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_recuperacao),
    UNIQUE KEY token (token)
);

CREATE TABLE simulacoes (
    id_simulacao int NOT NULL AUTO_INCREMENT,
    id_usuario int NOT NULL,
    vazao double NOT NULL,
    altura double NOT NULL,
    potTurbina double NOT NULL,
    qtdTurbinas int NOT NULL,
    potGerador double NOT NULL,
    eficiencia double DEFAULT NULL,
    horas double DEFAULT NULL,
    data_simulacao datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_simulacao)
);

CREATE TABLE resultadosimulacao (
    id_resultado int NOT NULL AUTO_INCREMENT,
    id_simulacao int NOT NULL,
    geracao_principal double NOT NULL,
    geracao_diaria double DEFAULT NULL,
    geracao_mensal double DEFAULT NULL,
    geracao_anual double DEFAULT NULL,
    PRIMARY KEY (id_resultado),
    UNIQUE KEY uk_resultado_simulacao (id_simulacao)
);

CREATE TABLE analisepreditiva (
    id_analise_preditiva int NOT NULL AUTO_INCREMENT,
    id_usuario int NOT NULL,
    id_simulacao int DEFAULT NULL,
    periodo varchar(50) DEFAULT NULL,
    data_calculo datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pluviosidade_informada decimal(10,2) NOT NULL,
    potencia_estimada decimal(10,2) NOT NULL,
    modelo varchar(100) NOT NULL DEFAULT 'Regressão Linear Simples',
    equacao varchar(100) DEFAULT NULL,
    status enum('concluido','pendente','erro') NOT NULL DEFAULT 'concluido',
    PRIMARY KEY (id_analise_preditiva)
);

CREATE TABLE dadoshistoricos (
    id_dado int NOT NULL AUTO_INCREMENT,
    data_registro date NOT NULL,
    pluviosidade_mm decimal(10,2) NOT NULL,
    potencia_mw decimal(10,2) NOT NULL,
    fonte varchar(500) DEFAULT NULL,
    id_usuario int DEFAULT NULL,
    PRIMARY KEY (id_dado)
);

CREATE TABLE dadosestacao (
    id_dado int NOT NULL AUTO_INCREMENT,
    estacao varchar(100) NOT NULL,
    mes_ano date NOT NULL,
    temp_min_media decimal(5,2) DEFAULT NULL,
    temp_max_media decimal(5,2) NOT NULL,
    umid_min_media decimal(5,2) NOT NULL,
    umid_max_media decimal(5,2) NOT NULL,
    precipitacao_mm decimal(10,2) NOT NULL,
    fonte varchar(500) DEFAULT NULL,
    PRIMARY KEY (id_dado)
);

ALTER TABLE usuarioconfiguracoes ADD CONSTRAINT usuarioconfiguracoes_ibfk_1
    FOREIGN KEY (id_usuario)
    REFERENCES usuario (id_usuario)
    ON DELETE NO ACTION;

ALTER TABLE recuperacaosenha ADD CONSTRAINT recuperacaosenha_ibfk_1
    FOREIGN KEY (id_usuario)
    REFERENCES usuario (id_usuario)
    ON DELETE CASCADE;

ALTER TABLE simulacoes ADD CONSTRAINT simulacoes_ibfk_1
    FOREIGN KEY (id_usuario)
    REFERENCES usuario (id_usuario)
    ON DELETE NO ACTION;

ALTER TABLE resultadosimulacao ADD CONSTRAINT resultadosimulacao_ibfk_1
    FOREIGN KEY (id_simulacao)
    REFERENCES simulacoes (id_simulacao)
    ON DELETE NO ACTION;

ALTER TABLE analisepreditiva ADD CONSTRAINT analisepreditiva_ibfk_1
    FOREIGN KEY (id_usuario)
    REFERENCES usuario (id_usuario)
    ON DELETE CASCADE;

ALTER TABLE analisepreditiva ADD CONSTRAINT fk_analise_simulacao
    FOREIGN KEY (id_simulacao)
    REFERENCES simulacoes (id_simulacao)
    ON DELETE CASCADE;

ALTER TABLE dadoshistoricos ADD CONSTRAINT fk_dados_historicos_usuario
    FOREIGN KEY (id_usuario)
    REFERENCES usuario (id_usuario)
    ON DELETE CASCADE;

