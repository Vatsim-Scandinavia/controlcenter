/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.1.2-MariaDB, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: controlcenter
-- ------------------------------------------------------
-- Server version	8.4.8

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('DEBUG','INFO','WARNING','DANGER') NOT NULL,
  `category` enum('ACCESS','TRAINING','BOOKING','ENDORSEMENT','OTHER') DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `message` longtext NOT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `api_keys`
--

DROP TABLE IF EXISTS `api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_keys` (
  `id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `read_only` tinyint(1) NOT NULL DEFAULT '1',
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  UNIQUE KEY `api_keys_id_unique` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_keys`
--

LOCK TABLES `api_keys` WRITE;
/*!40000 ALTER TABLE `api_keys` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `api_keys` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `area_endorsement`
--

DROP TABLE IF EXISTS `area_endorsement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `area_endorsement` (
  `area_id` int unsigned NOT NULL,
  `endorsement_id` bigint unsigned NOT NULL,
  KEY `area_endorsement_area_id_foreign` (`area_id`),
  KEY `area_endorsement_endorsement_id_foreign` (`endorsement_id`),
  CONSTRAINT `area_endorsement_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `area_endorsement_endorsement_id_foreign` FOREIGN KEY (`endorsement_id`) REFERENCES `endorsements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `area_endorsement`
--

LOCK TABLES `area_endorsement` WRITE;
/*!40000 ALTER TABLE `area_endorsement` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `area_endorsement` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `area_rating`
--

DROP TABLE IF EXISTS `area_rating`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `area_rating` (
  `area_id` int unsigned NOT NULL,
  `rating_id` int unsigned NOT NULL,
  `required_vatsim_rating` int unsigned DEFAULT NULL,
  `allow_bundling` tinyint(1) DEFAULT NULL,
  `hour_requirement` int DEFAULT NULL,
  `queue_length_low` int unsigned DEFAULT NULL,
  `queue_length_high` int unsigned DEFAULT NULL,
  PRIMARY KEY (`area_id`,`rating_id`),
  KEY `country_rating_rating_id_foreign` (`rating_id`),
  CONSTRAINT `country_rating_country_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`),
  CONSTRAINT `country_rating_rating_id_foreign` FOREIGN KEY (`rating_id`) REFERENCES `ratings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `area_rating`
--

LOCK TABLES `area_rating` WRITE;
/*!40000 ALTER TABLE `area_rating` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `area_rating` VALUES
(1,2,NULL,NULL,NULL,NULL,NULL),
(1,3,3,NULL,NULL,NULL,NULL),
(1,4,4,NULL,NULL,NULL,NULL),
(1,12,3,NULL,NULL,NULL,NULL),
(1,13,4,NULL,NULL,NULL,NULL),
(2,2,NULL,NULL,NULL,NULL,NULL),
(2,3,3,NULL,NULL,NULL,NULL),
(2,4,4,NULL,NULL,NULL,NULL),
(3,2,NULL,NULL,NULL,NULL,NULL),
(3,3,3,NULL,NULL,NULL,NULL),
(3,4,4,NULL,NULL,NULL,NULL),
(3,14,5,NULL,NULL,NULL,NULL),
(4,2,NULL,NULL,NULL,NULL,NULL),
(4,3,3,NULL,NULL,NULL,NULL),
(4,4,4,NULL,NULL,NULL,NULL),
(4,8,3,NULL,NULL,NULL,NULL),
(4,9,4,NULL,NULL,NULL,NULL),
(4,15,5,NULL,NULL,NULL,NULL),
(5,2,NULL,NULL,NULL,NULL,NULL),
(5,3,3,NULL,NULL,NULL,NULL),
(5,4,4,NULL,NULL,NULL,NULL),
(5,10,3,NULL,NULL,NULL,NULL),
(5,11,4,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `area_rating` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `areas`
--

DROP TABLE IF EXISTS `areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `areas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `contact` varchar(255) NOT NULL,
  `waiting_time` varchar(255) DEFAULT NULL,
  `template_newreq` text,
  `template_newmentor` text,
  `template_pretraining` text,
  `readme_url` varchar(255) DEFAULT NULL,
  `feedback_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `areas`
--

LOCK TABLES `areas` WRITE;
/*!40000 ALTER TABLE `areas` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `areas` VALUES
(1,'Denmark','training-denmark@vatsim-scandinavia.org',NULL,NULL,NULL,NULL,NULL,NULL),
(2,'Finland','training-finland@vatsim-scandinavia.org',NULL,NULL,NULL,NULL,NULL,NULL),
(3,'Iceland','training-iceland@vatsim-scandinavia.org',NULL,NULL,NULL,NULL,NULL,NULL),
(4,'Norway','training-norway@vatsim-scandinavia.org',NULL,NULL,NULL,NULL,NULL,NULL),
(5,'Sweden','training-sweden@vatsim-scandinavia.org',NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `areas` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `atc_activities`
--

DROP TABLE IF EXISTS `atc_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `atc_activities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `area_id` int unsigned NOT NULL,
  `hours` double NOT NULL DEFAULT '0',
  `start_of_grace_period` timestamp NULL DEFAULT NULL,
  `atc_active` tinyint(1) NOT NULL DEFAULT '0',
  `last_inactivity_warning` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_online` timestamp NULL DEFAULT NULL,
  `hours_in_period` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `atc_activities_user_id_area_id_unique` (`user_id`,`area_id`),
  KEY `atc_activities_area_id_foreign` (`area_id`),
  CONSTRAINT `atc_activities_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `atc_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `atc_activities`
--

LOCK TABLES `atc_activities` WRITE;
/*!40000 ALTER TABLE `atc_activities` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `atc_activities` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source` enum('CC','VATBOOK','DISCORD') NOT NULL DEFAULT 'CC',
  `vatsim_booking` int DEFAULT NULL,
  `callsign` varchar(30) NOT NULL,
  `position_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `time_start` datetime NOT NULL,
  `time_end` datetime NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `training` tinyint(1) NOT NULL DEFAULT '0',
  `event` tinyint(1) NOT NULL DEFAULT '0',
  `exam` tinyint(1) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vatbooks_user_id_foreign` (`user_id`),
  KEY `vatbooks_position_id_foreign` (`position_id`),
  CONSTRAINT `vatbooks_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vatbooks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `endorsement_position`
--

DROP TABLE IF EXISTS `endorsement_position`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `endorsement_position` (
  `endorsement_id` bigint unsigned NOT NULL,
  `position_id` bigint unsigned NOT NULL,
  KEY `endorsement_position_endorsement_id_foreign` (`endorsement_id`),
  KEY `endorsement_position_position_id_foreign` (`position_id`),
  CONSTRAINT `endorsement_position_endorsement_id_foreign` FOREIGN KEY (`endorsement_id`) REFERENCES `endorsements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `endorsement_position_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `endorsement_position`
--

LOCK TABLES `endorsement_position` WRITE;
/*!40000 ALTER TABLE `endorsement_position` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `endorsement_position` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `endorsement_rating`
--

DROP TABLE IF EXISTS `endorsement_rating`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `endorsement_rating` (
  `endorsement_id` bigint unsigned NOT NULL,
  `rating_id` int unsigned NOT NULL,
  KEY `endorsement_rating_endorsement_id_foreign` (`endorsement_id`),
  KEY `endorsement_rating_rating_id_foreign` (`rating_id`),
  CONSTRAINT `endorsement_rating_endorsement_id_foreign` FOREIGN KEY (`endorsement_id`) REFERENCES `endorsements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `endorsement_rating_rating_id_foreign` FOREIGN KEY (`rating_id`) REFERENCES `ratings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `endorsement_rating`
--

LOCK TABLES `endorsement_rating` WRITE;
/*!40000 ALTER TABLE `endorsement_rating` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `endorsement_rating` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `endorsements`
--

DROP TABLE IF EXISTS `endorsements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `endorsements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(32) NOT NULL,
  `valid_from` datetime NOT NULL,
  `valid_to` datetime DEFAULT NULL,
  `expired` tinyint(1) NOT NULL DEFAULT '0',
  `revoked` tinyint(1) NOT NULL DEFAULT '0',
  `issued_by` bigint unsigned DEFAULT NULL,
  `revoked_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `endorsements_user_id_foreign` (`user_id`),
  KEY `endorsements_issued_by_foreign` (`issued_by`),
  KEY `endorsements_revoked_by_foreign` (`revoked_by`),
  CONSTRAINT `endorsements_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `endorsements_revoked_by_foreign` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `endorsements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `endorsements`
--

LOCK TABLES `endorsements` WRITE;
/*!40000 ALTER TABLE `endorsements` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `endorsements` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `feedback` text NOT NULL,
  `submitter_user_id` bigint unsigned NOT NULL,
  `reference_user_id` bigint unsigned DEFAULT NULL,
  `reference_position_id` bigint unsigned DEFAULT NULL,
  `forwarded` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `feedback_submitter_user_id_foreign` (`submitter_user_id`),
  KEY `feedback_reference_user_id_foreign` (`reference_user_id`),
  KEY `feedback_reference_position_id_foreign` (`reference_position_id`),
  CONSTRAINT `feedback_reference_position_id_foreign` FOREIGN KEY (`reference_position_id`) REFERENCES `positions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `feedback_reference_user_id_foreign` FOREIGN KEY (`reference_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `feedback_submitter_user_id_foreign` FOREIGN KEY (`submitter_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `files` (
  `id` varchar(255) NOT NULL,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `files_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `files_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `files`
--

LOCK TABLES `files` WRITE;
/*!40000 ALTER TABLE `files` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `files` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `groups`
--

LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `groups` VALUES
(1,'Administrator','Rank meant for vACC Director, Training Director and technicaians, access to whole system.'),
(2,'Moderator','Access meant for FIR Director and Training assistants to have full control over trainings and statistics.'),
(3,'Mentor','Access meant for mentors, to give them mentor-related functionality.'),
(4,'Buddy','Access meant for buddies, to give them buddy-related functionality.');
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `migrations` VALUES
(1,'2020_03_08_000010_create_groups_table',1),
(2,'2020_03_08_100000_create_ratings_table',1),
(3,'2020_03_08_100020_create_countries_table',1),
(4,'2020_03_08_100030_create_country_rating',1),
(5,'2020_03_08_171820_create_positions_table',1),
(6,'2020_03_08_200000_create_users_table',1),
(7,'2020_03_08_200020_create_trainings_table',1),
(8,'2020_03_08_200030_create_training_reports_table',1),
(9,'2020_03_08_200040_create_training_object_attachments_table',1),
(10,'2020_03_08_200050_create_rating_user',1),
(11,'2020_03_08_200060_create_training_mentor_table',1),
(12,'2020_03_08_200080_create_rating_training_table',1),
(13,'2020_03_08_210515_create_sweatbooks_table',1),
(14,'2020_03_09_204817_create_solo_endorsements_table',1),
(15,'2020_03_10_201552_create_vatbooks_table',1),
(16,'2020_04_15_193948_create_files_table',1),
(17,'2020_04_18_204006_create_training_examinations_table',1),
(18,'2020_05_08_141436_create_trainingRole_country_table',1),
(19,'2020_05_21_140316_create_notifications_table',1),
(20,'2020_05_21_142025_create_jobs_table',1),
(21,'2020_05_30_175009_create_failed_jobs_table',1),
(22,'2020_05_31_192855_create_settings_table',1),
(23,'2020_06_10_190421_create_votes_table',1),
(24,'2020_06_10_190641_create_vote_options_table',1),
(25,'2020_06_10_190844_create_user_vote_table',1),
(26,'2020_08_24_191843_create_activity_logs',1),
(27,'2020_09_11_193706_create_one_time_links_table',1),
(28,'2020_10_03_151333_create_training_interests',1),
(29,'2020_10_09_115555_add_ratings_to_positions_table',1),
(30,'2020_10_10_192309_add_training_interests_expire_columns',1),
(31,'2020_11_19_203252_add_votes_table_vatsca_role_column',1),
(32,'2020_11_21_103028_add_pretraining_column_countries',1),
(33,'2020_12_01_050819_add_exam_column_to_vatbooks',1),
(34,'2020_12_03_002236_add_country_column_to_positions',1),
(35,'2021_01_29_001536_add_mae_column_to_positions',1),
(36,'2021_02_21_214807_rename_votes_member_column',1),
(37,'2021_02_22_181838_add_new_settings',1),
(38,'2021_02_25_075635_create_atc_activity_table',1),
(39,'2021_02_25_195620_add_new_groups',1),
(40,'2021_02_25_200220_create_permissions_table',1),
(41,'2021_02_27_154220_delete_old_permission_system',1),
(42,'2021_03_02_202644_change_country_to_areas',1),
(43,'2021_03_20_130343_add_new_estimate_columns',1),
(44,'2021_03_20_140940_add_traininginterval_setting',1),
(45,'2021_04_12_184638_create_positions_freq_column',1),
(46,'2021_05_13_154011_add_workmail_to_users',1),
(47,'2021_05_13_195341_add_acticity_category_enums',1),
(48,'2021_05_24_114034_add_grp_bundle_boolean_to_ratings',1),
(49,'2021_07_27_194313_add_new_acitivty_contact_row',1),
(50,'2021_08_28_193923_add_solo_req_setting',1),
(51,'2022_01_01_211040_vatbook_sources',1),
(52,'2022_02_10_220022_add_vatsim_booking_column',1),
(53,'2022_02_27_092504_create_endorsement_table',1),
(54,'2022_02_27_104538_alter_endorsement_pivot',1),
(55,'2022_02_27_105736_delete_solo_table',1),
(56,'2022_04_23_143945_delete_group_for_examiners',1),
(57,'2022_05_08_511291_add_examsheet_setting',1),
(58,'2022_05_14_000001_create_api_tokens_table',1),
(59,'2022_05_14_081018_delete_passport_tables',1),
(60,'2022_05_15_095715_add_endorsement_log_type',1),
(61,'2022_05_16_180346_create_training_activity_table',1),
(62,'2022_05_27_110006_add_user_activity_column',1),
(63,'2022_07_30_141538_add_new_settings',1),
(64,'2022_07_30_180006_add_user_warning_column',1),
(65,'2022_11_18_210255_add_rating_req_hours',1),
(66,'2022_11_19_142147_remove_vatbook_add_booking',1),
(67,'2022_12_10_122309_add_s1_template_to_areas',1),
(68,'2022_12_30_135008_alter_atcativity_table',1),
(69,'2023_02_12_114913_add_telemetry_setting',1),
(70,'2023_05_18_100944_add_oauth_user_fields',1),
(71,'2023_05_19_073653_transfer_handover_data_to_local',1),
(72,'2023_08_26_184643_fix_utc_defaults',1),
(73,'2023_10_01_194017_add_allow_inactive_controlling_setting',1),
(74,'2023_10_01_200047_add_cronjob_datetime',1),
(75,'2023_10_02_132846_add_createdby_training_column',1),
(76,'2023_10_04_085425_tasks',1),
(77,'2023_10_05_182438_add_notify_user_setting',1),
(78,'2023_10_08_144001_add_feedback',1),
(79,'2024_02_03_095300_add_areas_to_atc_activities',1),
(80,'2024_02_03_115826_add_totalhours_setting',1),
(81,'2024_02_04_093141_recalculate_atc_activity_grace_periods',1),
(82,'2024_02_04_111709_remove_s1_endorsements',1),
(83,'2024_02_04_113019_delete_template_s1_positions',1),
(84,'2024_02_06_203837_add_area_feedback_url',1),
(85,'2024_02_13_172836_rename_position_area_to_id_col',1),
(86,'2024_02_13_182836_migrate_atc_active_to_area',1),
(87,'2024_02_13_221808_rename_settings',1),
(88,'2024_02_18_164045_add_division_api_setting',1),
(89,'2024_02_18_203134_add_endorsement_type_to_ratings',1),
(90,'2024_02_22_175840_add_subject_training_rating_id_column',1),
(91,'2024_03_26_175154_extend_booking_callsign_characters',1),
(92,'2024_07_04_124209_add_pretraining_completed_check',1),
(93,'2024_07_08_194628_add_trainingactivity_pretraining_type',1),
(94,'2024_07_10_094930_change_objects_to_uuid',1),
(95,'2024_07_17_162745_add_required_endorsement_id_to_positions',1),
(96,'2024_07_19_103139_add_area_waiting_time_string',1),
(97,'2024_07_19_123109_rename_masc_to_facility',1),
(98,'2024_12_27_131934_add_area_readme_url',1),
(99,'2025_08_17_062853_add_last_atc_inactivity_reminder',1),
(100,'2025_08_17_065024_add_atc_reminder_setting',1),
(101,'2025_10_10_202944_add_buddy_group',1),
(102,'2026_01_11_231233_add_theme_setting',1),
(103,'2026_01_24_034000_update_atc_activity_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `one_time_links`
--

DROP TABLE IF EXISTS `one_time_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `one_time_links` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `training_id` bigint unsigned NOT NULL,
  `training_object_type` varchar(255) NOT NULL,
  `key` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `one_time_links_training_id_foreign` (`training_id`),
  CONSTRAINT `one_time_links_training_id_foreign` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `one_time_links`
--

LOCK TABLES `one_time_links` WRITE;
/*!40000 ALTER TABLE `one_time_links` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `one_time_links` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `user_id` bigint unsigned NOT NULL,
  `area_id` int unsigned NOT NULL,
  `group_id` int unsigned NOT NULL,
  `inserted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`,`area_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `positions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `callsign` varchar(30) NOT NULL,
  `name` varchar(30) NOT NULL,
  `frequency` varchar(7) DEFAULT NULL,
  `fir` varchar(4) NOT NULL,
  `area_id` int unsigned DEFAULT NULL,
  `rating` int unsigned NOT NULL,
  `required_facility_rating_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `positions_country_foreign` (`area_id`),
  KEY `positions_required_facility_rating_id_foreign` (`required_facility_rating_id`),
  CONSTRAINT `positions_country_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `positions_required_facility_rating_id_foreign` FOREIGN KEY (`required_facility_rating_id`) REFERENCES `ratings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=403 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `positions`
--

LOCK TABLES `positions` WRITE;
/*!40000 ALTER TABLE `positions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `positions` VALUES
(1,'EKAH_APP','Aarhus Approach',NULL,'EKDK',1,4,NULL),
(2,'EKAH_TWR','Aarhus Tower',NULL,'EKDK',1,3,NULL),
(3,'EKBI_APP','Billund Approach',NULL,'EKDK',1,4,NULL),
(4,'EKBI_TWR','Billund Tower',NULL,'EKDK',1,3,NULL),
(5,'EKBI_F_APP','Billund Arrival',NULL,'EKDK',1,4,NULL),
(6,'EKCH_E_DEP','Kastrup Departure',NULL,'EKDK',1,4,NULL),
(7,'EKCH_F_APP','Kastrup Final',NULL,'EKDK',1,4,NULL),
(8,'EKCH_DEP','Kastrup Departure',NULL,'EKDK',1,4,NULL),
(9,'EKCH_E_APP','Copenhagen Approach',NULL,'EKDK',1,4,NULL),
(10,'EKCH_DEL','Kastrup Delivery',NULL,'EKDK',1,2,NULL),
(11,'EKCH_GND','Kastrup Apron',NULL,'EKDK',1,2,NULL),
(12,'EKCH_APP','Copenhagen Approach',NULL,'EKDK',1,4,NULL),
(13,'EKCH_A_TWR','Kastrup Tower',NULL,'EKDK',1,3,NULL),
(14,'EKCH_C_TWR','Kastrup Tower',NULL,'EKDK',1,3,NULL),
(15,'EKCH_D_TWR','Kastrup Tower',NULL,'EKDK',1,3,NULL),
(16,'EKCH_TWR','Kastrup Tower',NULL,'EKDK',1,3,NULL),
(17,'EKCH_A_GND','Kastrup Apron',NULL,'EKDK',1,2,NULL),
(18,'EKDK_CTR','Copenhagen Control',NULL,'EKDK',1,5,NULL),
(19,'EKDK_B_CTR','Copenhagen Control',NULL,'EKDK',1,5,NULL),
(20,'EKDK_C_CTR','Copenhagen Control',NULL,'EKDK',1,5,NULL),
(21,'EKDK_D_CTR','Copenhagen Control',NULL,'EKDK',1,5,NULL),
(22,'EKDK_S_CTR','Copenhagen Control',NULL,'EKDK',1,5,NULL),
(23,'EKDK_N_CTR','Copenhagen Control',NULL,'EKDK',1,5,NULL),
(24,'EKDK_V_CTR','Copenhagen Control',NULL,'EKDK',1,5,NULL),
(25,'EKDK_I_CTR','Copenhagen Information',NULL,'EKDK',1,5,NULL),
(26,'EKEB_I_TWR','Esbjerg AFIS',NULL,'EKDK',1,3,NULL),
(27,'EKKA_APP','Karup Approach',NULL,'EKDK',1,4,NULL),
(28,'EKKA_TWR','Karup Tower',NULL,'EKDK',1,3,NULL),
(29,'EKOD_I_TWR','Odense AFIS',NULL,'EKDK',1,3,NULL),
(30,'EKRK_APP','Roskilde Approach',NULL,'EKDK',1,4,NULL),
(31,'EKRK_TWR','Roskilde Tower',NULL,'EKDK',1,3,NULL),
(32,'EKRN_TWR','Rønne Tower',NULL,'EKDK',1,3,NULL),
(33,'EKSB_I_TWR','Sønderborg AFIS',NULL,'EKDK',1,3,NULL),
(34,'EKSP_TWR','Skrydstrup Approach',NULL,'EKDK',1,3,NULL),
(35,'EKSP_APP','Skrydstrup Approach',NULL,'EKDK',1,4,NULL),
(36,'EKYT_TWR','Aalborg Tower',NULL,'EKDK',1,3,NULL),
(37,'EKYT_APP','Aalborg Approach',NULL,'EKDK',1,4,NULL),
(38,'EKYT_F_APP','Aalborg Arrival',NULL,'EKDK',1,4,NULL),
(39,'EFIN_A_CTR','Helsinki Control',NULL,'EFIN',2,5,NULL),
(40,'EFIN_B_CTR','Helsinki Control',NULL,'EFIN',2,5,NULL),
(41,'EFIN_C_CTR','Helsinki Control',NULL,'EFIN',2,5,NULL),
(42,'EFIN_D_CTR','Helsinki Control',NULL,'EFIN',2,5,NULL),
(43,'EFIN_E_CTR','Helsinki Control',NULL,'EFIN',2,5,NULL),
(44,'EFIN_F_CTR','Helsinki Control',NULL,'EFIN',2,5,NULL),
(45,'EFIN_G_CTR','Helsinki Control',NULL,'EFIN',2,5,NULL),
(46,'EFIN_H_CTR','Helsinki Control',NULL,'EFIN',2,5,NULL),
(47,'EFIN_J_CTR','Helsinki Control',NULL,'EFIN',2,5,NULL),
(48,'EFIN_K_CTR','Helsinki Control',NULL,'EFIN',2,5,NULL),
(49,'EFIN_L_CTR','Helsinki Control',NULL,'EFIN',2,5,NULL),
(50,'EFIN_M_CTR','Helsinki Control',NULL,'EFIN',2,5,NULL),
(51,'EFIN_N_CTR','Helsinki Control',NULL,'EFIN',2,5,NULL),
(52,'EFIN_V_CTR','Helsinki Control',NULL,'EFIN',2,5,NULL),
(53,'EFIN_Y_CTR','Helsinki Control',NULL,'EFIN',2,5,NULL),
(54,'EFIN_Z_CTR','Helsinki Control',NULL,'EFIN',2,5,NULL),
(55,'EFIN_Y_APP','Helsinki Control',NULL,'EFIN',2,4,NULL),
(56,'EFIN_Z_APP','Helsinki Control',NULL,'EFIN',2,4,NULL),
(57,'EFET_I_TWR','Enontekio AFIS',NULL,'EFIN',2,3,NULL),
(58,'EFHA_APP','Halli Radar',NULL,'EFIN',2,4,NULL),
(59,'EFHA_TWR','Halli Tower',NULL,'EFIN',2,3,NULL),
(60,'EFHK_W_APP','Helsinki Radar',NULL,'EFIN',2,4,NULL),
(61,'EFHK_E_APP','Helsinki Radar',NULL,'EFIN',2,4,NULL),
(62,'EFHK_R_APP','Helsinki Arrival',NULL,'EFIN',2,4,NULL),
(63,'EFHK_A_APP','Helsinki Arrival',NULL,'EFIN',2,4,NULL),
(64,'EFHK_E_TWR','Helsinki Tower',NULL,'EFIN',2,3,NULL),
(65,'EFHK_W_TWR','Helsinki Tower',NULL,'EFIN',2,3,NULL),
(66,'EFHK_GND','Helsinki Ground',NULL,'EFIN',2,2,NULL),
(67,'EFHK_DEL','Helsinki Ground',NULL,'EFIN',2,2,NULL),
(68,'EFIV_TWR','Ivalo Tower',NULL,'EFIN',2,3,NULL),
(69,'EFIV_I_TWR','Ivalo AFIS',NULL,'EFIN',2,3,NULL),
(70,'EFJO_TWR','Joensuu Tower',NULL,'EFIN',2,3,NULL),
(71,'EFJY_APP','Jyvaskylä Radar',NULL,'EFIN',2,4,NULL),
(72,'EFJY_R_APP','Jyvaskyla Arrival',NULL,'EFIN',2,4,NULL),
(73,'EFJY_TWR','Jyväskylä Tower',NULL,'EFIN',2,3,NULL),
(74,'EFJY_GND','Jyväskylä Ground',NULL,'EFIN',2,2,NULL),
(75,'EFKE_TWR','Kemi Tower',NULL,'EFIN',2,3,NULL),
(76,'EFKI_I_TWR','Kajaani AFIS',NULL,'EFIN',2,3,NULL),
(77,'EFKK_APP','Kruunu Radar',NULL,'EFIN',2,4,NULL),
(78,'EFKK_TWR','Kruunu Tower',NULL,'EFIN',2,3,NULL),
(79,'EFKS_TWR','Kuusamo Tower',NULL,'EFIN',2,3,NULL),
(80,'EFKS_I_TWR','Kuusamo AFIS',NULL,'EFIN',2,3,NULL),
(81,'EFKT_APP','Kittilä Radar',NULL,'EFIN',2,4,NULL),
(82,'EFKT_TWR','Kittilä Tower',NULL,'EFIN',2,3,NULL),
(83,'EFKT_I_TWR','Kittila AFIS',NULL,'EFIN',2,3,NULL),
(84,'EFKU_APP','Kuopio Radar',NULL,'EFIN',2,4,NULL),
(85,'EFKU_R_APP','Kuopio Arrival',NULL,'EFIN',2,4,NULL),
(86,'EFKU_TWR','Kuopio Tower',NULL,'EFIN',2,3,NULL),
(87,'EFLP_TWR','Lappeenranta Tower',NULL,'EFIN',2,3,NULL),
(88,'EFMA_APP','Mariehamn Radar',NULL,'EFIN',2,4,NULL),
(89,'EFMA_TWR','Mariehamn Tower',NULL,'EFIN',2,3,NULL),
(90,'EFMI_I_TWR','Mikkeli AFIS',NULL,'EFIN',2,3,NULL),
(91,'EFOU_APP','Oulu Radar',NULL,'EFIN',2,4,NULL),
(92,'EFOU_TWR','Oulu Tower',NULL,'EFIN',2,3,NULL),
(93,'EFPO_APP','Pori Radar',NULL,'EFIN',2,4,NULL),
(94,'EFPO_TWR','Pori Tower',NULL,'EFIN',2,3,NULL),
(95,'EFRO_APP','Rovaniemi Radar',NULL,'EFIN',2,4,NULL),
(96,'EFRO_R_APP','Rovaniemi Arrival',NULL,'EFIN',2,4,NULL),
(97,'EFRO_TWR','Rovaniemi Tower',NULL,'EFIN',2,3,NULL),
(98,'EFRO_DEL','Rovaniemi Delivery',NULL,'EFIN',2,2,NULL),
(99,'EFSA_I_TWR','Savonlinna AFIS',NULL,'EFIN',2,3,NULL),
(100,'EFSI_I_TWR','Seinajoki AFIS',NULL,'EFIN',2,3,NULL),
(101,'EFTP_APP','Pirkkala Radar',NULL,'EFIN',2,4,NULL),
(102,'EFTP_R_APP','Pirkkala Arrival',NULL,'EFIN',2,4,NULL),
(103,'EFTP_TWR','Pirkkala Tower',NULL,'EFIN',2,3,NULL),
(104,'EFTP_GND','Pirkkala Ground',NULL,'EFIN',2,2,NULL),
(105,'EFTU_APP','Turku Radar',NULL,'EFIN',2,4,NULL),
(106,'EFTU_TWR','Turku Tower',NULL,'EFIN',2,3,NULL),
(107,'EFUT_TWR','Utti Tower',NULL,'EFIN',2,3,NULL),
(108,'EFVA_APP','Vaasa Radar',NULL,'EFIN',2,4,NULL),
(109,'EFVA_TWR','Vaasa Tower',NULL,'EFIN',2,3,NULL),
(110,'BIRD_CTR','Reykjavik Control',NULL,'BIRD',3,5,NULL),
(111,'BIRD_1_CTR','Reykjavik Control',NULL,'BIRD',3,5,NULL),
(112,'BIRD_2_CTR','Reykjavik Control',NULL,'BIRD',3,5,NULL),
(113,'BIRD_3_CTR','Reykjavik Control',NULL,'BIRD',3,5,NULL),
(114,'BIRD_4_CTR','Reykjavik Control',NULL,'BIRD',3,5,NULL),
(115,'BIRD_5_CTR','Reykjavik Control',NULL,'BIRD',3,5,NULL),
(116,'BIRD_6_CTR','Reykjavik Control',NULL,'BIRD',3,5,NULL),
(117,'BICC_FSS','Iceland Radio',NULL,'BIRD',3,5,NULL),
(118,'BIKF_APP','Keflavik Approach',NULL,'BIRD',3,4,NULL),
(119,'BIKF_F_APP','Keflavik Approach',NULL,'BIRD',3,4,NULL),
(120,'BIKF_TWR','Keflavik Tower',NULL,'BIRD',3,3,NULL),
(121,'BIKF_GND','Keflavik Ground',NULL,'BIRD',3,2,NULL),
(122,'BIKF_2_GND','Keflavik Ground',NULL,'BIRD',3,2,NULL),
(123,'BIKF_DEL','Keflavik Delivery',NULL,'BIRD',3,2,NULL),
(124,'BIRK_APP','Reykjavik Approach',NULL,'BIRD',3,4,NULL),
(125,'BIRK_TWR','Reykjavik Tower',NULL,'BIRD',3,3,NULL),
(126,'BIRK_GND','Reykjavik Ground',NULL,'BIRD',3,2,NULL),
(127,'BIAR_APP','Akureyri Radar',NULL,'BIRD',3,4,NULL),
(128,'BIAR_TWR','Akureyri Tower',NULL,'BIRD',3,3,NULL),
(129,'BIIS_I_TWR','Ísafjörður Radio',NULL,'BIRD',3,3,NULL),
(130,'EKVG_I_TWR','Vágar AFIS',NULL,'BIRD',3,3,NULL),
(131,'BIVM_I_TWR','Vestmannaeyjar Information',NULL,'BIRD',3,3,NULL),
(132,'BIBA_I_TWR','Bakki Information',NULL,'BIRD',3,3,NULL),
(133,'BIEG_I_TWR','Egilsstadir Information',NULL,'BIRD',3,3,NULL),
(134,'BIBD_I_TWR','Bildudalur Radio',NULL,'BIRD',3,3,NULL),
(135,'BIBL_I_TWR','Blonduos Information',NULL,'BIRD',3,3,NULL),
(136,'BIGJ_I_TWR','Gjogur Information',NULL,'BIRD',3,3,NULL),
(137,'BIGR_I_TWR','Grimsey Information',NULL,'BIRD',3,3,NULL),
(138,'BIHK_I_TWR','Holmavik Information',NULL,'BIRD',3,3,NULL),
(139,'BIHN_I_TWR','Hornafjodur Radio',NULL,'BIRD',3,3,NULL),
(140,'BIHU_I_TWR','Husavik Radio',NULL,'BIRD',3,3,NULL),
(141,'BINF_I_TWR','Nordfjordur Information',NULL,'BIRD',3,3,NULL),
(142,'BIKR_I_TWR','Saudarkrokur Radio',NULL,'BIRD',3,3,NULL),
(143,'BIRF_I_TWR','Rif Information',NULL,'BIRD',3,3,NULL),
(144,'BITE_I_TWR','Thingeyri Information',NULL,'BIRD',3,3,NULL),
(145,'BITN_I_TWR','Thorshofn Radio',NULL,'BIRD',3,3,NULL),
(146,'BIVO_I_TWR','Vopnafjordur Information',NULL,'BIRD',3,3,NULL),
(147,'BGGL_FSS','Nuuk Information',NULL,'BGGL',3,5,NULL),
(148,'BGSF_TWR','Søndrestrøm Tower',NULL,'BGGL',3,3,NULL),
(149,'BGSF_APP','Søndrestrøm Approach',NULL,'BGGL',3,4,NULL),
(150,'BGBW_I_TWR','Narsarsuaq AFIS',NULL,'BGGL',3,3,NULL),
(151,'BGGH_I_TWR','Nuuk AFIS',NULL,'BGGL',3,3,NULL),
(152,'BGKK_I_TWR','Kulusuk AFIS',NULL,'BGGL',3,3,NULL),
(153,'BGTL_APP','Thule Approach',NULL,'BGGL',3,4,NULL),
(154,'BGTL_TWR','Thule Tower',NULL,'BGGL',3,3,NULL),
(155,'BGTL_GND','Thule Ground',NULL,'BGGL',3,2,NULL),
(156,'BGCO_TWR','Constable Pynt Tower',NULL,'BGGL',3,3,NULL),
(157,'ENAL_APP','Møre Approach',NULL,'ENOR',4,4,NULL),
(158,'ENAL_TWR','Vigra Tower',NULL,'ENOR',4,3,NULL),
(159,'ENAN_APP','Andøya Tower',NULL,'ENOR',4,4,NULL),
(160,'ENAN_TWR','Andøya Tower',NULL,'ENOR',4,3,NULL),
(161,'ENAT_TWR','Alta Tower',NULL,'ENOR',4,3,NULL),
(162,'ENAT_APP','Alta Tower',NULL,'ENOR',4,4,NULL),
(163,'ENBN_APP','Polaris Control (Helgeland)',NULL,'ENOR',4,4,NULL),
(164,'ENBD_S_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(165,'ENBD_9_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(166,'ENBD_C_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(167,'ENBD_O_CTR','Polaris Control (Offshore)',NULL,'ENOR',4,5,NULL),
(168,'ENBD_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(169,'ENBD_E_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(170,'ENBL_I_TWR','Bringeland Information',NULL,'ENOR',4,3,NULL),
(171,'ENBN_I_TWR','Brønnøy Information',NULL,'ENOR',4,3,NULL),
(172,'ENBO_APP','Bodø Approach',NULL,'ENOR',4,4,NULL),
(173,'ENBO_TWR','Bodø Tower',NULL,'ENOR',4,3,NULL),
(174,'ENBO_GND','Bodø Ground',NULL,'ENOR',4,2,NULL),
(175,'ENBR_W_APP','Flesland Approach',NULL,'ENOR',4,4,NULL),
(176,'ENBR_E_APP','Flesland Approach',NULL,'ENOR',4,4,NULL),
(177,'ENBR_D_APP','Flesland Director',NULL,'ENOR',4,4,NULL),
(178,'ENBR_TWR','Flesland Tower',NULL,'ENOR',4,3,NULL),
(179,'ENBR_GND','Flesland Ground',NULL,'ENOR',4,2,NULL),
(180,'ENBR_DEL','Flesland Delivery',NULL,'ENOR',4,2,NULL),
(181,'ENBS_I_TWR','Båtsfjord Information',NULL,'ENOR',4,3,NULL),
(182,'ENBV_I_TWR','Berlevåg Information',NULL,'ENOR',4,3,NULL),
(183,'ENCN_APP','Kjevik Tower/Approach',NULL,'ENOR',4,4,NULL),
(184,'ENCN_TWR','Kjevik Tower',NULL,'ENOR',4,3,NULL),
(185,'ENDU_APP','Bardufoss Approach',NULL,'ENOR',4,4,NULL),
(186,'ENDU_TWR','Bardufoss Tower',NULL,'ENOR',4,3,NULL),
(187,'ENDU_DEL','Bardufoss Delivery',NULL,'ENOR',4,2,NULL),
(188,'ENEV_TWR','Evenes Tower',NULL,'ENOR',4,3,NULL),
(189,'ENEV_APP','Evenes Tower/Approach',NULL,'ENOR',4,4,NULL),
(190,'ENFL_I_TWR','Florø Information',NULL,'ENOR',4,3,NULL),
(191,'ENGK_I_TWR','Gullknapp Information',NULL,'ENOR',4,3,NULL),
(192,'ENGM_W_APP','Oslo Approach',NULL,'ENOR',4,4,NULL),
(193,'ENGM_E_APP','Oslo Approach',NULL,'ENOR',4,4,NULL),
(194,'ENGM_D_APP','Oslo Director',NULL,'ENOR',4,4,NULL),
(195,'ENGM_F_APP','Oslo Final',NULL,'ENOR',4,4,NULL),
(196,'ENGM_W_DEL','Gardermoen Delivery West',NULL,'ENOR',4,2,NULL),
(197,'ENGM_W_TWR','Gardermoen Tower West',NULL,'ENOR',4,3,NULL),
(198,'ENGM_W_GND','Gardermoen Ground West',NULL,'ENOR',4,2,NULL),
(199,'ENGM_E_DEL','Gardermoen Delivery East',NULL,'ENOR',4,2,NULL),
(200,'ENGM_E_TWR','Gardermoen Tower East',NULL,'ENOR',4,3,NULL),
(201,'ENGM_E_GND','Gardermoen Ground East',NULL,'ENOR',4,2,NULL),
(202,'ENGM_Q_GND','Gardermoen Ground (Sequencer)',NULL,'ENOR',4,2,NULL),
(203,'ENHD_TWR','Karmøy Tower',NULL,'ENOR',4,3,NULL),
(204,'ENHF_I_TWR','Hammerfest Information',NULL,'ENOR',4,3,NULL),
(205,'ENHK_I_TWR','Hasvik Information',NULL,'ENOR',4,3,NULL),
(206,'ENHV_I_TWR','Valan Information',NULL,'ENOR',4,3,NULL),
(207,'ENKB_APP','Møre Approach',NULL,'ENOR',4,4,NULL),
(208,'ENKB_TWR','Kvernberget Tower',NULL,'ENOR',4,3,NULL),
(209,'ENKR_TWR','Kirkenes Tower',NULL,'ENOR',4,3,NULL),
(210,'ENKR_APP','Kirkenes Tower',NULL,'ENOR',4,4,NULL),
(211,'ENLK_I_TWR','Leknes Information',NULL,'ENOR',4,3,NULL),
(212,'ENMH_I_TWR','Mehamn Information',NULL,'ENOR',4,3,NULL),
(213,'ENML_I_TWR','Molde Information',NULL,'ENOR',4,3,NULL),
(214,'ENMS_I_TWR','Mosjøen Information',NULL,'ENOR',4,3,NULL),
(215,'ENNA_TWR','Banak Tower',NULL,'ENOR',4,3,NULL),
(216,'ENNA_APP','Banak Tower',NULL,'ENOR',4,4,NULL),
(217,'ENNM_I_TWR','Namsos Information',NULL,'ENOR',4,3,NULL),
(218,'ENNO_I_TWR','Notodden Information',NULL,'ENOR',4,3,NULL),
(219,'ENOL_APP','Ørland Approach',NULL,'ENOR',4,4,NULL),
(220,'ENOL_TWR','Ørland Tower',NULL,'ENOR',4,3,NULL),
(221,'ENOL_A_APP','Ørland Arrivial',NULL,'ENOR',4,4,NULL),
(222,'ENOR_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(223,'ENOR_S_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(224,'ENOS_1_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(225,'ENOS_2_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(226,'ENOS_3_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(227,'ENOS_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(228,'ENOS_5_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(229,'ENOS_N_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(230,'ENOS_7_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(231,'ENOS_8_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(232,'ENOV_I_TWR','Hovden Information',NULL,'ENOR',4,3,NULL),
(233,'ENRA_I_TWR','Røssvoll Information',NULL,'ENOR',4,3,NULL),
(234,'ENRM_I_TWR','Rørvik Information',NULL,'ENOR',4,3,NULL),
(235,'ENRO_I_TWR','Røros Information',NULL,'ENOR',4,3,NULL),
(236,'ENRS_I_TWR','Røst Information',NULL,'ENOR',4,3,NULL),
(237,'ENRY_GND','Rygge Ground',NULL,'ENOR',4,2,NULL),
(238,'ENRY_TWR','Rygge Tower',NULL,'ENOR',4,3,NULL),
(239,'ENRY_APP','Farris Approach',NULL,'ENOR',4,4,NULL),
(240,'ENSD_I_TWR','Anda Information',NULL,'ENOR',4,3,NULL),
(241,'ENSG_I_TWR','Sogndal Information',NULL,'ENOR',4,3,NULL),
(242,'ENSH_I_TWR','Helle Information',NULL,'ENOR',4,3,NULL),
(243,'ENSK_I_TWR','Skagen Information',NULL,'ENOR',4,3,NULL),
(244,'ENSO_I_TWR','Sorstokken Information',NULL,'ENOR',4,3,NULL),
(245,'ENSR_I_TWR','Sørkjosen Information',NULL,'ENOR',4,3,NULL),
(246,'ENSS_I_TWR','Vardø Information',NULL,'ENOR',4,3,NULL),
(247,'ENST_I_TWR','Stokka Information',NULL,'ENOR',4,3,NULL),
(248,'ENSV_O_CTR','Polaris Control (Offshore)',NULL,'ENOR',4,5,NULL),
(249,'ENSV_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(250,'ENSV_N_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(251,'ENSV_5_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(252,'ENSV_4_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(253,'ENSV_E_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(254,'ENSV_0_CTR','Polaris Control',NULL,'ENOR',4,5,NULL),
(255,'ENTC_APP','Tromsø Approach',NULL,'ENOR',4,4,NULL),
(256,'ENTC_TWR','Tromsø Tower',NULL,'ENOR',4,3,NULL),
(257,'ENTO_APP','Farris Approach',NULL,'ENOR',4,4,NULL),
(258,'ENTO_TWR','Torp Tower',NULL,'ENOR',4,3,NULL),
(259,'ENTO_GND','Torp Ground',NULL,'ENOR',4,2,NULL),
(260,'ENVA_APP','Værnes Approach',NULL,'ENOR',4,4,NULL),
(261,'ENVA_D_APP','Værnes Director',NULL,'ENOR',4,4,NULL),
(262,'ENVA_TWR','Værnes Tower',NULL,'ENOR',4,3,NULL),
(263,'ENVA_GND','Værnes Ground',NULL,'ENOR',4,2,NULL),
(264,'ENVD_I_TWR','Vadsø Information',NULL,'ENOR',4,3,NULL),
(265,'ENZV_A_APP','Sola Arrival',NULL,'ENOR',4,4,NULL),
(266,'ENZV_APP','Sola Approach',NULL,'ENOR',4,4,NULL),
(267,'ENZV_D_APP','Sola Director',NULL,'ENOR',4,4,NULL),
(268,'ENZV_TWR','Sola Tower',NULL,'ENOR',4,3,NULL),
(269,'ENZV_GND','Sola Ground',NULL,'ENOR',4,2,NULL),
(270,'ENAS_I_TWR','Ny Ålesund Information',NULL,'ENOB',4,3,NULL),
(271,'ENOB_CTR','Bodø Oceanic Control',NULL,'ENOB',4,5,NULL),
(272,'ENSA_I_TWR','Svea Information',NULL,'ENOB',4,3,NULL),
(273,'ENSB_I_TWR','Longyear Information',NULL,'ENOB',4,3,NULL),
(274,'ESCF_GND','Malmen Ground',NULL,'ESAA',5,2,NULL),
(275,'ESCF_TWR','Malmen Tower',NULL,'ESAA',5,3,NULL),
(276,'ESCM_TWR','Uppsala Tower',NULL,'ESAA',5,3,NULL),
(277,'ESCM_APP','Uppsala Control',NULL,'ESAA',5,4,NULL),
(278,'ESCR_CTR','Grizzly',NULL,'ESAA',5,5,NULL),
(279,'ESDF_GND','Ronneby Ground',NULL,'ESAA',5,2,NULL),
(280,'ESDF_TWR','Ronneby Tower',NULL,'ESAA',5,3,NULL),
(281,'ESDF_APP','Ronneby Control',NULL,'ESAA',5,4,NULL),
(282,'ESDK_CTR','Blue Shark',NULL,'ESAA',5,5,NULL),
(283,'ESFR_TWR','Råda Tower',NULL,'ESAA',5,3,NULL),
(284,'ESFR_APP','Råda Control',NULL,'ESAA',5,4,NULL),
(285,'ESGG_APP','Göteborg Control',NULL,'ESAA',5,4,NULL),
(286,'ESGG_E_APP','Göteborg Control',NULL,'ESAA',5,4,NULL),
(287,'ESGG_W_APP','Göteborg Control',NULL,'ESAA',5,4,NULL),
(288,'ESGG_A_APP','Göteborg Arrival',NULL,'ESAA',5,4,NULL),
(289,'ESGG_X_APP','Göteborg Control',NULL,'ESAA',5,4,NULL),
(290,'ESGG_TWR','Landvetter Tower',NULL,'ESAA',5,3,NULL),
(291,'ESGG_X_TWR','Landvetter Tower',NULL,'ESAA',5,3,NULL),
(292,'ESGG_GND','Landvetter Ground',NULL,'ESAA',5,2,NULL),
(293,'ESGG_DEL','Clearance Delivery',NULL,'ESAA',5,2,NULL),
(294,'ESGJ_TWR','Jönköping Tower',NULL,'ESAA',5,3,NULL),
(295,'ESGP_TWR','Säve Tower',NULL,'ESAA',5,3,NULL),
(296,'ESGR_I_TWR','Skövde Information',NULL,'ESAA',5,3,NULL),
(297,'ESGT_TWR','Trollhättan Tower',NULL,'ESAA',5,3,NULL),
(298,'ESIA_TWR','Karlsborg Tower',NULL,'ESAA',5,3,NULL),
(299,'ESIA_APP','Karlsborg Control',NULL,'ESAA',5,4,NULL),
(300,'ESIB_GND','Såtenäs Ground',NULL,'ESAA',5,2,NULL),
(301,'ESIB_TWR','Såtenäs Tower',NULL,'ESAA',5,3,NULL),
(302,'ESIB_APP','Såtenäs Control',NULL,'ESAA',5,4,NULL),
(303,'ESKM_I_TWR','Mora Information',NULL,'ESAA',5,3,NULL),
(304,'ESKN_TWR','Skavsta Tower',NULL,'ESAA',5,3,NULL),
(305,'ESKS_TWR','Sälen Tower',NULL,'ESAA',5,3,NULL),
(306,'ESMK_TWR','Kristianstad Tower',NULL,'ESAA',5,3,NULL),
(307,'ESMM_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(308,'ESMM_7_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(309,'ESMM_5_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(310,'ESMM_3_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(311,'ESMM_2_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(312,'ESMM_6_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(313,'ESMM_4_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(314,'ESMM_8_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(315,'ESMM_9_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(316,'ESMM_C_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(317,'ESMM_K_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(318,'ESMM_W_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(319,'ESMM_Y_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(320,'ESMM_X_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(321,'ESMQ_TWR','Kalmar Tower',NULL,'ESAA',5,3,NULL),
(322,'ESMS_APP','Sweden Control',NULL,'ESAA',5,4,NULL),
(323,'ESMS_TWR','Sturup Tower',NULL,'ESAA',5,3,NULL),
(324,'ESMS_GND','Sturup Ground',NULL,'ESAA',5,2,NULL),
(325,'ESMT_TWR','Halmstad Tower',NULL,'ESAA',5,3,NULL),
(326,'ESMV_TWR','Hagshult Tower',NULL,'ESAA',5,3,NULL),
(327,'ESMV_APP','Hagshult Control',NULL,'ESAA',5,4,NULL),
(328,'ESMX_TWR','Kronoberg Tower',NULL,'ESAA',5,3,NULL),
(329,'ESND_I_TWR','Sveg Information',NULL,'ESAA',5,3,NULL),
(330,'ESNG_I_TWR','Gällivare Information',NULL,'ESAA',5,3,NULL),
(331,'ESNJ_TWR','Jokkmokk Tower',NULL,'ESAA',5,3,NULL),
(332,'ESNJ_APP','Jokkmokk Control',NULL,'ESAA',5,4,NULL),
(333,'ESNK_I_TWR','Kramfors Information',NULL,'ESAA',5,3,NULL),
(334,'ESNL_I_TWR','Lycksele Information',NULL,'ESAA',5,3,NULL),
(335,'ESNN_TWR','Sundsvall Tower',NULL,'ESAA',5,3,NULL),
(336,'ESNO_TWR','Örnsköldsvik Tower',NULL,'ESAA',5,3,NULL),
(337,'ESNQ_TWR','Kiruna Tower',NULL,'ESAA',5,3,NULL),
(338,'ESNS_TWR','Skellefteå Tower',NULL,'ESAA',5,3,NULL),
(339,'ESNU_TWR','Umeå Tower',NULL,'ESAA',5,3,NULL),
(340,'ESNV_I_TWR','Vilhelmina Information',NULL,'ESAA',5,3,NULL),
(341,'ESNX_TWR','Arvidsjaur Tower',NULL,'ESAA',5,3,NULL),
(342,'ESNZ_TWR','Östersund Tower',NULL,'ESAA',5,3,NULL),
(343,'ESOE_TWR','Örebro Tower',NULL,'ESAA',5,3,NULL),
(344,'ESOH_I_TWR','Hagfors Information',NULL,'ESAA',5,3,NULL),
(345,'ESOK_TWR','Karlstad Tower',NULL,'ESAA',5,3,NULL),
(346,'ESOS_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(347,'ESOS_6_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(348,'ESOS_1_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(349,'ESOS_3_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(350,'ESOS_4_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(351,'ESOS_2_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(352,'ESOS_8_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(353,'ESOS_F_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(354,'ESOS_7_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(355,'ESOS_K_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(356,'ESOS_N_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(357,'ESOS_Y_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(358,'ESOS_X_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(359,'ESOS_9_CTR','Sweden Control',NULL,'ESAA',5,5,NULL),
(360,'ESOW_TWR','Västerås Tower',NULL,'ESAA',5,3,NULL),
(361,'ESOW_APP','Västerås Control',NULL,'ESAA',5,4,NULL),
(362,'ESPA_TWR','Kallax Tower',NULL,'ESAA',5,3,NULL),
(363,'ESPA_APP','Kallax Control',NULL,'ESAA',5,4,NULL),
(364,'ESPE_TWR','Vidsel Tower',NULL,'ESAA',5,3,NULL),
(365,'ESPE_APP','Vidsel Control',NULL,'ESAA',5,4,NULL),
(366,'ESPF_CTR','Cobra',NULL,'ESAA',5,5,NULL),
(367,'ESSA_W_APP','Stockholm Control',NULL,'ESAA',5,4,NULL),
(368,'ESSA_E_APP','Stockholm Control',NULL,'ESAA',5,4,NULL),
(369,'ESSA_W_DEP','Stockholm Control',NULL,'ESAA',5,4,NULL),
(370,'ESSA_APP','Stockholm Arrival',NULL,'ESAA',5,4,NULL),
(371,'ESSA_F_APP','Stockholm Arrival',NULL,'ESAA',5,4,NULL),
(372,'ESSA_A_APP','Stockholm Arrival',NULL,'ESAA',5,4,NULL),
(373,'ESSA_X_APP','Stockholm Arrival',NULL,'ESAA',5,4,NULL),
(374,'ESSA_TWR','Arlanda Tower',NULL,'ESAA',5,3,NULL),
(375,'ESSA_W_TWR','Arlanda Tower',NULL,'ESAA',5,3,NULL),
(376,'ESSA_E_TWR','Arlanda Tower',NULL,'ESAA',5,3,NULL),
(377,'ESSA_S_TWR','Arlanda Tower',NULL,'ESAA',5,3,NULL),
(378,'ESSA_X_TWR','Arlanda Tower',NULL,'ESAA',5,3,NULL),
(379,'ESSA_N_GND','Arlanda Ground',NULL,'ESAA',5,2,NULL),
(380,'ESSA_W_GND','Arlanda Ground',NULL,'ESAA',5,2,NULL),
(381,'ESSA_E_GND','Arlanda Ground',NULL,'ESAA',5,2,NULL),
(382,'ESSA_DEL','Arlanda Clearance Delivery',NULL,'ESAA',5,2,NULL),
(383,'ESSA_E_DEP','Stockholm Control',NULL,'ESAA',5,4,NULL),
(384,'ESSB_APP','Stockholm Control',NULL,'ESAA',5,4,NULL),
(385,'ESSB_TWR','Bromma Tower',NULL,'ESAA',5,3,NULL),
(386,'ESSB_GND','Bromma Ground',NULL,'ESAA',5,2,NULL),
(387,'ESSD_TWR','Borlänge Tower',NULL,'ESAA',5,3,NULL),
(388,'ESSL_TWR','Saab Tower',NULL,'ESAA',5,3,NULL),
(389,'ESSP_APP','Östgöta Control',NULL,'ESAA',5,4,NULL),
(390,'ESSP_TWR','Kungsängen Tower',NULL,'ESAA',5,3,NULL),
(391,'ESSR_CTR','RTC Stockholm',NULL,'ESAA',5,5,NULL),
(392,'ESST_I_TWR','Torsby Information',NULL,'ESAA',5,3,NULL),
(393,'ESSU_I_TWR','Eskilstuna Information',NULL,'ESAA',5,3,NULL),
(394,'ESSV_TWR','Visby Tower',NULL,'ESAA',5,3,NULL),
(395,'ESSV_APP','Visby Control',NULL,'ESAA',5,4,NULL),
(396,'ESTA_APP','Ängelholm Control',NULL,'ESAA',5,4,NULL),
(397,'ESTA_TWR','Ängelholm Tower',NULL,'ESAA',5,3,NULL),
(398,'ESTL_GND','Ljungbyhed Ground',NULL,'ESAA',5,2,NULL),
(399,'ESTL_APP','Ljungbyhed Control',NULL,'ESAA',5,4,NULL),
(400,'ESTL_TWR','Ljungbyhed Tower',NULL,'ESAA',5,3,NULL),
(401,'ESUP_I_TWR','Pajala Information',NULL,'ESAA',5,3,NULL),
(402,'ESUT_I_TWR','Hemavan Information',NULL,'ESAA',5,3,NULL);
/*!40000 ALTER TABLE `positions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `rating_training`
--

DROP TABLE IF EXISTS `rating_training`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rating_training` (
  `rating_id` int unsigned NOT NULL,
  `training_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`rating_id`,`training_id`),
  KEY `rating_training_training_id_foreign` (`training_id`),
  CONSTRAINT `rating_training_rating_id_foreign` FOREIGN KEY (`rating_id`) REFERENCES `ratings` (`id`),
  CONSTRAINT `rating_training_training_id_foreign` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rating_training`
--

LOCK TABLES `rating_training` WRITE;
/*!40000 ALTER TABLE `rating_training` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `rating_training` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ratings`
--

DROP TABLE IF EXISTS `ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ratings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(16) NOT NULL,
  `description` varchar(100) NOT NULL,
  `vatsim_rating` int unsigned DEFAULT NULL COMMENT 'NULL = Endorsement',
  `endorsement_type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ratings`
--

LOCK TABLES `ratings` WRITE;
/*!40000 ALTER TABLE `ratings` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `ratings` VALUES
(1,'S1','Rating required to sit GND position',2,NULL),
(2,'S2','Rating required to sit TWR position',3,NULL),
(3,'S3','Rating required to sit APP position',4,NULL),
(4,'C1','Rating required to sit ACC position',5,NULL),
(5,'C3','Rating required to sit ACC position',7,NULL),
(6,'I1','Rating required to sit ACC position',8,NULL),
(7,'I3','Rating required to sit ACC position',10,NULL),
(8,'MAE ENGM TWR','Major Airport endorsement for tower position',NULL,NULL),
(9,'MAE ENGM APP','Major Airport endorsement for approach position',NULL,NULL),
(10,'MAE ESSA TWR','Major Airport endorsement for tower position',NULL,NULL),
(11,'MAE ESSA APP','Major Airport endorsement for approach position',NULL,NULL),
(12,'MAE EKCH TWR','Major Airport endorsement for tower position',NULL,NULL),
(13,'MAE EKCH APP','Major Airport endorsement for approach position',NULL,NULL),
(14,'Oceanic BICC','Endorsement for oceanic position',NULL,NULL),
(15,'Oceanic ENOB','Endorsement for oceanic position',NULL,NULL);
/*!40000 ALTER TABLE `ratings` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `settings_key_index` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `settings` VALUES
(1,'trainingEnabled','0'),
(2,'trainingShowEstimate','0'),
(3,'trainingSOP','https://vatsim-scandinavia.org/applications/core/interface/file/attachment.php?id=2049'),
(4,'trainingSubDivisions','SCA'),
(5,'trainingQueue','The queue length varies between countries, but you can expect to wait between 6-12 months.'),
(6,'atcActivityQualificationPeriod','12'),
(7,'atcActivityGracePeriod','12'),
(8,'atcActivityRequirement','10'),
(9,'linkDomain','vatsim-scandinavia.org'),
(10,'linkHome','https://vatsim-scandinavia.org/'),
(11,'linkJoin','https://vatsim-scandinavia.org/about/join/'),
(12,'linkContact','https://vatsim-scandinavia.org/about/staff/'),
(13,'linkVisiting','https://vatsim-scandinavia.org/atc/visiting-controller/'),
(14,'linkDiscord','http://discord.vatsim-scandinavia.org'),
(15,'linkMoodle','https://moodle.vatsim-scandinavia.org/'),
(16,'trainingInterval','14'),
(17,'atcActivityContact','local training staff'),
(18,'trainingSoloRequirement','The student has passed the requirements to gain a solo endorsement.'),
(19,'trainingExamTemplate',''),
(20,'atcActivityNotifyInactive','1'),
(21,'telemetryEnabled','1'),
(22,'atcActivityAllowInactiveControlling','0'),
(23,'_lastCronRun','2026-02-12 20:02:23'),
(24,'feedbackEnable','1'),
(25,'feedbackForwardEmail','0'),
(26,'atcActivityBasedOnTotalHours','1'),
(27,'divisionApiEnabled','0'),
(28,'atcActivityInactivityReminder','0');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `sweatbooks`
--

DROP TABLE IF EXISTS `sweatbooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sweatbooks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `start_at` time NOT NULL,
  `end_at` time NOT NULL,
  `position_id` bigint unsigned NOT NULL,
  `mentor_notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sweatbooks_user_id_foreign` (`user_id`),
  KEY `sweatbooks_position_id_foreign` (`position_id`),
  CONSTRAINT `sweatbooks_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sweatbooks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sweatbooks`
--

LOCK TABLES `sweatbooks` WRITE;
/*!40000 ALTER TABLE `sweatbooks` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `sweatbooks` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  `status_comment` varchar(256) DEFAULT NULL,
  `message` varchar(256) DEFAULT NULL,
  `subject_user_id` bigint unsigned NOT NULL,
  `subject_training_id` bigint unsigned NOT NULL,
  `subject_training_rating_id` int unsigned DEFAULT NULL,
  `assignee_user_id` bigint unsigned NOT NULL,
  `creator_user_id` bigint unsigned DEFAULT NULL,
  `assignee_notified` tinyint(1) NOT NULL DEFAULT '0',
  `creator_notified` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_subject_user_id_foreign` (`subject_user_id`),
  KEY `tasks_subject_training_id_foreign` (`subject_training_id`),
  KEY `tasks_assignee_user_id_foreign` (`assignee_user_id`),
  KEY `tasks_creator_user_id_foreign` (`creator_user_id`),
  KEY `tasks_subject_training_rating_id_foreign` (`subject_training_rating_id`),
  CONSTRAINT `tasks_assignee_user_id_foreign` FOREIGN KEY (`assignee_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_creator_user_id_foreign` FOREIGN KEY (`creator_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_subject_training_id_foreign` FOREIGN KEY (`subject_training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_subject_training_rating_id_foreign` FOREIGN KEY (`subject_training_rating_id`) REFERENCES `ratings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_subject_user_id_foreign` FOREIGN KEY (`subject_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `training_activity`
--

DROP TABLE IF EXISTS `training_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_activity` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `training_id` bigint unsigned NOT NULL,
  `triggered_by_id` bigint unsigned DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `old_data` bigint DEFAULT NULL,
  `new_data` bigint DEFAULT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_activity_training_id_foreign` (`training_id`),
  KEY `training_activity_triggered_by_id_foreign` (`triggered_by_id`),
  CONSTRAINT `training_activity_training_id_foreign` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `training_activity_triggered_by_id_foreign` FOREIGN KEY (`triggered_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_activity`
--

LOCK TABLES `training_activity` WRITE;
/*!40000 ALTER TABLE `training_activity` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `training_activity` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `training_examinations`
--

DROP TABLE IF EXISTS `training_examinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_examinations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `training_id` bigint unsigned NOT NULL,
  `position_id` bigint unsigned DEFAULT NULL,
  `examiner_id` bigint unsigned DEFAULT NULL,
  `result` enum('PASSED','FAILED','INCOMPLETE','POSTPONED') DEFAULT NULL,
  `examination_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_examinations_training_id_foreign` (`training_id`),
  KEY `training_examinations_position_id_foreign` (`position_id`),
  KEY `training_examinations_examiner_id_foreign` (`examiner_id`),
  CONSTRAINT `training_examinations_examiner_id_foreign` FOREIGN KEY (`examiner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `training_examinations_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `training_examinations_training_id_foreign` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_examinations`
--

LOCK TABLES `training_examinations` WRITE;
/*!40000 ALTER TABLE `training_examinations` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `training_examinations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `training_interests`
--

DROP TABLE IF EXISTS `training_interests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_interests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `training_id` bigint unsigned NOT NULL,
  `key` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deadline` timestamp NULL DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `expired` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `training_interests_training_id_foreign` (`training_id`),
  CONSTRAINT `training_interests_training_id_foreign` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_interests`
--

LOCK TABLES `training_interests` WRITE;
/*!40000 ALTER TABLE `training_interests` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `training_interests` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `training_mentor`
--

DROP TABLE IF EXISTS `training_mentor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_mentor` (
  `user_id` bigint unsigned NOT NULL,
  `training_id` bigint unsigned NOT NULL,
  `expire_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`,`training_id`),
  KEY `training_mentor_training_id_foreign` (`training_id`),
  CONSTRAINT `training_mentor_training_id_foreign` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_mentor_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_mentor`
--

LOCK TABLES `training_mentor` WRITE;
/*!40000 ALTER TABLE `training_mentor` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `training_mentor` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `training_object_attachments`
--

DROP TABLE IF EXISTS `training_object_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_object_attachments` (
  `id` char(36) NOT NULL,
  `object_type` varchar(255) NOT NULL,
  `object_id` bigint unsigned NOT NULL,
  `file_id` varchar(255) NOT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_object_attachments_object_type_object_id_index` (`object_type`,`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_object_attachments`
--

LOCK TABLES `training_object_attachments` WRITE;
/*!40000 ALTER TABLE `training_object_attachments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `training_object_attachments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `training_reports`
--

DROP TABLE IF EXISTS `training_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `training_id` bigint unsigned NOT NULL,
  `written_by_id` bigint unsigned DEFAULT NULL,
  `report_date` date NOT NULL,
  `content` text NOT NULL,
  `contentimprove` text,
  `position` varchar(255) DEFAULT NULL,
  `draft` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_reports_training_id_foreign` (`training_id`),
  KEY `training_reports_written_by_id_foreign` (`written_by_id`),
  CONSTRAINT `training_reports_training_id_foreign` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `training_reports_written_by_id_foreign` FOREIGN KEY (`written_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_reports`
--

LOCK TABLES `training_reports` WRITE;
/*!40000 ALTER TABLE `training_reports` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `training_reports` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `trainings`
--

DROP TABLE IF EXISTS `trainings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `trainings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` tinyint NOT NULL DEFAULT '1' COMMENT '1=Standard, 2=Refresh, 3=Transfer, 4=Fast Track, 5=Familiarisation',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '-4: Closed by system, -3: Closed on student’s request, -2: Closed on TA request, -1: Completed, 0: In queue, 1: Pre-training, 2: Active training, 3: Awaiting exam',
  `area_id` int unsigned NOT NULL,
  `motivation` text NOT NULL,
  `english_only_training` tinyint(1) NOT NULL,
  `experience` tinyint DEFAULT NULL,
  `pre_training_completed` tinyint(1) NOT NULL DEFAULT '0',
  `paused_at` timestamp NULL DEFAULT NULL,
  `paused_length` int unsigned NOT NULL DEFAULT '0',
  `closed_reason` varchar(255) DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trainings_user_id_foreign` (`user_id`),
  KEY `trainings_country_id_foreign` (`area_id`),
  KEY `trainings_created_by_foreign` (`created_by`),
  CONSTRAINT `trainings_country_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`),
  CONSTRAINT `trainings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `trainings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainings`
--

LOCK TABLES `trainings` WRITE;
/*!40000 ALTER TABLE `trainings` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `trainings` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `user_vote`
--

DROP TABLE IF EXISTS `user_vote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_vote` (
  `vote_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`vote_id`,`user_id`),
  KEY `user_vote_user_id_foreign` (`user_id`),
  CONSTRAINT `user_vote_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_vote_vote_id_foreign` FOREIGN KEY (`vote_id`) REFERENCES `votes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_vote`
--

LOCK TABLES `user_vote` WRITE;
/*!40000 ALTER TABLE `user_vote` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `user_vote` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(64) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `rating` tinyint NOT NULL,
  `rating_short` varchar(3) NOT NULL,
  `rating_long` varchar(24) NOT NULL,
  `region` varchar(8) NOT NULL,
  `division` varchar(20) DEFAULT NULL,
  `subdivision` varchar(20) DEFAULT NULL,
  `last_login` timestamp NOT NULL,
  `last_activity` timestamp NULL DEFAULT NULL,
  `last_inactivity_warning` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `access_token` text,
  `refresh_token` text,
  `token_expires` bigint unsigned DEFAULT NULL,
  `setting_workmail_address` varchar(64) DEFAULT NULL,
  `setting_workmail_expire` timestamp NULL DEFAULT NULL,
  `setting_notify_newreport` tinyint(1) NOT NULL DEFAULT '1',
  `setting_notify_newreq` tinyint(1) NOT NULL DEFAULT '1',
  `setting_notify_closedreq` tinyint(1) NOT NULL DEFAULT '1',
  `setting_notify_newexamreport` tinyint(1) NOT NULL DEFAULT '1',
  `setting_notify_tasks` tinyint(1) NOT NULL DEFAULT '1',
  `setting_theme` varchar(16) NOT NULL DEFAULT 'system',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `vote_options`
--

DROP TABLE IF EXISTS `vote_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vote_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vote_id` bigint unsigned NOT NULL,
  `option` varchar(255) NOT NULL,
  `voted` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `vote_options_vote_id_foreign` (`vote_id`),
  CONSTRAINT `vote_options_vote_id_foreign` FOREIGN KEY (`vote_id`) REFERENCES `votes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vote_options`
--

LOCK TABLES `vote_options` WRITE;
/*!40000 ALTER TABLE `vote_options` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `vote_options` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `votes`
--

DROP TABLE IF EXISTS `votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `votes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(255) NOT NULL,
  `require_active` tinyint(1) NOT NULL,
  `require_member` tinyint(1) NOT NULL DEFAULT '0',
  `closed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `end_at` timestamp NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `votes`
--

LOCK TABLES `votes` WRITE;
/*!40000 ALTER TABLE `votes` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `votes` ENABLE KEYS */;
UNLOCK TABLES;
commit;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-02-12 21:02:50
