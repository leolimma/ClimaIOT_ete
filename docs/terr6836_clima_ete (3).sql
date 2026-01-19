-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 14/12/2025 às 21:43
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
-- Banco de dados: `seu_usuario_clima_ete`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `clima_config`
--

CREATE TABLE `clima_config` (
  `chave` varchar(50) NOT NULL,
  `valor` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `clima_config`
--

-- CREDENCIAIS REMOVIDAS POR SEGURANÇA - Configure as suas no painel admin ou .env:
INSERT INTO `clima_config` (`chave`, `valor`) VALUES
('cron_key', 'INSIRA_SUA_CHAVE_SEGURA_AQUI'),
('setup_done', 'CONFIGURE_VIA_SETUP'),
('thinger_device', 'SEU_ID_DISPOSITIVO'),
('thinger_resource', 'SEU_RECURSO'),
('thinger_token', 'SEU_TOKEN_THINGER'),
('thinger_user', 'SEU_USUARIO_THINGER');

-- --------------------------------------------------------

--
-- Estrutura para tabela `clima_historico`
--

CREATE TABLE `clima_historico` (
  `id` int(11) NOT NULL,
  `data_registro` datetime DEFAULT current_timestamp(),
  `temp` float DEFAULT NULL,
  `hum` int(11) DEFAULT NULL,
  `pres` float DEFAULT NULL,
  `uv` float DEFAULT NULL,
  `gas` float DEFAULT NULL,
  `chuva` float DEFAULT NULL,
  `chuva_status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `clima_historico`
--

INSERT INTO `clima_historico` (`id`, `data_registro`, `temp`, `hum`, `pres`, `uv`, `gas`, `chuva`, `chuva_status`) VALUES
(1, '2025-12-09 20:36:37', 33.5475, 40, 958.68, 0.548387, 138.546, 100, 'Chovendo'),
(2, '2025-12-09 20:36:40', 33.7757, 40, 958.68, 0.258065, 148.809, 100, 'Chovendo'),
(3, '2025-12-09 20:36:43', 33.6541, 41, 958.7, 0.258065, 143.984, 100, 'Chovendo'),
(4, '2025-12-09 20:36:48', 33.7948, 41, 958.68, 0.548387, 149.468, 100, 'Chovendo'),
(5, '2025-12-09 20:38:25', 33.5037, 40, 958.69, 0.516129, 137.195, 100, 'Chovendo'),
(6, '2025-12-09 20:38:27', 33.6957, 40, 958.69, 0.516129, 148.416, 100, 'Chovendo'),
(7, '2025-12-09 20:40:11', 33.5237, 40, 958.71, 0.516129, 137.755, 100, 'Chovendo'),
(8, '2025-12-09 20:42:52', 33.7268, 41, 958.71, 0.548387, 142.766, 100, 'Chovendo'),
(9, '2025-12-09 20:43:13', 33.4843, 41, 958.74, 0.548387, 131.939, 100, 'Chovendo'),
(10, '2025-12-09 21:55:24', 33.1472, 42, 958.93, 0.548387, 134.569, 100, 'Chovendo'),
(11, '2025-12-09 22:05:32', 33.2104, 42, 958.94, 0.516129, 137.867, 100, 'Chovendo'),
(12, '2025-12-11 16:41:32', 34.8426, 36, 954.57, 0.548387, 20.166, 100, 'Chovendo'),
(13, '2025-12-11 16:41:45', 34.7883, 35, 954.58, 0.290323, 28.399, 100, 'Chovendo'),
(14, '2025-12-11 16:58:12', 35.9012, 32, 954.57, 0.548387, 136.639, 100, 'Chovendo'),
(15, '2025-12-11 17:05:48', 35.7739, 33, 954.58, 0.516129, 145.474, 100, 'Chovendo'),
(16, '2025-12-11 17:29:09', 35.7447, 32, 954.67, 0.516129, 158.005, 100, 'Chovendo'),
(17, '2025-12-11 17:29:14', 35.6225, 31, 954.65, 0.516129, 155.961, 100, 'Chovendo'),
(18, '2025-12-11 17:33:28', 35.4504, 31, 954.68, 0.548387, 150.133, 100, 'Chovendo'),
(19, '2025-12-11 17:33:47', 35.6206, 31, 954.69, 0.516129, 156.539, 100, 'Chovendo'),
(20, '2025-12-11 17:44:20', 35.4422, 32, 954.74, 0.548387, 150.401, 100, 'Chovendo'),
(21, '2025-12-11 23:57:41', 33.3275, 39, 957.62, 0.548387, 138.546, 100, 'Chovendo');

-- --------------------------------------------------------

--
-- Estrutura para tabela `clima_users`
--

CREATE TABLE `clima_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `role` varchar(20) NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `clima_users`
--

-- USUÁRIO ADMIN REMOVIDO POR SEGURANÇA - Configure via setup.php ou bin/reset_admin.php
INSERT INTO `clima_users` (`id`, `username`, `password_hash`, `name`, `email`, `created_at`, `role`) VALUES
(1, 'admin', 'CONFIGURE_VIA_SETUP_OU_RESET', 'Seu Nome Completo', 'seu_email@dominio.com', NOW(), 'admin');

-- --------------------------------------------------------

--
-- Estrutura para tabela `migrations`
--

CREATE TABLE `migrations` (
  `id` int(11) NOT NULL,
  `migration` varchar(255) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `applied_at`) VALUES
(1, 'V1__init_tables', '2025-12-11 18:08:55');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `clima_config`
--
ALTER TABLE `clima_config`
  ADD PRIMARY KEY (`chave`);

--
-- Índices de tabela `clima_historico`
--
ALTER TABLE `clima_historico`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `clima_users`
--
ALTER TABLE `clima_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Índices de tabela `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `migration` (`migration`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `clima_historico`
--
ALTER TABLE `clima_historico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `clima_users`
--
ALTER TABLE `clima_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
