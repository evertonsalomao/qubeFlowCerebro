-- Sistema de Cadastro para Agente de IA
-- Criação do banco de dados e tabelas

CREATE DATABASE IF NOT EXISTS ai_agent_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ai_agent_system;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de empresas
CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    cnpj VARCHAR(20) DEFAULT '',
    main_address TEXT DEFAULT '',
    main_phone VARCHAR(20) DEFAULT '',
    main_whatsapp VARCHAR(20) DEFAULT '',
    business_hours VARCHAR(255) DEFAULT '',
    general_info TEXT DEFAULT '',
    slug VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_slug (slug)
);

-- Tabela de endereços das empresas
CREATE TABLE IF NOT EXISTS company_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    address TEXT DEFAULT '',
    phone VARCHAR(20) DEFAULT '',
    whatsapp VARCHAR(20) DEFAULT '',
    business_hours VARCHAR(255) DEFAULT '',
    additional_info TEXT DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_company_id (company_id)
);

-- Tabela de perguntas e respostas
CREATE TABLE IF NOT EXISTS faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    order_index INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_company_id (company_id),
    INDEX idx_order (company_id, order_index)
);

-- Tabela de acesso de usuários às empresas
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

-- Inserir usuário admin padrão (senha: admin123)
INSERT IGNORE INTO users (email, password, is_admin) VALUES 
('admin@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Inserir usuário comum de teste (senha: 123456)
INSERT IGNORE INTO users (email, password, is_admin) VALUES 
('usuario@teste.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0);