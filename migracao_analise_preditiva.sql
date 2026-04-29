-- Migração: criar tabela de dados históricos para análise preditiva

CREATE TABLE IF NOT EXISTS DadosHistoricos (
    id_dado INT AUTO_INCREMENT PRIMARY KEY,
    data_registro DATE NOT NULL,
    pluviosidade_mm DECIMAL(10,2) NOT NULL,
    potencia_mw DECIMAL(10,2) NOT NULL
);

-- Exemplo de dados históricos iniciados para o módulo
INSERT INTO DadosHistoricos (data_registro, pluviosidade_mm, potencia_mw) VALUES
('2023-01-01', 120.00, 50.00),
('2023-02-01', 150.00, 65.00),
('2023-03-01', 180.00, 70.00),
('2023-04-01', 200.00, 80.00),
('2023-05-01', 170.00, 68.00);
