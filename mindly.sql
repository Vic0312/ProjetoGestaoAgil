-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 09/10/2026 às 19:09
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
-- Banco de dados: `mindly`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `auditoria_administrativa`
--

CREATE TABLE `auditoria_administrativa` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` bigint(20) UNSIGNED NOT NULL,
  `usuario_alvo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `acao` enum('aprovar_psicologo','rejeitar_psicologo','suspender_usuario','reativar_usuario','atualizar_usuario','outra') NOT NULL,
  `detalhes` varchar(500) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `avaliacoes`
--

CREATE TABLE `avaliacoes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `consulta_id` bigint(20) UNSIGNED NOT NULL,
  `paciente_id` bigint(20) UNSIGNED NOT NULL,
  `psicologo_id` bigint(20) UNSIGNED NOT NULL,
  `nota` tinyint(3) UNSIGNED NOT NULL,
  `comentario` text DEFAULT NULL,
  `publicada` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Despejando dados para a tabela `avaliacoes`
--

INSERT INTO `avaliacoes` (`id`, `consulta_id`, `paciente_id`, `psicologo_id`, `nota`, `comentario`, `publicada`, `criado_em`, `atualizado_em`) VALUES
(15, 60, 27, 87, 5, 'Gostei muito do atendimento. A profissional foi atenciosa e acolhedora.', 1, '2026-09-18 20:00:00', '2026-10-09 17:01:35'),
(16, 61, 27, 87, 5, 'A consulta foi muito boa e me ajudou a organizar melhor meus pensamentos.', 1, '2026-10-02 21:00:00', '2026-10-09 17:01:35'),
(17, 64, 82, 88, 4, 'Atendimento muito bom e explicações claras.', 1, '2026-09-25 15:00:00', '2026-10-09 17:01:35'),
(18, 68, 86, 87, 5, 'Me senti confortável durante toda a consulta.', 1, '2026-09-29 18:00:00', '2026-10-09 17:01:35');

-- --------------------------------------------------------

--
-- Estrutura para tabela `bloqueios_agenda`
--

CREATE TABLE `bloqueios_agenda` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `psicologo_id` bigint(20) UNSIGNED NOT NULL,
  `inicio_em` datetime NOT NULL,
  `fim_em` datetime NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `consentimentos`
--

CREATE TABLE `consentimentos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `tipo` enum('termos_uso','politica_privacidade','teleatendimento') NOT NULL,
  `versao` varchar(30) NOT NULL,
  `aceito_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `consentimentos`
--

INSERT INTO `consentimentos` (`id`, `usuario_id`, `tipo`, `versao`, `aceito_em`) VALUES
(9, 6, 'termos_uso', '2026-09', '2026-09-24 23:28:20'),
(10, 6, 'politica_privacidade', '2026-09', '2026-09-24 23:28:20'),
(31, 20, 'termos_uso', '2026-09', '2026-09-24 23:56:26'),
(32, 20, 'politica_privacidade', '2026-09', '2026-09-24 23:56:26'),
(33, 21, 'termos_uso', '2026-09', '2026-09-24 23:58:20'),
(34, 21, 'politica_privacidade', '2026-09', '2026-09-24 23:58:20'),
(43, 27, 'termos_uso', '2026-09', '2026-09-25 00:48:32'),
(44, 27, 'politica_privacidade', '2026-09', '2026-09-25 00:48:32');

-- --------------------------------------------------------

--
-- Estrutura para tabela `consultas`
--

CREATE TABLE `consultas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `horario_id` bigint(20) UNSIGNED NOT NULL,
  `psicologo_id` bigint(20) UNSIGNED NOT NULL,
  `paciente_id` bigint(20) UNSIGNED NOT NULL,
  `consulta_origem_id` bigint(20) UNSIGNED DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `modalidade` enum('online') NOT NULL DEFAULT 'online',
  `status` enum('aguardando_pagamento','confirmada','em_andamento','concluida','cancelada','nao_compareceu') NOT NULL DEFAULT 'aguardando_pagamento',
  `motivo_cancelamento` varchar(500) DEFAULT NULL,
  `cancelada_em` datetime DEFAULT NULL,
  `iniciada_em` datetime DEFAULT NULL,
  `encerrada_em` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `horario_ocupado_id` bigint(20) UNSIGNED GENERATED ALWAYS AS (case when `status` = 'cancelada' then NULL else `horario_id` end) STORED
) ;

--
-- Despejando dados para a tabela `consultas`
--

