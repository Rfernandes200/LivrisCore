-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 21-Jun-2026 às 23:50
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
-- Banco de dados: `bibliobase`
--

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
(1, 'José');

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
(1, 'Livro', '98766667576', 'Porto editora', 2026, '6', 'afefewg', NULL, '0b1de6ae1f68d7506c98f8ca146605e7.png', 'disponivel');

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
(1, 1);

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
(1, 'Rodrigo', 'a@a.a', '987747853', '$2y$10$LfnMfcU8mkYy4oEHzyHK8OViefTew.5I1vGdFa/Uy9xrPV8bxuQkq', 1, 1, '2026-06-21 18:43:17');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `emprestimos`
--
ALTER TABLE `emprestimos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `livros`
--
ALTER TABLE `livros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `utilizadores`
--
ALTER TABLE `utilizadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
