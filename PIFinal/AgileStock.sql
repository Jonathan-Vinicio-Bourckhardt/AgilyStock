-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 28/11/2025 às 00:15
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `agilestock`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `cadfornecedor`
--

CREATE TABLE `cadfornecedor` (
  `CNPJ` varchar(14) NOT NULL,
  `Fornecedor` varchar(100) NOT NULL,
  `NumContato` varchar(11) NOT NULL,
  `id_empresa` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cadmovimento`
--

CREATE TABLE `cadmovimento` (
  `CodMovimento` int(11) NOT NULL,
  `Devolucao` enum('Sim','Não') NOT NULL,
  `Acao` enum('Soma','Subtracao') NOT NULL,
  `Tipo` enum('fruta','verdura','legume','outro') NOT NULL,
  `CodProdFor_FK` int(11) NOT NULL,
  `Quantidade` decimal(10,2) NOT NULL,
  `ValorUnitario` decimal(10,2) NOT NULL,
  `DataMovimento` datetime DEFAULT current_timestamp(),
  `id_empresa` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cadproduto`
--

CREATE TABLE `cadproduto` (
  `CodProduto` int(11) NOT NULL,
  `Tipo` enum('fruta','verdura','legume','outro') NOT NULL,
  `Produto` varchar(100) NOT NULL,
  `Formato` varchar(20) DEFAULT NULL,
  `Fornecedor` varchar(100) DEFAULT NULL,
  `id_empresa` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cadquantidade`
--

CREATE TABLE `cadquantidade` (
  `CodMovimento` int(11) NOT NULL,
  `Devolucao` enum('Sim','Não') NOT NULL,
  `Acao` enum('Soma','Subtracao') CHARACTER SET sjis COLLATE sjis_bin NOT NULL,
  `Tipo` enum('fruta','verdura','legume','outro') NOT NULL,
  `CodProdFor_FK` int(11) NOT NULL,
  `Quantidade` decimal(10,2) NOT NULL,
  `ValorUnitario` decimal(10,2) NOT NULL,
  `id_empresa` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `comentarios_estoque`
--

CREATE TABLE `comentarios_estoque` (
  `id_comentario` int(11) NOT NULL,
  `CodProdFor_FK` int(11) NOT NULL,
  `comentario` text NOT NULL,
  `data_comentario` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_empresa` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `empresas`
--

CREATE TABLE `empresas` (
  `id` int(11) NOT NULL,
  `nome_empresa` varchar(50) NOT NULL,
  `cnpj` varchar(14) NOT NULL,
  `email` varchar(50) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_empresa` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `estoque`
--

CREATE TABLE `estoque` (
  `CodProdFor_FK` int(11) NOT NULL,
  `Quantidade` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ValorTotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `id_empresa` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produto_fornecedor`
--

CREATE TABLE `produto_fornecedor` (
  `CodProdFor` int(11) NOT NULL,
  `CodProduto_FK` int(11) NOT NULL,
  `CNPJ_Fornecedor_FK` varchar(14) NOT NULL,
  `Formato` enum('kg','unidade') NOT NULL,
  `id_empresa` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `cadfornecedor`
--
ALTER TABLE `cadfornecedor`
  ADD UNIQUE KEY `unique_cnpj_empresa` (`CNPJ`,`id_empresa`),
  ADD UNIQUE KEY `unique_contato_empresa` (`NumContato`,`id_empresa`);

--
-- Índices de tabela `cadmovimento`
--
ALTER TABLE `cadmovimento`
  ADD PRIMARY KEY (`CodMovimento`),
  ADD KEY `cadmovimento_ibfk_1` (`CodProdFor_FK`);

--
-- Índices de tabela `cadproduto`
--
ALTER TABLE `cadproduto`
  ADD PRIMARY KEY (`CodProduto`);

--
-- Índices de tabela `cadquantidade`
--
ALTER TABLE `cadquantidade`
  ADD PRIMARY KEY (`CodMovimento`),
  ADD KEY `CodProdFor_FK` (`CodProdFor_FK`);

--
-- Índices de tabela `comentarios_estoque`
--
ALTER TABLE `comentarios_estoque`
  ADD PRIMARY KEY (`id_comentario`),
  ADD KEY `comentarios_estoque_ibfk_1` (`CodProdFor_FK`);

--
-- Índices de tabela `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cnpj` (`cnpj`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `estoque`
--
ALTER TABLE `estoque`
  ADD PRIMARY KEY (`CodProdFor_FK`);

--
-- Índices de tabela `produto_fornecedor`
--
ALTER TABLE `produto_fornecedor`
  ADD PRIMARY KEY (`CodProdFor`),
  ADD KEY `CodProduto_FK` (`CodProduto_FK`),
  ADD KEY `CNPJ_Fornecedor_FK` (`CNPJ_Fornecedor_FK`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `cadmovimento`
--
ALTER TABLE `cadmovimento`
  MODIFY `CodMovimento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cadproduto`
--
ALTER TABLE `cadproduto`
  MODIFY `CodProduto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cadquantidade`
--
ALTER TABLE `cadquantidade`
  MODIFY `CodMovimento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `comentarios_estoque`
--
ALTER TABLE `comentarios_estoque`
  MODIFY `id_comentario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `produto_fornecedor`
--
ALTER TABLE `produto_fornecedor`
  MODIFY `CodProdFor` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `cadmovimento`
--
ALTER TABLE `cadmovimento`
  ADD CONSTRAINT `cadmovimento_ibfk_1` FOREIGN KEY (`CodProdFor_FK`) REFERENCES `produto_fornecedor` (`CodProdFor`) ON DELETE CASCADE;

--
-- Restrições para tabelas `comentarios_estoque`
--
ALTER TABLE `comentarios_estoque`
  ADD CONSTRAINT `comentarios_estoque_ibfk_1` FOREIGN KEY (`CodProdFor_FK`) REFERENCES `produto_fornecedor` (`CodProdFor`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
