CREATE DATABASE  IF NOT EXISTS `gremio_etec_poa` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `gremio_etec_poa`;
-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: localhost    Database: gremio_etec_poa
-- ------------------------------------------------------
-- Server version	8.4.7

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `anexos_evento`
--

DROP TABLE IF EXISTS `anexos_evento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `anexos_evento` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `evento_id` int unsigned NOT NULL,
  `usuario_id` int unsigned NOT NULL,
  `nome_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_arquivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caminho` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamanho_bytes` bigint unsigned NOT NULL,
  `hash_arquivo` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SHA-256 para verificação de integridade',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_anexos_evento_evento_id` (`evento_id`),
  KEY `idx_anexos_evento_usuario_id` (`usuario_id`),
  CONSTRAINT `fk_anexos_evento_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_anexos_evento_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `anexos_evento`
--

LOCK TABLES `anexos_evento` WRITE;
/*!40000 ALTER TABLE `anexos_evento` DISABLE KEYS */;
/*!40000 ALTER TABLE `anexos_evento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `anexos_projeto`
--

DROP TABLE IF EXISTS `anexos_projeto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `anexos_projeto` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `projeto_id` int unsigned NOT NULL,
  `usuario_id` int unsigned NOT NULL,
  `nome_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_arquivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caminho` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamanho_bytes` bigint unsigned NOT NULL,
  `hash_arquivo` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_anexos_projeto_projeto_id` (`projeto_id`),
  KEY `idx_anexos_projeto_usuario_id` (`usuario_id`),
  CONSTRAINT `fk_anexos_projeto_projeto` FOREIGN KEY (`projeto_id`) REFERENCES `projetos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_anexos_projeto_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `anexos_projeto`
--

