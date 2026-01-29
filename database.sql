-- --------------------------------------------------------
-- SCRIPT DE INSTALAÇÃO DO SISMIL (2º BEC)
-- Data: 25/01/2026
-- Autor: SISMIL Dev Team
-- --------------------------------------------------------

-- 1. CRIAÇÃO DO BANCO DE DADOS
CREATE DATABASE IF NOT EXISTS sismil_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE sismil_db;

-- 2. TABELA DE USUÁRIOS DO SISTEMA
CREATE TABLE IF NOT EXISTS tb_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identidade VARCHAR(20) NOT NULL UNIQUE COMMENT 'Login do usuário (CPF ou Identidade)',
    senha_hash VARCHAR(255) NOT NULL COMMENT 'Senha criptografada (Bcrypt)',
    role VARCHAR(20) NOT NULL DEFAULT 'user' COMMENT 'Nível: admin, sargenteacao, s2, user',
    ativo TINYINT(1) DEFAULT 1 COMMENT '1 = Ativo, 0 = Bloqueado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. TABELA DE MILITARES (FICHA INDIVIDUAL)
CREATE TABLE IF NOT EXISTS tb_militares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Identificação Fundamental
    cpf VARCHAR(14) NOT NULL UNIQUE,
    idt_militar VARCHAR(20),
    posto_grad VARCHAR(20) NOT NULL,
    numero INT,
    nome_guerra VARCHAR(50) NOT NULL,
    nome_completo VARCHAR(100),
    
    -- Dados Organizacionais
    subunidade VARCHAR(20),
    pelotao VARCHAR(30),
    secao VARCHAR(30),
    qmg VARCHAR(50),
    dt_praca DATE,
    
    -- Dados Pessoais
    dt_nascimento DATE,
    tipo_sanguineo VARCHAR(5),
    
    -- Contato e Endereço
    email VARCHAR(100),
    celular_princ VARCHAR(20),
    celular_sec VARCHAR(20),
    nome_resp VARCHAR(100),
    tel_resp VARCHAR(20),
    cep VARCHAR(10),
    endereco VARCHAR(150),
    num_residencia VARCHAR(10),
    bairro VARCHAR(50),
    cidade VARCHAR(50),
    estado VARCHAR(2),
    
    -- Trânsito (S2)
    cat_cnh VARCHAR(5),
    validade_cnh DATE,
    tipo_veiculo VARCHAR(20),
    placa VARCHAR(10),
    modelo VARCHAR(50),
    cor VARCHAR(20),
    validade_crlv DATE,
    homologado TINYINT(1) DEFAULT 0 COMMENT '1 = Homologado pelo S2',
    
    -- Arquivos
    foto_path VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. USUÁRIO ADMINISTRADOR PADRÃO
-- Login: admin
-- Senha: 123456 (Hash gerado via Bcrypt)
-- IMPORTANTE: Solicite a troca da senha no primeiro acesso.

INSERT INTO tb_usuarios (identidade, senha_hash, role, ativo) VALUES 
('admin', '$2y$10$YHtTDQEjxSIr.UCLmj/JD.VN7UD4hMBOtJNzfdjxW3s1TmcMyaOYK', 'admin', 1)
ON DUPLICATE KEY UPDATE identidade=identidade; -- Evita erro se já existir