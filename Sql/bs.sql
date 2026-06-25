-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 22-Jun-2026 às 22:52
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Criação e Seleção Segura da Base de Dados (CORREÇÃO AQUI)
--
CREATE DATABASE IF NOT EXISTS `livriscore` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `livriscore`;

-- --------------------------------------------------------

--
-- Estrutura da tabela `autores`
--

CREATE TABLE `autores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `autores`
--

INSERT INTO `autores` (`id`, `nome`) VALUES
(1, 'José'),
(2, 'Miguel');

-- --------------------------------------------------------

--
-- Estrutura da tabela `cdu_classes`
--

CREATE TABLE `cdu_classes` (
  `codigo` varchar(20) NOT NULL,
  `descricao` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `cdu_classes`
--

INSERT INTO `cdu_classes` (`codigo`, `descricao`) VALUES
('0', 'Generalidades. Ciência e conhecimento. Organização. Informação. Documentação. Biblioteconomia. Instituições. Publicações'),
('1', 'Filosofia. Psicologia'),
('2', 'Religião. Teologia'),
('3', 'Ciências Sociais. Estatística. Política. Economia. Comércio. Direito. Administração Pública. Forças Armadas. Bem-estar Social. Seguros. Educação. Etno'),
('5', 'Matemática e Ciências Naturais (Física, Química, Biologia, Geologia)'),
('6', 'Ciências Aplicadas. Medicina. Tecnologia. Engenharia. Agricultura. Indústria. Profissões'),
('7', 'Arte. Belas-artes. Recreação. Diversões. Cinema. Música. Desporto'),
('8', 'Linguagem. Linguística. Literature'),
('9', 'Geografia. Biografia. História');

-- --------------------------------------------------------

--
-- Estrutura da tabela `emprestimos`
--

CREATE TABLE `emprestimos` (
  `id` int(11) NOT NULL,
  `utilizador_id` int(11) NOT NULL,
  `livro_id` int(11) NOT NULL,
  `reserva_id` int(11) DEFAULT NULL,
  `data_saida` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_prevista_devolucao` date NOT NULL,
  `data_devolucao_real` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `emprestimos`
--

INSERT INTO `emprestimos` (`id`, `utilizador_id`, `livro_id`, `reserva_id`, `data_saida`, `data_prevista_devolucao`, `data_devolucao_real`) VALUES
(1, 1, 1, 3, '2026-06-21 23:00:00', '2026-07-07', '2026-06-22 10:21:40'),
(2, 1, 3, 5, '2026-06-21 23:00:00', '2026-07-07', '2026-06-22 13:27:46'),
(4, 3, 1, 13, '2026-06-23 23:00:00', '2026-07-09', '2026-06-22 18:07:36');

-- --------------------------------------------------------

--
-- Estrutura da tabela `livros`
--

