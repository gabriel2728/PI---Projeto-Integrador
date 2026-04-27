-- Migração: Adicionar campos para confirmação de email
-- Data: 27/04/2026
-- Descrição: Adiciona campos necessários para o sistema de confirmação de email

USE SistemaHidreletrico;

-- Adicionar coluna para status de confirmação do email
ALTER TABLE Usuario ADD COLUMN emailConfirmado TINYINT(1) DEFAULT 0 NOT NULL COMMENT '0 = não confirmado, 1 = confirmado';

-- Adicionar coluna para token de confirmação
ALTER TABLE Usuario ADD COLUMN confirmacaoToken VARCHAR(64) NULL COMMENT 'Token único para confirmação de email';

-- Adicionar coluna para expiração do token
ALTER TABLE Usuario ADD COLUMN emailConfirmacaoExpiracao TIMESTAMP NULL COMMENT 'Data de expiração do token de confirmação';

-- Adicionar índice para melhorar performance das consultas por token
ALTER TABLE Usuario ADD INDEX idx_confirmacao_token (confirmacaoToken);

-- Adicionar índice para limpeza automática de tokens expirados
ALTER TABLE Usuario ADD INDEX idx_confirmacao_expiracao (emailConfirmacaoExpiracao);

-- Para usuários existentes, marcar como confirmados (já que não havia sistema antes)
-- UPDATE Usuario SET emailConfirmado = 1 WHERE emailConfirmado = 0;

-- Verificar se as colunas foram adicionadas
DESCRIBE Usuario;