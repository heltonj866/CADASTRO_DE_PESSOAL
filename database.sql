-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 12/02/2026 às 14:21
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sismil_db`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_alteracoes`
--

CREATE TABLE `tb_alteracoes` (
  `id` int(11) NOT NULL,
  `militar_id` int(11) NOT NULL,
  `categoria` enum('SAUDE','DISCIPLINA','ELOGIO','ACIDENTE') NOT NULL,
  `tipo_detalhe` varchar(100) NOT NULL,
  `data_fato` date NOT NULL,
  `data_publicacao` date DEFAULT NULL,
  `descricao` text NOT NULL,
  `documento_ref` varchar(100) DEFAULT NULL,
  `qtd_dias` int(11) DEFAULT 0,
  `consumido` tinyint(1) DEFAULT 0,
  `arquivo_path` varchar(255) DEFAULT NULL,
  `registrado_por` varchar(100) DEFAULT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_militares`
--

CREATE TABLE `tb_militares` (
  `id` int(11) NOT NULL,
  `identidade` varchar(20) NOT NULL,
  `posto_grad` varchar(20) DEFAULT NULL,
  `numero` int(11) DEFAULT NULL,
  `nome_guerra` varchar(50) DEFAULT NULL,
  `subunidade` varchar(50) DEFAULT NULL,
  `pelotao` varchar(50) DEFAULT NULL,
  `secao` varchar(50) DEFAULT NULL,
  `nome_completo` varchar(150) DEFAULT NULL,
  `qmg` varchar(100) DEFAULT NULL,
  `dt_nascimento` date DEFAULT NULL,
  `tipo_sanguineo` varchar(5) DEFAULT NULL,
  `dt_praca` date DEFAULT NULL,
  `idt_militar` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `celular_princ` varchar(20) DEFAULT NULL,
  `celular_sec` varchar(20) DEFAULT NULL,
  `nome_resp` varchar(100) DEFAULT NULL,
  `tel_resp` varchar(20) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `endereco` varchar(150) DEFAULT NULL,
  `num_residencia` varchar(10) DEFAULT NULL,
  `bairro` varchar(50) DEFAULT NULL,
  `cidade` varchar(50) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `cat_cnh` varchar(5) DEFAULT NULL,
  `validade_cnh` date DEFAULT NULL,
  `tipo_veiculo` varchar(20) DEFAULT NULL,
  `placa` varchar(10) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `cor` varchar(20) DEFAULT NULL,
  `validade_crlv` date DEFAULT NULL,
  `homologado` tinyint(1) DEFAULT 0,
  `complemento` varchar(100) DEFAULT NULL,
  `foto_path` varchar(255) DEFAULT 'default.png',
  `status` varchar(20) DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_usuarios`
--

CREATE TABLE `tb_usuarios` (
  `id` int(11) NOT NULL,
  `identidade` varchar(20) NOT NULL,
  `numero` int(11) DEFAULT NULL,
  `posto_grad` varchar(20) DEFAULT NULL,
  `qmg` varchar(100) DEFAULT NULL,
  `dt_praca` date DEFAULT NULL,
  `nome_guerra` varchar(50) DEFAULT NULL,
  `nome_completo` varchar(150) DEFAULT NULL,
  `subunidade` varchar(20) DEFAULT NULL,
  `secao` varchar(50) DEFAULT NULL,
  `pelotao` varchar(50) DEFAULT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `ativo` tinyint(1) DEFAULT 1,
  `status` varchar(10) DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_usuarios`
--

INSERT INTO `tb_usuarios` (`id`, `identidade`, `numero`, `posto_grad`, `qmg`, `dt_praca`, `nome_guerra`, `nome_completo`, `subunidade`, `secao`, `pelotao`, `senha_hash`, `role`, `ativo`, `status`) VALUES
(11, '000.000.000-00', NULL, 'Gen', NULL, NULL, 'ADMINISTRADOR', NULL, 'EM', NULL, NULL, '$2y$10$O.uLua25E1s4Zx/u1GaRH.lEYvX/ZrnrbMC5vSYw9Y4OyDeyGkTAW', 'admin', 1, 'ativo');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_veiculos`
--

CREATE TABLE `tb_veiculos` (
  `id` int(11) NOT NULL,
  `militar_id` int(11) NOT NULL,
  `cat_cnh` varchar(5) DEFAULT NULL,
  `validade_cnh` date DEFAULT NULL,
  `tipo_veiculo` varchar(20) DEFAULT NULL,
  `placa` varchar(10) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `cor` varchar(20) DEFAULT NULL,
  `validade_crlv` date DEFAULT NULL,
  `homologado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `tb_alteracoes`
--
ALTER TABLE `tb_alteracoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `militar_id` (`militar_id`);

--
-- Índices de tabela `tb_militares`
--
ALTER TABLE `tb_militares`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idt_cpf_militar` (`identidade`);

--
-- Índices de tabela `tb_usuarios`
--
ALTER TABLE `tb_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `identidade` (`identidade`),
  ADD UNIQUE KEY `identidade_2` (`identidade`);

--
-- Índices de tabela `tb_veiculos`
--
ALTER TABLE `tb_veiculos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `militar_id` (`militar_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `tb_alteracoes`
--
ALTER TABLE `tb_alteracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `tb_militares`
--
ALTER TABLE `tb_militares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `tb_usuarios`
--
ALTER TABLE `tb_usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `tb_veiculos`
--
ALTER TABLE `tb_veiculos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `tb_alteracoes`
--
ALTER TABLE `tb_alteracoes`
  ADD CONSTRAINT `tb_alteracoes_ibfk_1` FOREIGN KEY (`militar_id`) REFERENCES `tb_militares` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `tb_veiculos`
--
ALTER TABLE `tb_veiculos`
  ADD CONSTRAINT `tb_veiculos_ibfk_1` FOREIGN KEY (`militar_id`) REFERENCES `tb_militares` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