CREATE TABLE `livros` (
  `id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `editora` varchar(100) DEFAULT NULL,
  `ano_edicao` int(4) DEFAULT NULL,
  `cdu_codigo` varchar(20) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `imagem_url` varchar(300) DEFAULT NULL,
  `estado` enum('disponivel','reservado','emprestado') NOT NULL DEFAULT 'disponivel'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `livros`
--

INSERT INTO `livros` (`id`, `titulo`, `isbn`, `editora`, `ano_edicao`, `cdu_codigo`, `descricao`, `imagem`, `imagem_url`, `estado`) VALUES
(1, 'Livro', '98766667576', 'Porto editora', 2026, '6', 'afefewg', NULL, '0b1de6ae1f68d7506c98f8ca146605e7.png', 'disponivel'),
(3, 'Memorial do Convento', '978-972-0-04671-1', 'Porto editora', 2022, '2', 'pt9+90+', NULL, 'capa_6a390ae0804e77.59021355.png', 'reservado'),
(5, 'Livroad', '978-972-0-04671-1', 'Porto editora', 2026, '1', 'o8ro', 'capa_6a390c687b5a13.21060649.png', 'capa_6a390c687b5a13.21060649.png', 'disponivel'),
(8, 'Livro', '978-972-0-04671-1', 'Porto editora', 2023, '2', 'grsrg', 'capa_6a398c23b46b18.08041270.png', 'capa_6a398c23b46b18.08041270.png', 'disponivel');

-- --------------------------------------------------------

--
-- Estrutura da tabela `livro_autores`
--

CREATE TABLE `livro_autores` (
  `livro_id` int(11) NOT NULL,
  `autor_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `livro_autores`
--

INSERT INTO `livro_autores` (`livro_id`, `autor_id`) VALUES
(1, 1),
(3, 1),
(5, 1),
(8, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `reservas`
--

CREATE TABLE `reservas` (
  `id` int(11) NOT NULL,
  `utilizador_id` int(11) NOT NULL,
  `livro_id` int(11) NOT NULL,
  `data_inicio` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_fim` datetime NOT NULL,
  `status` enum('pendente','concluida','cancelada') NOT NULL DEFAULT 'pendente',
  `codigo_validacao` int(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `reservas`
--

INSERT INTO `reservas` (`id`, `utilizador_id`, `livro_id`, `data_inicio`, `data_fim`, `status`, `codigo_validacao`) VALUES
(1, 1, 1, '2026-06-21 21:59:06', '0000-00-00 00:00:00', 'cancelada', 0),
(2, 1, 1, '2026-06-22 09:17:35', '0000-00-00 00:00:00', 'cancelada', 0),
(3, 1, 1, '2026-06-22 09:19:47', '0000-00-00 00:00:00', 'concluida', 946),
(4, 1, 1, '2026-06-22 09:21:45', '0000-00-00 00:00:00', 'cancelada', 296),
(5, 1, 3, '2026-06-22 10:29:06', '0000-00-00 00:00:00', 'concluida', 60),
(10, 1, 1, '2026-06-22 13:22:03', '0000-00-00 00:00:00', 'cancelada', 595),
(11, 1, 3, '2026-06-22 13:22:06', '0000-00-00 00:00:00', 'cancelada', 14),
(13, 3, 1, '2026-06-22 17:06:10', '0000-00-00 00:00:00', 'concluida', 596),
(14, 1, 3, '2026-06-22 18:23:25', '0000-00-00 00:00:00', 'cancelada', 695),
(15, 1, 3, '2026-06-22 18:50:39', '0000-00-00 00:00:00', 'pendente', 278);

-- --------------------------------------------------------

--
-- Estrutura da tabela `utilizadores`
--

CREATE TABLE `utilizadores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telemovel` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `tipo` tinyint(1) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `data_registo` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `utilizadores`
--

INSERT INTO `utilizadores` (`id`, `nome`, `email`, `telemovel`, `password_hash`, `tipo`, `ativo`, `data_registo`) VALUES
(1, 'Rodrigo', 'a@a.a', '987747853', '$2y$10$TLD6xL8ms0VQyx1d8yl9xuwPRKLN9qd1HC160mkaNfK6B6/vcZtGi', 1, 1, '2026-06-21 18:43:17'),
(3, 'João Baião', 'g@g.g', '987747853', '$2y$10$HHBWBTmv6PIX.pvqwI0AJOu8lVSA8ekTqtRDVyncyljj4zsyiDZXW', 0, 1, '2026-06-22 17:03:40');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `autores`
--
ALTER TABLE `autores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices para tabela `cdu_classes`
--
ALTER TABLE `cdu_classes`
  ADD PRIMARY KEY (`codigo`);

--
-- Índices para tabela `emprestimos`
--
ALTER TABLE `emprestimos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilizador_id` (`utilizador_id`),
  ADD KEY `livro_id` (`livro_id`),
  ADD KEY `reserva_id` (`reserva_id`);

--
-- Índices para tabela `livros`
--
ALTER TABLE `livros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cdu_codigo` (`cdu_codigo`);

--
-- Índices para tabela `livro_autores`
--
ALTER TABLE `livro_autores`
  ADD PRIMARY KEY (`livro_id`,`autor_id`),
  ADD KEY `livro_autores_ibfk_2` (`autor_id`);

--
-- Índices para tabela `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilizador_id` (`utilizador_id`),
  ADD KEY `livro_id` (`livro_id`);

--
-- Índices para tabela `utilizadores`
--
ALTER TABLE `utilizadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `autores`
--
ALTER TABLE `autores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `emprestimos`
--
ALTER TABLE `emprestimos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `livros`
--
ALTER TABLE `livros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `utilizadores`
--
ALTER TABLE `utilizadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `emprestimos`
--
ALTER TABLE `emprestimos`
  ADD CONSTRAINT `emprestimos_ibfk_1` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emprestimos_ibfk_2` FOREIGN KEY (`livro_id`) REFERENCES `livros` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emprestimos_ibfk_3` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `livros`
--
ALTER TABLE `livros`
  ADD CONSTRAINT `livros_ibfk_cdu` FOREIGN KEY (`cdu_codigo`) REFERENCES `cdu_classes` (`codigo`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `livro_autores`
--
ALTER TABLE `livro_autores`
  ADD CONSTRAINT `livro_autores_ibfk_1` FOREIGN KEY (`livro_id`) REFERENCES `livros` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `livro_autores_ibfk_2` FOREIGN KEY (`autor_id`) REFERENCES `autores` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservas_ibfk_2` FOREIGN KEY (`livro_id`) REFERENCES `livros` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;