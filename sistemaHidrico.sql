CREATE DATABASE SistemaHidreletrico;

USE SistemaHidreletrico;

CREATE TABLE Usuario ( 
    id_usuario INT AUTO_INCREMENT PRIMARY KEY, 
    nomeUsuario VARCHAR(100) NOT NULL,
    telefoneUsuario VARCHAR(15) NOT NULL,
    emailUsuario VARCHAR(100) NOT NULL UNIQUE, 
    senha VARCHAR(255) NOT NULL
);
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
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
);

CREATE TABLE ResultadoSimulacao (
    id_resultado INT AUTO_INCREMENT PRIMARY KEY,
    id_simulacao INT NOT NULL,
    geracao_diaria DOUBLE,
    geracao_mensal DOUBLE,
    geracao_anual DOUBLE,
    FOREIGN KEY (id_simulacao) REFERENCES Simulacoes(id_simulacao)
);
ALTER TABLE ResultadoSimulacao ADD COLUMN geracao_principal DOUBLE NOT NULL AFTER id_simulacao;

select * from Usuario;
select * from ResultadoSimulacao;
select * from Simulacoes;
DESCRIBE Simulacoes;
insert into Simulacoes(id_usuario, vazao, altura, potTurbina, qtdTurbinas, potGerador, data_simulacao)
values ('1', '42', '8', '20', '3', '50', '2025-10-20');

-- Inserindo Usuários
INSERT INTO Usuario (nomeUsuario, telefoneUsuario, emailUsuario, senha) VALUES
('Mariana Silva', '11999990001', 'mariana.silva@example.com', '123456789'),
('João Pereira',   '11999990002', 'joao.pereira@example.com',   '123456789'),
('Ana Costa',      '11999990003', 'ana.costa@example.com',      '123456789');

-- Inserindo Simulações
INSERT INTO Simulacoes (id_usuario, vazao, altura, potTurbina, qtdTurbinas, potGerador, eficiencia, horas, data_simulacao) VALUES
-- Simulação 1 (usuário 6) — vazão alta, queda moderada, 4 turbinas, eficiência 90%, 6 horas/dia
(6, 50.00, 20.00, 5.00, 4, 10.00, 0.90, 6, NOW()),
-- Simulação 2 (usuário 7) — vazão média, queda alta, 2 turbinas, eficiência 85%, 6 horas/dia
(7, 30.00, 35.00, 8.00, 2, 12.00, 0.85, 6, NOW()),
-- Simulação 3 (usuário 8) — vazão baixa, pequena queda, 1 turbina, eficiência 100%, 6 horas/dia
(8, 10.00, 10.00, 2.50, 1, 3.00, 1.00, 6, NOW());

-- Inserindo resultados para as simulações dos usuários 6, 7 e 8
INSERT INTO ResultadoSimulacao (id_simulacao, geracao_principal, geracao_diaria, geracao_mensal, geracao_anual) VALUES
-- Resultado para simulação 21 (usuário 6)
(21, 120.50, 723.00, 21690.00, 260280.00),
-- Resultado para simulação 22 (usuário 7)
(22, 75.20, 451.20, 13536.00, 162432.00),
-- Resultado para simulação 23 (usuário 8)
(23, 30.10, 180.60, 5418.00, 65016.00);


-- Funções de Agregação

-- 1️ Potência média das turbinas cadastradas
-- Mostra a média de potência por turbina em todas as simulações
SELECT AVG(potTurbina) AS media_potencia_turbina
FROM Simulacoes;

-- 2️ Soma total da geração anual de todas as simulações
-- Total de energia gerada no ano por todas as simulações registradas
SELECT SUM(geracao_anual) AS total_geracao_anual
FROM ResultadoSimulacao;

-- 3️ Maior vazão registrada em uma simulação
-- Mostra a maior vazão registrada em todas as simulações
SELECT MAX(vazao) AS maior_vazao
FROM Simulacoes;

-- 4️ Menor geração diária registrada
-- Identifica a menor quantidade de energia gerada por dia
SELECT MIN(geracao_diaria) AS menor_geracao_diaria
FROM ResultadoSimulacao;