LOCK TABLES `anexos_projeto` WRITE;
/*!40000 ALTER TABLE `anexos_projeto` DISABLE KEYS */;
/*!40000 ALTER TABLE `anexos_projeto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias_financeiras`
--

DROP TABLE IF EXISTS `categorias_financeiras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias_financeiras` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('entrada','saida') COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categorias_financeiras_nome_tipo` (`nome`,`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias_financeiras`
--

LOCK TABLES `categorias_financeiras` WRITE;
/*!40000 ALTER TABLE `categorias_financeiras` DISABLE KEYS */;
/*!40000 ALTER TABLE `categorias_financeiras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias_publicacao`
--

DROP TABLE IF EXISTS `categorias_publicacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias_publicacao` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categorias_publicacao_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias_publicacao`
--

LOCK TABLES `categorias_publicacao` WRITE;
/*!40000 ALTER TABLE `categorias_publicacao` DISABLE KEYS */;
/*!40000 ALTER TABLE `categorias_publicacao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comprovantes`
--

DROP TABLE IF EXISTS `comprovantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comprovantes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `movimentacao_id` int unsigned NOT NULL,
  `nome_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_arquivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caminho` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamanho_bytes` bigint unsigned NOT NULL,
  `hash_arquivo` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_comprovantes_movimentacao_id` (`movimentacao_id`),
  CONSTRAINT `fk_comprovantes_movimentacao` FOREIGN KEY (`movimentacao_id`) REFERENCES `movimentacoes_financeiras` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comprovantes`
--

LOCK TABLES `comprovantes` WRITE;
/*!40000 ALTER TABLE `comprovantes` DISABLE KEYS */;
/*!40000 ALTER TABLE `comprovantes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `eventos`
--

DROP TABLE IF EXISTS `eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eventos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `imagem_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `local` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_evento` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time DEFAULT NULL,
  `limite_participantes` smallint unsigned DEFAULT NULL COMMENT 'NULL = sem limite',
  `status` enum('planejado','em_andamento','realizado','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planejado',
  `criado_por` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_eventos_criado_por` (`criado_por`),
  KEY `idx_eventos_data_evento` (`data_evento`),
  KEY `idx_eventos_status` (`status`),
  CONSTRAINT `fk_eventos_usuario_criador` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eventos`
--

LOCK TABLES `eventos` WRITE;
/*!40000 ALTER TABLE `eventos` DISABLE KEYS */;
/*!40000 ALTER TABLE `eventos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historico_projeto`
--

DROP TABLE IF EXISTS `historico_projeto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historico_projeto` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `projeto_id` int unsigned NOT NULL,
  `usuario_id` int unsigned DEFAULT NULL,
  `acao` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_historico_projeto_projeto_id` (`projeto_id`),
  KEY `idx_historico_projeto_usuario_id` (`usuario_id`),
  CONSTRAINT `fk_historico_projeto_projeto` FOREIGN KEY (`projeto_id`) REFERENCES `projetos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_historico_projeto_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historico_projeto`
--

LOCK TABLES `historico_projeto` WRITE;
/*!40000 ALTER TABLE `historico_projeto` DISABLE KEYS */;
/*!40000 ALTER TABLE `historico_projeto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itens_orcamento`
--

DROP TABLE IF EXISTS `itens_orcamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itens_orcamento` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `orcamento_id` int unsigned NOT NULL,
  `categoria_id` int unsigned NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_previsto` decimal(10,2) NOT NULL DEFAULT '0.00',
  `valor_realizado` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_itens_orcamento_orcamento_id` (`orcamento_id`),
  KEY `idx_itens_orcamento_categoria_id` (`categoria_id`),
  CONSTRAINT `fk_itens_orcamento_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_financeiras` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_itens_orcamento_orcamento` FOREIGN KEY (`orcamento_id`) REFERENCES `orcamentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itens_orcamento`
--

LOCK TABLES `itens_orcamento` WRITE;
/*!40000 ALTER TABLE `itens_orcamento` DISABLE KEYS */;
/*!40000 ALTER TABLE `itens_orcamento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned DEFAULT NULL,
  `acao` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex.: login, logout, criacao, alteracao, exclusao',
  `modulo` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex.: autenticacao, publicacoes, eventos, projetos, financeiro, votacoes',
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_usuario_id` (`usuario_id`),
  KEY `idx_logs_modulo` (`modulo`),
  KEY `idx_logs_created_at` (`created_at`),
  CONSTRAINT `fk_logs_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log geral de auditoria do sistema (append-only)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs_alteracoes`
--

DROP TABLE IF EXISTS `logs_alteracoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_alteracoes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned DEFAULT NULL,
  `tabela_afetada` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registro_id` bigint unsigned NOT NULL,
  `tipo_operacao` enum('criacao','atualizacao','exclusao') COLLATE utf8mb4_unicode_ci NOT NULL,
  `dados_anteriores` json DEFAULT NULL COMMENT 'NULL quando tipo_operacao = criacao',
  `dados_novos` json DEFAULT NULL COMMENT 'NULL quando tipo_operacao = exclusao',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_alteracoes_usuario_id` (`usuario_id`),
  KEY `idx_logs_alteracoes_tabela_registro` (`tabela_afetada`,`registro_id`),
  KEY `idx_logs_alteracoes_tipo_operacao` (`tipo_operacao`),
  CONSTRAINT `fk_logs_alteracoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro unificado de criação/atualização/exclusão em qualquer tabela do sistema (append-only)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs_alteracoes`
--

LOCK TABLES `logs_alteracoes` WRITE;
/*!40000 ALTER TABLE `logs_alteracoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `logs_alteracoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs_login`
--

DROP TABLE IF EXISTS `logs_login`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_login` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sucesso` tinyint(1) NOT NULL,
  `motivo_falha` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_login_usuario_id` (`usuario_id`),
  KEY `idx_logs_login_created_at` (`created_at`),
  CONSTRAINT `fk_logs_login_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de tentativas de login (sucesso e falha), preservado mesmo após exclusão do usuário';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs_login`
--

LOCK TABLES `logs_login` WRITE;
/*!40000 ALTER TABLE `logs_login` DISABLE KEYS */;
/*!40000 ALTER TABLE `logs_login` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membros_projeto`
--

DROP TABLE IF EXISTS `membros_projeto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `membros_projeto` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `projeto_id` int unsigned NOT NULL,
  `usuario_id` int unsigned NOT NULL,
  `papel` enum('coordenador','membro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'membro',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_membros_projeto_usuario` (`projeto_id`,`usuario_id`),
  KEY `idx_membros_projeto_usuario_id` (`usuario_id`),
  CONSTRAINT `fk_membros_projeto_projeto` FOREIGN KEY (`projeto_id`) REFERENCES `projetos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_membros_projeto_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membros_projeto`
--

LOCK TABLES `membros_projeto` WRITE;
/*!40000 ALTER TABLE `membros_projeto` DISABLE KEYS */;
/*!40000 ALTER TABLE `membros_projeto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimentacoes_financeiras`
--

DROP TABLE IF EXISTS `movimentacoes_financeiras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimentacoes_financeiras` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `categoria_id` int unsigned NOT NULL,
  `usuario_id` int unsigned NOT NULL,
  `tipo` enum('entrada','saida') COLLATE utf8mb4_unicode_ci NOT NULL,
  `origem` enum('doacao','patrocinio','evento','outro') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_movimentacao` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_movimentacoes_categoria_id` (`categoria_id`),
  KEY `idx_movimentacoes_usuario_id` (`usuario_id`),
  KEY `idx_movimentacoes_data` (`data_movimentacao`),
  CONSTRAINT `fk_movimentacoes_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_financeiras` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_movimentacoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_movimentacoes_valor_positivo` CHECK ((`valor` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimentacoes_financeiras`
--

LOCK TABLES `movimentacoes_financeiras` WRITE;
/*!40000 ALTER TABLE `movimentacoes_financeiras` DISABLE KEYS */;
/*!40000 ALTER TABLE `movimentacoes_financeiras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificacoes`
--

DROP TABLE IF EXISTS `notificacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificacoes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tipo_notificacao_id` int unsigned NOT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensagem` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `referencia_tabela` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencia_id` int unsigned DEFAULT NULL,
  `criado_por` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notificacoes_tipo_notificacao_id` (`tipo_notificacao_id`),
  KEY `idx_notificacoes_criado_por` (`criado_por`),
  KEY `idx_notificacoes_referencia` (`referencia_tabela`,`referencia_id`),
  CONSTRAINT `fk_notificacoes_tipo` FOREIGN KEY (`tipo_notificacao_id`) REFERENCES `tipos_notificacao` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_notificacoes_usuario_criador` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificacoes`
--

LOCK TABLES `notificacoes` WRITE;
/*!40000 ALTER TABLE `notificacoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `notificacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `opcoes_votacao`
--

DROP TABLE IF EXISTS `opcoes_votacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `opcoes_votacao` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `votacao_id` int unsigned NOT NULL,
  `descricao_opcao` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_opcoes_votacao_votacao_id` (`votacao_id`),
  CONSTRAINT `fk_opcoes_votacao_votacao` FOREIGN KEY (`votacao_id`) REFERENCES `votacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `opcoes_votacao`
--

LOCK TABLES `opcoes_votacao` WRITE;
/*!40000 ALTER TABLE `opcoes_votacao` DISABLE KEYS */;
/*!40000 ALTER TABLE `opcoes_votacao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orcamentos`
--

DROP TABLE IF EXISTS `orcamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orcamentos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ano_referencia` year NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `valor_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('planejado','aprovado','encerrado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planejado',
  `criado_por` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_orcamentos_criado_por` (`criado_por`),
  KEY `idx_orcamentos_ano_referencia` (`ano_referencia`),
  CONSTRAINT `fk_orcamentos_usuario_criador` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_orcamentos_valor_total_positivo` CHECK ((`valor_total` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orcamentos`
--

LOCK TABLES `orcamentos` WRITE;
/*!40000 ALTER TABLE `orcamentos` DISABLE KEYS */;
/*!40000 ALTER TABLE `orcamentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `participantes_evento`
--

DROP TABLE IF EXISTS `participantes_evento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `participantes_evento` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `evento_id` int unsigned NOT NULL,
  `usuario_id` int unsigned NOT NULL,
  `status_inscricao` enum('inscrito','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inscrito',
  `inscrito_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_participantes_evento_usuario` (`evento_id`,`usuario_id`),
  KEY `idx_participantes_evento_usuario_id` (`usuario_id`),
  CONSTRAINT `fk_participantes_evento_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_participantes_evento_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `participantes_evento`
--

LOCK TABLES `participantes_evento` WRITE;
/*!40000 ALTER TABLE `participantes_evento` DISABLE KEYS */;
/*!40000 ALTER TABLE `participantes_evento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `perfil_permissao`
--

DROP TABLE IF EXISTS `perfil_permissao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `perfil_permissao` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `perfil_id` int unsigned NOT NULL,
  `permissao_id` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_perfil_permissao` (`perfil_id`,`permissao_id`),
  KEY `idx_perfil_permissao_permissao_id` (`permissao_id`),
  CONSTRAINT `fk_perfil_permissao_perfil` FOREIGN KEY (`perfil_id`) REFERENCES `perfis` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_perfil_permissao_permissao` FOREIGN KEY (`permissao_id`) REFERENCES `permissoes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `perfil_permissao`
--

LOCK TABLES `perfil_permissao` WRITE;
/*!40000 ALTER TABLE `perfil_permissao` DISABLE KEYS */;
/*!40000 ALTER TABLE `perfil_permissao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `perfis`
--

DROP TABLE IF EXISTS `perfis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `perfis` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_perfis_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `perfis`
--

LOCK TABLES `perfis` WRITE;
/*!40000 ALTER TABLE `perfis` DISABLE KEYS */;
/*!40000 ALTER TABLE `perfis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissoes`
--

DROP TABLE IF EXISTS `permissoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissoes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permissoes_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissoes`
--

LOCK TABLES `permissoes` WRITE;
/*!40000 ALTER TABLE `permissoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `permissoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `presenca_evento`
--

DROP TABLE IF EXISTS `presenca_evento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `presenca_evento` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `evento_id` int unsigned NOT NULL,
  `usuario_id` int unsigned NOT NULL,
  `presente` tinyint(1) NOT NULL DEFAULT '0',
  `marcado_por` int unsigned NOT NULL,
  `marcado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_presenca_evento_usuario` (`evento_id`,`usuario_id`),
  KEY `idx_presenca_evento_marcado_por` (`marcado_por`),
  KEY `fk_presenca_evento_usuario` (`usuario_id`),
  CONSTRAINT `fk_presenca_evento_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_presenca_evento_marcado_por` FOREIGN KEY (`marcado_por`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_presenca_evento_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `presenca_evento`
--

LOCK TABLES `presenca_evento` WRITE;
/*!40000 ALTER TABLE `presenca_evento` DISABLE KEYS */;
/*!40000 ALTER TABLE `presenca_evento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prestacoes_contas`
--

DROP TABLE IF EXISTS `prestacoes_contas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prestacoes_contas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `orcamento_id` int unsigned DEFAULT NULL,
  `responsavel_id` int unsigned NOT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `periodo_inicio` date NOT NULL,
  `periodo_fim` date NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `arquivo_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hash_arquivo` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('em_elaboracao','publicada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'em_elaboracao',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_prestacoes_contas_orcamento_id` (`orcamento_id`),
  KEY `idx_prestacoes_contas_responsavel_id` (`responsavel_id`),
  CONSTRAINT `fk_prestacoes_contas_orcamento` FOREIGN KEY (`orcamento_id`) REFERENCES `orcamentos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_prestacoes_contas_responsavel` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_prestacoes_contas_periodo` CHECK ((`periodo_fim` >= `periodo_inicio`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prestacoes_contas`
--

LOCK TABLES `prestacoes_contas` WRITE;
/*!40000 ALTER TABLE `prestacoes_contas` DISABLE KEYS */;
/*!40000 ALTER TABLE `prestacoes_contas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projetos`
--

DROP TABLE IF EXISTS `projetos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `projetos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `data_inicio` date DEFAULT NULL,
  `data_fim_prevista` date DEFAULT NULL,
  `data_fim_real` date DEFAULT NULL,
  `status` enum('planejado','em_andamento','concluido','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planejado',
  `responsavel_id` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_projetos_responsavel_id` (`responsavel_id`),
  KEY `idx_projetos_status` (`status`),
  CONSTRAINT `fk_projetos_responsavel` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projetos`
--

LOCK TABLES `projetos` WRITE;
/*!40000 ALTER TABLE `projetos` DISABLE KEYS */;
/*!40000 ALTER TABLE `projetos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `publicacoes`
--

DROP TABLE IF EXISTS `publicacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `publicacoes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned NOT NULL COMMENT 'Autor: Gremio ou Administrador (regra aplicada via RBAC)',
  `categoria_id` int unsigned NOT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conteudo` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `imagem_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publicado_em` datetime DEFAULT NULL,
  `status` enum('rascunho','publicado','arquivado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rascunho',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_publicacoes_usuario_id` (`usuario_id`),
  KEY `idx_publicacoes_categoria_id` (`categoria_id`),
  KEY `idx_publicacoes_status_publicado_em` (`status`,`publicado_em`),
  CONSTRAINT `fk_publicacoes_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_publicacao` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_publicacoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `publicacoes`
--

LOCK TABLES `publicacoes` WRITE;
/*!40000 ALTER TABLE `publicacoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `publicacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reacoes`
--

DROP TABLE IF EXISTS `reacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reacoes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned NOT NULL,
  `publicacao_id` int unsigned NOT NULL,
  `tipo_reacao_id` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reacoes_usuario_publicacao` (`usuario_id`,`publicacao_id`) COMMENT 'Uma reação por usuário por publicação',
  KEY `idx_reacoes_publicacao_id` (`publicacao_id`),
  KEY `idx_reacoes_tipo_reacao_id` (`tipo_reacao_id`),
  CONSTRAINT `fk_reacoes_publicacao` FOREIGN KEY (`publicacao_id`) REFERENCES `publicacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reacoes_tipo_reacao` FOREIGN KEY (`tipo_reacao_id`) REFERENCES `tipos_reacao` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_reacoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reacoes`
--

LOCK TABLES `reacoes` WRITE;
/*!40000 ALTER TABLE `reacoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `reacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resultado_votacao`
--

DROP TABLE IF EXISTS `resultado_votacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resultado_votacao` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `votacao_id` int unsigned NOT NULL,
  `opcao_id` int unsigned NOT NULL,
  `total_votos` int unsigned NOT NULL DEFAULT '0',
  `apurado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_resultado_votacao_opcao` (`votacao_id`,`opcao_id`),
  KEY `fk_resultado_votacao_opcao` (`opcao_id`),
  CONSTRAINT `fk_resultado_votacao_opcao` FOREIGN KEY (`opcao_id`) REFERENCES `opcoes_votacao` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_resultado_votacao_votacao` FOREIGN KEY (`votacao_id`) REFERENCES `votacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resultado_votacao`
--

LOCK TABLES `resultado_votacao` WRITE;
/*!40000 ALTER TABLE `resultado_votacao` DISABLE KEYS */;
/*!40000 ALTER TABLE `resultado_votacao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessoes`
--

DROP TABLE IF EXISTS `sessoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessoes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned NOT NULL,
  `token_sessao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iniciada_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expira_em` datetime NOT NULL,
  `encerrada_em` datetime DEFAULT NULL,
  `ativa` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sessoes_token_sessao` (`token_sessao`),
  KEY `idx_sessoes_usuario_ativa` (`usuario_id`,`ativa`),
  KEY `idx_sessoes_expira_em` (`expira_em`),
  CONSTRAINT `fk_sessoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessoes`
--

LOCK TABLES `sessoes` WRITE;
/*!40000 ALTER TABLE `sessoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitacoes_cadastro`
--

DROP TABLE IF EXISTS `solicitacoes_cadastro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitacoes_cadastro` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned NOT NULL,
  `tipo_solicitacao` enum('cadastro','alteracao_foto') COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto_solicitada_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pendente','aprovado','rejeitado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `analisado_por` int unsigned DEFAULT NULL,
  `observacao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_solicitacao` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `analisado_em` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_solicitacoes_usuario_id` (`usuario_id`),
  KEY `idx_solicitacoes_status` (`status`),
  KEY `idx_solicitacoes_tipo_solicitacao` (`tipo_solicitacao`),
  KEY `idx_solicitacoes_analisado_por` (`analisado_por`),
  CONSTRAINT `fk_solicitacoes_administrador` FOREIGN KEY (`analisado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_solicitacoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitacoes_cadastro`
--

LOCK TABLES `solicitacoes_cadastro` WRITE;
/*!40000 ALTER TABLE `solicitacoes_cadastro` DISABLE KEYS */;
/*!40000 ALTER TABLE `solicitacoes_cadastro` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `status_tarefa`
--

DROP TABLE IF EXISTS `status_tarefa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `status_tarefa` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex.: pendente, em_andamento, concluida, atrasada',
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_status_tarefa_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `status_tarefa`
--

LOCK TABLES `status_tarefa` WRITE;
/*!40000 ALTER TABLE `status_tarefa` DISABLE KEYS */;
/*!40000 ALTER TABLE `status_tarefa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tarefas`
--

DROP TABLE IF EXISTS `tarefas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tarefas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `projeto_id` int unsigned NOT NULL,
  `status_tarefa_id` int unsigned NOT NULL,
  `responsavel_id` int unsigned DEFAULT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` mediumtext COLLATE utf8mb4_unicode_ci,
  `data_inicio` date DEFAULT NULL,
  `data_prazo` date DEFAULT NULL,
  `data_conclusao` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tarefas_projeto_status` (`projeto_id`,`status_tarefa_id`),
  KEY `idx_tarefas_responsavel_id` (`responsavel_id`),
  KEY `fk_tarefas_status_tarefa` (`status_tarefa_id`),
  CONSTRAINT `fk_tarefas_projeto` FOREIGN KEY (`projeto_id`) REFERENCES `projetos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tarefas_responsavel` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tarefas_status_tarefa` FOREIGN KEY (`status_tarefa_id`) REFERENCES `status_tarefa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tarefas`
--

LOCK TABLES `tarefas` WRITE;
/*!40000 ALTER TABLE `tarefas` DISABLE KEYS */;
/*!40000 ALTER TABLE `tarefas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_notificacao`
--

DROP TABLE IF EXISTS `tipos_notificacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_notificacao` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex.: evento, publicacao, projeto, votacao, financeiro, sistema',
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tipos_notificacao_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_notificacao`
--

LOCK TABLES `tipos_notificacao` WRITE;
/*!40000 ALTER TABLE `tipos_notificacao` DISABLE KEYS */;
/*!40000 ALTER TABLE `tipos_notificacao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_reacao`
--

DROP TABLE IF EXISTS `tipos_reacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_reacao` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emoji` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tipos_reacao_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_reacao`
--

LOCK TABLES `tipos_reacao` WRITE;
/*!40000 ALTER TABLE `tipos_reacao` DISABLE KEYS */;
/*!40000 ALTER TABLE `tipos_reacao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tokens_recuperacao`
--

DROP TABLE IF EXISTS `tokens_recuperacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tokens_recuperacao` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_solicitacao` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expira_em` datetime NOT NULL,
  `utilizado` tinyint(1) NOT NULL DEFAULT '0',
  `utilizado_em` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tokens_recuperacao_token` (`token`),
  KEY `idx_tokens_recuperacao_usuario_id` (`usuario_id`),
  KEY `idx_tokens_recuperacao_expira_em` (`expira_em`),
  CONSTRAINT `fk_tokens_recuperacao_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tokens_recuperacao`
--

LOCK TABLES `tokens_recuperacao` WRITE;
/*!40000 ALTER TABLE `tokens_recuperacao` DISABLE KEYS */;
/*!40000 ALTER TABLE `tokens_recuperacao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario_notificacao`
--

DROP TABLE IF EXISTS `usuario_notificacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_notificacao` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notificacao_id` bigint unsigned NOT NULL,
  `usuario_id` int unsigned NOT NULL,
  `lida` tinyint(1) NOT NULL DEFAULT '0',
  `lida_em` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuario_notificacao` (`notificacao_id`,`usuario_id`),
  KEY `idx_usuario_notificacao_usuario_lida` (`usuario_id`,`lida`),
  CONSTRAINT `fk_usuario_notificacao_notificacao` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_usuario_notificacao_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_notificacao`
--

LOCK TABLES `usuario_notificacao` WRITE;
/*!40000 ALTER TABLE `usuario_notificacao` DISABLE KEYS */;
/*!40000 ALTER TABLE `usuario_notificacao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario_perfil`
--

DROP TABLE IF EXISTS `usuario_perfil`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_perfil` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned NOT NULL,
  `perfil_id` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuario_perfil` (`usuario_id`,`perfil_id`),
  KEY `idx_usuario_perfil_perfil_id` (`perfil_id`),
  CONSTRAINT `fk_usuario_perfil_perfil` FOREIGN KEY (`perfil_id`) REFERENCES `perfis` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_usuario_perfil_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_perfil`
--

LOCK TABLES `usuario_perfil` WRITE;
/*!40000 ALTER TABLE `usuario_perfil` DISABLE KEYS */;
/*!40000 ALTER TABLE `usuario_perfil` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `rm` char(5) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Registro do aluno/professor - exatamente 5 caracteres',
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Gerado via password_hash()',
  `nome_completo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nsa/default.png' COMMENT 'Foto inicial provida pelo sistema NSA; alteração exige aprovação',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_login_em` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_rm` (`rm`),
  UNIQUE KEY `uk_usuarios_email` (`email`),
  KEY `idx_usuarios_ativo` (`ativo`),
  KEY `idx_usuarios_deleted_at` (`deleted_at`),
  CONSTRAINT `chk_usuarios_rm_tamanho` CHECK ((char_length(`rm`) = 5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `votacoes`
--

DROP TABLE IF EXISTS `votacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `votacoes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `tipo` enum('enquete','eleicao','votacao_gremio') COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_inicio` datetime NOT NULL,
  `data_fim` datetime NOT NULL,
  `status` enum('agendada','aberta','encerrada','cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'agendada',
  `criado_por` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_votacoes_criado_por` (`criado_por`),
  KEY `idx_votacoes_status` (`status`),
  CONSTRAINT `fk_votacoes_usuario_criador` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_votacoes_periodo` CHECK ((`data_fim` > `data_inicio`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `votacoes`
--

LOCK TABLES `votacoes` WRITE;
/*!40000 ALTER TABLE `votacoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `votacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `votos`
--

DROP TABLE IF EXISTS `votos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `votos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `votacao_id` int unsigned NOT NULL,
  `opcao_id` int unsigned NOT NULL,
  `usuario_id` int unsigned NOT NULL,
  `votado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_votos_votacao_usuario` (`votacao_id`,`usuario_id`) COMMENT 'Garante voto único por usuário em cada votação',
  KEY `idx_votos_opcao_id` (`opcao_id`),
  KEY `fk_votos_usuario` (`usuario_id`),
  CONSTRAINT `fk_votos_opcao` FOREIGN KEY (`opcao_id`) REFERENCES `opcoes_votacao` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_votos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_votos_votacao` FOREIGN KEY (`votacao_id`) REFERENCES `votacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `votos`
--

LOCK TABLES `votos` WRITE;
/*!40000 ALTER TABLE `votos` DISABLE KEYS */;
/*!40000 ALTER TABLE `votos` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-08 18:53:41
