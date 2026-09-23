-- =============================================================================
-- WMS full database dump (schema + data)
-- Generated for website-project
-- Import: mysql -u root -p < wc/database/full_schema.sql
-- Or:     mysql -u root -p -e "SOURCE /path/to/wc/database/full_schema.sql"
-- =============================================================================

-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: wms_core
-- ------------------------------------------------------
-- Server version	8.0.46-0ubuntu0.24.04.4

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `wms_core`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `wms_core` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `wms_core`;

--
-- Table structure for table `api_sessions`
--

DROP TABLE IF EXISTS `api_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `csrf_token` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_seen_at` datetime DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_api_sessions_token` (`token_hash`),
  KEY `idx_api_sessions_user` (`user_id`),
  KEY `idx_api_sessions_expires` (`expires_at`),
  CONSTRAINT `fk_api_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_sessions`
--

LOCK TABLES `api_sessions` WRITE;
/*!40000 ALTER TABLE `api_sessions` DISABLE KEYS */;
INSERT INTO `api_sessions` (`id`, `token_hash`, `user_id`, `csrf_token`, `ip_address`, `user_agent`, `expires_at`, `created_at`, `last_seen_at`, `revoked_at`) VALUES (1,'7bbf0f9add81bde2e2f45655891bd0a0ed72c51661499a4bdc1e2dca460a8432',1,'f0973e9c58d5cd249bfeee8abb273993f06ff06f049c310d5455dd5776163921','127.0.0.1','','2026-09-23 19:23:15','2026-09-23 17:23:15','2026-09-23 17:23:16',NULL),(2,'1a9fab419f8bcb8f9cbfdd25ef9234735cfd2104b8bf9880d875472201ea564e',2,'be847b3eaa366cb4e8b5f593b2af6c159c6a53c4d9eed1ed420f201aa7403709','127.0.0.1','','2026-09-23 19:23:15','2026-09-23 17:23:15','2026-09-23 17:23:15',NULL),(3,'8a18ca5f5d7f15fa4edda7ccca28bebd62adc4fef31771425f5f8a03ec8f51bd',1,'e610a114560123992683f1a2138ea7008a90503362fbf67423b553f3ab0c0ea3','127.0.0.1','','2026-09-23 19:23:33','2026-09-23 17:23:33','2026-09-23 17:23:33',NULL),(4,'518a1de74ad89afff77d2a0eac3746965d8e8e082bb380f7345c2cc0075a004e',1,'49825d8652a6078b3f9a43c7230be59f0d9fc067f0c90a99b8d56c24c5c5418e','127.0.0.1','','2026-09-23 19:23:44','2026-09-23 17:23:44','2026-09-23 17:23:44',NULL),(5,'122acbe118ec1fcbd330c6b893bcb2075af296cf8c76634f67a1bba48752c6eb',1,'710edcc5874690755a0522a66c2ed311314fa2e0097f6b410e52691ec3bb7157','127.0.0.1','curl/8.5.0','2026-09-23 19:23:50','2026-09-23 17:23:50','2026-09-23 17:23:50',NULL),(6,'9b1b75cdef68aa6e8d6af80fa12f6eb3ff0eb479ce5ca29d8ffbb3e0fe5e0f08',1,'caf173277ae825fc770cbcee31bd37f3c4f2ed8651674c84650a31b29a49fc79','127.0.0.1','','2026-09-23 19:24:15','2026-09-23 17:24:15','2026-09-23 17:24:16',NULL),(7,'bc983fd80d314a946301f58bff7f84aa77f240bf3f4e30c91dc3a86999f3f2b2',1,'86bd84ff231d224199c14b5e59582ed6a97f7f0599ffabb7d69a90cfff2c5fc9','127.0.0.1','','2026-09-23 19:35:25','2026-09-23 17:35:25','2026-09-23 19:05:49',NULL),(8,'550a48535c65d2df910cbf9ca0c6bccc7df832a94a6bb9d5d29bb93f5a07d7f3',1,'79f94bd782121bdca5385fce342915ec4ad88db71fea851de7cae46f941cd338','127.0.0.1','','2026-09-23 20:38:45','2026-09-23 18:38:45','2026-09-23 18:38:45',NULL);
/*!40000 ALTER TABLE `api_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `details_json` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  KEY `idx_audit_created` (`created_at`),
  KEY `idx_audit_action` (`action`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details_json`, `ip_address`, `user_agent`, `created_at`) VALUES (1,1,'create','users',1,'{\"via\": \"cli_bootstrap\", \"email\": \"superadmin@example.com\"}','0.0.0.0','','2026-09-23 17:15:20'),(2,1,'login','users',1,'{\"email\": \"superadmin@example.com\"}','127.0.0.1','','2026-09-23 17:23:15'),(3,1,'create','users',2,'{\"email\": \"editor1@example.com\", \"role_id\": 3}','127.0.0.1','','2026-09-23 17:23:15'),(4,1,'soft_delete','users',2,NULL,'127.0.0.1','','2026-09-23 17:23:15'),(5,1,'restore','users',2,NULL,'127.0.0.1','','2026-09-23 17:23:15'),(6,2,'login','users',2,'{\"email\": \"editor1@example.com\"}','127.0.0.1','','2026-09-23 17:23:15'),(7,1,'create','hero',1,'{\"title\": \"Welcome Hero\"}','127.0.0.1','','2026-09-23 17:23:15'),(8,1,'create','hero',2,'{\"title\": \"About Featured\"}','127.0.0.1','','2026-09-23 17:23:15'),(9,1,'soft_delete','hero',1,NULL,'127.0.0.1','','2026-09-23 17:23:16'),(10,1,'restore','hero',1,NULL,'127.0.0.1','','2026-09-23 17:23:16'),(11,1,'login','users',1,'{\"email\": \"superadmin@example.com\"}','127.0.0.1','','2026-09-23 17:23:33'),(12,1,'login','users',1,'{\"email\": \"superadmin@example.com\"}','127.0.0.1','','2026-09-23 17:23:44'),(13,1,'login','users',1,'{\"email\": \"superadmin@example.com\"}','127.0.0.1','curl/8.5.0','2026-09-23 17:23:50'),(14,1,'login','users',1,'{\"email\": \"superadmin@example.com\"}','127.0.0.1','','2026-09-23 17:24:15'),(15,1,'soft_delete','hero',1,NULL,'127.0.0.1','','2026-09-23 17:24:15'),(16,1,'restore','hero',1,NULL,'127.0.0.1','','2026-09-23 17:24:16'),(17,1,'login','users',1,'{\"email\": \"superadmin@example.com\"}','127.0.0.1','','2026-09-23 17:35:25'),(18,1,'update','hero',1,'{\"title\": \"Welcome Hero\", \"image_replaced\": true}','127.0.0.1','','2026-09-23 17:41:12'),(19,1,'update','pages',16,'{\"slug\": \"home\"}','127.0.0.1','','2026-09-23 17:42:50'),(20,1,'update','pages',16,'{\"slug\": \"home\"}','127.0.0.1','','2026-09-23 17:43:05'),(21,1,'update','pages',17,'{\"slug\": \"about\"}','127.0.0.1','','2026-09-23 17:43:47'),(22,1,'update','hero',2,'{\"title\": \"About Featured\", \"image_replaced\": false}','127.0.0.1','','2026-09-23 17:44:09'),(23,1,'update','hero',4,'{\"title\": \"Welcome to Our Website\", \"image_replaced\": false}','127.0.0.1','','2026-09-23 17:59:32'),(24,1,'update','hero',8,'{\"title\": \"Our Mission\", \"image_replaced\": false}','127.0.0.1','','2026-09-23 18:00:17'),(25,1,'update','hero',7,'{\"title\": \"Our Story\", \"image_replaced\": false}','127.0.0.1','','2026-09-23 18:01:47'),(26,1,'login','users',1,'{\"email\": \"superadmin@example.com\"}','127.0.0.1','','2026-09-23 18:38:45'),(27,1,'create','services',4,'{\"title\": \"Tijaabo Aan Liis Lahayn\"}','127.0.0.1','','2026-09-23 18:38:45'),(28,1,'update','services',5,'{\"title\": \"Customer Support\"}','127.0.0.1','','2026-09-23 18:51:30'),(29,1,'update','hero',7,'{\"title\": \"Our Story\", \"image_replaced\": true}','127.0.0.1','','2026-09-23 18:56:32'),(30,1,'update','hero',7,'{\"title\": \"Our Story\", \"image_replaced\": false}','127.0.0.1','','2026-09-23 19:00:59'),(31,1,'update','site_settings',NULL,'{\"setting_key\": \"hero_size_preset\", \"size_preset\": \"xl\"}','127.0.0.1','','2026-09-23 19:05:21'),(32,1,'update','site_settings',NULL,'{\"setting_key\": \"hero_size_preset\", \"size_preset\": \"sm\"}','127.0.0.1','','2026-09-23 19:05:31'),(33,1,'update','site_settings',NULL,'{\"setting_key\": \"hero_size_preset\", \"size_preset\": \"lg\"}','127.0.0.1','','2026-09-23 19:05:47'),(34,1,'update','site_settings',NULL,'{\"setting_key\": \"hero_size_preset\", \"size_preset\": \"lg\"}','127.0.0.1','','2026-09-23 19:05:49');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hero`
--

DROP TABLE IF EXISTS `hero`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hero` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `button_text` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `button_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hero_active` (`is_active`),
  KEY `idx_hero_sort` (`sort_order`),
  KEY `idx_hero_deleted` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hero`
--

LOCK TABLES `hero` WRITE;
/*!40000 ALTER TABLE `hero` DISABLE KEYS */;
INSERT INTO `hero` (`id`, `title`, `description`, `image_path`, `button_text`, `button_url`, `sort_order`, `is_active`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`) VALUES (4,'Welcome to Our Website','A clean public experience powered by Website Core menus, pages, and hero content.','hero/sample_sample_home_welcome_87d7e11b.jpg','Explore About','/about',10,1,'2026-09-23 17:53:38',NULL,'2026-09-23 18:59:52',1,NULL,NULL),(5,'Built for Your Community','Highlight programs, stories, and updates with responsive hero slides.','hero/sample_sample_home_community_1cefbf74.jpg','Learn more','/about',20,1,'2026-09-23 17:53:38',NULL,NULL,NULL,NULL,NULL),(6,'Ready When You Are','Call visitors to action with clear messaging across every screen size.','hero/sample_sample_home_action_326c1df3.jpg','Get started','/about',30,1,'2026-09-23 17:53:38',NULL,NULL,NULL,NULL,NULL),(7,'Our Story','About page featured hero — active and featured on About only.','hero/3eca38cd0ecb02a0ad56b0b34d4d77a0.webp','Back to Home','/home',10,1,'2026-09-23 17:53:38',NULL,'2026-09-23 19:00:59',1,NULL,NULL),(8,'Our Mission','Second About slide — active but not featured, still included in the slider.','hero/sample_sample_about_mission_36de6140.jpg',NULL,NULL,20,1,'2026-09-23 17:53:38',NULL,'2026-09-23 18:00:17',1,NULL,NULL);
/*!40000 ALTER TABLE `hero` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hero_pages`
--

DROP TABLE IF EXISTS `hero_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hero_pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hero_id` bigint unsigned NOT NULL,
  `page_id` bigint unsigned NOT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `hero_page_active` varchar(64) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (if((`deleted_at` is null),concat(`hero_id`,_utf8mb4':',`page_id`),NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hero_pages_active` (`hero_page_active`),
  KEY `idx_hero_pages_hero` (`hero_id`),
  KEY `idx_hero_pages_page` (`page_id`),
  KEY `idx_hero_pages_featured` (`is_featured`),
  KEY `idx_hero_pages_active` (`is_active`),
  KEY `idx_hero_pages_deleted` (`deleted_at`),
  CONSTRAINT `fk_hero_pages_hero` FOREIGN KEY (`hero_id`) REFERENCES `hero` (`id`),
  CONSTRAINT `fk_hero_pages_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hero_pages`
--

LOCK TABLES `hero_pages` WRITE;
/*!40000 ALTER TABLE `hero_pages` DISABLE KEYS */;
INSERT INTO `hero_pages` (`id`, `hero_id`, `page_id`, `is_featured`, `is_active`, `sort_order`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`) VALUES (1,4,16,1,1,10,'2026-09-23 17:53:38',NULL,'2026-09-23 17:59:32',1,NULL,NULL),(2,4,17,0,1,30,'2026-09-23 17:53:38',NULL,'2026-09-23 17:59:32',1,'2026-09-23 17:59:32',1),(3,5,16,0,1,20,'2026-09-23 17:53:38',NULL,NULL,NULL,NULL,NULL),(4,6,16,0,1,30,'2026-09-23 17:53:38',NULL,NULL,NULL,NULL,NULL),(5,7,17,1,1,10,'2026-09-23 17:53:38',NULL,'2026-09-23 18:01:47',1,'2026-09-23 18:01:47',1),(6,8,17,0,1,20,'2026-09-23 17:53:38',NULL,'2026-09-23 18:00:17',1,'2026-09-23 18:00:17',1),(7,8,16,0,1,0,'2026-09-23 18:00:17',1,NULL,NULL,NULL,NULL),(8,7,16,0,1,0,'2026-09-23 18:01:47',1,'2026-09-23 19:00:59',1,NULL,NULL);
/*!40000 ALTER TABLE `hero_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `succeeded` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_lookup` (`identifier`,`ip_address`,`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
INSERT INTO `login_attempts` (`id`, `identifier`, `ip_address`, `attempted_at`, `succeeded`) VALUES (1,'superadmin@example.com','127.0.0.1','2026-09-23 17:23:15',1),(2,'editor1@example.com','127.0.0.1','2026-09-23 17:23:15',1),(3,'superadmin@example.com','127.0.0.1','2026-09-23 17:23:33',1),(4,'superadmin@example.com','127.0.0.1','2026-09-23 17:23:43',1),(5,'superadmin@example.com','127.0.0.1','2026-09-23 17:23:50',1),(6,'superadmin@example.com','127.0.0.1','2026-09-23 17:24:15',1),(7,'superadmin@example.com','127.0.0.1','2026-09-23 17:35:25',1),(8,'superadmin@example.com','127.0.0.1','2026-09-23 18:38:45',1);
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_pages`
--

DROP TABLE IF EXISTS `menu_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` bigint unsigned NOT NULL,
  `page_id` bigint unsigned NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `menu_page_active` varchar(64) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (if((`deleted_at` is null),concat(`menu_id`,_utf8mb4':',`page_id`),NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_menu_pages_active` (`menu_page_active`),
  KEY `idx_menu_pages_menu` (`menu_id`),
  KEY `idx_menu_pages_page` (`page_id`),
  KEY `idx_menu_pages_deleted` (`deleted_at`),
  CONSTRAINT `fk_menu_pages_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`),
  CONSTRAINT `fk_menu_pages_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_pages`
--

LOCK TABLES `menu_pages` WRITE;
/*!40000 ALTER TABLE `menu_pages` DISABLE KEYS */;
INSERT INTO `menu_pages` (`id`, `menu_id`, `page_id`, `sort_order`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`) VALUES (1,1,16,0,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(2,2,17,0,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(3,3,1,0,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(4,7,5,0,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(5,8,7,0,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(6,9,9,0,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(7,10,2,0,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(8,11,3,0,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(9,12,4,0,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(10,13,6,0,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(11,14,8,0,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(12,15,10,0,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(13,16,11,0,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(18,20,18,0,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `menu_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menus` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `menu_type` enum('website','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_menu_id` bigint unsigned DEFAULT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_menus_type` (`menu_type`),
  KEY `idx_menus_parent` (`parent_menu_id`),
  KEY `idx_menus_sort` (`menu_type`,`parent_menu_id`,`sort_order`),
  KEY `idx_menus_active` (`is_active`),
  KEY `idx_menus_deleted` (`deleted_at`),
  CONSTRAINT `fk_menus_parent` FOREIGN KEY (`parent_menu_id`) REFERENCES `menus` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menus`
--

LOCK TABLES `menus` WRITE;
/*!40000 ALTER TABLE `menus` DISABLE KEYS */;
INSERT INTO `menus` (`id`, `menu_type`, `parent_menu_id`, `title`, `icon`, `url`, `sort_order`, `is_active`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`) VALUES (1,'website',NULL,'Home',NULL,'/home',10,1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(2,'website',NULL,'About',NULL,'/about',20,1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(3,'admin',NULL,'Dashboard','dashboard','/dashboard',10,1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(4,'admin',NULL,'Website Management','website',NULL,20,1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(5,'admin',NULL,'Access Control','access',NULL,30,1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(6,'admin',NULL,'System Operations','system',NULL,40,1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(7,'admin',4,'Website Menus',NULL,'/website-menus',10,1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(8,'admin',4,'Website Pages',NULL,'/website-pages',20,1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(9,'admin',4,'Hero',NULL,'/hero',30,1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(10,'admin',5,'Users',NULL,'/users',10,1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(11,'admin',5,'Roles',NULL,'/roles',20,1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(12,'admin',5,'Permissions',NULL,'/permissions',30,1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(13,'admin',6,'Admin Menus',NULL,'/admin-menus',10,1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(14,'admin',6,'Admin Pages',NULL,'/admin-pages',20,1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(15,'admin',6,'Trash',NULL,'/trash',30,1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(16,'admin',6,'Audit Logs',NULL,'/audit-logs',40,1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(20,'admin',4,'Services',NULL,'/services',40,1,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `menus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `page_type` enum('website','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `type_slug_active` varchar(200) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (if((`deleted_at` is null),concat(`page_type`,_utf8mb4':',`slug`),NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pages_type_slug_active` (`type_slug_active`),
  KEY `idx_pages_type` (`page_type`),
  KEY `idx_pages_slug` (`slug`),
  KEY `idx_pages_active` (`is_active`),
  KEY `idx_pages_deleted` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` (`id`, `page_type`, `title`, `slug`, `template_key`, `is_active`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`) VALUES (1,'admin','Dashboard','dashboard','dashboard',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(2,'admin','Users','users','users',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(3,'admin','Roles','roles','roles',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(4,'admin','Permissions','permissions','permissions',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(5,'admin','Website Menus','website-menus','website_menus',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(6,'admin','Admin Menus','admin-menus','admin_menus',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(7,'admin','Website Pages','website-pages','website_pages',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(8,'admin','Admin Pages','admin-pages','admin_pages',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(9,'admin','Hero','hero','hero',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(10,'admin','Trash','trash','trash',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(11,'admin','Audit Logs','audit-logs','audit_logs',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(12,'admin','Account','account','account',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(16,'website','Home','home','home',1,'2026-09-23 17:15:20',NULL,'2026-09-23 17:43:05',1,NULL,NULL),(17,'website','About','about','about',1,'2026-09-23 17:15:20',NULL,'2026-09-23 17:43:47',1,NULL,NULL),(18,'admin','Services','services','services',1,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `code_active` varchar(128) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (if((`deleted_at` is null),`code`,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permissions_code_active` (`code_active`),
  KEY `idx_permissions_module` (`module`),
  KEY `idx_permissions_deleted_at` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` (`id`, `module`, `action`, `code`, `description`, `is_system`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`) VALUES (1,'users','view','users.view','View users',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(2,'users','create','users.create','Create users',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(3,'users','edit','users.edit','Edit users',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(4,'users','delete','users.delete','Soft-delete users',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(5,'users','restore','users.restore','Restore users',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(6,'roles','view','roles.view','View roles',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(7,'roles','create','roles.create','Create roles',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(8,'roles','edit','roles.edit','Edit roles',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(9,'roles','delete','roles.delete','Soft-delete roles',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(10,'roles','restore','roles.restore','Restore roles',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(11,'permissions','view','permissions.view','View permissions',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(12,'permissions','create','permissions.create','Create permissions',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(13,'permissions','edit','permissions.edit','Edit permissions',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(14,'permissions','delete','permissions.delete','Soft-delete permissions',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(15,'permissions','restore','permissions.restore','Restore permissions',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(16,'menus','view','menus.view','View menus',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(17,'menus','create','menus.create','Create menus',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(18,'menus','edit','menus.edit','Edit menus',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(19,'menus','delete','menus.delete','Soft-delete menus',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(20,'menus','restore','menus.restore','Restore menus',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(21,'pages','view','pages.view','View pages',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(22,'pages','create','pages.create','Create pages',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(23,'pages','edit','pages.edit','Edit pages',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(24,'pages','delete','pages.delete','Soft-delete pages',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(25,'pages','restore','pages.restore','Restore pages',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(26,'hero','view','hero.view','View hero',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(27,'hero','create','hero.create','Create hero',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(28,'hero','edit','hero.edit','Edit hero',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(29,'hero','delete','hero.delete','Soft-delete hero',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(30,'hero','restore','hero.restore','Restore hero',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(31,'trash','view','trash.view','View trash',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(32,'trash','restore','trash.restore','Restore from trash',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(33,'audit','view','audit.view','View audit logs',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(34,'dashboard','view','dashboard.view','View dashboard',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(64,'services','view','services.view','View services',1,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL),(65,'services','create','services.create','Create services',1,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL),(66,'services','edit','services.edit','Edit services',1,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL),(67,'services','delete','services.delete','Soft-delete services',1,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL),(68,'services','restore','services.restore','Restore services',1,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `role_perm_active` varchar(64) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (if((`deleted_at` is null),concat(`role_id`,_utf8mb4':',`permission_id`),NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_permissions_active` (`role_perm_active`),
  KEY `idx_role_permissions_role` (`role_id`),
  KEY `idx_role_permissions_perm` (`permission_id`),
  KEY `idx_role_permissions_deleted` (`deleted_at`),
  CONSTRAINT `fk_role_permissions_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`),
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=159 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`) VALUES (1,1,1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(2,1,2,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(3,1,3,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(4,1,4,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(5,1,5,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(6,1,6,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(7,1,7,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(8,1,8,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(9,1,9,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(10,1,10,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(11,1,11,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(12,1,12,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(13,1,13,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(14,1,14,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(15,1,15,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(16,1,16,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(17,1,17,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(18,1,18,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(19,1,19,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(20,1,20,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(21,1,21,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(22,1,22,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(23,1,23,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(24,1,24,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(25,1,25,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(26,1,26,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(27,1,27,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(28,1,28,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(29,1,29,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(30,1,30,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(31,1,31,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(32,1,32,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(33,1,33,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(34,1,34,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(64,2,1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(65,2,2,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(66,2,3,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(67,2,4,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(68,2,5,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(69,2,6,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(70,2,7,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(71,2,8,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(72,2,9,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(73,2,10,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(74,2,11,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(75,2,13,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(76,2,15,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(77,2,16,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(78,2,17,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(79,2,18,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(80,2,19,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(81,2,20,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(82,2,21,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(83,2,22,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(84,2,23,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(85,2,24,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(86,2,25,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(87,2,26,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(88,2,27,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(89,2,28,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(90,2,29,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(91,2,30,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(92,2,31,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(93,2,32,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(94,2,33,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(95,2,34,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(127,3,16,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(128,3,17,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(129,3,18,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(130,3,21,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(131,3,22,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(132,3,23,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(133,3,26,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(134,3,27,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(135,3,28,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(136,3,34,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(142,1,64,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL),(143,1,65,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL),(144,1,66,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL),(145,1,67,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL),(146,1,68,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL),(149,2,64,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL),(150,2,65,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL),(151,2,66,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL),(152,2,67,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL),(153,2,68,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL),(156,3,64,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL),(157,3,65,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL),(158,3,66,'2026-09-23 18:35:53',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `code_active` varchar(64) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (if((`deleted_at` is null),`code`,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_code_active` (`code_active`),
  KEY `idx_roles_deleted_at` (`deleted_at`),
  KEY `idx_roles_is_system` (`is_system`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` (`id`, `name`, `code`, `description`, `is_system`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`) VALUES (1,'Super Admin','super_admin','Full system access; cannot remove last active Super Admin',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(2,'Admin','admin','Administrative access excluding critical system locks',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL),(3,'Editor','editor','Content editing for website menus, pages, and hero',1,'2026-09-23 17:15:20',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_items`
--

DROP TABLE IF EXISTS `service_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint unsigned NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_service_items_service` (`service_id`),
  KEY `idx_service_items_sort` (`service_id`,`sort_order`),
  KEY `idx_service_items_active` (`is_active`),
  KEY `idx_service_items_deleted` (`deleted_at`),
  CONSTRAINT `fk_service_items_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_items`
--

LOCK TABLES `service_items` WRITE;
/*!40000 ALTER TABLE `service_items` DISABLE KEYS */;
INSERT INTO `service_items` (`id`, `service_id`, `title`, `body`, `sort_order`, `is_active`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`) VALUES (1,1,'Wicitaan 24/7','Taageero telefoon oo joogto ah',0,1,'2026-09-23 18:38:44',0,'2026-09-23 18:44:01',0,'2026-09-23 18:44:01',0),(2,1,'Chat toos ah','Jawaab degdeg ah',1,1,'2026-09-23 18:38:44',0,'2026-09-23 18:44:01',0,'2026-09-23 18:44:01',0),(3,1,'Raadraaca dalabka','La soco xaaladdaada',2,1,'2026-09-23 18:38:44',0,'2026-09-23 18:44:01',0,'2026-09-23 18:44:01',0),(4,2,'Rakibid',NULL,0,1,'2026-09-23 18:38:45',0,NULL,NULL,NULL,NULL),(5,2,'Dayactir',NULL,1,1,'2026-09-23 18:38:45',0,NULL,NULL,NULL,NULL),(6,2,'Tababar','Kooxo shaqaale',2,1,'2026-09-23 18:38:45',0,NULL,NULL,NULL,NULL),(7,1,'24/7 phone line','x',0,1,'2026-09-23 18:44:01',0,NULL,NULL,NULL,NULL),(8,5,'24/7 phone line','Always-on telephone support',0,1,'2026-09-23 18:44:09',0,'2026-09-23 18:44:18',0,'2026-09-23 18:44:18',0),(9,5,'Live chat','Quick answers when you need them',1,1,'2026-09-23 18:44:09',0,'2026-09-23 18:44:18',0,'2026-09-23 18:44:18',0),(10,5,'Order tracking','Follow your request status',2,1,'2026-09-23 18:44:09',0,'2026-09-23 18:44:18',0,'2026-09-23 18:44:18',0),(11,6,'Installation',NULL,0,1,'2026-09-23 18:44:09',0,'2026-09-23 18:44:18',0,'2026-09-23 18:44:18',0),(12,6,'Maintenance',NULL,1,1,'2026-09-23 18:44:09',0,'2026-09-23 18:44:18',0,'2026-09-23 18:44:18',0),(13,6,'Training','Staff workshops',2,1,'2026-09-23 18:44:09',0,'2026-09-23 18:44:18',0,'2026-09-23 18:44:18',0),(14,5,'24/7 phone line','Always-on telephone support',0,1,'2026-09-23 18:44:18',0,'2026-09-23 18:51:30',1,NULL,NULL),(15,5,'Live chat','Quick answers when you need them',1,1,'2026-09-23 18:44:18',0,'2026-09-23 18:51:30',1,NULL,NULL),(16,5,'Order tracking','Follow your request status',2,1,'2026-09-23 18:44:18',0,'2026-09-23 18:51:30',1,NULL,NULL),(17,6,'Installation',NULL,0,1,'2026-09-23 18:44:18',0,NULL,NULL,NULL,NULL),(18,6,'Maintenance',NULL,1,1,'2026-09-23 18:44:18',0,NULL,NULL,NULL,NULL),(19,6,'Training','Staff workshops',2,1,'2026-09-23 18:44:18',0,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `service_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_pages`
--

DROP TABLE IF EXISTS `service_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint unsigned NOT NULL,
  `page_id` bigint unsigned NOT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `service_page_active` varchar(64) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (if((`deleted_at` is null),concat(`service_id`,_utf8mb4':',`page_id`),NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_service_pages_active` (`service_page_active`),
  KEY `idx_service_pages_service` (`service_id`),
  KEY `idx_service_pages_page` (`page_id`),
  KEY `idx_service_pages_featured` (`is_featured`),
  KEY `idx_service_pages_active` (`is_active`),
  KEY `idx_service_pages_deleted` (`deleted_at`),
  CONSTRAINT `fk_service_pages_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`),
  CONSTRAINT `fk_service_pages_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_pages`
--

LOCK TABLES `service_pages` WRITE;
/*!40000 ALTER TABLE `service_pages` DISABLE KEYS */;
INSERT INTO `service_pages` (`id`, `service_id`, `page_id`, `is_featured`, `is_active`, `sort_order`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`) VALUES (1,1,16,1,1,10,'2026-09-23 18:38:44',0,'2026-09-23 18:44:01',0,NULL,NULL),(2,2,16,0,1,20,'2026-09-23 18:38:45',0,NULL,NULL,NULL,NULL),(3,3,16,0,1,30,'2026-09-23 18:38:45',0,NULL,NULL,NULL,NULL),(4,5,16,1,1,10,'2026-09-23 18:44:09',0,'2026-09-23 18:51:30',1,NULL,NULL),(5,6,16,0,1,20,'2026-09-23 18:44:09',0,'2026-09-23 18:44:18',0,NULL,NULL),(6,7,16,0,1,30,'2026-09-23 18:44:09',0,'2026-09-23 18:44:18',0,NULL,NULL);
/*!40000 ALTER TABLE `service_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `button_text` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `button_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_services_active` (`is_active`),
  KEY `idx_services_sort` (`sort_order`),
  KEY `idx_services_deleted` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` (`id`, `title`, `description`, `image_path`, `button_text`, `button_url`, `sort_order`, `is_active`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`) VALUES (1,'Adeegga Macmiilka','Waxaan bixinnaa taageero macmiil oo degdeg ah oo ku salaysan baahidaada.','services/sample_0e55dcc237de.jpg','Nala soo xiriir','/about',10,1,'2026-09-23 18:38:44',NULL,'2026-09-23 18:44:01',NULL,'2026-09-23 18:44:01',NULL),(2,'Taageero Teknik','Xalalka farsamada iyo dayactirka nidaamyadaada.','services/sample_25b38539ae5e.jpg',NULL,NULL,20,1,'2026-09-23 18:38:45',NULL,'2026-09-23 18:44:08',NULL,'2026-09-23 18:44:08',NULL),(3,'Qorsheyn Ganacsi','Adeeg qorsheyn oo kooban — aan liis hoose lahayn (ikhtiyaari).','services/sample_57ee8d33af5f.jpg','Akhri wax badan','/about',30,1,'2026-09-23 18:38:45',NULL,'2026-09-23 18:44:09',NULL,'2026-09-23 18:44:09',NULL),(4,'Tijaabo Aan Liis Lahayn','Adeeg bilaa items',NULL,NULL,NULL,99,1,'2026-09-23 18:38:45',1,'2026-09-23 18:44:09',NULL,'2026-09-23 18:44:09',NULL),(5,'Customer Support','Fast, friendly customer support tailored to your needs.','services/aef43a3a34969b2a6ad46def860fe8e5.webp','Contact us','/about',10,1,'2026-09-23 18:44:09',NULL,'2026-09-23 18:51:30',1,NULL,NULL),(6,'Technical Support','Technical solutions and maintenance for your systems.','services/sample_267b649c9c2a.jpg',NULL,NULL,20,1,'2026-09-23 18:44:09',NULL,'2026-09-23 18:44:18',NULL,NULL,NULL),(7,'Business Planning','A concise planning service — no list items under the description (optional).','services/sample_ef574714d9f7.jpg','Read more','/about',30,1,'2026-09-23 18:44:09',NULL,'2026-09-23 18:44:18',NULL,NULL,NULL);
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_settings` (
  `setting_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `updated_at`, `updated_by`) VALUES ('hero_size_preset','lg','2026-09-23 19:05:49',1);
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `email_active` varchar(191) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (if((`deleted_at` is null),`email`,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email_active` (`email_active`),
  KEY `idx_users_role_id` (`role_id`),
  KEY `idx_users_is_active` (`is_active`),
  KEY `idx_users_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role_id`, `is_active`, `last_login_at`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`) VALUES (1,'Super Admin','superadmin@example.com','$2y$10$wPMOCMS2A0E/vGhJ1fJNhuyYN0R/00n0B8gDJ8/kGS9vrAmrMaNwi',1,1,'2026-09-23 18:38:45','2026-09-23 17:15:20',NULL,'2026-09-23 18:38:45',NULL,NULL,NULL),(2,'Editor One','editor1@example.com','$2y$10$Fghd/KxwWp5sKbwESdSJb.bUcfSg3ItdDdaysyxIpEDykeHm0fEQy',3,1,'2026-09-23 17:23:15','2026-09-23 17:23:15',1,'2026-09-23 17:23:15',1,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'wms_core'
--

--
-- Dumping routines for database 'wms_core'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-23 22:13:23