-- 5️ Contagem de simulações por usuário
-- Mostra quantas simulações cada usuário realizou
SELECT id_usuario, COUNT(*) AS total_simulacoes
FROM Simulacoes
GROUP BY id_usuario;

-- 6️ Soma total da geração anual por usuário
-- Geração anual acumulada separada por usuário
SELECT s.id_usuario, SUM(r.geracao_anual) AS geracao_anual_usuario
FROM Simulacoes s
JOIN ResultadoSimulacao r ON s.id_simulacao = r.id_simulacao
GROUP BY s.id_usuario;

-- 7️ Potência média total das turbinas por usuário
-- Mostra a média de potência das turbinas para cada usuário
SELECT id_usuario, AVG(potTurbina) AS media_potencia_usuario
FROM Simulacoes
GROUP BY id_usuario;

-- 4. Junções (JOINS) — Consultas de Usuário

-- Junções (JOINS)
-- 1️ Exibir nome do usuário e dados das suas simulações
SELECT u.nomeUsuario, s.id_simulacao, s.vazao, s.altura, s.potTurbina,
 s.qtdTurbinas, s.potGerador, s.eficiencia, s.horas, s.data_simulacao
FROM Usuario u
JOIN Simulacoes s ON u.id_usuario = s.id_usuario;

-- 2️ Exibir nome do usuário e resultados de geração de energia de suas simulações
SELECT u.nomeUsuario, s.id_simulacao, r.geracao_principal, r.geracao_diaria,
 r.geracao_mensal, r.geracao_anual
FROM Usuario u
JOIN Simulacoes s ON u.id_usuario = s.id_usuario
JOIN ResultadoSimulacao r ON s.id_simulacao = r.id_simulacao;

-- 3️ Exibir nome do usuário e a soma da geração anual de todas as suas simulações
SELECT u.nomeUsuario, SUM(r.geracao_anual) AS total_geracao_anual
FROM Usuario u
JOIN Simulacoes s ON u.id_usuario = s.id_usuario
JOIN ResultadoSimulacao r ON s.id_simulacao = r.id_simulacao
GROUP BY u.nomeUsuario;

-- 4️ Exibir nome do usuário e a média da potência das turbinas de suas simulações
SELECT u.nomeUsuario, AVG(s.potTurbina) AS media_potencia_turbina
FROM Usuario u
JOIN Simulacoes s ON u.id_usuario = s.id_usuario
GROUP BY u.nomeUsuario;


SELECT id_simulacao, id_usuario 
FROM Simulacoes
WHERE id_simulacao IN (21, 22, 23);
-- UPDATE REALIZADO
UPDATE Simulacoes SET id_usuario = 6 WHERE id_simulacao = 21;
UPDATE Simulacoes SET id_usuario = 7 WHERE id_simulacao = 22;
UPDATE Simulacoes SET id_usuario = 8 WHERE id_simulacao = 23;

select * from Usuario;
select * from ResultadoSimulacao;
select * from Simulacoes;

-- teste join 06/11/25
select 
u.id_usuario,
u.nomeUsuario,
s.id_simulacao as ID_Simulação
from Usuario as u
join Simulacoes as si
on s.id_simulacao = si.id_simulacao;
-- teste joins
select 
Usuario.id_usuario, Usuario.nomeUsuario, Simulacoes.id_simulacao,
 Simulacoes.vazao,
 Simulacoes.altura
from Usuario
left join Simulacoes
on Usuario.id_usuario = Simulacoes.id_usuario;
-- teste 2 joins alter 

CREATE TABLE UsuarioConfiguracoes (
    id_config INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    tema ENUM('claro', 'escuro') DEFAULT 'claro',
    notificacoes_email BOOLEAN DEFAULT true,
    notificacoes_sistema BOOLEAN DEFAULT true,
    notificacoes_simulacao BOOLEAN DEFAULT true,
    notificacoes_relatorios BOOLEAN DEFAULT true,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
);
select * from UsuarioConfiguracoes;

-- Tabela para tokens de recuperação de senha (segurança aprimorada)
CREATE TABLE RecuperacaoSenha (
    id_recuperacao INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    data_expiracao TIMESTAMP NOT NULL,
    usado BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_usuario_expiracao (id_usuario, data_expiracao)
);