INSERT INTO `consultas` (`id`, `horario_id`, `psicologo_id`, `paciente_id`, `consulta_origem_id`, `valor`, `modalidade`, `status`, `motivo_cancelamento`, `cancelada_em`, `iniciada_em`, `encerrada_em`, `criado_em`, `atualizado_em`) VALUES
(9, 14, 21, 27, NULL, 90.00, 'online', 'cancelada', 'vou ter um compromisso', '2026-09-25 00:55:42', NULL, NULL, '2026-09-25 00:49:24', '2026-09-25 00:55:42'),
(10, 18, 21, 27, NULL, 90.00, 'online', 'aguardando_pagamento', NULL, NULL, NULL, NULL, '2026-09-25 17:18:41', '2026-09-25 17:18:41'),
(26, 21, 21, 27, NULL, 90.00, 'online', 'confirmada', NULL, NULL, NULL, NULL, '2026-10-02 18:17:19', '2026-10-09 16:40:40'),
(60, 685, 87, 27, NULL, 120.00, 'online', 'concluida', NULL, NULL, '2026-09-18 14:00:00', '2026-09-18 14:50:00', '2026-09-15 13:00:00', '2026-10-09 16:57:10'),
(61, 686, 87, 27, NULL, 120.00, 'online', 'concluida', NULL, NULL, '2026-10-02 15:00:00', '2026-10-02 15:50:00', '2026-09-28 14:30:00', '2026-10-09 16:57:10'),
(62, 687, 88, 27, NULL, 135.00, 'online', 'confirmada', NULL, NULL, NULL, NULL, '2026-10-06 12:00:00', '2026-10-09 16:57:10'),
(63, 688, 89, 27, NULL, 110.00, 'online', 'aguardando_pagamento', NULL, NULL, NULL, NULL, '2026-10-09 13:00:00', '2026-10-09 16:57:10'),
(64, 689, 88, 82, NULL, 135.00, 'online', 'concluida', NULL, NULL, '2026-09-25 10:00:00', '2026-09-25 10:50:00', '2026-09-20 12:00:00', '2026-10-09 16:59:14'),
(65, 690, 91, 83, NULL, 125.00, 'online', 'confirmada', NULL, NULL, NULL, NULL, '2026-10-07 15:00:00', '2026-10-09 16:59:14'),
(66, 691, 90, 84, NULL, 145.00, 'online', 'aguardando_pagamento', NULL, NULL, NULL, NULL, '2026-10-09 11:00:00', '2026-10-09 16:59:14'),
(67, 692, 89, 85, NULL, 110.00, 'online', 'cancelada', 'Paciente solicitou o cancelamento da consulta.', '2026-10-05 14:30:00', NULL, NULL, '2026-10-01 13:00:00', '2026-10-09 16:59:14'),
(68, 693, 87, 86, NULL, 120.00, 'online', 'concluida', NULL, NULL, '2026-09-29 13:00:00', '2026-09-29 13:50:00', '2026-09-24 18:00:00', '2026-10-09 16:59:14'),
(69, 694, 87, 86, NULL, 120.00, 'online', 'confirmada', NULL, NULL, NULL, NULL, '2026-10-08 12:00:00', '2026-10-09 16:59:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `disponibilidades_semanais`
--

CREATE TABLE `disponibilidades_semanais` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `psicologo_id` bigint(20) UNSIGNED NOT NULL,
  `dia_semana` tinyint(3) UNSIGNED NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL,
  `ativa` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Despejando dados para a tabela `disponibilidades_semanais`
--

INSERT INTO `disponibilidades_semanais` (`id`, `psicologo_id`, `dia_semana`, `hora_inicio`, `hora_fim`, `ativa`, `criado_em`) VALUES
(3, 21, 5, '12:30:00', '15:00:00', 1, '2026-09-25 00:46:14'),
(21, 21, 1, '14:00:00', '18:30:00', 1, '2026-10-09 16:47:24'),
(22, 21, 3, '15:00:00', '17:30:00', 1, '2026-10-09 16:47:39'),
(23, 87, 1, '13:00:00', '18:00:00', 1, '2026-10-09 17:00:42'),
(24, 87, 3, '13:00:00', '18:00:00', 1, '2026-10-09 17:00:42'),
(25, 88, 2, '09:00:00', '14:00:00', 1, '2026-10-09 17:00:42'),
(26, 88, 4, '09:00:00', '14:00:00', 1, '2026-10-09 17:00:42'),
(27, 89, 1, '16:00:00', '21:00:00', 1, '2026-10-09 17:00:42'),
(28, 89, 5, '16:00:00', '21:00:00', 1, '2026-10-09 17:00:42'),
(29, 90, 3, '14:00:00', '19:00:00', 1, '2026-10-09 17:00:42'),
(30, 90, 4, '14:00:00', '19:00:00', 1, '2026-10-09 17:00:42'),
(31, 91, 2, '10:00:00', '15:00:00', 1, '2026-10-09 17:00:42'),
(32, 91, 5, '10:00:00', '15:00:00', 1, '2026-10-09 17:00:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `especialidades`
--

CREATE TABLE `especialidades` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `especialidades`
--

INSERT INTO `especialidades` (`id`, `nome`) VALUES
(1, 'Ansiedade'),
(2, 'Autoconhecimento'),
(4, 'Estresse'),
(3, 'Relacionamentos');

-- --------------------------------------------------------

--
-- Estrutura para tabela `favoritos`
--

CREATE TABLE `favoritos` (
  `paciente_id` bigint(20) UNSIGNED NOT NULL,
  `psicologo_id` bigint(20) UNSIGNED NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `favoritos`
--

INSERT INTO `favoritos` (`paciente_id`, `psicologo_id`, `criado_em`) VALUES
(27, 87, '2026-10-09 17:01:35'),
(27, 88, '2026-10-09 17:01:35'),
(27, 89, '2026-10-09 17:01:35'),
(27, 91, '2026-10-09 17:01:35');

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_status_consultas`
--

CREATE TABLE `historico_status_consultas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `consulta_id` bigint(20) UNSIGNED NOT NULL,
  `status_anterior` varchar(30) DEFAULT NULL,
  `status_novo` varchar(30) NOT NULL,
  `alterado_por_usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `motivo` varchar(500) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `historico_status_consultas`
--

INSERT INTO `historico_status_consultas` (`id`, `consulta_id`, `status_anterior`, `status_novo`, `alterado_por_usuario_id`, `motivo`, `criado_em`) VALUES
(1, 9, NULL, 'aguardando_pagamento', 27, 'Agendamento criado.', '2026-09-25 00:49:24'),
(2, 9, 'aguardando_pagamento', 'cancelada', 27, 'Cancelamento solicitado.', '2026-09-25 00:55:42'),
(3, 10, NULL, 'aguardando_pagamento', 27, 'Agendamento criado.', '2026-09-25 17:18:41'),
(15, 26, NULL, 'aguardando_pagamento', 27, 'Agendamento criado.', '2026-10-02 18:17:19'),
(54, 26, 'aguardando_pagamento', 'confirmada', 27, 'Pagamento simulado aprovado (projeto acadêmico).', '2026-10-09 16:40:40'),
(55, 60, NULL, 'aguardando_pagamento', 27, 'Consulta agendada pelo paciente.', '2026-09-15 13:00:00'),
(56, 60, 'aguardando_pagamento', 'confirmada', 27, 'Pagamento confirmado.', '2026-09-15 13:05:00'),
(57, 60, 'confirmada', 'concluida', 87, 'Consulta finalizada.', '2026-09-18 17:50:00'),
(58, 61, 'confirmada', 'concluida', 87, 'Consulta finalizada.', '2026-10-02 18:50:00'),
(59, 62, 'aguardando_pagamento', 'confirmada', 27, 'Pagamento aprovado.', '2026-10-06 12:10:00'),
(60, 63, NULL, 'aguardando_pagamento', 27, 'Consulta reservada e aguardando pagamento.', '2026-10-09 13:00:00'),
(61, 67, 'confirmada', 'cancelada', 85, 'Cancelamento solicitado pelo paciente.', '2026-10-05 17:30:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `horarios_agenda`
--

CREATE TABLE `horarios_agenda` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `psicologo_id` bigint(20) UNSIGNED NOT NULL,
  `inicio_em` datetime NOT NULL,
  `fim_em` datetime NOT NULL,
  `status` enum('livre','reservado','bloqueado') NOT NULL DEFAULT 'livre',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Despejando dados para a tabela `horarios_agenda`
--

INSERT INTO `horarios_agenda` (`id`, `psicologo_id`, `inicio_em`, `fim_em`, `status`, `criado_em`, `atualizado_em`) VALUES
(13, 21, '2026-09-25 15:30:00', '2026-09-25 16:20:00', 'livre', '2026-09-25 00:46:14', '2026-09-25 00:55:42'),
(14, 21, '2026-09-25 16:20:00', '2026-09-25 17:10:00', 'livre', '2026-09-25 00:46:14', '2026-09-25 00:55:42'),
(15, 21, '2026-09-25 17:10:00', '2026-09-25 18:00:00', 'livre', '2026-09-25 00:46:14', '2026-09-25 00:55:42'),
(16, 21, '2026-10-02 15:30:00', '2026-10-02 16:20:00', 'livre', '2026-09-25 00:46:14', '2026-09-25 00:55:42'),
(17, 21, '2026-10-02 16:20:00', '2026-10-02 17:10:00', 'livre', '2026-09-25 00:46:14', '2026-09-25 00:55:42'),
(18, 21, '2026-10-02 17:10:00', '2026-10-02 18:00:00', 'reservado', '2026-09-25 00:46:14', '2026-09-25 17:18:41'),
(19, 21, '2026-10-09 15:30:00', '2026-10-09 16:20:00', 'livre', '2026-09-25 00:46:14', '2026-09-25 00:55:42'),
(20, 21, '2026-10-09 16:20:00', '2026-10-09 17:10:00', 'livre', '2026-09-25 00:46:14', '2026-09-25 00:55:42'),
(21, 21, '2026-10-09 17:10:00', '2026-10-09 18:00:00', 'reservado', '2026-09-25 00:46:14', '2026-10-02 18:17:19'),
(22, 21, '2026-10-16 15:30:00', '2026-10-16 16:20:00', 'livre', '2026-09-25 00:46:14', '2026-10-09 16:47:39'),
(23, 21, '2026-10-16 16:20:00', '2026-10-16 17:10:00', 'livre', '2026-09-25 00:46:14', '2026-10-09 16:47:39'),
(24, 21, '2026-10-16 17:10:00', '2026-10-16 18:00:00', 'livre', '2026-09-25 00:46:14', '2026-10-09 16:47:39'),
(25, 21, '2026-10-23 15:30:00', '2026-10-23 16:20:00', 'livre', '2026-09-25 00:46:14', '2026-10-09 16:47:39'),
(26, 21, '2026-10-23 16:20:00', '2026-10-23 17:10:00', 'livre', '2026-09-25 00:46:14', '2026-10-09 16:47:39'),
(27, 21, '2026-10-23 17:10:00', '2026-10-23 18:00:00', 'livre', '2026-09-25 00:46:14', '2026-10-09 16:47:39'),
(28, 21, '2026-10-30 15:30:00', '2026-10-30 16:20:00', 'livre', '2026-09-25 00:46:14', '2026-10-09 16:47:39'),
(29, 21, '2026-10-30 16:20:00', '2026-10-30 17:10:00', 'livre', '2026-09-25 00:46:14', '2026-10-09 16:47:39'),
(30, 21, '2026-10-30 17:10:00', '2026-10-30 18:00:00', 'livre', '2026-09-25 00:46:14', '2026-10-09 16:47:39'),
(31, 21, '2026-11-06 15:30:00', '2026-11-06 16:20:00', 'livre', '2026-09-25 00:46:14', '2026-10-09 16:47:39'),
(32, 21, '2026-11-06 16:20:00', '2026-11-06 17:10:00', 'livre', '2026-09-25 00:46:14', '2026-10-09 16:47:39'),
(33, 21, '2026-11-06 17:10:00', '2026-11-06 18:00:00', 'livre', '2026-09-25 00:46:14', '2026-10-09 16:47:39'),
(34, 21, '2026-11-13 15:30:00', '2026-11-13 16:20:00', 'livre', '2026-09-25 00:46:14', '2026-10-09 16:47:39'),
(35, 21, '2026-11-13 16:20:00', '2026-11-13 17:10:00', 'livre', '2026-09-25 00:46:14', '2026-10-09 16:47:39'),
(36, 21, '2026-11-13 17:10:00', '2026-11-13 18:00:00', 'livre', '2026-09-25 00:46:14', '2026-10-09 16:47:39'),
(37, 21, '2026-11-20 15:30:00', '2026-11-20 16:20:00', 'livre', '2026-09-25 00:46:14', '2026-10-09 16:47:39'),
(38, 21, '2026-11-20 16:20:00', '2026-11-20 17:10:00', 'livre', '2026-09-25 00:46:14', '2026-10-09 16:47:39'),
(39, 21, '2026-11-20 17:10:00', '2026-11-20 18:00:00', 'livre', '2026-09-25 00:46:14', '2026-10-09 16:47:39'),
(610, 21, '2026-10-12 17:00:00', '2026-10-12 17:50:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(611, 21, '2026-10-12 17:50:00', '2026-10-12 18:40:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(612, 21, '2026-10-12 18:40:00', '2026-10-12 19:30:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(613, 21, '2026-10-12 19:30:00', '2026-10-12 20:20:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(614, 21, '2026-10-12 20:20:00', '2026-10-12 21:10:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(615, 21, '2026-10-19 17:00:00', '2026-10-19 17:50:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(616, 21, '2026-10-19 17:50:00', '2026-10-19 18:40:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(617, 21, '2026-10-19 18:40:00', '2026-10-19 19:30:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(618, 21, '2026-10-19 19:30:00', '2026-10-19 20:20:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(619, 21, '2026-10-19 20:20:00', '2026-10-19 21:10:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(620, 21, '2026-10-26 17:00:00', '2026-10-26 17:50:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(621, 21, '2026-10-26 17:50:00', '2026-10-26 18:40:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(622, 21, '2026-10-26 18:40:00', '2026-10-26 19:30:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(623, 21, '2026-10-26 19:30:00', '2026-10-26 20:20:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(624, 21, '2026-10-26 20:20:00', '2026-10-26 21:10:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(625, 21, '2026-11-02 17:00:00', '2026-11-02 17:50:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(626, 21, '2026-11-02 17:50:00', '2026-11-02 18:40:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(627, 21, '2026-11-02 18:40:00', '2026-11-02 19:30:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(628, 21, '2026-11-02 19:30:00', '2026-11-02 20:20:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(629, 21, '2026-11-02 20:20:00', '2026-11-02 21:10:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(630, 21, '2026-11-09 17:00:00', '2026-11-09 17:50:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(631, 21, '2026-11-09 17:50:00', '2026-11-09 18:40:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(632, 21, '2026-11-09 18:40:00', '2026-11-09 19:30:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(633, 21, '2026-11-09 19:30:00', '2026-11-09 20:20:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(634, 21, '2026-11-09 20:20:00', '2026-11-09 21:10:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(635, 21, '2026-11-16 17:00:00', '2026-11-16 17:50:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(636, 21, '2026-11-16 17:50:00', '2026-11-16 18:40:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(637, 21, '2026-11-16 18:40:00', '2026-11-16 19:30:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(638, 21, '2026-11-16 19:30:00', '2026-11-16 20:20:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(639, 21, '2026-11-16 20:20:00', '2026-11-16 21:10:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(640, 21, '2026-11-23 17:00:00', '2026-11-23 17:50:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(641, 21, '2026-11-23 17:50:00', '2026-11-23 18:40:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(642, 21, '2026-11-23 18:40:00', '2026-11-23 19:30:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(643, 21, '2026-11-23 19:30:00', '2026-11-23 20:20:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(644, 21, '2026-11-23 20:20:00', '2026-11-23 21:10:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(645, 21, '2026-11-27 15:30:00', '2026-11-27 16:20:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(646, 21, '2026-11-27 16:20:00', '2026-11-27 17:10:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(647, 21, '2026-11-27 17:10:00', '2026-11-27 18:00:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(648, 21, '2026-11-30 17:00:00', '2026-11-30 17:50:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(649, 21, '2026-11-30 17:50:00', '2026-11-30 18:40:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(650, 21, '2026-11-30 18:40:00', '2026-11-30 19:30:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(651, 21, '2026-11-30 19:30:00', '2026-11-30 20:20:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(652, 21, '2026-11-30 20:20:00', '2026-11-30 21:10:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(653, 21, '2026-12-04 15:30:00', '2026-12-04 16:20:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(654, 21, '2026-12-04 16:20:00', '2026-12-04 17:10:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(655, 21, '2026-12-04 17:10:00', '2026-12-04 18:00:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(656, 21, '2026-12-07 17:00:00', '2026-12-07 17:50:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(657, 21, '2026-12-07 17:50:00', '2026-12-07 18:40:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(658, 21, '2026-12-07 18:40:00', '2026-12-07 19:30:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(659, 21, '2026-12-07 19:30:00', '2026-12-07 20:20:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(660, 21, '2026-12-07 20:20:00', '2026-12-07 21:10:00', 'livre', '2026-10-09 16:47:24', '2026-10-09 16:47:39'),
(661, 21, '2026-10-14 18:00:00', '2026-10-14 18:50:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(662, 21, '2026-10-14 18:50:00', '2026-10-14 19:40:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(663, 21, '2026-10-14 19:40:00', '2026-10-14 20:30:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(664, 21, '2026-10-21 18:00:00', '2026-10-21 18:50:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(665, 21, '2026-10-21 18:50:00', '2026-10-21 19:40:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(666, 21, '2026-10-21 19:40:00', '2026-10-21 20:30:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(667, 21, '2026-10-28 18:00:00', '2026-10-28 18:50:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(668, 21, '2026-10-28 18:50:00', '2026-10-28 19:40:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(669, 21, '2026-10-28 19:40:00', '2026-10-28 20:30:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(670, 21, '2026-11-04 18:00:00', '2026-11-04 18:50:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(671, 21, '2026-11-04 18:50:00', '2026-11-04 19:40:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(672, 21, '2026-11-04 19:40:00', '2026-11-04 20:30:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(673, 21, '2026-11-11 18:00:00', '2026-11-11 18:50:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(674, 21, '2026-11-11 18:50:00', '2026-11-11 19:40:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(675, 21, '2026-11-11 19:40:00', '2026-11-11 20:30:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(676, 21, '2026-11-18 18:00:00', '2026-11-18 18:50:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(677, 21, '2026-11-18 18:50:00', '2026-11-18 19:40:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(678, 21, '2026-11-18 19:40:00', '2026-11-18 20:30:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(679, 21, '2026-11-25 18:00:00', '2026-11-25 18:50:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(680, 21, '2026-11-25 18:50:00', '2026-11-25 19:40:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(681, 21, '2026-11-25 19:40:00', '2026-11-25 20:30:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(682, 21, '2026-12-02 18:00:00', '2026-12-02 18:50:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(683, 21, '2026-12-02 18:50:00', '2026-12-02 19:40:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(684, 21, '2026-12-02 19:40:00', '2026-12-02 20:30:00', 'livre', '2026-10-09 16:47:39', '2026-10-09 16:47:39'),
(685, 87, '2026-09-18 14:00:00', '2026-09-18 14:50:00', 'reservado', '2026-10-09 16:57:10', '2026-10-09 16:57:10'),
(686, 87, '2026-10-02 15:00:00', '2026-10-02 15:50:00', 'reservado', '2026-10-09 16:57:10', '2026-10-09 16:57:10'),
(687, 88, '2026-10-15 18:00:00', '2026-10-15 18:50:00', 'reservado', '2026-10-09 16:57:10', '2026-10-09 16:57:10'),
(688, 89, '2026-10-20 19:00:00', '2026-10-20 19:50:00', 'reservado', '2026-10-09 16:57:10', '2026-10-09 16:57:10'),
(689, 88, '2026-09-25 10:00:00', '2026-09-25 10:50:00', 'reservado', '2026-10-09 16:59:14', '2026-10-09 16:59:14'),
(690, 91, '2026-10-16 14:00:00', '2026-10-16 14:50:00', 'reservado', '2026-10-09 16:59:14', '2026-10-09 16:59:14'),
(691, 90, '2026-10-21 17:00:00', '2026-10-21 17:50:00', 'reservado', '2026-10-09 16:59:14', '2026-10-09 16:59:14'),
(692, 89, '2026-10-06 16:00:00', '2026-10-06 16:50:00', 'livre', '2026-10-09 16:59:14', '2026-10-09 16:59:14'),
(693, 87, '2026-09-29 13:00:00', '2026-09-29 13:50:00', 'reservado', '2026-10-09 16:59:14', '2026-10-09 16:59:14'),
(694, 87, '2026-10-22 13:00:00', '2026-10-22 13:50:00', 'reservado', '2026-10-09 16:59:14', '2026-10-09 16:59:14'),
(695, 87, '2026-10-12 13:00:00', '2026-10-12 13:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(696, 87, '2026-10-12 14:00:00', '2026-10-12 14:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(697, 87, '2026-10-14 15:00:00', '2026-10-14 15:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(698, 87, '2026-10-19 13:00:00', '2026-10-19 13:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(699, 87, '2026-10-21 16:00:00', '2026-10-21 16:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(700, 88, '2026-10-13 09:00:00', '2026-10-13 09:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(701, 88, '2026-10-13 11:00:00', '2026-10-13 11:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(702, 88, '2026-10-15 10:00:00', '2026-10-15 10:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(703, 88, '2026-10-20 09:00:00', '2026-10-20 09:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(704, 88, '2026-10-22 13:00:00', '2026-10-22 13:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(705, 89, '2026-10-12 16:00:00', '2026-10-12 16:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(706, 89, '2026-10-12 18:00:00', '2026-10-12 18:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(707, 89, '2026-10-16 17:00:00', '2026-10-16 17:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(708, 89, '2026-10-19 19:00:00', '2026-10-19 19:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(709, 89, '2026-10-23 20:00:00', '2026-10-23 20:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(710, 90, '2026-10-14 14:00:00', '2026-10-14 14:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(711, 90, '2026-10-14 16:00:00', '2026-10-14 16:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(712, 90, '2026-10-15 15:00:00', '2026-10-15 15:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(713, 90, '2026-10-21 18:00:00', '2026-10-21 18:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(714, 90, '2026-10-22 16:00:00', '2026-10-22 16:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(715, 91, '2026-10-13 10:00:00', '2026-10-13 10:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(716, 91, '2026-10-13 12:00:00', '2026-10-13 12:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(717, 91, '2026-10-16 10:00:00', '2026-10-16 10:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(718, 91, '2026-10-20 14:00:00', '2026-10-20 14:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42'),
(719, 91, '2026-10-23 11:00:00', '2026-10-23 11:50:00', 'livre', '2026-10-09 17:00:42', '2026-10-09 17:00:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `metodos_pagamento`
--

CREATE TABLE `metodos_pagamento` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `paciente_id` bigint(20) UNSIGNED NOT NULL,
  `provedor` varchar(50) NOT NULL,
  `referencia_token` varchar(190) NOT NULL,
  `bandeira` varchar(30) DEFAULT NULL,
  `ultimos_quatro` char(4) DEFAULT NULL,
  `mes_validade` tinyint(3) UNSIGNED DEFAULT NULL,
  `ano_validade` smallint(5) UNSIGNED DEFAULT NULL,
  `preferencial` tinyint(1) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `consulta_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tipo` enum('consulta','pagamento','conta','sistema') NOT NULL DEFAULT 'sistema',
  `titulo` varchar(150) NOT NULL,
  `mensagem` text NOT NULL,
  `lida_em` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `notificacoes`
--

INSERT INTO `notificacoes` (`id`, `usuario_id`, `consulta_id`, `tipo`, `titulo`, `mensagem`, `lida_em`, `criado_em`) VALUES
(10, 27, 9, 'consulta', 'Novo agendamento', 'Uma consulta foi agendada e aguarda confirmação financeira.', '2026-09-25 00:49:46', '2026-09-25 00:49:24'),
(11, 21, 9, 'consulta', 'Novo agendamento', 'Uma consulta foi agendada e aguarda confirmação financeira.', '2026-09-25 00:52:06', '2026-09-25 00:49:24'),
(12, 27, 9, 'consulta', 'Consulta cancelada', 'A consulta foi cancelada. Valores pagos dependem de processamento de reembolso.', NULL, '2026-09-25 00:55:42'),
(13, 21, 9, 'consulta', 'Consulta cancelada', 'A consulta foi cancelada. Valores pagos dependem de processamento de reembolso.', '2026-09-25 18:20:54', '2026-09-25 00:55:42'),
(14, 27, 10, 'consulta', 'Novo agendamento', 'Uma consulta foi agendada e aguarda confirmação financeira.', NULL, '2026-09-25 17:18:41'),
(15, 21, 10, 'consulta', 'Novo agendamento', 'Uma consulta foi agendada e aguarda confirmação financeira.', '2026-09-25 18:21:00', '2026-09-25 17:18:41'),
(51, 27, 26, 'consulta', 'Novo agendamento', 'Uma consulta foi agendada e aguarda confirmação financeira.', NULL, '2026-10-02 18:17:19'),
(52, 21, 26, 'consulta', 'Novo agendamento', 'Uma consulta foi agendada e aguarda confirmação financeira.', NULL, '2026-10-02 18:17:19'),
(142, 27, 26, 'consulta', 'Pagamento simulado aprovado', 'O pagamento simulado foi registrado e a consulta está confirmada.', NULL, '2026-10-09 16:40:40'),
(143, 21, 26, 'consulta', 'Pagamento simulado aprovado', 'O pagamento simulado foi registrado e a consulta está confirmada.', NULL, '2026-10-09 16:40:40'),
(144, 27, 62, 'consulta', 'Consulta confirmada', 'Sua próxima consulta foi confirmada com sucesso.', NULL, '2026-10-06 12:11:00'),
(145, 27, 63, 'pagamento', 'Pagamento pendente', 'Existe uma consulta aguardando confirmação de pagamento.', NULL, '2026-10-09 13:02:00'),
(146, 83, 65, 'consulta', 'Consulta confirmada', 'Sua consulta foi confirmada e já está disponível em Minhas consultas.', NULL, '2026-10-07 15:06:00'),
(147, 84, 66, 'pagamento', 'Pagamento pendente', 'Finalize o pagamento para confirmar sua consulta.', NULL, '2026-10-09 11:02:00'),
(148, 86, 69, 'consulta', 'Próxima consulta', 'Você possui uma nova consulta confirmada.', NULL, '2026-10-08 12:06:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pacientes`
--

CREATE TABLE `pacientes` (
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `data_nascimento` date DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `pacientes`
--

INSERT INTO `pacientes` (`usuario_id`, `data_nascimento`, `criado_em`) VALUES
(6, NULL, '2026-09-24 23:28:20'),
(20, NULL, '2026-09-24 23:56:26'),
(27, '2008-06-12', '2026-09-25 00:48:32'),
(82, '2002-03-14', '2026-10-09 16:54:59'),
(83, '1998-08-22', '2026-10-09 16:54:59'),
(84, '2001-11-05', '2026-10-09 16:54:59'),
(85, '1996-06-17', '2026-10-09 16:54:59'),
(86, '2003-01-30', '2026-10-09 16:54:59');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `consulta_id` bigint(20) UNSIGNED NOT NULL,
  `metodo_pagamento_id` bigint(20) UNSIGNED DEFAULT NULL,
  `provedor` varchar(50) DEFAULT NULL,
  `referencia_externa` varchar(190) DEFAULT NULL,
  `metodo` enum('pix','cartao') NOT NULL,
  `status` enum('pendente','processando','aprovado','recusado','cancelado','estornado') NOT NULL DEFAULT 'pendente',
  `valor` decimal(10,2) NOT NULL,
  `moeda` char(3) NOT NULL DEFAULT 'BRL',
  `vencimento_em` datetime DEFAULT NULL,
  `pago_em` datetime DEFAULT NULL,
  `recibo_url` varchar(500) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Despejando dados para a tabela `pagamentos`
--

INSERT INTO `pagamentos` (`id`, `consulta_id`, `metodo_pagamento_id`, `provedor`, `referencia_externa`, `metodo`, `status`, `valor`, `moeda`, `vencimento_em`, `pago_em`, `recibo_url`, `criado_em`, `atualizado_em`) VALUES
(24, 26, NULL, 'simulacao_academica', 'f874a9d8fb839135763089f5ab56f29009d9c9b817e855fc', 'pix', 'aprovado', 90.00, 'BRL', NULL, '2026-10-09 16:40:40', NULL, '2026-10-09 16:40:40', '2026-10-09 16:40:40'),
(25, 60, NULL, 'simulado', 'DEMO-MARY-60', 'pix', 'aprovado', 120.00, 'BRL', NULL, '2026-09-15 10:05:00', NULL, '2026-09-15 13:04:00', '2026-10-09 16:57:10'),
(26, 61, NULL, 'simulado', 'DEMO-MARY-61', 'cartao', 'aprovado', 120.00, 'BRL', NULL, '2026-09-28 11:35:00', NULL, '2026-09-28 14:34:00', '2026-10-09 16:57:10'),
(27, 62, NULL, 'simulado', 'DEMO-MARY-62', 'pix', 'aprovado', 135.00, 'BRL', NULL, '2026-10-06 09:10:00', NULL, '2026-10-06 12:09:00', '2026-10-09 16:57:10'),
(28, 63, NULL, 'simulado', 'PENDENTE-MARY-63', 'pix', 'pendente', 110.00, 'BRL', '2026-10-20 18:30:00', NULL, NULL, '2026-10-09 13:01:00', '2026-10-09 16:57:10'),
(29, 64, NULL, 'simulado', 'DEMO-ANA-64', 'pix', 'aprovado', 135.00, 'BRL', NULL, '2026-09-20 09:10:00', NULL, '2026-09-20 12:09:00', '2026-10-09 16:59:14'),
(30, 65, NULL, 'simulado', 'DEMO-BRUNO-65', 'cartao', 'aprovado', 125.00, 'BRL', NULL, '2026-10-07 12:05:00', NULL, '2026-10-07 15:04:00', '2026-10-09 16:59:14'),
(31, 66, NULL, 'simulado', 'PENDENTE-CAROLINA-66', 'pix', 'pendente', 145.00, 'BRL', '2026-10-21 16:30:00', NULL, NULL, '2026-10-09 11:01:00', '2026-10-09 16:59:14'),
(32, 68, NULL, 'simulado', 'DEMO-EDUARDA-68', 'pix', 'aprovado', 120.00, 'BRL', NULL, '2026-09-24 15:05:00', NULL, '2026-09-24 18:04:00', '2026-10-09 16:59:14'),
(33, 69, NULL, 'simulado', 'DEMO-EDUARDA-FUTURA-69', 'cartao', 'aprovado', 120.00, 'BRL', NULL, '2026-10-08 09:05:00', NULL, '2026-10-08 12:04:00', '2026-10-09 16:59:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `prontuarios`
--

CREATE TABLE `prontuarios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `paciente_id` bigint(20) UNSIGNED NOT NULL,
  `psicologo_id` bigint(20) UNSIGNED NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `prontuarios`
--

INSERT INTO `prontuarios` (`id`, `paciente_id`, `psicologo_id`, `criado_em`, `atualizado_em`) VALUES
(15, 27, 87, '2026-10-09 16:57:10', '2026-10-09 16:57:10'),
(16, 82, 88, '2026-10-09 16:59:14', '2026-10-09 16:59:14'),
(17, 86, 87, '2026-10-09 16:59:14', '2026-10-09 16:59:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `psicologos`
--

CREATE TABLE `psicologos` (
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `crp` varchar(24) NOT NULL,
  `nome_profissional` varchar(150) DEFAULT NULL,
  `area_atuacao` varchar(150) DEFAULT NULL,
  `biografia` text DEFAULT NULL,
  `abordagem` text DEFAULT NULL,
  `valor_consulta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `duracao_padrao_minutos` smallint(5) UNSIGNED NOT NULL DEFAULT 50,
  `status_verificacao` enum('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'pendente',
  `verificado_em` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Despejando dados para a tabela `psicologos`
--

INSERT INTO `psicologos` (`usuario_id`, `crp`, `nome_profissional`, `area_atuacao`, `biografia`, `abordagem`, `valor_consulta`, `duracao_padrao_minutos`, `status_verificacao`, `verificado_em`, `criado_em`, `atualizado_em`) VALUES
(21, '55/87956', 'Nina Psi', 'Psicologia Infantil', 'Psicologa voltada para o atendimento infantil.', 'Abordagem simples', 90.00, 50, 'aprovado', '2026-09-24 21:00:51', '2026-09-24 23:58:20', '2026-09-25 00:45:04'),
(87, '06/TESTE001', 'Dra. Camila Rocha', 'Ansiedade e Autoconhecimento', 'Psicóloga com atuação voltada ao acolhimento emocional e desenvolvimento pessoal.', 'Terapia Cognitivo-Comportamental', 120.00, 50, 'aprovado', '2026-10-09 13:54:59', '2026-10-09 16:54:59', '2026-10-09 16:54:59'),
(88, '06/TESTE002', 'Dr. Rafael Mendes', 'Estresse e Qualidade de Vida', 'Atendimento focado em manejo do estresse, rotina e qualidade de vida.', 'Terapia Cognitivo-Comportamental', 135.00, 50, 'aprovado', '2026-10-09 13:54:59', '2026-10-09 16:54:59', '2026-10-09 16:54:59'),
(89, '06/TESTE003', 'Dra. Juliana Costa', 'Relacionamentos', 'Atuação em questões emocionais, autoestima e relacionamentos.', 'Abordagem Humanista', 110.00, 50, 'aprovado', '2026-10-09 13:54:59', '2026-10-09 16:54:59', '2026-10-09 16:54:59'),
(90, '06/TESTE004', 'Dr. Lucas Andrade', 'Ansiedade e Estresse', 'Atendimento para adultos com foco em ansiedade, organização emocional e estresse.', 'Terapia Comportamental', 145.00, 50, 'aprovado', '2026-10-09 13:54:59', '2026-10-09 16:54:59', '2026-10-09 16:54:59'),
(91, '06/TESTE005', 'Dra. Fernanda Ribeiro', 'Autoconhecimento e Relacionamentos', 'Atuação focada no desenvolvimento pessoal e fortalecimento emocional.', 'Abordagem Integrativa', 125.00, 50, 'aprovado', '2026-10-09 13:54:59', '2026-10-09 16:54:59', '2026-10-09 16:54:59');

--
-- Acionadores `psicologos`
--
DELIMITER $$
CREATE TRIGGER `mindly_aviso_verificacao` AFTER UPDATE ON `psicologos` FOR EACH ROW BEGIN
    IF OLD.status_verificacao <> NEW.status_verificacao THEN
        INSERT INTO notificacoes(usuario_id,tipo,titulo,mensagem)
        VALUES(NEW.usuario_id,'conta','Verifica├º├úo profissional atualizada',CONCAT('O status da verifica├º├úo foi alterado para: ',NEW.status_verificacao,'.'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `psicologo_especialidades`
--

CREATE TABLE `psicologo_especialidades` (
  `psicologo_id` bigint(20) UNSIGNED NOT NULL,
  `especialidade_id` smallint(5) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `psicologo_especialidades`
--

INSERT INTO `psicologo_especialidades` (`psicologo_id`, `especialidade_id`) VALUES
(21, 1),
(21, 4),
(87, 1),
(87, 2),
(88, 2),
(88, 4),
(89, 2),
(89, 3),
(90, 1),
(90, 4),
(91, 2),
(91, 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `reembolsos`
--

CREATE TABLE `reembolsos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pagamento_id` bigint(20) UNSIGNED NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `status` enum('solicitado','processando','concluido','recusado') NOT NULL DEFAULT 'solicitado',
  `referencia_externa` varchar(190) DEFAULT NULL,
  `motivo` varchar(500) DEFAULT NULL,
  `concluido_em` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `registros_prontuario`
--

CREATE TABLE `registros_prontuario` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `prontuario_id` bigint(20) UNSIGNED NOT NULL,
  `consulta_id` bigint(20) UNSIGNED NOT NULL,
  `observacoes` text NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `registros_prontuario`
--

INSERT INTO `registros_prontuario` (`id`, `prontuario_id`, `consulta_id`, `observacoes`, `criado_em`, `atualizado_em`) VALUES
(15, 15, 60, 'Registro interno de demonstração referente ao primeiro atendimento. Conteúdo restrito ao profissional.', '2026-09-18 17:55:00', '2026-10-09 16:57:10'),
(16, 15, 61, 'Registro interno de demonstração referente ao acompanhamento da paciente. Conteúdo restrito ao profissional.', '2026-10-02 18:55:00', '2026-10-09 16:57:10'),
(17, 16, 64, 'Registro interno referente ao atendimento da paciente.', '2026-09-25 14:00:00', '2026-10-09 16:59:14'),
(18, 17, 68, 'Registro interno de acompanhamento da consulta.', '2026-09-29 17:00:00', '2026-10-09 16:59:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `resumos_compartilhados`
--

CREATE TABLE `resumos_compartilhados` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `consulta_id` bigint(20) UNSIGNED NOT NULL,
  `texto` text NOT NULL,
  `compartilhado_por_psicologo_id` bigint(20) UNSIGNED NOT NULL,
  `compartilhado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `resumos_compartilhados`
--

INSERT INTO `resumos_compartilhados` (`id`, `consulta_id`, `texto`, `compartilhado_por_psicologo_id`, `compartilhado_em`, `atualizado_em`) VALUES
(15, 60, 'Primeiro atendimento realizado. Foram definidos objetivos iniciais para acompanhamento e organização da rotina.', 87, '2026-09-18 18:00:00', '2026-10-09 16:57:10'),
(16, 61, 'Consulta de acompanhamento concluída. Foram revisados os objetivos definidos anteriormente e combinados os próximos passos.', 87, '2026-10-02 19:00:00', '2026-10-09 16:57:10'),
(17, 64, 'Atendimento realizado com foco na organização da rotina e redução do estresse.', 88, '2026-09-25 14:05:00', '2026-10-09 16:59:14'),
(18, 68, 'Consulta realizada com foco em autoconhecimento e identificação de situações de ansiedade no cotidiano.', 87, '2026-09-29 17:05:00', '2026-10-09 16:59:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `salas_atendimento`
--

CREATE TABLE `salas_atendimento` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `consulta_id` bigint(20) UNSIGNED NOT NULL,
  `codigo_sala` varchar(100) NOT NULL,
  `provedor` varchar(50) DEFAULT NULL,
  `referencia_externa` varchar(190) DEFAULT NULL,
  `status` enum('preparada','em_andamento','encerrada') NOT NULL DEFAULT 'preparada',
  `iniciada_em` datetime DEFAULT NULL,
  `encerrada_em` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tokens_login_persistente`
--

CREATE TABLE `tokens_login_persistente` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `seletor` char(32) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expira_em` datetime NOT NULL,
  `revogado_em` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tokens_redefinicao_senha`
--

CREATE TABLE `tokens_redefinicao_senha` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expira_em` datetime NOT NULL,
  `utilizado_em` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tokens_verificacao_email`
--

CREATE TABLE `tokens_verificacao_email` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expira_em` datetime NOT NULL,
  `utilizado_em` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `papel` enum('paciente','psicologo','admin') NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `status` enum('pendente','ativo','suspenso','inativo') NOT NULL DEFAULT 'ativo',
  `email_verificado_em` datetime DEFAULT NULL,
  `ultimo_acesso_em` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `papel`, `telefone`, `avatar_url`, `status`, `email_verificado_em`, `ultimo_acesso_em`, `criado_em`, `atualizado_em`) VALUES
(6, 'NENA', 'nine@gmail.com', '$2y$10$HelfrPXhPZSu2GsfyE8bOelrucR63E7QOHP72xRVhIliFYd.86lpW', 'paciente', NULL, NULL, 'ativo', NULL, NULL, '2026-09-24 23:28:20', '2026-09-24 23:28:20'),
(20, 'sasa', 'sasa@gmail.com', '$2y$10$l.8SJEWiMr6DOrwGlFdmXe6jXy4i5F6S46Yiac913kC4kV5VQ7/Ym', 'paciente', NULL, NULL, 'ativo', NULL, NULL, '2026-09-24 23:56:26', '2026-09-24 23:56:26'),
(21, 'Nina Psi', 'psinina@gmail.com', '$2y$10$rv2CeFkrd/yERe90Z7WOfuix55gt71jda1nAy8g8TF2C4wXsdd6KK', 'psicologo', '18996127814', NULL, 'ativo', NULL, '2026-10-09 16:46:50', '2026-09-24 23:58:20', '2026-10-09 16:46:50'),
(27, 'mary', 'mary@gmail.com', '$2y$10$2dSbmVEzUH9.9N2lRqgIKOTE4eyC7Lu2WUU/sa09Y2o.lOJWYoAye', 'paciente', '18996320648', NULL, 'ativo', NULL, '2026-10-09 17:02:08', '2026-09-25 00:48:32', '2026-10-09 17:02:08'),
(82, 'Ana Souza', 'ana.souza@mindlyteste.com', '$2y$12$ncLNANl/3KyXvgOlLOI2Xu9qUdwrMFCUH2UTIdy0DkJsw7qDisOjq', 'paciente', '18999110001', NULL, 'ativo', '2026-10-09 13:54:58', NULL, '2026-10-09 16:54:58', '2026-10-09 16:54:58'),
(83, 'Bruno Martins', 'bruno.martins@mindlyteste.com', '$2y$12$ncLNANl/3KyXvgOlLOI2Xu9qUdwrMFCUH2UTIdy0DkJsw7qDisOjq', 'paciente', '18999110002', NULL, 'ativo', '2026-10-09 13:54:58', NULL, '2026-10-09 16:54:58', '2026-10-09 16:54:58'),
(84, 'Carolina Alves', 'carolina.alves@mindlyteste.com', '$2y$12$ncLNANl/3KyXvgOlLOI2Xu9qUdwrMFCUH2UTIdy0DkJsw7qDisOjq', 'paciente', '18999110003', NULL, 'ativo', '2026-10-09 13:54:58', NULL, '2026-10-09 16:54:58', '2026-10-09 16:54:58'),
(85, 'Diego Ferreira', 'diego.ferreira@mindlyteste.com', '$2y$12$ncLNANl/3KyXvgOlLOI2Xu9qUdwrMFCUH2UTIdy0DkJsw7qDisOjq', 'paciente', '18999110004', NULL, 'ativo', '2026-10-09 13:54:58', NULL, '2026-10-09 16:54:58', '2026-10-09 16:54:58'),
(86, 'Eduarda Lima', 'eduarda.lima@mindlyteste.com', '$2y$12$ncLNANl/3KyXvgOlLOI2Xu9qUdwrMFCUH2UTIdy0DkJsw7qDisOjq', 'paciente', '18999110005', NULL, 'ativo', '2026-10-09 13:54:58', NULL, '2026-10-09 16:54:58', '2026-10-09 16:54:58'),
(87, 'Dra. Camila Rocha', 'camila.rocha@mindlyteste.com', '$2y$12$ncLNANl/3KyXvgOlLOI2Xu9qUdwrMFCUH2UTIdy0DkJsw7qDisOjq', 'psicologo', '18999220001', NULL, 'ativo', '2026-10-09 13:54:59', '2026-10-09 17:07:30', '2026-10-09 16:54:59', '2026-10-09 17:07:30'),
(88, 'Dr. Rafael Mendes', 'rafael.mendes@mindlyteste.com', '$2y$12$ncLNANl/3KyXvgOlLOI2Xu9qUdwrMFCUH2UTIdy0DkJsw7qDisOjq', 'psicologo', '18999220002', NULL, 'ativo', '2026-10-09 13:54:59', NULL, '2026-10-09 16:54:59', '2026-10-09 16:54:59'),
(89, 'Dra. Juliana Costa', 'juliana.costa@mindlyteste.com', '$2y$12$ncLNANl/3KyXvgOlLOI2Xu9qUdwrMFCUH2UTIdy0DkJsw7qDisOjq', 'psicologo', '18999220003', NULL, 'ativo', '2026-10-09 13:54:59', NULL, '2026-10-09 16:54:59', '2026-10-09 16:54:59'),
(90, 'Dr. Lucas Andrade', 'lucas.andrade@mindlyteste.com', '$2y$12$ncLNANl/3KyXvgOlLOI2Xu9qUdwrMFCUH2UTIdy0DkJsw7qDisOjq', 'psicologo', '18999220004', NULL, 'ativo', '2026-10-09 13:54:59', NULL, '2026-10-09 16:54:59', '2026-10-09 16:54:59'),
(91, 'Dra. Fernanda Ribeiro', 'fernanda.ribeiro@mindlyteste.com', '$2y$12$ncLNANl/3KyXvgOlLOI2Xu9qUdwrMFCUH2UTIdy0DkJsw7qDisOjq', 'psicologo', '18999220005', NULL, 'ativo', '2026-10-09 13:54:59', '2026-10-09 17:06:49', '2026-10-09 16:54:59', '2026-10-09 17:06:49');

--
-- Acionadores `usuarios`
--
DELIMITER $$
CREATE TRIGGER `mindly_aviso_status_conta` AFTER UPDATE ON `usuarios` FOR EACH ROW BEGIN
    IF NEW.papel = 'psicologo' AND OLD.status <> NEW.status THEN
        INSERT INTO notificacoes(usuario_id,tipo,titulo,mensagem)
        VALUES(NEW.id,'conta','Status da conta atualizado',CONCAT('O status da sua conta foi alterado para: ',NEW.status,'.'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `versoes_registros_prontuario`
--

CREATE TABLE `versoes_registros_prontuario` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `registro_id` bigint(20) UNSIGNED NOT NULL,
  `autor_id` bigint(20) UNSIGNED NOT NULL,
  `observacoes` text NOT NULL,
  `salvo_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `auditoria_administrativa`
--
ALTER TABLE `auditoria_administrativa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_auditoria_admin_data` (`admin_id`,`criado_em`),
  ADD KEY `idx_auditoria_alvo_data` (`usuario_alvo_id`,`criado_em`);

--
-- Índices de tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_avaliacao_consulta` (`consulta_id`),
  ADD KEY `idx_avaliacoes_psicologo` (`psicologo_id`,`publicada`),
  ADD KEY `fk_avaliacao_paciente` (`paciente_id`);

--
-- Índices de tabela `bloqueios_agenda`
--
ALTER TABLE `bloqueios_agenda`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bloqueios_periodo` (`psicologo_id`,`inicio_em`,`fim_em`);

--
-- Índices de tabela `consentimentos`
--
ALTER TABLE `consentimentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_consentimento_versao` (`usuario_id`,`tipo`,`versao`);

--
-- Índices de tabela `consultas`
--
ALTER TABLE `consultas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_consultas_horario_ocupado` (`horario_ocupado_id`),
  ADD KEY `idx_consultas_paciente_status` (`paciente_id`,`status`),
  ADD KEY `idx_consultas_psicologo_status` (`psicologo_id`,`status`),
  ADD KEY `idx_consultas_horario_psicologo` (`horario_id`,`psicologo_id`),
  ADD KEY `idx_consultas_origem` (`consulta_origem_id`);

--
-- Índices de tabela `disponibilidades_semanais`
--
ALTER TABLE `disponibilidades_semanais`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_disp_faixa` (`psicologo_id`,`dia_semana`,`hora_inicio`,`hora_fim`);

--
-- Índices de tabela `especialidades`
--
ALTER TABLE `especialidades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_especialidades_nome` (`nome`);

--
-- Índices de tabela `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`paciente_id`,`psicologo_id`),
  ADD KEY `idx_favoritos_psicologo` (`psicologo_id`);

--
-- Índices de tabela `historico_status_consultas`
--
ALTER TABLE `historico_status_consultas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_historico_consulta_data` (`consulta_id`,`criado_em`),
  ADD KEY `fk_historico_autor` (`alterado_por_usuario_id`);

--
-- Índices de tabela `horarios_agenda`
--
ALTER TABLE `horarios_agenda`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_horario_psicologo_inicio` (`psicologo_id`,`inicio_em`),
  ADD UNIQUE KEY `uk_horario_id_psicologo` (`id`,`psicologo_id`),
  ADD KEY `idx_horarios_busca` (`psicologo_id`,`status`,`inicio_em`);

--
-- Índices de tabela `metodos_pagamento`
--
ALTER TABLE `metodos_pagamento`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_metodo_token` (`provedor`,`referencia_token`),
  ADD KEY `idx_metodos_paciente` (`paciente_id`,`ativo`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notificacoes_usuario_lida` (`usuario_id`,`lida_em`,`criado_em`),
  ADD KEY `fk_notificacao_consulta` (`consulta_id`);

--
-- Índices de tabela `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`usuario_id`);

--
-- Índices de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pagamento_referencia_externa` (`provedor`,`referencia_externa`),
  ADD KEY `idx_pagamentos_consulta_status` (`consulta_id`,`status`),
  ADD KEY `idx_pagamentos_criacao` (`criado_em`),
  ADD KEY `fk_pagamento_metodo` (`metodo_pagamento_id`);

--
-- Índices de tabela `prontuarios`
--
ALTER TABLE `prontuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_prontuario_vinculo` (`paciente_id`,`psicologo_id`),
  ADD KEY `fk_prontuario_psicologo` (`psicologo_id`);

--
-- Índices de tabela `psicologos`
--
ALTER TABLE `psicologos`
  ADD PRIMARY KEY (`usuario_id`),
  ADD UNIQUE KEY `uk_psicologos_crp` (`crp`),
  ADD KEY `idx_psicologos_verificacao` (`status_verificacao`);

--
-- Índices de tabela `psicologo_especialidades`
--
ALTER TABLE `psicologo_especialidades`
  ADD PRIMARY KEY (`psicologo_id`,`especialidade_id`),
  ADD KEY `idx_pe_especialidade` (`especialidade_id`);

--
-- Índices de tabela `reembolsos`
--
ALTER TABLE `reembolsos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reembolsos_pagamento` (`pagamento_id`,`status`);

--
-- Índices de tabela `registros_prontuario`
--
ALTER TABLE `registros_prontuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_registro_consulta` (`consulta_id`),
  ADD KEY `idx_registros_prontuario_data` (`prontuario_id`,`criado_em`);

--
-- Índices de tabela `resumos_compartilhados`
--
ALTER TABLE `resumos_compartilhados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_resumo_consulta` (`consulta_id`),
  ADD KEY `fk_resumo_psicologo` (`compartilhado_por_psicologo_id`);

--
-- Índices de tabela `salas_atendimento`
--
ALTER TABLE `salas_atendimento`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_salas_consulta` (`consulta_id`),
  ADD UNIQUE KEY `uk_salas_codigo` (`codigo_sala`);

--
-- Índices de tabela `tokens_login_persistente`
--
ALTER TABLE `tokens_login_persistente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_login_seletor` (`seletor`),
  ADD KEY `idx_login_usuario_expiracao` (`usuario_id`,`expira_em`);

--
-- Índices de tabela `tokens_redefinicao_senha`
--
ALTER TABLE `tokens_redefinicao_senha`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_token_redefinicao` (`token_hash`),
  ADD KEY `idx_tokens_usuario_expiracao` (`usuario_id`,`expira_em`);

--
-- Índices de tabela `tokens_verificacao_email`
--
ALTER TABLE `tokens_verificacao_email`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_token_email` (`token_hash`),
  ADD KEY `idx_token_email_usuario` (`usuario_id`,`expira_em`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuarios_email` (`email`),
  ADD KEY `idx_usuarios_papel_status` (`papel`,`status`),
  ADD KEY `idx_usuarios_cadastro` (`criado_em`);

--
-- Índices de tabela `versoes_registros_prontuario`
--
ALTER TABLE `versoes_registros_prontuario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_versao_autor` (`autor_id`),
  ADD KEY `idx_versao_registro` (`registro_id`,`salvo_em`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `auditoria_administrativa`
--
ALTER TABLE `auditoria_administrativa`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `bloqueios_agenda`
--
ALTER TABLE `bloqueios_agenda`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `consentimentos`
--
ALTER TABLE `consentimentos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT de tabela `consultas`
--
ALTER TABLE `consultas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `disponibilidades_semanais`
--
ALTER TABLE `disponibilidades_semanais`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `especialidades`
--
ALTER TABLE `especialidades`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `historico_status_consultas`
--
ALTER TABLE `historico_status_consultas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT de tabela `horarios_agenda`
--
ALTER TABLE `horarios_agenda`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `metodos_pagamento`
--
ALTER TABLE `metodos_pagamento`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=149;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `prontuarios`
--
ALTER TABLE `prontuarios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `reembolsos`
--
ALTER TABLE `reembolsos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `registros_prontuario`
--
ALTER TABLE `registros_prontuario`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `resumos_compartilhados`
--
ALTER TABLE `resumos_compartilhados`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `salas_atendimento`
--
ALTER TABLE `salas_atendimento`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tokens_login_persistente`
--
ALTER TABLE `tokens_login_persistente`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tokens_redefinicao_senha`
--
ALTER TABLE `tokens_redefinicao_senha`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `tokens_verificacao_email`
--
ALTER TABLE `tokens_verificacao_email`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT de tabela `versoes_registros_prontuario`
--
ALTER TABLE `versoes_registros_prontuario`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `auditoria_administrativa`
--
ALTER TABLE `auditoria_administrativa`
  ADD CONSTRAINT `fk_auditoria_admin` FOREIGN KEY (`admin_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_auditoria_alvo` FOREIGN KEY (`usuario_alvo_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD CONSTRAINT `fk_avaliacao_consulta` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_avaliacao_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`usuario_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_avaliacao_psicologo` FOREIGN KEY (`psicologo_id`) REFERENCES `psicologos` (`usuario_id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `bloqueios_agenda`
--
ALTER TABLE `bloqueios_agenda`
  ADD CONSTRAINT `fk_bloqueios_psicologo` FOREIGN KEY (`psicologo_id`) REFERENCES `psicologos` (`usuario_id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `consentimentos`
--
ALTER TABLE `consentimentos`
  ADD CONSTRAINT `fk_consentimento_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `consultas`
--
ALTER TABLE `consultas`
  ADD CONSTRAINT `fk_consultas_horario_psicologo` FOREIGN KEY (`horario_id`,`psicologo_id`) REFERENCES `horarios_agenda` (`id`, `psicologo_id`),
  ADD CONSTRAINT `fk_consultas_origem` FOREIGN KEY (`consulta_origem_id`) REFERENCES `consultas` (`id`),
  ADD CONSTRAINT `fk_consultas_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`usuario_id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `disponibilidades_semanais`
--
ALTER TABLE `disponibilidades_semanais`
  ADD CONSTRAINT `fk_disp_psicologo` FOREIGN KEY (`psicologo_id`) REFERENCES `psicologos` (`usuario_id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `fk_favorito_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`usuario_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_favorito_psicologo` FOREIGN KEY (`psicologo_id`) REFERENCES `psicologos` (`usuario_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `historico_status_consultas`
--
ALTER TABLE `historico_status_consultas`
  ADD CONSTRAINT `fk_historico_autor` FOREIGN KEY (`alterado_por_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_historico_consulta` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `horarios_agenda`
--
ALTER TABLE `horarios_agenda`
  ADD CONSTRAINT `fk_horarios_psicologo` FOREIGN KEY (`psicologo_id`) REFERENCES `psicologos` (`usuario_id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `metodos_pagamento`
--
ALTER TABLE `metodos_pagamento`
  ADD CONSTRAINT `fk_metodo_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`usuario_id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `fk_notificacao_consulta` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notificacao_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `pacientes`
--
ALTER TABLE `pacientes`
  ADD CONSTRAINT `fk_pacientes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD CONSTRAINT `fk_pagamento_consulta` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pagamento_metodo` FOREIGN KEY (`metodo_pagamento_id`) REFERENCES `metodos_pagamento` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `prontuarios`
--
ALTER TABLE `prontuarios`
  ADD CONSTRAINT `fk_prontuario_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`usuario_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prontuario_psicologo` FOREIGN KEY (`psicologo_id`) REFERENCES `psicologos` (`usuario_id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `psicologos`
--
ALTER TABLE `psicologos`
  ADD CONSTRAINT `fk_psicologos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `psicologo_especialidades`
--
ALTER TABLE `psicologo_especialidades`
  ADD CONSTRAINT `fk_pe_especialidade` FOREIGN KEY (`especialidade_id`) REFERENCES `especialidades` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pe_psicologo` FOREIGN KEY (`psicologo_id`) REFERENCES `psicologos` (`usuario_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `reembolsos`
--
ALTER TABLE `reembolsos`
  ADD CONSTRAINT `fk_reembolso_pagamento` FOREIGN KEY (`pagamento_id`) REFERENCES `pagamentos` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `registros_prontuario`
--
ALTER TABLE `registros_prontuario`
  ADD CONSTRAINT `fk_registro_consulta` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_registro_prontuario` FOREIGN KEY (`prontuario_id`) REFERENCES `prontuarios` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `resumos_compartilhados`
--
ALTER TABLE `resumos_compartilhados`
  ADD CONSTRAINT `fk_resumo_consulta` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resumo_psicologo` FOREIGN KEY (`compartilhado_por_psicologo_id`) REFERENCES `psicologos` (`usuario_id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `salas_atendimento`
--
ALTER TABLE `salas_atendimento`
  ADD CONSTRAINT `fk_salas_consulta` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `tokens_login_persistente`
--
ALTER TABLE `tokens_login_persistente`
  ADD CONSTRAINT `fk_login_persistente_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `tokens_redefinicao_senha`
--
ALTER TABLE `tokens_redefinicao_senha`
  ADD CONSTRAINT `fk_token_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `tokens_verificacao_email`
--
ALTER TABLE `tokens_verificacao_email`
  ADD CONSTRAINT `fk_token_email_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `versoes_registros_prontuario`
--
ALTER TABLE `versoes_registros_prontuario`
  ADD CONSTRAINT `fk_versao_autor` FOREIGN KEY (`autor_id`) REFERENCES `psicologos` (`usuario_id`),
  ADD CONSTRAINT `fk_versao_registro` FOREIGN KEY (`registro_id`) REFERENCES `registros_prontuario` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
