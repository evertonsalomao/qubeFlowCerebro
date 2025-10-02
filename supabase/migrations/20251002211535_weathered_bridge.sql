-- Script de Atualização do Banco de Dados
-- Sistema de Cadastro para Agente de IA
-- IMPORTANTE: Este script apenas ADICIONA colunas e tabelas, não remove dados existentes

-- =====================================================
-- 1. ATUALIZAR TABELA USERS
-- =====================================================

-- Adicionar coluna is_admin se não existir
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) DEFAULT 0 AFTER password;

-- Adicionar coluna active se não existir
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS active TINYINT(1) DEFAULT 1 AFTER is_admin;

-- =====================================================
-- 2. CRIAR TABELA DE CONTROLE DE ACESSO
-- =====================================================

-- Criar tabela user_company_access se não existir
CREATE TABLE IF NOT EXISTS user_company_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_company (user_id, company_id),
    INDEX idx_user_id (user_id),
    INDEX idx_company_id (company_id)
);

-- =====================================================
-- 3. CRIAR USUÁRIOS PADRÃO (SE NÃO EXISTIREM)
-- =====================================================

-- Inserir usuário admin padrão se não existir
INSERT IGNORE INTO users (email, password, is_admin, active) VALUES 
('admin@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1);

-- Atualizar usuários existentes para serem ativos por padrão
UPDATE users SET active = 1 WHERE active IS NULL;

-- =====================================================
-- 4. VERIFICAÇÕES E RELATÓRIO
-- =====================================================

-- Mostrar estrutura atualizada da tabela users
SELECT 'Estrutura da tabela users:' as info;
DESCRIBE users;

-- Mostrar se a tabela user_company_access foi criada
SELECT 'Tabela user_company_access criada:' as info;
SHOW TABLES LIKE 'user_company_access';

-- Contar usuários existentes
SELECT 'Total de usuários no sistema:' as info, COUNT(*) as total FROM users;

-- Mostrar usuários admin
SELECT 'Usuários administradores:' as info;
SELECT id, email, is_admin, active, created_at FROM users WHERE is_admin = 1;

-- =====================================================
-- 5. MENSAGENS DE SUCESSO
-- =====================================================

SELECT '✅ ATUALIZAÇÃO CONCLUÍDA COM SUCESSO!' as status;
SELECT 'Todas as alterações foram aplicadas sem perder dados existentes.' as info;
SELECT 'Agora você pode usar o sistema de gerenciamento de usuários.' as info;