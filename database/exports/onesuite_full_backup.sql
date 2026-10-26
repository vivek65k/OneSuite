-- MySQL dump 10.13  Distrib 8.0.35, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: onesuite
-- ------------------------------------------------------
-- Server version	8.0.35

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
-- Table structure for table `appraisal_cycles`
--

DROP TABLE IF EXISTS `appraisal_cycles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appraisal_cycles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appraisal_cycles_company_id_foreign` (`company_id`),
  CONSTRAINT `appraisal_cycles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appraisal_cycles`
--

LOCK TABLES `appraisal_cycles` WRITE;
/*!40000 ALTER TABLE `appraisal_cycles` DISABLE KEYS */;
INSERT INTO `appraisal_cycles` VALUES (12,2,'Annual 2026','2026-01-01','2026-12-31','active','2026-10-06 22:26:36','2026-10-06 22:26:36');
/*!40000 ALTER TABLE `appraisal_cycles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appraisals`
--

DROP TABLE IF EXISTS `appraisals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appraisals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `appraisal_cycle_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `self_review_rating` tinyint unsigned DEFAULT NULL,
  `self_review_comments` text COLLATE utf8mb4_unicode_ci,
  `self_review_submitted_at` timestamp NULL DEFAULT NULL,
  `manager_review_rating` tinyint unsigned DEFAULT NULL,
  `manager_review_comments` text COLLATE utf8mb4_unicode_ci,
  `manager_reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `manager_review_submitted_at` timestamp NULL DEFAULT NULL,
  `overall_rating` decimal(3,2) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appraisals_employee_id_appraisal_cycle_id_unique` (`employee_id`,`appraisal_cycle_id`),
  KEY `appraisals_appraisal_cycle_id_foreign` (`appraisal_cycle_id`),
  KEY `appraisals_manager_reviewed_by_user_id_foreign` (`manager_reviewed_by_user_id`),
  KEY `appraisals_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `appraisals_appraisal_cycle_id_foreign` FOREIGN KEY (`appraisal_cycle_id`) REFERENCES `appraisal_cycles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appraisals_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appraisals_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appraisals_manager_reviewed_by_user_id_foreign` FOREIGN KEY (`manager_reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appraisals`
--

LOCK TABLES `appraisals` WRITE;
/*!40000 ALTER TABLE `appraisals` DISABLE KEYS */;
INSERT INTO `appraisals` VALUES (19,2,16,12,'draft',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-06 22:27:13','2026-10-06 22:27:13'),(25,2,18,12,'draft',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-06 22:27:25','2026-10-06 22:27:25');
/*!40000 ALTER TABLE `appraisals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_level_settings`
--

DROP TABLE IF EXISTS `approval_level_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_level_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `request_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `levels` tinyint unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `approval_level_settings_company_id_request_type_unique` (`company_id`,`request_type`),
  CONSTRAINT `approval_level_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_level_settings`
--

LOCK TABLES `approval_level_settings` WRITE;
/*!40000 ALTER TABLE `approval_level_settings` DISABLE KEYS */;
INSERT INTO `approval_level_settings` VALUES (2,2,'leave_request',1,'2026-10-07 04:53:26','2026-10-07 04:53:27'),(3,2,'expense_claim',1,'2026-10-07 04:53:26','2026-10-07 04:53:27'),(4,2,'regularization_request',1,'2026-10-07 04:53:26','2026-10-07 04:53:26'),(5,2,'wfh_request',1,'2026-10-07 04:53:26','2026-10-07 04:53:26'),(6,2,'overtime_request',1,'2026-10-07 04:53:26','2026-10-07 04:53:26'),(7,2,'travel_request',1,'2026-10-07 04:53:26','2026-10-07 04:53:26');
/*!40000 ALTER TABLE `approval_level_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_rules`
--

DROP TABLE IF EXISTS `approval_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `min_amount` decimal(14,2) DEFAULT NULL,
  `max_amount` decimal(14,2) DEFAULT NULL,
  `approver_role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` int unsigned NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `approval_rules_company_id_foreign` (`company_id`),
  KEY `approval_rules_department_id_foreign` (`department_id`),
  CONSTRAINT `approval_rules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `approval_rules_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_rules`
--

LOCK TABLES `approval_rules` WRITE;
/*!40000 ALTER TABLE `approval_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `approval_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_steps`
--

DROP TABLE IF EXISTS `approval_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_steps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `approvable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `approvable_id` bigint unsigned NOT NULL,
  `level` tinyint unsigned NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `acted_by_user_id` bigint unsigned DEFAULT NULL,
  `on_behalf` tinyint(1) NOT NULL DEFAULT '0',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `approval_steps_company_id_foreign` (`company_id`),
  KEY `approval_steps_approvable_type_approvable_id_index` (`approvable_type`,`approvable_id`),
  KEY `approval_steps_acted_by_user_id_foreign` (`acted_by_user_id`),
  CONSTRAINT `approval_steps_acted_by_user_id_foreign` FOREIGN KEY (`acted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `approval_steps_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_steps`
--

LOCK TABLES `approval_steps` WRITE;
/*!40000 ALTER TABLE `approval_steps` DISABLE KEYS */;
/*!40000 ALTER TABLE `approval_steps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessment_results`
--

DROP TABLE IF EXISTS `assessment_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_results` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `assessment_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `score` decimal(6,2) NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempted_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assessment_results_assessment_id_employee_id_unique` (`assessment_id`,`employee_id`),
  KEY `assessment_results_company_id_foreign` (`company_id`),
  KEY `assessment_results_employee_id_foreign` (`employee_id`),
  CONSTRAINT `assessment_results_assessment_id_foreign` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_results_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_results_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessment_results`
--

LOCK TABLES `assessment_results` WRITE;
/*!40000 ALTER TABLE `assessment_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `assessment_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessments`
--

DROP TABLE IF EXISTS `assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `max_score` decimal(6,2) NOT NULL DEFAULT '100.00',
  `passing_score` decimal(6,2) NOT NULL DEFAULT '40.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assessments_company_id_foreign` (`company_id`),
  KEY `assessments_course_id_foreign` (`course_id`),
  CONSTRAINT `assessments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessments`
--

LOCK TABLES `assessments` WRITE;
/*!40000 ALTER TABLE `assessments` DISABLE KEYS */;
/*!40000 ALTER TABLE `assessments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_records`
--

DROP TABLE IF EXISTS `attendance_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'present',
  `source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `work_hours` decimal(5,2) DEFAULT NULL,
  `check_in_lat` decimal(10,7) DEFAULT NULL,
  `check_in_lng` decimal(10,7) DEFAULT NULL,
  `check_out_lat` decimal(10,7) DEFAULT NULL,
  `check_out_lng` decimal(10,7) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_records_employee_id_date_unique` (`employee_id`,`date`),
  KEY `attendance_records_company_id_date_index` (`company_id`,`date`),
  KEY `attendance_records_status_index` (`status`),
  KEY `attendance_records_source_index` (`source`),
  CONSTRAINT `attendance_records_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_records_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=206 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_records`
--

LOCK TABLES `attendance_records` WRITE;
/*!40000 ALTER TABLE `attendance_records` DISABLE KEYS */;
INSERT INTO `attendance_records` VALUES (4,2,4,'2026-08-20','04:34:00','03:04:00','present','manual',NULL,NULL,NULL,NULL,NULL,NULL,'2026-08-20 01:26:49','2026-08-20 01:26:49'),(68,2,17,'2026-10-06',NULL,NULL,'present','manual',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-06 07:41:11','2026-10-06 07:41:11'),(69,2,18,'2026-10-06',NULL,NULL,'present','manual',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-06 07:41:16','2026-10-06 07:41:16'),(70,2,16,'2026-10-06',NULL,NULL,'present','manual',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-06 07:41:17','2026-10-06 07:41:17'),(71,2,19,'2026-10-06',NULL,NULL,'wfh','manual',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-06 07:41:21','2026-10-06 07:41:21'),(72,2,4,'2026-10-06',NULL,NULL,'wfh','manual',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-06 07:41:23','2026-10-06 07:41:23'),(73,2,16,'2026-10-07',NULL,NULL,'present','manual',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-07 02:18:48','2026-10-07 02:18:48'),(75,2,18,'2026-10-07',NULL,NULL,'present','manual',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-07 04:12:18','2026-10-07 04:12:18');
/*!40000 ALTER TABLE `attendance_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_rule_settings`
--

DROP TABLE IF EXISTS `attendance_rule_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_rule_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `late_grace_minutes` int unsigned NOT NULL DEFAULT '0',
  `half_day_threshold_hours` decimal(4,2) NOT NULL DEFAULT '4.00',
  `full_day_threshold_hours` decimal(4,2) NOT NULL DEFAULT '8.00',
  `auto_mark_absent_if_no_checkin` tinyint(1) NOT NULL DEFAULT '0',
  `weekend_overtime_multiplier` decimal(4,2) NOT NULL DEFAULT '1.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_rule_settings_company_id_branch_id_unique` (`company_id`,`branch_id`),
  KEY `attendance_rule_settings_branch_id_foreign` (`branch_id`),
  CONSTRAINT `attendance_rule_settings_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_rule_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_rule_settings`
--

LOCK TABLES `attendance_rule_settings` WRITE;
/*!40000 ALTER TABLE `attendance_rule_settings` DISABLE KEYS */;
INSERT INTO `attendance_rule_settings` VALUES (1,2,1,30,4.00,8.00,0,1.00,'2026-09-17 06:36:43','2026-09-28 02:16:23');
/*!40000 ALTER TABLE `attendance_rule_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `automation_rules`
--

DROP TABLE IF EXISTS `automation_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `automation_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `trigger_event` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_target` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `automation_rules_company_id_foreign` (`company_id`),
  CONSTRAINT `automation_rules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `automation_rules`
--

LOCK TABLES `automation_rules` WRITE;
/*!40000 ALTER TABLE `automation_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `automation_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bonuses`
--

DROP TABLE IF EXISTS `bonuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bonuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `payroll_run_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `period_month` tinyint unsigned NOT NULL,
  `period_year` smallint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bonuses_employee_id_foreign` (`employee_id`),
  KEY `bonuses_company_id_status_index` (`company_id`,`status`),
  KEY `bonuses_payroll_run_id_index` (`payroll_run_id`),
  CONSTRAINT `bonuses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bonuses_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bonuses`
--

LOCK TABLES `bonuses` WRITE;
/*!40000 ALTER TABLE `bonuses` DISABLE KEYS */;
INSERT INTO `bonuses` VALUES (16,2,17,NULL,'Festival bonus',10000.00,11,2026,'pending',NULL,'2026-10-06 07:24:41','2026-10-06 07:24:41'),(17,2,18,NULL,'Festival bonus',10000.00,11,2026,'pending',NULL,'2026-10-06 07:24:41','2026-10-06 07:24:41');
/*!40000 ALTER TABLE `bonuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_user`
--

DROP TABLE IF EXISTS `branch_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branch_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_user_branch_id_user_id_unique` (`branch_id`,`user_id`),
  KEY `branch_user_user_id_foreign` (`user_id`),
  CONSTRAINT `branch_user_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `branch_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_user`
--

LOCK TABLES `branch_user` WRITE;
/*!40000 ALTER TABLE `branch_user` DISABLE KEYS */;
INSERT INTO `branch_user` VALUES (4,6,27,NULL,NULL),(5,9,30,NULL,NULL),(6,10,31,NULL,NULL),(7,1,23,NULL,NULL),(8,1,1,NULL,NULL);
/*!40000 ALTER TABLE `branch_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_company_id_code_unique` (`company_id`,`code`),
  KEY `branches_name_index` (`name`),
  KEY `branches_is_active_index` (`is_active`),
  CONSTRAINT `branches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,2,'Head Office',1,'HO-001','+91 98765 10001','head@vascilayte.com',NULL,NULL,NULL,NULL,NULL,1,'2026-08-02 06:29:24','2026-09-15 20:24:14'),(5,2,'Kolkata Branch',0,'KOL-001','+91 98765 10002','kol@vascilayte.com','1175, Sodepur , Kolkata','Kolkata','West Bengal','India','834002',1,'2026-09-15 20:23:41','2026-09-15 20:24:14'),(6,3,'Head Office',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-09-19 10:21:56','2026-09-19 10:21:56'),(9,6,'Head Office',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-09-21 21:55:49','2026-09-21 21:55:49'),(10,7,'Head Office',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-09-21 21:57:40','2026-09-21 21:57:40');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_units`
--

DROP TABLE IF EXISTS `business_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_units_company_id_code_unique` (`company_id`,`code`),
  KEY `business_units_name_index` (`name`),
  KEY `business_units_is_active_index` (`is_active`),
  CONSTRAINT `business_units_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_units`
--

LOCK TABLES `business_units` WRITE;
/*!40000 ALTER TABLE `business_units` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('onesuite-cache-admin@amin.com|127.0.0.1','i:1;',1792234260),('onesuite-cache-admin@amin.com|127.0.0.1:timer','i:1792234260;',1792234260),('onesuite-cache-admin@gmail.com|127.0.0.1','i:1;',1792234224),('onesuite-cache-admin@gmail.com|127.0.0.1:timer','i:1792234223;',1792234224),('onesuite-cache-spatie.permission.cache','a:3:{s:5:\"alias\";a:6:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";s:1:\"j\";s:10:\"company_id\";s:1:\"k\";s:10:\"data_scope\";}s:11:\"permissions\";a:944:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:9:\"view_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:11;i:2;i:14;i:3;i:15;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:13:\"view_any_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:11;i:2;i:14;i:3;i:15;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:11:\"create_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:11;i:2;i:14;i:3;i:15;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:11:\"update_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:11;i:2;i:14;i:3;i:15;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:11:\"delete_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:11;i:2;i:14;i:3;i:15;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:15:\"delete_any_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:11;i:2;i:14;i:3;i:15;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:9:\"view_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:11;i:2;i:14;i:3;i:15;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:13:\"view_any_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:11;i:2;i:14;i:3;i:15;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:11:\"create_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:11;i:2;i:14;i:3;i:15;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:11:\"update_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:11;i:2;i:14;i:3;i:15;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:12:\"restore_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:11;i:2;i:14;i:3;i:15;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:16:\"restore_any_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:11;i:2;i:14;i:3;i:15;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:14:\"replicate_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:11;i:2;i:14;i:3;i:15;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:12:\"reorder_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:11;i:2;i:14;i:3;i:15;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:11:\"delete_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:11;i:2;i:14;i:3;i:15;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:15:\"delete_any_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:11;i:2;i:14;i:3;i:15;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:17:\"force_delete_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:11;i:2;i:14;i:3;i:15;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:21:\"force_delete_any_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:11;i:2;i:14;i:3;i:15;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:11:\"view_branch\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:15:\"view_any_branch\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:13:\"create_branch\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:13:\"update_branch\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:14:\"restore_branch\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:18:\"restore_any_branch\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:16:\"replicate_branch\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:14:\"reorder_branch\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:13:\"delete_branch\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:17:\"delete_any_branch\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:19:\"force_delete_branch\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:23:\"force_delete_any_branch\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:19:\"view_business::unit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:23:\"view_any_business::unit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:21:\"create_business::unit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:21:\"update_business::unit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:22:\"restore_business::unit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:26:\"restore_any_business::unit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:24:\"replicate_business::unit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:37;a:4:{s:1:\"a\";i:38;s:1:\"b\";s:22:\"reorder_business::unit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:38;a:4:{s:1:\"a\";i:39;s:1:\"b\";s:21:\"delete_business::unit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:39;a:4:{s:1:\"a\";i:40;s:1:\"b\";s:25:\"delete_any_business::unit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:40;a:4:{s:1:\"a\";i:41;s:1:\"b\";s:27:\"force_delete_business::unit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:41;a:4:{s:1:\"a\";i:42;s:1:\"b\";s:31:\"force_delete_any_business::unit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:42;a:4:{s:1:\"a\";i:55;s:1:\"b\";s:17:\"view_cost::center\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:43;a:4:{s:1:\"a\";i:56;s:1:\"b\";s:21:\"view_any_cost::center\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:44;a:4:{s:1:\"a\";i:57;s:1:\"b\";s:19:\"create_cost::center\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:45;a:4:{s:1:\"a\";i:58;s:1:\"b\";s:19:\"update_cost::center\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:46;a:4:{s:1:\"a\";i:59;s:1:\"b\";s:20:\"restore_cost::center\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:47;a:4:{s:1:\"a\";i:60;s:1:\"b\";s:24:\"restore_any_cost::center\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:48;a:4:{s:1:\"a\";i:61;s:1:\"b\";s:22:\"replicate_cost::center\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:49;a:4:{s:1:\"a\";i:62;s:1:\"b\";s:20:\"reorder_cost::center\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:50;a:4:{s:1:\"a\";i:63;s:1:\"b\";s:19:\"delete_cost::center\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:51;a:4:{s:1:\"a\";i:64;s:1:\"b\";s:23:\"delete_any_cost::center\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:52;a:4:{s:1:\"a\";i:65;s:1:\"b\";s:25:\"force_delete_cost::center\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:53;a:4:{s:1:\"a\";i:66;s:1:\"b\";s:29:\"force_delete_any_cost::center\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:54;a:4:{s:1:\"a\";i:67;s:1:\"b\";s:15:\"view_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:55;a:4:{s:1:\"a\";i:68;s:1:\"b\";s:19:\"view_any_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:56;a:4:{s:1:\"a\";i:69;s:1:\"b\";s:17:\"create_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:57;a:4:{s:1:\"a\";i:70;s:1:\"b\";s:17:\"update_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:58;a:4:{s:1:\"a\";i:71;s:1:\"b\";s:18:\"restore_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:59;a:4:{s:1:\"a\";i:72;s:1:\"b\";s:22:\"restore_any_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:60;a:4:{s:1:\"a\";i:73;s:1:\"b\";s:20:\"replicate_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:61;a:4:{s:1:\"a\";i:74;s:1:\"b\";s:18:\"reorder_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:62;a:4:{s:1:\"a\";i:75;s:1:\"b\";s:17:\"delete_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:63;a:4:{s:1:\"a\";i:76;s:1:\"b\";s:21:\"delete_any_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:64;a:4:{s:1:\"a\";i:77;s:1:\"b\";s:23:\"force_delete_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:65;a:4:{s:1:\"a\";i:78;s:1:\"b\";s:27:\"force_delete_any_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:66;a:4:{s:1:\"a\";i:91;s:1:\"b\";s:20:\"page_GeneralSettings\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:67;a:4:{s:1:\"a\";i:92;s:1:\"b\";s:12:\"view_holiday\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:68;a:4:{s:1:\"a\";i:93;s:1:\"b\";s:16:\"view_any_holiday\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:69;a:4:{s:1:\"a\";i:94;s:1:\"b\";s:14:\"create_holiday\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:70;a:4:{s:1:\"a\";i:95;s:1:\"b\";s:14:\"update_holiday\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:71;a:4:{s:1:\"a\";i:96;s:1:\"b\";s:15:\"restore_holiday\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:72;a:4:{s:1:\"a\";i:97;s:1:\"b\";s:19:\"restore_any_holiday\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:73;a:4:{s:1:\"a\";i:98;s:1:\"b\";s:17:\"replicate_holiday\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:74;a:4:{s:1:\"a\";i:99;s:1:\"b\";s:15:\"reorder_holiday\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:75;a:4:{s:1:\"a\";i:100;s:1:\"b\";s:14:\"delete_holiday\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:76;a:4:{s:1:\"a\";i:101;s:1:\"b\";s:18:\"delete_any_holiday\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:77;a:4:{s:1:\"a\";i:102;s:1:\"b\";s:20:\"force_delete_holiday\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:78;a:4:{s:1:\"a\";i:103;s:1:\"b\";s:24:\"force_delete_any_holiday\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:79;a:4:{s:1:\"a\";i:104;s:1:\"b\";s:17:\"page_WorkingHours\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:80;a:4:{s:1:\"a\";i:105;s:1:\"b\";s:13:\"view_workflow\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:81;a:4:{s:1:\"a\";i:106;s:1:\"b\";s:17:\"view_any_workflow\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:82;a:4:{s:1:\"a\";i:107;s:1:\"b\";s:15:\"create_workflow\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:83;a:4:{s:1:\"a\";i:108;s:1:\"b\";s:15:\"update_workflow\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:84;a:4:{s:1:\"a\";i:109;s:1:\"b\";s:16:\"restore_workflow\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:85;a:4:{s:1:\"a\";i:110;s:1:\"b\";s:20:\"restore_any_workflow\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:86;a:4:{s:1:\"a\";i:111;s:1:\"b\";s:18:\"replicate_workflow\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:87;a:4:{s:1:\"a\";i:112;s:1:\"b\";s:16:\"reorder_workflow\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:88;a:4:{s:1:\"a\";i:113;s:1:\"b\";s:15:\"delete_workflow\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:89;a:4:{s:1:\"a\";i:114;s:1:\"b\";s:19:\"delete_any_workflow\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:90;a:4:{s:1:\"a\";i:115;s:1:\"b\";s:21:\"force_delete_workflow\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:91;a:4:{s:1:\"a\";i:116;s:1:\"b\";s:25:\"force_delete_any_workflow\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:92;a:4:{s:1:\"a\";i:117;s:1:\"b\";s:18:\"view_approval_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:93;a:4:{s:1:\"a\";i:118;s:1:\"b\";s:22:\"view_any_approval_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:94;a:4:{s:1:\"a\";i:119;s:1:\"b\";s:20:\"create_approval_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:95;a:4:{s:1:\"a\";i:120;s:1:\"b\";s:20:\"update_approval_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:96;a:4:{s:1:\"a\";i:121;s:1:\"b\";s:21:\"restore_approval_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:97;a:4:{s:1:\"a\";i:122;s:1:\"b\";s:25:\"restore_any_approval_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:98;a:4:{s:1:\"a\";i:123;s:1:\"b\";s:23:\"replicate_approval_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:99;a:4:{s:1:\"a\";i:124;s:1:\"b\";s:21:\"reorder_approval_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:100;a:4:{s:1:\"a\";i:125;s:1:\"b\";s:20:\"delete_approval_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:101;a:4:{s:1:\"a\";i:126;s:1:\"b\";s:24:\"delete_any_approval_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:102;a:4:{s:1:\"a\";i:127;s:1:\"b\";s:26:\"force_delete_approval_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:103;a:4:{s:1:\"a\";i:128;s:1:\"b\";s:30:\"force_delete_any_approval_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:104;a:4:{s:1:\"a\";i:129;s:1:\"b\";s:20:\"view_escalation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:105;a:4:{s:1:\"a\";i:130;s:1:\"b\";s:24:\"view_any_escalation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:106;a:4:{s:1:\"a\";i:131;s:1:\"b\";s:22:\"create_escalation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:107;a:4:{s:1:\"a\";i:132;s:1:\"b\";s:22:\"update_escalation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:108;a:4:{s:1:\"a\";i:133;s:1:\"b\";s:23:\"restore_escalation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:109;a:4:{s:1:\"a\";i:134;s:1:\"b\";s:27:\"restore_any_escalation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:110;a:4:{s:1:\"a\";i:135;s:1:\"b\";s:25:\"replicate_escalation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:111;a:4:{s:1:\"a\";i:136;s:1:\"b\";s:23:\"reorder_escalation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:112;a:4:{s:1:\"a\";i:137;s:1:\"b\";s:22:\"delete_escalation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:113;a:4:{s:1:\"a\";i:138;s:1:\"b\";s:26:\"delete_any_escalation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:114;a:4:{s:1:\"a\";i:139;s:1:\"b\";s:28:\"force_delete_escalation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:115;a:4:{s:1:\"a\";i:140;s:1:\"b\";s:32:\"force_delete_any_escalation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:116;a:4:{s:1:\"a\";i:141;s:1:\"b\";s:20:\"view_automation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:117;a:4:{s:1:\"a\";i:142;s:1:\"b\";s:24:\"view_any_automation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:118;a:4:{s:1:\"a\";i:143;s:1:\"b\";s:22:\"create_automation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:119;a:4:{s:1:\"a\";i:144;s:1:\"b\";s:22:\"update_automation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:120;a:4:{s:1:\"a\";i:145;s:1:\"b\";s:23:\"restore_automation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:121;a:4:{s:1:\"a\";i:146;s:1:\"b\";s:27:\"restore_any_automation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:122;a:4:{s:1:\"a\";i:147;s:1:\"b\";s:25:\"replicate_automation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:123;a:4:{s:1:\"a\";i:148;s:1:\"b\";s:23:\"reorder_automation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:124;a:4:{s:1:\"a\";i:149;s:1:\"b\";s:22:\"delete_automation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:125;a:4:{s:1:\"a\";i:150;s:1:\"b\";s:26:\"delete_any_automation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:126;a:4:{s:1:\"a\";i:151;s:1:\"b\";s:28:\"force_delete_automation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:127;a:4:{s:1:\"a\";i:152;s:1:\"b\";s:32:\"force_delete_any_automation_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:128;a:4:{s:1:\"a\";i:153;s:1:\"b\";s:19:\"view_email_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:129;a:4:{s:1:\"a\";i:154;s:1:\"b\";s:23:\"view_any_email_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:130;a:4:{s:1:\"a\";i:155;s:1:\"b\";s:21:\"create_email_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:131;a:4:{s:1:\"a\";i:156;s:1:\"b\";s:21:\"update_email_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:132;a:4:{s:1:\"a\";i:157;s:1:\"b\";s:22:\"restore_email_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:133;a:4:{s:1:\"a\";i:158;s:1:\"b\";s:26:\"restore_any_email_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:134;a:4:{s:1:\"a\";i:159;s:1:\"b\";s:24:\"replicate_email_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:135;a:4:{s:1:\"a\";i:160;s:1:\"b\";s:22:\"reorder_email_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:136;a:4:{s:1:\"a\";i:161;s:1:\"b\";s:21:\"delete_email_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:137;a:4:{s:1:\"a\";i:162;s:1:\"b\";s:25:\"delete_any_email_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:138;a:4:{s:1:\"a\";i:163;s:1:\"b\";s:27:\"force_delete_email_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:139;a:4:{s:1:\"a\";i:164;s:1:\"b\";s:31:\"force_delete_any_email_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:140;a:4:{s:1:\"a\";i:165;s:1:\"b\";s:17:\"view_sms_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:141;a:4:{s:1:\"a\";i:166;s:1:\"b\";s:21:\"view_any_sms_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:142;a:4:{s:1:\"a\";i:167;s:1:\"b\";s:19:\"create_sms_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:143;a:4:{s:1:\"a\";i:168;s:1:\"b\";s:19:\"update_sms_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:144;a:4:{s:1:\"a\";i:169;s:1:\"b\";s:20:\"restore_sms_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:145;a:4:{s:1:\"a\";i:170;s:1:\"b\";s:24:\"restore_any_sms_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:146;a:4:{s:1:\"a\";i:171;s:1:\"b\";s:22:\"replicate_sms_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:147;a:4:{s:1:\"a\";i:172;s:1:\"b\";s:20:\"reorder_sms_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:148;a:4:{s:1:\"a\";i:173;s:1:\"b\";s:19:\"delete_sms_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:149;a:4:{s:1:\"a\";i:174;s:1:\"b\";s:23:\"delete_any_sms_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:150;a:4:{s:1:\"a\";i:175;s:1:\"b\";s:25:\"force_delete_sms_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:151;a:4:{s:1:\"a\";i:176;s:1:\"b\";s:29:\"force_delete_any_sms_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:152;a:4:{s:1:\"a\";i:177;s:1:\"b\";s:22:\"view_whatsapp_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:153;a:4:{s:1:\"a\";i:178;s:1:\"b\";s:26:\"view_any_whatsapp_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:154;a:4:{s:1:\"a\";i:179;s:1:\"b\";s:24:\"create_whatsapp_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:155;a:4:{s:1:\"a\";i:180;s:1:\"b\";s:24:\"update_whatsapp_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:156;a:4:{s:1:\"a\";i:181;s:1:\"b\";s:25:\"restore_whatsapp_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:157;a:4:{s:1:\"a\";i:182;s:1:\"b\";s:29:\"restore_any_whatsapp_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:158;a:4:{s:1:\"a\";i:183;s:1:\"b\";s:27:\"replicate_whatsapp_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:159;a:4:{s:1:\"a\";i:184;s:1:\"b\";s:25:\"reorder_whatsapp_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:160;a:4:{s:1:\"a\";i:185;s:1:\"b\";s:24:\"delete_whatsapp_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:161;a:4:{s:1:\"a\";i:186;s:1:\"b\";s:28:\"delete_any_whatsapp_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:162;a:4:{s:1:\"a\";i:187;s:1:\"b\";s:30:\"force_delete_whatsapp_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:163;a:4:{s:1:\"a\";i:188;s:1:\"b\";s:34:\"force_delete_any_whatsapp_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:164;a:4:{s:1:\"a\";i:189;s:1:\"b\";s:22:\"view_notification_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:165;a:4:{s:1:\"a\";i:190;s:1:\"b\";s:26:\"view_any_notification_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:166;a:4:{s:1:\"a\";i:191;s:1:\"b\";s:24:\"create_notification_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:167;a:4:{s:1:\"a\";i:192;s:1:\"b\";s:24:\"update_notification_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:168;a:4:{s:1:\"a\";i:193;s:1:\"b\";s:25:\"restore_notification_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:169;a:4:{s:1:\"a\";i:194;s:1:\"b\";s:29:\"restore_any_notification_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:170;a:4:{s:1:\"a\";i:195;s:1:\"b\";s:27:\"replicate_notification_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:171;a:4:{s:1:\"a\";i:196;s:1:\"b\";s:25:\"reorder_notification_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:172;a:4:{s:1:\"a\";i:197;s:1:\"b\";s:24:\"delete_notification_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:173;a:4:{s:1:\"a\";i:198;s:1:\"b\";s:28:\"delete_any_notification_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:174;a:4:{s:1:\"a\";i:199;s:1:\"b\";s:30:\"force_delete_notification_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:175;a:4:{s:1:\"a\";i:200;s:1:\"b\";s:34:\"force_delete_any_notification_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:176;a:4:{s:1:\"a\";i:201;s:1:\"b\";s:13:\"view_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:177;a:4:{s:1:\"a\";i:202;s:1:\"b\";s:17:\"view_any_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:178;a:4:{s:1:\"a\";i:203;s:1:\"b\";s:15:\"create_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:179;a:4:{s:1:\"a\";i:204;s:1:\"b\";s:15:\"update_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:180;a:4:{s:1:\"a\";i:205;s:1:\"b\";s:16:\"restore_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:181;a:4:{s:1:\"a\";i:206;s:1:\"b\";s:20:\"restore_any_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:182;a:4:{s:1:\"a\";i:207;s:1:\"b\";s:18:\"replicate_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:183;a:4:{s:1:\"a\";i:208;s:1:\"b\";s:16:\"reorder_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:184;a:4:{s:1:\"a\";i:209;s:1:\"b\";s:15:\"delete_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:185;a:4:{s:1:\"a\";i:210;s:1:\"b\";s:19:\"delete_any_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:186;a:4:{s:1:\"a\";i:211;s:1:\"b\";s:21:\"force_delete_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:187;a:4:{s:1:\"a\";i:212;s:1:\"b\";s:25:\"force_delete_any_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:188;a:4:{s:1:\"a\";i:213;s:1:\"b\";s:17:\"view_job_position\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:189;a:4:{s:1:\"a\";i:214;s:1:\"b\";s:21:\"view_any_job_position\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:190;a:4:{s:1:\"a\";i:215;s:1:\"b\";s:19:\"create_job_position\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:191;a:4:{s:1:\"a\";i:216;s:1:\"b\";s:19:\"update_job_position\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:192;a:4:{s:1:\"a\";i:217;s:1:\"b\";s:20:\"restore_job_position\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:193;a:4:{s:1:\"a\";i:218;s:1:\"b\";s:24:\"restore_any_job_position\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:194;a:4:{s:1:\"a\";i:219;s:1:\"b\";s:22:\"replicate_job_position\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:195;a:4:{s:1:\"a\";i:220;s:1:\"b\";s:20:\"reorder_job_position\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:196;a:4:{s:1:\"a\";i:221;s:1:\"b\";s:19:\"delete_job_position\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:197;a:4:{s:1:\"a\";i:222;s:1:\"b\";s:23:\"delete_any_job_position\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:198;a:4:{s:1:\"a\";i:223;s:1:\"b\";s:25:\"force_delete_job_position\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:199;a:4:{s:1:\"a\";i:224;s:1:\"b\";s:29:\"force_delete_any_job_position\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:200;a:4:{s:1:\"a\";i:225;s:1:\"b\";s:14:\"view_candidate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:201;a:4:{s:1:\"a\";i:226;s:1:\"b\";s:18:\"view_any_candidate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:202;a:4:{s:1:\"a\";i:227;s:1:\"b\";s:16:\"create_candidate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:203;a:4:{s:1:\"a\";i:228;s:1:\"b\";s:16:\"update_candidate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:204;a:4:{s:1:\"a\";i:229;s:1:\"b\";s:17:\"restore_candidate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:205;a:4:{s:1:\"a\";i:230;s:1:\"b\";s:21:\"restore_any_candidate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:206;a:4:{s:1:\"a\";i:231;s:1:\"b\";s:19:\"replicate_candidate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:207;a:4:{s:1:\"a\";i:232;s:1:\"b\";s:17:\"reorder_candidate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:208;a:4:{s:1:\"a\";i:233;s:1:\"b\";s:16:\"delete_candidate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:209;a:4:{s:1:\"a\";i:234;s:1:\"b\";s:20:\"delete_any_candidate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:210;a:4:{s:1:\"a\";i:235;s:1:\"b\";s:22:\"force_delete_candidate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:211;a:4:{s:1:\"a\";i:236;s:1:\"b\";s:26:\"force_delete_any_candidate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:212;a:4:{s:1:\"a\";i:237;s:1:\"b\";s:22:\"view_resume_bank_entry\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:213;a:4:{s:1:\"a\";i:238;s:1:\"b\";s:26:\"view_any_resume_bank_entry\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:214;a:4:{s:1:\"a\";i:239;s:1:\"b\";s:24:\"create_resume_bank_entry\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:215;a:4:{s:1:\"a\";i:240;s:1:\"b\";s:24:\"update_resume_bank_entry\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:216;a:4:{s:1:\"a\";i:241;s:1:\"b\";s:25:\"restore_resume_bank_entry\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:217;a:4:{s:1:\"a\";i:242;s:1:\"b\";s:29:\"restore_any_resume_bank_entry\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:218;a:4:{s:1:\"a\";i:243;s:1:\"b\";s:27:\"replicate_resume_bank_entry\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:219;a:4:{s:1:\"a\";i:244;s:1:\"b\";s:25:\"reorder_resume_bank_entry\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:220;a:4:{s:1:\"a\";i:245;s:1:\"b\";s:24:\"delete_resume_bank_entry\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:221;a:4:{s:1:\"a\";i:246;s:1:\"b\";s:28:\"delete_any_resume_bank_entry\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:222;a:4:{s:1:\"a\";i:247;s:1:\"b\";s:30:\"force_delete_resume_bank_entry\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:223;a:4:{s:1:\"a\";i:248;s:1:\"b\";s:34:\"force_delete_any_resume_bank_entry\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:224;a:4:{s:1:\"a\";i:249;s:1:\"b\";s:14:\"view_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:225;a:4:{s:1:\"a\";i:250;s:1:\"b\";s:18:\"view_any_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:226;a:4:{s:1:\"a\";i:251;s:1:\"b\";s:16:\"create_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:227;a:4:{s:1:\"a\";i:252;s:1:\"b\";s:16:\"update_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:228;a:4:{s:1:\"a\";i:253;s:1:\"b\";s:17:\"restore_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:229;a:4:{s:1:\"a\";i:254;s:1:\"b\";s:21:\"restore_any_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:230;a:4:{s:1:\"a\";i:255;s:1:\"b\";s:19:\"replicate_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:231;a:4:{s:1:\"a\";i:256;s:1:\"b\";s:17:\"reorder_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:232;a:4:{s:1:\"a\";i:257;s:1:\"b\";s:16:\"delete_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:233;a:4:{s:1:\"a\";i:258;s:1:\"b\";s:20:\"delete_any_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:234;a:4:{s:1:\"a\";i:259;s:1:\"b\";s:22:\"force_delete_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:235;a:4:{s:1:\"a\";i:260;s:1:\"b\";s:26:\"force_delete_any_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:236;a:4:{s:1:\"a\";i:261;s:1:\"b\";s:17:\"view_offer_letter\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:237;a:4:{s:1:\"a\";i:262;s:1:\"b\";s:21:\"view_any_offer_letter\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:238;a:4:{s:1:\"a\";i:263;s:1:\"b\";s:19:\"create_offer_letter\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:239;a:4:{s:1:\"a\";i:264;s:1:\"b\";s:19:\"update_offer_letter\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:240;a:4:{s:1:\"a\";i:265;s:1:\"b\";s:20:\"restore_offer_letter\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:241;a:4:{s:1:\"a\";i:266;s:1:\"b\";s:24:\"restore_any_offer_letter\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:242;a:4:{s:1:\"a\";i:267;s:1:\"b\";s:22:\"replicate_offer_letter\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:243;a:4:{s:1:\"a\";i:268;s:1:\"b\";s:20:\"reorder_offer_letter\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:244;a:4:{s:1:\"a\";i:269;s:1:\"b\";s:19:\"delete_offer_letter\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:245;a:4:{s:1:\"a\";i:270;s:1:\"b\";s:23:\"delete_any_offer_letter\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:246;a:4:{s:1:\"a\";i:271;s:1:\"b\";s:25:\"force_delete_offer_letter\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:247;a:4:{s:1:\"a\";i:272;s:1:\"b\";s:29:\"force_delete_any_offer_letter\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:8;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:248;a:4:{s:1:\"a\";i:273;s:1:\"b\";s:20:\"view_onboarding_task\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:249;a:4:{s:1:\"a\";i:274;s:1:\"b\";s:24:\"view_any_onboarding_task\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:250;a:4:{s:1:\"a\";i:275;s:1:\"b\";s:22:\"create_onboarding_task\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:251;a:4:{s:1:\"a\";i:276;s:1:\"b\";s:22:\"update_onboarding_task\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:252;a:4:{s:1:\"a\";i:277;s:1:\"b\";s:23:\"restore_onboarding_task\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:253;a:4:{s:1:\"a\";i:278;s:1:\"b\";s:27:\"restore_any_onboarding_task\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:254;a:4:{s:1:\"a\";i:279;s:1:\"b\";s:25:\"replicate_onboarding_task\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:255;a:4:{s:1:\"a\";i:280;s:1:\"b\";s:23:\"reorder_onboarding_task\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:256;a:4:{s:1:\"a\";i:281;s:1:\"b\";s:22:\"delete_onboarding_task\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:257;a:4:{s:1:\"a\";i:282;s:1:\"b\";s:26:\"delete_any_onboarding_task\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:258;a:4:{s:1:\"a\";i:283;s:1:\"b\";s:28:\"force_delete_onboarding_task\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:259;a:4:{s:1:\"a\";i:284;s:1:\"b\";s:32:\"force_delete_any_onboarding_task\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:260;a:4:{s:1:\"a\";i:285;s:1:\"b\";s:21:\"view_probation_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:261;a:4:{s:1:\"a\";i:286;s:1:\"b\";s:25:\"view_any_probation_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:262;a:4:{s:1:\"a\";i:287;s:1:\"b\";s:23:\"create_probation_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:263;a:4:{s:1:\"a\";i:288;s:1:\"b\";s:23:\"update_probation_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:264;a:4:{s:1:\"a\";i:289;s:1:\"b\";s:24:\"restore_probation_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:265;a:4:{s:1:\"a\";i:290;s:1:\"b\";s:28:\"restore_any_probation_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:266;a:4:{s:1:\"a\";i:291;s:1:\"b\";s:26:\"replicate_probation_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:267;a:4:{s:1:\"a\";i:292;s:1:\"b\";s:24:\"reorder_probation_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:268;a:4:{s:1:\"a\";i:293;s:1:\"b\";s:23:\"delete_probation_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:269;a:4:{s:1:\"a\";i:294;s:1:\"b\";s:27:\"delete_any_probation_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:270;a:4:{s:1:\"a\";i:295;s:1:\"b\";s:29:\"force_delete_probation_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:271;a:4:{s:1:\"a\";i:296;s:1:\"b\";s:33:\"force_delete_any_probation_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:272;a:4:{s:1:\"a\";i:297;s:1:\"b\";s:22:\"view_attendance_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:273;a:4:{s:1:\"a\";i:298;s:1:\"b\";s:26:\"view_any_attendance_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:274;a:4:{s:1:\"a\";i:299;s:1:\"b\";s:24:\"create_attendance_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:275;a:4:{s:1:\"a\";i:300;s:1:\"b\";s:24:\"update_attendance_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:276;a:4:{s:1:\"a\";i:301;s:1:\"b\";s:25:\"restore_attendance_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:277;a:4:{s:1:\"a\";i:302;s:1:\"b\";s:29:\"restore_any_attendance_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:278;a:4:{s:1:\"a\";i:303;s:1:\"b\";s:27:\"replicate_attendance_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:279;a:4:{s:1:\"a\";i:304;s:1:\"b\";s:25:\"reorder_attendance_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:280;a:4:{s:1:\"a\";i:305;s:1:\"b\";s:24:\"delete_attendance_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:281;a:4:{s:1:\"a\";i:306;s:1:\"b\";s:28:\"delete_any_attendance_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:282;a:4:{s:1:\"a\";i:307;s:1:\"b\";s:30:\"force_delete_attendance_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:283;a:4:{s:1:\"a\";i:308;s:1:\"b\";s:34:\"force_delete_any_attendance_record\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:284;a:4:{s:1:\"a\";i:309;s:1:\"b\";s:27:\"view_regularization_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:285;a:4:{s:1:\"a\";i:310;s:1:\"b\";s:31:\"view_any_regularization_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:286;a:4:{s:1:\"a\";i:311;s:1:\"b\";s:29:\"create_regularization_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:287;a:4:{s:1:\"a\";i:312;s:1:\"b\";s:29:\"update_regularization_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:288;a:4:{s:1:\"a\";i:313;s:1:\"b\";s:30:\"restore_regularization_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:289;a:4:{s:1:\"a\";i:314;s:1:\"b\";s:34:\"restore_any_regularization_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:290;a:4:{s:1:\"a\";i:315;s:1:\"b\";s:32:\"replicate_regularization_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:291;a:4:{s:1:\"a\";i:316;s:1:\"b\";s:30:\"reorder_regularization_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:292;a:4:{s:1:\"a\";i:317;s:1:\"b\";s:29:\"delete_regularization_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:293;a:4:{s:1:\"a\";i:318;s:1:\"b\";s:33:\"delete_any_regularization_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:294;a:4:{s:1:\"a\";i:319;s:1:\"b\";s:35:\"force_delete_regularization_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:295;a:4:{s:1:\"a\";i:320;s:1:\"b\";s:39:\"force_delete_any_regularization_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:296;a:4:{s:1:\"a\";i:321;s:1:\"b\";s:16:\"view_wfh_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:297;a:4:{s:1:\"a\";i:322;s:1:\"b\";s:20:\"view_any_wfh_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:298;a:4:{s:1:\"a\";i:323;s:1:\"b\";s:18:\"create_wfh_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:299;a:4:{s:1:\"a\";i:324;s:1:\"b\";s:18:\"update_wfh_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:300;a:4:{s:1:\"a\";i:325;s:1:\"b\";s:19:\"restore_wfh_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:301;a:4:{s:1:\"a\";i:326;s:1:\"b\";s:23:\"restore_any_wfh_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:302;a:4:{s:1:\"a\";i:327;s:1:\"b\";s:21:\"replicate_wfh_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:303;a:4:{s:1:\"a\";i:328;s:1:\"b\";s:19:\"reorder_wfh_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:304;a:4:{s:1:\"a\";i:329;s:1:\"b\";s:18:\"delete_wfh_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:305;a:4:{s:1:\"a\";i:330;s:1:\"b\";s:22:\"delete_any_wfh_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:306;a:4:{s:1:\"a\";i:331;s:1:\"b\";s:24:\"force_delete_wfh_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:307;a:4:{s:1:\"a\";i:332;s:1:\"b\";s:28:\"force_delete_any_wfh_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:308;a:4:{s:1:\"a\";i:333;s:1:\"b\";s:21:\"view_overtime_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:309;a:4:{s:1:\"a\";i:334;s:1:\"b\";s:25:\"view_any_overtime_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:310;a:4:{s:1:\"a\";i:335;s:1:\"b\";s:23:\"create_overtime_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:311;a:4:{s:1:\"a\";i:336;s:1:\"b\";s:23:\"update_overtime_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:312;a:4:{s:1:\"a\";i:337;s:1:\"b\";s:24:\"restore_overtime_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:313;a:4:{s:1:\"a\";i:338;s:1:\"b\";s:28:\"restore_any_overtime_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:314;a:4:{s:1:\"a\";i:339;s:1:\"b\";s:26:\"replicate_overtime_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:315;a:4:{s:1:\"a\";i:340;s:1:\"b\";s:24:\"reorder_overtime_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:316;a:4:{s:1:\"a\";i:341;s:1:\"b\";s:23:\"delete_overtime_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:317;a:4:{s:1:\"a\";i:342;s:1:\"b\";s:27:\"delete_any_overtime_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:318;a:4:{s:1:\"a\";i:343;s:1:\"b\";s:29:\"force_delete_overtime_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:319;a:4:{s:1:\"a\";i:344;s:1:\"b\";s:33:\"force_delete_any_overtime_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:320;a:4:{s:1:\"a\";i:345;s:1:\"b\";s:15:\"view_leave_type\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:321;a:4:{s:1:\"a\";i:346;s:1:\"b\";s:19:\"view_any_leave_type\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:322;a:4:{s:1:\"a\";i:347;s:1:\"b\";s:17:\"create_leave_type\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:323;a:4:{s:1:\"a\";i:348;s:1:\"b\";s:17:\"update_leave_type\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:324;a:4:{s:1:\"a\";i:349;s:1:\"b\";s:18:\"restore_leave_type\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:325;a:4:{s:1:\"a\";i:350;s:1:\"b\";s:22:\"restore_any_leave_type\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:326;a:4:{s:1:\"a\";i:351;s:1:\"b\";s:20:\"replicate_leave_type\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:327;a:4:{s:1:\"a\";i:352;s:1:\"b\";s:18:\"reorder_leave_type\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:328;a:4:{s:1:\"a\";i:353;s:1:\"b\";s:17:\"delete_leave_type\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:329;a:4:{s:1:\"a\";i:354;s:1:\"b\";s:21:\"delete_any_leave_type\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:330;a:4:{s:1:\"a\";i:355;s:1:\"b\";s:23:\"force_delete_leave_type\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:331;a:4:{s:1:\"a\";i:356;s:1:\"b\";s:27:\"force_delete_any_leave_type\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:332;a:4:{s:1:\"a\";i:357;s:1:\"b\";s:18:\"view_leave_balance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:333;a:4:{s:1:\"a\";i:358;s:1:\"b\";s:22:\"view_any_leave_balance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:334;a:4:{s:1:\"a\";i:359;s:1:\"b\";s:20:\"create_leave_balance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:335;a:4:{s:1:\"a\";i:360;s:1:\"b\";s:20:\"update_leave_balance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:336;a:4:{s:1:\"a\";i:361;s:1:\"b\";s:21:\"restore_leave_balance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:337;a:4:{s:1:\"a\";i:362;s:1:\"b\";s:25:\"restore_any_leave_balance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:338;a:4:{s:1:\"a\";i:363;s:1:\"b\";s:23:\"replicate_leave_balance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:339;a:4:{s:1:\"a\";i:364;s:1:\"b\";s:21:\"reorder_leave_balance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:340;a:4:{s:1:\"a\";i:365;s:1:\"b\";s:20:\"delete_leave_balance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:341;a:4:{s:1:\"a\";i:366;s:1:\"b\";s:24:\"delete_any_leave_balance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:342;a:4:{s:1:\"a\";i:367;s:1:\"b\";s:26:\"force_delete_leave_balance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:343;a:4:{s:1:\"a\";i:368;s:1:\"b\";s:30:\"force_delete_any_leave_balance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:344;a:4:{s:1:\"a\";i:369;s:1:\"b\";s:18:\"view_leave_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:345;a:4:{s:1:\"a\";i:370;s:1:\"b\";s:22:\"view_any_leave_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:346;a:4:{s:1:\"a\";i:371;s:1:\"b\";s:20:\"create_leave_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:347;a:4:{s:1:\"a\";i:372;s:1:\"b\";s:20:\"update_leave_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:348;a:4:{s:1:\"a\";i:373;s:1:\"b\";s:21:\"restore_leave_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:349;a:4:{s:1:\"a\";i:374;s:1:\"b\";s:25:\"restore_any_leave_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:350;a:4:{s:1:\"a\";i:375;s:1:\"b\";s:23:\"replicate_leave_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:351;a:4:{s:1:\"a\";i:376;s:1:\"b\";s:21:\"reorder_leave_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:352;a:4:{s:1:\"a\";i:377;s:1:\"b\";s:20:\"delete_leave_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:8;i:1;i:10;i:2;i:11;i:3;i:14;i:4;i:15;i:5;i:17;i:6;i:20;i:7;i:23;}}i:353;a:4:{s:1:\"a\";i:378;s:1:\"b\";s:24:\"delete_any_leave_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:354;a:4:{s:1:\"a\";i:379;s:1:\"b\";s:26:\"force_delete_leave_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:355;a:4:{s:1:\"a\";i:380;s:1:\"b\";s:30:\"force_delete_any_leave_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:356;a:4:{s:1:\"a\";i:381;s:1:\"b\";s:13:\"view_comp_off\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:357;a:4:{s:1:\"a\";i:382;s:1:\"b\";s:17:\"view_any_comp_off\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:358;a:4:{s:1:\"a\";i:383;s:1:\"b\";s:15:\"create_comp_off\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:359;a:4:{s:1:\"a\";i:384;s:1:\"b\";s:15:\"update_comp_off\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:360;a:4:{s:1:\"a\";i:385;s:1:\"b\";s:16:\"restore_comp_off\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:361;a:4:{s:1:\"a\";i:386;s:1:\"b\";s:20:\"restore_any_comp_off\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:362;a:4:{s:1:\"a\";i:387;s:1:\"b\";s:18:\"replicate_comp_off\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:363;a:4:{s:1:\"a\";i:388;s:1:\"b\";s:16:\"reorder_comp_off\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:364;a:4:{s:1:\"a\";i:389;s:1:\"b\";s:15:\"delete_comp_off\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:365;a:4:{s:1:\"a\";i:390;s:1:\"b\";s:19:\"delete_any_comp_off\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:366;a:4:{s:1:\"a\";i:391;s:1:\"b\";s:21:\"force_delete_comp_off\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:367;a:4:{s:1:\"a\";i:392;s:1:\"b\";s:25:\"force_delete_any_comp_off\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:368;a:4:{s:1:\"a\";i:393;s:1:\"b\";s:21:\"view_leave_encashment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:369;a:4:{s:1:\"a\";i:394;s:1:\"b\";s:25:\"view_any_leave_encashment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:370;a:4:{s:1:\"a\";i:395;s:1:\"b\";s:23:\"create_leave_encashment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:371;a:4:{s:1:\"a\";i:396;s:1:\"b\";s:23:\"update_leave_encashment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:372;a:4:{s:1:\"a\";i:397;s:1:\"b\";s:24:\"restore_leave_encashment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:373;a:4:{s:1:\"a\";i:398;s:1:\"b\";s:28:\"restore_any_leave_encashment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:374;a:4:{s:1:\"a\";i:399;s:1:\"b\";s:26:\"replicate_leave_encashment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:375;a:4:{s:1:\"a\";i:400;s:1:\"b\";s:24:\"reorder_leave_encashment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:376;a:4:{s:1:\"a\";i:401;s:1:\"b\";s:23:\"delete_leave_encashment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:377;a:4:{s:1:\"a\";i:402;s:1:\"b\";s:27:\"delete_any_leave_encashment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:378;a:4:{s:1:\"a\";i:403;s:1:\"b\";s:29:\"force_delete_leave_encashment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:379;a:4:{s:1:\"a\";i:404;s:1:\"b\";s:33:\"force_delete_any_leave_encashment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:380;a:4:{s:1:\"a\";i:405;s:1:\"b\";s:10:\"view_shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:381;a:4:{s:1:\"a\";i:406;s:1:\"b\";s:14:\"view_any_shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:382;a:4:{s:1:\"a\";i:407;s:1:\"b\";s:12:\"create_shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:383;a:4:{s:1:\"a\";i:408;s:1:\"b\";s:12:\"update_shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:384;a:4:{s:1:\"a\";i:409;s:1:\"b\";s:13:\"restore_shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:385;a:4:{s:1:\"a\";i:410;s:1:\"b\";s:17:\"restore_any_shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:386;a:4:{s:1:\"a\";i:411;s:1:\"b\";s:15:\"replicate_shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:387;a:4:{s:1:\"a\";i:412;s:1:\"b\";s:13:\"reorder_shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:388;a:4:{s:1:\"a\";i:413;s:1:\"b\";s:12:\"delete_shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:389;a:4:{s:1:\"a\";i:414;s:1:\"b\";s:16:\"delete_any_shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:390;a:4:{s:1:\"a\";i:415;s:1:\"b\";s:18:\"force_delete_shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:391;a:4:{s:1:\"a\";i:416;s:1:\"b\";s:22:\"force_delete_any_shift\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:392;a:4:{s:1:\"a\";i:417;s:1:\"b\";s:17:\"view_shift_roster\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:393;a:4:{s:1:\"a\";i:418;s:1:\"b\";s:21:\"view_any_shift_roster\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:394;a:4:{s:1:\"a\";i:419;s:1:\"b\";s:19:\"create_shift_roster\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:395;a:4:{s:1:\"a\";i:420;s:1:\"b\";s:19:\"update_shift_roster\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:396;a:4:{s:1:\"a\";i:421;s:1:\"b\";s:20:\"restore_shift_roster\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:397;a:4:{s:1:\"a\";i:422;s:1:\"b\";s:24:\"restore_any_shift_roster\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:398;a:4:{s:1:\"a\";i:423;s:1:\"b\";s:22:\"replicate_shift_roster\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:399;a:4:{s:1:\"a\";i:424;s:1:\"b\";s:20:\"reorder_shift_roster\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:400;a:4:{s:1:\"a\";i:425;s:1:\"b\";s:19:\"delete_shift_roster\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:401;a:4:{s:1:\"a\";i:426;s:1:\"b\";s:23:\"delete_any_shift_roster\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:402;a:4:{s:1:\"a\";i:427;s:1:\"b\";s:25:\"force_delete_shift_roster\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:403;a:4:{s:1:\"a\";i:428;s:1:\"b\";s:29:\"force_delete_any_shift_roster\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:404;a:4:{s:1:\"a\";i:429;s:1:\"b\";s:23:\"view_shift_swap_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:405;a:4:{s:1:\"a\";i:430;s:1:\"b\";s:27:\"view_any_shift_swap_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:406;a:4:{s:1:\"a\";i:431;s:1:\"b\";s:25:\"create_shift_swap_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:407;a:4:{s:1:\"a\";i:432;s:1:\"b\";s:25:\"update_shift_swap_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:408;a:4:{s:1:\"a\";i:433;s:1:\"b\";s:26:\"restore_shift_swap_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:409;a:4:{s:1:\"a\";i:434;s:1:\"b\";s:30:\"restore_any_shift_swap_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:410;a:4:{s:1:\"a\";i:435;s:1:\"b\";s:28:\"replicate_shift_swap_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:411;a:4:{s:1:\"a\";i:436;s:1:\"b\";s:26:\"reorder_shift_swap_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:412;a:4:{s:1:\"a\";i:437;s:1:\"b\";s:25:\"delete_shift_swap_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:413;a:4:{s:1:\"a\";i:438;s:1:\"b\";s:29:\"delete_any_shift_swap_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:414;a:4:{s:1:\"a\";i:439;s:1:\"b\";s:31:\"force_delete_shift_swap_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:415;a:4:{s:1:\"a\";i:440;s:1:\"b\";s:35:\"force_delete_any_shift_swap_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:416;a:4:{s:1:\"a\";i:441;s:1:\"b\";s:18:\"view_overtime_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:417;a:4:{s:1:\"a\";i:442;s:1:\"b\";s:22:\"view_any_overtime_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:418;a:4:{s:1:\"a\";i:443;s:1:\"b\";s:20:\"create_overtime_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:419;a:4:{s:1:\"a\";i:444;s:1:\"b\";s:20:\"update_overtime_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:420;a:4:{s:1:\"a\";i:445;s:1:\"b\";s:21:\"restore_overtime_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:421;a:4:{s:1:\"a\";i:446;s:1:\"b\";s:25:\"restore_any_overtime_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:422;a:4:{s:1:\"a\";i:447;s:1:\"b\";s:23:\"replicate_overtime_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:423;a:4:{s:1:\"a\";i:448;s:1:\"b\";s:21:\"reorder_overtime_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:424;a:4:{s:1:\"a\";i:449;s:1:\"b\";s:20:\"delete_overtime_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:425;a:4:{s:1:\"a\";i:450;s:1:\"b\";s:24:\"delete_any_overtime_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:426;a:4:{s:1:\"a\";i:451;s:1:\"b\";s:26:\"force_delete_overtime_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:427;a:4:{s:1:\"a\";i:452;s:1:\"b\";s:30:\"force_delete_any_overtime_rule\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:428;a:4:{s:1:\"a\";i:453;s:1:\"b\";s:21:\"view_salary_component\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:429;a:4:{s:1:\"a\";i:454;s:1:\"b\";s:25:\"view_any_salary_component\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:430;a:4:{s:1:\"a\";i:455;s:1:\"b\";s:23:\"create_salary_component\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:431;a:4:{s:1:\"a\";i:456;s:1:\"b\";s:23:\"update_salary_component\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:432;a:4:{s:1:\"a\";i:457;s:1:\"b\";s:24:\"restore_salary_component\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:433;a:4:{s:1:\"a\";i:458;s:1:\"b\";s:28:\"restore_any_salary_component\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:434;a:4:{s:1:\"a\";i:459;s:1:\"b\";s:26:\"replicate_salary_component\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:435;a:4:{s:1:\"a\";i:460;s:1:\"b\";s:24:\"reorder_salary_component\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:436;a:4:{s:1:\"a\";i:461;s:1:\"b\";s:23:\"delete_salary_component\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:437;a:4:{s:1:\"a\";i:462;s:1:\"b\";s:27:\"delete_any_salary_component\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:438;a:4:{s:1:\"a\";i:463;s:1:\"b\";s:29:\"force_delete_salary_component\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:439;a:4:{s:1:\"a\";i:464;s:1:\"b\";s:33:\"force_delete_any_salary_component\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:440;a:4:{s:1:\"a\";i:465;s:1:\"b\";s:21:\"view_salary_structure\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:441;a:4:{s:1:\"a\";i:466;s:1:\"b\";s:25:\"view_any_salary_structure\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:442;a:4:{s:1:\"a\";i:467;s:1:\"b\";s:23:\"create_salary_structure\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:443;a:4:{s:1:\"a\";i:468;s:1:\"b\";s:23:\"update_salary_structure\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:444;a:4:{s:1:\"a\";i:469;s:1:\"b\";s:24:\"restore_salary_structure\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:445;a:4:{s:1:\"a\";i:470;s:1:\"b\";s:28:\"restore_any_salary_structure\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:446;a:4:{s:1:\"a\";i:471;s:1:\"b\";s:26:\"replicate_salary_structure\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:447;a:4:{s:1:\"a\";i:472;s:1:\"b\";s:24:\"reorder_salary_structure\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:448;a:4:{s:1:\"a\";i:473;s:1:\"b\";s:23:\"delete_salary_structure\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:449;a:4:{s:1:\"a\";i:474;s:1:\"b\";s:27:\"delete_any_salary_structure\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:450;a:4:{s:1:\"a\";i:475;s:1:\"b\";s:29:\"force_delete_salary_structure\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:451;a:4:{s:1:\"a\";i:476;s:1:\"b\";s:33:\"force_delete_any_salary_structure\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:452;a:4:{s:1:\"a\";i:477;s:1:\"b\";s:10:\"view_bonus\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:453;a:4:{s:1:\"a\";i:478;s:1:\"b\";s:14:\"view_any_bonus\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:454;a:4:{s:1:\"a\";i:479;s:1:\"b\";s:12:\"create_bonus\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:455;a:4:{s:1:\"a\";i:480;s:1:\"b\";s:12:\"update_bonus\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:456;a:4:{s:1:\"a\";i:481;s:1:\"b\";s:13:\"restore_bonus\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:457;a:4:{s:1:\"a\";i:482;s:1:\"b\";s:17:\"restore_any_bonus\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:458;a:4:{s:1:\"a\";i:483;s:1:\"b\";s:15:\"replicate_bonus\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:459;a:4:{s:1:\"a\";i:484;s:1:\"b\";s:13:\"reorder_bonus\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:460;a:4:{s:1:\"a\";i:485;s:1:\"b\";s:12:\"delete_bonus\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:461;a:4:{s:1:\"a\";i:486;s:1:\"b\";s:16:\"delete_any_bonus\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:462;a:4:{s:1:\"a\";i:487;s:1:\"b\";s:18:\"force_delete_bonus\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:463;a:4:{s:1:\"a\";i:488;s:1:\"b\";s:22:\"force_delete_any_bonus\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:464;a:4:{s:1:\"a\";i:489;s:1:\"b\";s:14:\"view_incentive\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:465;a:4:{s:1:\"a\";i:490;s:1:\"b\";s:18:\"view_any_incentive\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:466;a:4:{s:1:\"a\";i:491;s:1:\"b\";s:16:\"create_incentive\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:467;a:4:{s:1:\"a\";i:492;s:1:\"b\";s:16:\"update_incentive\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:468;a:4:{s:1:\"a\";i:493;s:1:\"b\";s:17:\"restore_incentive\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:469;a:4:{s:1:\"a\";i:494;s:1:\"b\";s:21:\"restore_any_incentive\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:470;a:4:{s:1:\"a\";i:495;s:1:\"b\";s:19:\"replicate_incentive\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:471;a:4:{s:1:\"a\";i:496;s:1:\"b\";s:17:\"reorder_incentive\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:472;a:4:{s:1:\"a\";i:497;s:1:\"b\";s:16:\"delete_incentive\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:473;a:4:{s:1:\"a\";i:498;s:1:\"b\";s:20:\"delete_any_incentive\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:474;a:4:{s:1:\"a\";i:499;s:1:\"b\";s:22:\"force_delete_incentive\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:475;a:4:{s:1:\"a\";i:500;s:1:\"b\";s:26:\"force_delete_any_incentive\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:476;a:4:{s:1:\"a\";i:501;s:1:\"b\";s:14:\"view_deduction\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:477;a:4:{s:1:\"a\";i:502;s:1:\"b\";s:18:\"view_any_deduction\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:478;a:4:{s:1:\"a\";i:503;s:1:\"b\";s:16:\"create_deduction\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:479;a:4:{s:1:\"a\";i:504;s:1:\"b\";s:16:\"update_deduction\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:480;a:4:{s:1:\"a\";i:505;s:1:\"b\";s:17:\"restore_deduction\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:481;a:4:{s:1:\"a\";i:506;s:1:\"b\";s:21:\"restore_any_deduction\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:482;a:4:{s:1:\"a\";i:507;s:1:\"b\";s:19:\"replicate_deduction\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:483;a:4:{s:1:\"a\";i:508;s:1:\"b\";s:17:\"reorder_deduction\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:484;a:4:{s:1:\"a\";i:509;s:1:\"b\";s:16:\"delete_deduction\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:485;a:4:{s:1:\"a\";i:510;s:1:\"b\";s:20:\"delete_any_deduction\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:486;a:4:{s:1:\"a\";i:511;s:1:\"b\";s:22:\"force_delete_deduction\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:487;a:4:{s:1:\"a\";i:512;s:1:\"b\";s:26:\"force_delete_any_deduction\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:488;a:4:{s:1:\"a\";i:513;s:1:\"b\";s:9:\"view_loan\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:489;a:4:{s:1:\"a\";i:514;s:1:\"b\";s:13:\"view_any_loan\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:490;a:4:{s:1:\"a\";i:515;s:1:\"b\";s:11:\"create_loan\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:491;a:4:{s:1:\"a\";i:516;s:1:\"b\";s:11:\"update_loan\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:492;a:4:{s:1:\"a\";i:517;s:1:\"b\";s:12:\"restore_loan\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:493;a:4:{s:1:\"a\";i:518;s:1:\"b\";s:16:\"restore_any_loan\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:494;a:4:{s:1:\"a\";i:519;s:1:\"b\";s:14:\"replicate_loan\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:495;a:4:{s:1:\"a\";i:520;s:1:\"b\";s:12:\"reorder_loan\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:496;a:4:{s:1:\"a\";i:521;s:1:\"b\";s:11:\"delete_loan\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:497;a:4:{s:1:\"a\";i:522;s:1:\"b\";s:15:\"delete_any_loan\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:498;a:4:{s:1:\"a\";i:523;s:1:\"b\";s:17:\"force_delete_loan\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:499;a:4:{s:1:\"a\";i:524;s:1:\"b\";s:21:\"force_delete_any_loan\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:500;a:4:{s:1:\"a\";i:525;s:1:\"b\";s:20:\"view_tax_declaration\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:501;a:4:{s:1:\"a\";i:526;s:1:\"b\";s:24:\"view_any_tax_declaration\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:502;a:4:{s:1:\"a\";i:527;s:1:\"b\";s:22:\"create_tax_declaration\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:503;a:4:{s:1:\"a\";i:528;s:1:\"b\";s:22:\"update_tax_declaration\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:504;a:4:{s:1:\"a\";i:529;s:1:\"b\";s:23:\"restore_tax_declaration\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:505;a:4:{s:1:\"a\";i:530;s:1:\"b\";s:27:\"restore_any_tax_declaration\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:506;a:4:{s:1:\"a\";i:531;s:1:\"b\";s:25:\"replicate_tax_declaration\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:507;a:4:{s:1:\"a\";i:532;s:1:\"b\";s:23:\"reorder_tax_declaration\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:508;a:4:{s:1:\"a\";i:533;s:1:\"b\";s:22:\"delete_tax_declaration\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:509;a:4:{s:1:\"a\";i:534;s:1:\"b\";s:26:\"delete_any_tax_declaration\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:510;a:4:{s:1:\"a\";i:535;s:1:\"b\";s:28:\"force_delete_tax_declaration\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:511;a:4:{s:1:\"a\";i:536;s:1:\"b\";s:32:\"force_delete_any_tax_declaration\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:512;a:4:{s:1:\"a\";i:537;s:1:\"b\";s:15:\"view_pf_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:513;a:4:{s:1:\"a\";i:538;s:1:\"b\";s:19:\"view_any_pf_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:514;a:4:{s:1:\"a\";i:539;s:1:\"b\";s:17:\"create_pf_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:515;a:4:{s:1:\"a\";i:540;s:1:\"b\";s:17:\"update_pf_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:516;a:4:{s:1:\"a\";i:541;s:1:\"b\";s:18:\"restore_pf_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:517;a:4:{s:1:\"a\";i:542;s:1:\"b\";s:22:\"restore_any_pf_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:518;a:4:{s:1:\"a\";i:543;s:1:\"b\";s:20:\"replicate_pf_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:519;a:4:{s:1:\"a\";i:544;s:1:\"b\";s:18:\"reorder_pf_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:520;a:4:{s:1:\"a\";i:545;s:1:\"b\";s:17:\"delete_pf_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:521;a:4:{s:1:\"a\";i:546;s:1:\"b\";s:21:\"delete_any_pf_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:522;a:4:{s:1:\"a\";i:547;s:1:\"b\";s:23:\"force_delete_pf_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:523;a:4:{s:1:\"a\";i:548;s:1:\"b\";s:27:\"force_delete_any_pf_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:524;a:4:{s:1:\"a\";i:549;s:1:\"b\";s:16:\"view_esi_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:525;a:4:{s:1:\"a\";i:550;s:1:\"b\";s:20:\"view_any_esi_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:526;a:4:{s:1:\"a\";i:551;s:1:\"b\";s:18:\"create_esi_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:527;a:4:{s:1:\"a\";i:552;s:1:\"b\";s:18:\"update_esi_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:528;a:4:{s:1:\"a\";i:553;s:1:\"b\";s:19:\"restore_esi_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:529;a:4:{s:1:\"a\";i:554;s:1:\"b\";s:23:\"restore_any_esi_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:530;a:4:{s:1:\"a\";i:555;s:1:\"b\";s:21:\"replicate_esi_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:531;a:4:{s:1:\"a\";i:556;s:1:\"b\";s:19:\"reorder_esi_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:532;a:4:{s:1:\"a\";i:557;s:1:\"b\";s:18:\"delete_esi_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:533;a:4:{s:1:\"a\";i:558;s:1:\"b\";s:22:\"delete_any_esi_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:534;a:4:{s:1:\"a\";i:559;s:1:\"b\";s:24:\"force_delete_esi_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:535;a:4:{s:1:\"a\";i:560;s:1:\"b\";s:28:\"force_delete_any_esi_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:536;a:4:{s:1:\"a\";i:561;s:1:\"b\";s:16:\"view_payroll_run\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:537;a:4:{s:1:\"a\";i:562;s:1:\"b\";s:20:\"view_any_payroll_run\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:538;a:4:{s:1:\"a\";i:563;s:1:\"b\";s:18:\"create_payroll_run\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:539;a:4:{s:1:\"a\";i:564;s:1:\"b\";s:18:\"update_payroll_run\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:540;a:4:{s:1:\"a\";i:565;s:1:\"b\";s:19:\"restore_payroll_run\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:541;a:4:{s:1:\"a\";i:566;s:1:\"b\";s:23:\"restore_any_payroll_run\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:542;a:4:{s:1:\"a\";i:567;s:1:\"b\";s:21:\"replicate_payroll_run\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:543;a:4:{s:1:\"a\";i:568;s:1:\"b\";s:19:\"reorder_payroll_run\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:544;a:4:{s:1:\"a\";i:569;s:1:\"b\";s:18:\"delete_payroll_run\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:545;a:4:{s:1:\"a\";i:570;s:1:\"b\";s:22:\"delete_any_payroll_run\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:546;a:4:{s:1:\"a\";i:571;s:1:\"b\";s:24:\"force_delete_payroll_run\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:547;a:4:{s:1:\"a\";i:572;s:1:\"b\";s:28:\"force_delete_any_payroll_run\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:548;a:4:{s:1:\"a\";i:573;s:1:\"b\";s:12:\"view_payslip\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:549;a:4:{s:1:\"a\";i:574;s:1:\"b\";s:16:\"view_any_payslip\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:550;a:4:{s:1:\"a\";i:575;s:1:\"b\";s:14:\"create_payslip\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:551;a:4:{s:1:\"a\";i:576;s:1:\"b\";s:14:\"update_payslip\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:552;a:4:{s:1:\"a\";i:577;s:1:\"b\";s:15:\"restore_payslip\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:553;a:4:{s:1:\"a\";i:578;s:1:\"b\";s:19:\"restore_any_payslip\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:554;a:4:{s:1:\"a\";i:579;s:1:\"b\";s:17:\"replicate_payslip\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:555;a:4:{s:1:\"a\";i:580;s:1:\"b\";s:15:\"reorder_payslip\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:556;a:4:{s:1:\"a\";i:581;s:1:\"b\";s:14:\"delete_payslip\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:557;a:4:{s:1:\"a\";i:582;s:1:\"b\";s:18:\"delete_any_payslip\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:558;a:4:{s:1:\"a\";i:583;s:1:\"b\";s:20:\"force_delete_payslip\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:559;a:4:{s:1:\"a\";i:584;s:1:\"b\";s:24:\"force_delete_any_payslip\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:560;a:4:{s:1:\"a\";i:585;s:1:\"b\";s:9:\"view_goal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:561;a:4:{s:1:\"a\";i:586;s:1:\"b\";s:13:\"view_any_goal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:562;a:4:{s:1:\"a\";i:587;s:1:\"b\";s:11:\"create_goal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:563;a:4:{s:1:\"a\";i:588;s:1:\"b\";s:11:\"update_goal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:564;a:4:{s:1:\"a\";i:589;s:1:\"b\";s:12:\"restore_goal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:565;a:4:{s:1:\"a\";i:590;s:1:\"b\";s:16:\"restore_any_goal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:566;a:4:{s:1:\"a\";i:591;s:1:\"b\";s:14:\"replicate_goal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:567;a:4:{s:1:\"a\";i:592;s:1:\"b\";s:12:\"reorder_goal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:568;a:4:{s:1:\"a\";i:593;s:1:\"b\";s:11:\"delete_goal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:569;a:4:{s:1:\"a\";i:594;s:1:\"b\";s:15:\"delete_any_goal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:570;a:4:{s:1:\"a\";i:595;s:1:\"b\";s:17:\"force_delete_goal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:571;a:4:{s:1:\"a\";i:596;s:1:\"b\";s:21:\"force_delete_any_goal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:572;a:4:{s:1:\"a\";i:597;s:1:\"b\";s:8:\"view_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:573;a:4:{s:1:\"a\";i:598;s:1:\"b\";s:12:\"view_any_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:574;a:4:{s:1:\"a\";i:599;s:1:\"b\";s:10:\"create_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:575;a:4:{s:1:\"a\";i:600;s:1:\"b\";s:10:\"update_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:576;a:4:{s:1:\"a\";i:601;s:1:\"b\";s:11:\"restore_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:577;a:4:{s:1:\"a\";i:602;s:1:\"b\";s:15:\"restore_any_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:578;a:4:{s:1:\"a\";i:603;s:1:\"b\";s:13:\"replicate_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:579;a:4:{s:1:\"a\";i:604;s:1:\"b\";s:11:\"reorder_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:580;a:4:{s:1:\"a\";i:605;s:1:\"b\";s:10:\"delete_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:581;a:4:{s:1:\"a\";i:606;s:1:\"b\";s:14:\"delete_any_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:582;a:4:{s:1:\"a\";i:607;s:1:\"b\";s:16:\"force_delete_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:583;a:4:{s:1:\"a\";i:608;s:1:\"b\";s:20:\"force_delete_any_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:584;a:4:{s:1:\"a\";i:609;s:1:\"b\";s:17:\"view_employee_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:585;a:4:{s:1:\"a\";i:610;s:1:\"b\";s:21:\"view_any_employee_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:586;a:4:{s:1:\"a\";i:611;s:1:\"b\";s:19:\"create_employee_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:587;a:4:{s:1:\"a\";i:612;s:1:\"b\";s:19:\"update_employee_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:588;a:4:{s:1:\"a\";i:613;s:1:\"b\";s:20:\"restore_employee_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:589;a:4:{s:1:\"a\";i:614;s:1:\"b\";s:24:\"restore_any_employee_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:590;a:4:{s:1:\"a\";i:615;s:1:\"b\";s:22:\"replicate_employee_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:591;a:4:{s:1:\"a\";i:616;s:1:\"b\";s:20:\"reorder_employee_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:592;a:4:{s:1:\"a\";i:617;s:1:\"b\";s:19:\"delete_employee_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:593;a:4:{s:1:\"a\";i:618;s:1:\"b\";s:23:\"delete_any_employee_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:594;a:4:{s:1:\"a\";i:619;s:1:\"b\";s:25:\"force_delete_employee_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:595;a:4:{s:1:\"a\";i:620;s:1:\"b\";s:29:\"force_delete_any_employee_kpi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:596;a:4:{s:1:\"a\";i:621;s:1:\"b\";s:14:\"view_appraisal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:597;a:4:{s:1:\"a\";i:622;s:1:\"b\";s:18:\"view_any_appraisal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:598;a:4:{s:1:\"a\";i:623;s:1:\"b\";s:16:\"create_appraisal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:599;a:4:{s:1:\"a\";i:624;s:1:\"b\";s:16:\"update_appraisal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:600;a:4:{s:1:\"a\";i:625;s:1:\"b\";s:17:\"restore_appraisal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:601;a:4:{s:1:\"a\";i:626;s:1:\"b\";s:21:\"restore_any_appraisal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:602;a:4:{s:1:\"a\";i:627;s:1:\"b\";s:19:\"replicate_appraisal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:603;a:4:{s:1:\"a\";i:628;s:1:\"b\";s:17:\"reorder_appraisal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:604;a:4:{s:1:\"a\";i:629;s:1:\"b\";s:16:\"delete_appraisal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:605;a:4:{s:1:\"a\";i:630;s:1:\"b\";s:20:\"delete_any_appraisal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:606;a:4:{s:1:\"a\";i:631;s:1:\"b\";s:22:\"force_delete_appraisal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:607;a:4:{s:1:\"a\";i:632;s:1:\"b\";s:26:\"force_delete_any_appraisal\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:608;a:4:{s:1:\"a\";i:633;s:1:\"b\";s:17:\"view_feedback_360\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:609;a:4:{s:1:\"a\";i:634;s:1:\"b\";s:21:\"view_any_feedback_360\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:610;a:4:{s:1:\"a\";i:635;s:1:\"b\";s:19:\"create_feedback_360\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:611;a:4:{s:1:\"a\";i:636;s:1:\"b\";s:19:\"update_feedback_360\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:612;a:4:{s:1:\"a\";i:637;s:1:\"b\";s:20:\"restore_feedback_360\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:613;a:4:{s:1:\"a\";i:638;s:1:\"b\";s:24:\"restore_any_feedback_360\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:614;a:4:{s:1:\"a\";i:639;s:1:\"b\";s:22:\"replicate_feedback_360\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:615;a:4:{s:1:\"a\";i:640;s:1:\"b\";s:20:\"reorder_feedback_360\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:616;a:4:{s:1:\"a\";i:641;s:1:\"b\";s:19:\"delete_feedback_360\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:617;a:4:{s:1:\"a\";i:642;s:1:\"b\";s:23:\"delete_any_feedback_360\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:618;a:4:{s:1:\"a\";i:643;s:1:\"b\";s:25:\"force_delete_feedback_360\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:619;a:4:{s:1:\"a\";i:644;s:1:\"b\";s:29:\"force_delete_any_feedback_360\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:620;a:4:{s:1:\"a\";i:645;s:1:\"b\";s:14:\"view_increment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:621;a:4:{s:1:\"a\";i:646;s:1:\"b\";s:18:\"view_any_increment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:622;a:4:{s:1:\"a\";i:647;s:1:\"b\";s:16:\"create_increment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:623;a:4:{s:1:\"a\";i:648;s:1:\"b\";s:16:\"update_increment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:624;a:4:{s:1:\"a\";i:649;s:1:\"b\";s:17:\"restore_increment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:625;a:4:{s:1:\"a\";i:650;s:1:\"b\";s:21:\"restore_any_increment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:626;a:4:{s:1:\"a\";i:651;s:1:\"b\";s:19:\"replicate_increment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:627;a:4:{s:1:\"a\";i:652;s:1:\"b\";s:17:\"reorder_increment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:628;a:4:{s:1:\"a\";i:653;s:1:\"b\";s:16:\"delete_increment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:629;a:4:{s:1:\"a\";i:654;s:1:\"b\";s:20:\"delete_any_increment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:630;a:4:{s:1:\"a\";i:655;s:1:\"b\";s:22:\"force_delete_increment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:631;a:4:{s:1:\"a\";i:656;s:1:\"b\";s:26:\"force_delete_any_increment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:632;a:4:{s:1:\"a\";i:657;s:1:\"b\";s:12:\"view_trainer\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:633;a:4:{s:1:\"a\";i:658;s:1:\"b\";s:16:\"view_any_trainer\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:634;a:4:{s:1:\"a\";i:659;s:1:\"b\";s:14:\"create_trainer\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:635;a:4:{s:1:\"a\";i:660;s:1:\"b\";s:14:\"update_trainer\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:636;a:4:{s:1:\"a\";i:661;s:1:\"b\";s:15:\"restore_trainer\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:637;a:4:{s:1:\"a\";i:662;s:1:\"b\";s:19:\"restore_any_trainer\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:638;a:4:{s:1:\"a\";i:663;s:1:\"b\";s:17:\"replicate_trainer\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:639;a:4:{s:1:\"a\";i:664;s:1:\"b\";s:15:\"reorder_trainer\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:640;a:4:{s:1:\"a\";i:665;s:1:\"b\";s:14:\"delete_trainer\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:641;a:4:{s:1:\"a\";i:666;s:1:\"b\";s:18:\"delete_any_trainer\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:642;a:4:{s:1:\"a\";i:667;s:1:\"b\";s:20:\"force_delete_trainer\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:643;a:4:{s:1:\"a\";i:668;s:1:\"b\";s:24:\"force_delete_any_trainer\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:644;a:4:{s:1:\"a\";i:669;s:1:\"b\";s:11:\"view_course\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:645;a:4:{s:1:\"a\";i:670;s:1:\"b\";s:15:\"view_any_course\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:646;a:4:{s:1:\"a\";i:671;s:1:\"b\";s:13:\"create_course\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:647;a:4:{s:1:\"a\";i:672;s:1:\"b\";s:13:\"update_course\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:648;a:4:{s:1:\"a\";i:673;s:1:\"b\";s:14:\"restore_course\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:649;a:4:{s:1:\"a\";i:674;s:1:\"b\";s:18:\"restore_any_course\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:650;a:4:{s:1:\"a\";i:675;s:1:\"b\";s:16:\"replicate_course\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:651;a:4:{s:1:\"a\";i:676;s:1:\"b\";s:14:\"reorder_course\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:652;a:4:{s:1:\"a\";i:677;s:1:\"b\";s:13:\"delete_course\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:653;a:4:{s:1:\"a\";i:678;s:1:\"b\";s:17:\"delete_any_course\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:654;a:4:{s:1:\"a\";i:679;s:1:\"b\";s:19:\"force_delete_course\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:655;a:4:{s:1:\"a\";i:680;s:1:\"b\";s:23:\"force_delete_any_course\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:656;a:4:{s:1:\"a\";i:681;s:1:\"b\";s:21:\"view_training_session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:657;a:4:{s:1:\"a\";i:682;s:1:\"b\";s:25:\"view_any_training_session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:658;a:4:{s:1:\"a\";i:683;s:1:\"b\";s:23:\"create_training_session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:659;a:4:{s:1:\"a\";i:684;s:1:\"b\";s:23:\"update_training_session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:660;a:4:{s:1:\"a\";i:685;s:1:\"b\";s:24:\"restore_training_session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:661;a:4:{s:1:\"a\";i:686;s:1:\"b\";s:28:\"restore_any_training_session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:662;a:4:{s:1:\"a\";i:687;s:1:\"b\";s:26:\"replicate_training_session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:663;a:4:{s:1:\"a\";i:688;s:1:\"b\";s:24:\"reorder_training_session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:664;a:4:{s:1:\"a\";i:689;s:1:\"b\";s:23:\"delete_training_session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:665;a:4:{s:1:\"a\";i:690;s:1:\"b\";s:27:\"delete_any_training_session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:666;a:4:{s:1:\"a\";i:691;s:1:\"b\";s:29:\"force_delete_training_session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:667;a:4:{s:1:\"a\";i:692;s:1:\"b\";s:33:\"force_delete_any_training_session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:668;a:4:{s:1:\"a\";i:693;s:1:\"b\";s:24:\"view_training_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:669;a:4:{s:1:\"a\";i:694;s:1:\"b\";s:28:\"view_any_training_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:670;a:4:{s:1:\"a\";i:695;s:1:\"b\";s:26:\"create_training_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:671;a:4:{s:1:\"a\";i:696;s:1:\"b\";s:26:\"update_training_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:672;a:4:{s:1:\"a\";i:697;s:1:\"b\";s:27:\"restore_training_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:673;a:4:{s:1:\"a\";i:698;s:1:\"b\";s:31:\"restore_any_training_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:674;a:4:{s:1:\"a\";i:699;s:1:\"b\";s:29:\"replicate_training_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:675;a:4:{s:1:\"a\";i:700;s:1:\"b\";s:27:\"reorder_training_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:676;a:4:{s:1:\"a\";i:701;s:1:\"b\";s:26:\"delete_training_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:677;a:4:{s:1:\"a\";i:702;s:1:\"b\";s:30:\"delete_any_training_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:678;a:4:{s:1:\"a\";i:703;s:1:\"b\";s:32:\"force_delete_training_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:679;a:4:{s:1:\"a\";i:704;s:1:\"b\";s:36:\"force_delete_any_training_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:680;a:4:{s:1:\"a\";i:705;s:1:\"b\";s:18:\"view_certification\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:681;a:4:{s:1:\"a\";i:706;s:1:\"b\";s:22:\"view_any_certification\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:682;a:4:{s:1:\"a\";i:707;s:1:\"b\";s:20:\"create_certification\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:683;a:4:{s:1:\"a\";i:708;s:1:\"b\";s:20:\"update_certification\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:684;a:4:{s:1:\"a\";i:709;s:1:\"b\";s:21:\"restore_certification\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:685;a:4:{s:1:\"a\";i:710;s:1:\"b\";s:25:\"restore_any_certification\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:686;a:4:{s:1:\"a\";i:711;s:1:\"b\";s:23:\"replicate_certification\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:687;a:4:{s:1:\"a\";i:712;s:1:\"b\";s:21:\"reorder_certification\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:688;a:4:{s:1:\"a\";i:713;s:1:\"b\";s:20:\"delete_certification\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:689;a:4:{s:1:\"a\";i:714;s:1:\"b\";s:24:\"delete_any_certification\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:690;a:4:{s:1:\"a\";i:715;s:1:\"b\";s:26:\"force_delete_certification\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:691;a:4:{s:1:\"a\";i:716;s:1:\"b\";s:30:\"force_delete_any_certification\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:692;a:4:{s:1:\"a\";i:717;s:1:\"b\";s:15:\"view_assessment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:693;a:4:{s:1:\"a\";i:718;s:1:\"b\";s:19:\"view_any_assessment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:694;a:4:{s:1:\"a\";i:719;s:1:\"b\";s:17:\"create_assessment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:695;a:4:{s:1:\"a\";i:720;s:1:\"b\";s:17:\"update_assessment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:696;a:4:{s:1:\"a\";i:721;s:1:\"b\";s:18:\"restore_assessment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:697;a:4:{s:1:\"a\";i:722;s:1:\"b\";s:22:\"restore_any_assessment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:698;a:4:{s:1:\"a\";i:723;s:1:\"b\";s:20:\"replicate_assessment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:699;a:4:{s:1:\"a\";i:724;s:1:\"b\";s:18:\"reorder_assessment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:700;a:4:{s:1:\"a\";i:725;s:1:\"b\";s:17:\"delete_assessment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:701;a:4:{s:1:\"a\";i:726;s:1:\"b\";s:21:\"delete_any_assessment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:702;a:4:{s:1:\"a\";i:727;s:1:\"b\";s:23:\"force_delete_assessment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:703;a:4:{s:1:\"a\";i:728;s:1:\"b\";s:27:\"force_delete_any_assessment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:704;a:4:{s:1:\"a\";i:729;s:1:\"b\";s:22:\"view_assessment_result\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:705;a:4:{s:1:\"a\";i:730;s:1:\"b\";s:26:\"view_any_assessment_result\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:706;a:4:{s:1:\"a\";i:731;s:1:\"b\";s:24:\"create_assessment_result\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:707;a:4:{s:1:\"a\";i:732;s:1:\"b\";s:24:\"update_assessment_result\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:708;a:4:{s:1:\"a\";i:733;s:1:\"b\";s:25:\"restore_assessment_result\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:709;a:4:{s:1:\"a\";i:734;s:1:\"b\";s:29:\"restore_any_assessment_result\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:710;a:4:{s:1:\"a\";i:735;s:1:\"b\";s:27:\"replicate_assessment_result\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:711;a:4:{s:1:\"a\";i:736;s:1:\"b\";s:25:\"reorder_assessment_result\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:712;a:4:{s:1:\"a\";i:737;s:1:\"b\";s:24:\"delete_assessment_result\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:713;a:4:{s:1:\"a\";i:738;s:1:\"b\";s:28:\"delete_any_assessment_result\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:714;a:4:{s:1:\"a\";i:739;s:1:\"b\";s:30:\"force_delete_assessment_result\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:715;a:4:{s:1:\"a\";i:740;s:1:\"b\";s:34:\"force_delete_any_assessment_result\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:716;a:4:{s:1:\"a\";i:741;s:1:\"b\";s:19:\"view_employee_asset\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:717;a:4:{s:1:\"a\";i:742;s:1:\"b\";s:23:\"view_any_employee_asset\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:718;a:4:{s:1:\"a\";i:743;s:1:\"b\";s:21:\"create_employee_asset\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:719;a:4:{s:1:\"a\";i:744;s:1:\"b\";s:21:\"update_employee_asset\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:720;a:4:{s:1:\"a\";i:745;s:1:\"b\";s:22:\"restore_employee_asset\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:721;a:4:{s:1:\"a\";i:746;s:1:\"b\";s:26:\"restore_any_employee_asset\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:722;a:4:{s:1:\"a\";i:747;s:1:\"b\";s:24:\"replicate_employee_asset\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:723;a:4:{s:1:\"a\";i:748;s:1:\"b\";s:22:\"reorder_employee_asset\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:724;a:4:{s:1:\"a\";i:749;s:1:\"b\";s:21:\"delete_employee_asset\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:725;a:4:{s:1:\"a\";i:750;s:1:\"b\";s:25:\"delete_any_employee_asset\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:726;a:4:{s:1:\"a\";i:751;s:1:\"b\";s:27:\"force_delete_employee_asset\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:727;a:4:{s:1:\"a\";i:752;s:1:\"b\";s:31:\"force_delete_any_employee_asset\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:728;a:4:{s:1:\"a\";i:753;s:1:\"b\";s:19:\"view_travel_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:729;a:4:{s:1:\"a\";i:754;s:1:\"b\";s:23:\"view_any_travel_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:730;a:4:{s:1:\"a\";i:755;s:1:\"b\";s:21:\"create_travel_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:731;a:4:{s:1:\"a\";i:756;s:1:\"b\";s:21:\"update_travel_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:732;a:4:{s:1:\"a\";i:757;s:1:\"b\";s:22:\"restore_travel_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:733;a:4:{s:1:\"a\";i:758;s:1:\"b\";s:26:\"restore_any_travel_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:734;a:4:{s:1:\"a\";i:759;s:1:\"b\";s:24:\"replicate_travel_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:735;a:4:{s:1:\"a\";i:760;s:1:\"b\";s:22:\"reorder_travel_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:736;a:4:{s:1:\"a\";i:761;s:1:\"b\";s:21:\"delete_travel_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:737;a:4:{s:1:\"a\";i:762;s:1:\"b\";s:25:\"delete_any_travel_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:738;a:4:{s:1:\"a\";i:763;s:1:\"b\";s:27:\"force_delete_travel_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:739;a:4:{s:1:\"a\";i:764;s:1:\"b\";s:31:\"force_delete_any_travel_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:740;a:4:{s:1:\"a\";i:765;s:1:\"b\";s:19:\"view_travel_booking\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:741;a:4:{s:1:\"a\";i:766;s:1:\"b\";s:23:\"view_any_travel_booking\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:742;a:4:{s:1:\"a\";i:767;s:1:\"b\";s:21:\"create_travel_booking\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:743;a:4:{s:1:\"a\";i:768;s:1:\"b\";s:21:\"update_travel_booking\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:744;a:4:{s:1:\"a\";i:769;s:1:\"b\";s:22:\"restore_travel_booking\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:745;a:4:{s:1:\"a\";i:770;s:1:\"b\";s:26:\"restore_any_travel_booking\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:746;a:4:{s:1:\"a\";i:771;s:1:\"b\";s:24:\"replicate_travel_booking\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:747;a:4:{s:1:\"a\";i:772;s:1:\"b\";s:22:\"reorder_travel_booking\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:748;a:4:{s:1:\"a\";i:773;s:1:\"b\";s:21:\"delete_travel_booking\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:749;a:4:{s:1:\"a\";i:774;s:1:\"b\";s:25:\"delete_any_travel_booking\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:750;a:4:{s:1:\"a\";i:775;s:1:\"b\";s:27:\"force_delete_travel_booking\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:751;a:4:{s:1:\"a\";i:776;s:1:\"b\";s:31:\"force_delete_any_travel_booking\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:752;a:4:{s:1:\"a\";i:777;s:1:\"b\";s:19:\"view_travel_expense\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:753;a:4:{s:1:\"a\";i:778;s:1:\"b\";s:23:\"view_any_travel_expense\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:754;a:4:{s:1:\"a\";i:779;s:1:\"b\";s:21:\"create_travel_expense\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:755;a:4:{s:1:\"a\";i:780;s:1:\"b\";s:21:\"update_travel_expense\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:756;a:4:{s:1:\"a\";i:781;s:1:\"b\";s:22:\"restore_travel_expense\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:757;a:4:{s:1:\"a\";i:782;s:1:\"b\";s:26:\"restore_any_travel_expense\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:758;a:4:{s:1:\"a\";i:783;s:1:\"b\";s:24:\"replicate_travel_expense\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:759;a:4:{s:1:\"a\";i:784;s:1:\"b\";s:22:\"reorder_travel_expense\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:760;a:4:{s:1:\"a\";i:785;s:1:\"b\";s:21:\"delete_travel_expense\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:761;a:4:{s:1:\"a\";i:786;s:1:\"b\";s:25:\"delete_any_travel_expense\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:762;a:4:{s:1:\"a\";i:787;s:1:\"b\";s:27:\"force_delete_travel_expense\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:763;a:4:{s:1:\"a\";i:788;s:1:\"b\";s:31:\"force_delete_any_travel_expense\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:764;a:4:{s:1:\"a\";i:789;s:1:\"b\";s:22:\"view_travel_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:765;a:4:{s:1:\"a\";i:790;s:1:\"b\";s:26:\"view_any_travel_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:766;a:4:{s:1:\"a\";i:791;s:1:\"b\";s:24:\"create_travel_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:767;a:4:{s:1:\"a\";i:792;s:1:\"b\";s:24:\"update_travel_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:768;a:4:{s:1:\"a\";i:793;s:1:\"b\";s:25:\"restore_travel_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:769;a:4:{s:1:\"a\";i:794;s:1:\"b\";s:29:\"restore_any_travel_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:770;a:4:{s:1:\"a\";i:795;s:1:\"b\";s:27:\"replicate_travel_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:771;a:4:{s:1:\"a\";i:796;s:1:\"b\";s:25:\"reorder_travel_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:772;a:4:{s:1:\"a\";i:797;s:1:\"b\";s:24:\"delete_travel_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:773;a:4:{s:1:\"a\";i:798;s:1:\"b\";s:28:\"delete_any_travel_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:774;a:4:{s:1:\"a\";i:799;s:1:\"b\";s:30:\"force_delete_travel_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:775;a:4:{s:1:\"a\";i:800;s:1:\"b\";s:34:\"force_delete_any_travel_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:776;a:4:{s:1:\"a\";i:801;s:1:\"b\";s:21:\"view_expense_category\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:777;a:4:{s:1:\"a\";i:802;s:1:\"b\";s:25:\"view_any_expense_category\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:778;a:4:{s:1:\"a\";i:803;s:1:\"b\";s:23:\"create_expense_category\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:779;a:4:{s:1:\"a\";i:804;s:1:\"b\";s:23:\"update_expense_category\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:780;a:4:{s:1:\"a\";i:805;s:1:\"b\";s:24:\"restore_expense_category\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:781;a:4:{s:1:\"a\";i:806;s:1:\"b\";s:28:\"restore_any_expense_category\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:782;a:4:{s:1:\"a\";i:807;s:1:\"b\";s:26:\"replicate_expense_category\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:783;a:4:{s:1:\"a\";i:808;s:1:\"b\";s:24:\"reorder_expense_category\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:784;a:4:{s:1:\"a\";i:809;s:1:\"b\";s:23:\"delete_expense_category\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:785;a:4:{s:1:\"a\";i:810;s:1:\"b\";s:27:\"delete_any_expense_category\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:786;a:4:{s:1:\"a\";i:811;s:1:\"b\";s:29:\"force_delete_expense_category\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:787;a:4:{s:1:\"a\";i:812;s:1:\"b\";s:33:\"force_delete_any_expense_category\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:788;a:4:{s:1:\"a\";i:813;s:1:\"b\";s:18:\"view_expense_claim\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:789;a:4:{s:1:\"a\";i:814;s:1:\"b\";s:22:\"view_any_expense_claim\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:790;a:4:{s:1:\"a\";i:815;s:1:\"b\";s:20:\"create_expense_claim\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:791;a:4:{s:1:\"a\";i:816;s:1:\"b\";s:20:\"update_expense_claim\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:792;a:4:{s:1:\"a\";i:817;s:1:\"b\";s:21:\"restore_expense_claim\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:793;a:4:{s:1:\"a\";i:818;s:1:\"b\";s:25:\"restore_any_expense_claim\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:794;a:4:{s:1:\"a\";i:819;s:1:\"b\";s:23:\"replicate_expense_claim\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:795;a:4:{s:1:\"a\";i:820;s:1:\"b\";s:21:\"reorder_expense_claim\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:796;a:4:{s:1:\"a\";i:821;s:1:\"b\";s:20:\"delete_expense_claim\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:797;a:4:{s:1:\"a\";i:822;s:1:\"b\";s:24:\"delete_any_expense_claim\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:798;a:4:{s:1:\"a\";i:823;s:1:\"b\";s:26:\"force_delete_expense_claim\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:799;a:4:{s:1:\"a\";i:824;s:1:\"b\";s:30:\"force_delete_any_expense_claim\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:800;a:4:{s:1:\"a\";i:825;s:1:\"b\";s:22:\"view_employee_document\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:801;a:4:{s:1:\"a\";i:826;s:1:\"b\";s:26:\"view_any_employee_document\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:802;a:4:{s:1:\"a\";i:827;s:1:\"b\";s:24:\"create_employee_document\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:803;a:4:{s:1:\"a\";i:828;s:1:\"b\";s:24:\"update_employee_document\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:804;a:4:{s:1:\"a\";i:829;s:1:\"b\";s:25:\"restore_employee_document\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:805;a:4:{s:1:\"a\";i:830;s:1:\"b\";s:29:\"restore_any_employee_document\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:806;a:4:{s:1:\"a\";i:831;s:1:\"b\";s:27:\"replicate_employee_document\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:807;a:4:{s:1:\"a\";i:832;s:1:\"b\";s:25:\"reorder_employee_document\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:808;a:4:{s:1:\"a\";i:833;s:1:\"b\";s:24:\"delete_employee_document\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:809;a:4:{s:1:\"a\";i:834;s:1:\"b\";s:28:\"delete_any_employee_document\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:810;a:4:{s:1:\"a\";i:835;s:1:\"b\";s:30:\"force_delete_employee_document\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:811;a:4:{s:1:\"a\";i:836;s:1:\"b\";s:34:\"force_delete_any_employee_document\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:812;a:4:{s:1:\"a\";i:837;s:1:\"b\";s:16:\"view_resignation\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:813;a:4:{s:1:\"a\";i:838;s:1:\"b\";s:20:\"view_any_resignation\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:814;a:4:{s:1:\"a\";i:839;s:1:\"b\";s:18:\"create_resignation\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:815;a:4:{s:1:\"a\";i:840;s:1:\"b\";s:18:\"update_resignation\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:816;a:4:{s:1:\"a\";i:841;s:1:\"b\";s:19:\"restore_resignation\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:817;a:4:{s:1:\"a\";i:842;s:1:\"b\";s:23:\"restore_any_resignation\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:818;a:4:{s:1:\"a\";i:843;s:1:\"b\";s:21:\"replicate_resignation\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:819;a:4:{s:1:\"a\";i:844;s:1:\"b\";s:19:\"reorder_resignation\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:820;a:4:{s:1:\"a\";i:845;s:1:\"b\";s:18:\"delete_resignation\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:821;a:4:{s:1:\"a\";i:846;s:1:\"b\";s:22:\"delete_any_resignation\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:822;a:4:{s:1:\"a\";i:847;s:1:\"b\";s:24:\"force_delete_resignation\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:823;a:4:{s:1:\"a\";i:848;s:1:\"b\";s:28:\"force_delete_any_resignation\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:824;a:4:{s:1:\"a\";i:849;s:1:\"b\";s:25:\"view_separation_clearance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:825;a:4:{s:1:\"a\";i:850;s:1:\"b\";s:29:\"view_any_separation_clearance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:826;a:4:{s:1:\"a\";i:851;s:1:\"b\";s:27:\"create_separation_clearance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:827;a:4:{s:1:\"a\";i:852;s:1:\"b\";s:27:\"update_separation_clearance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:828;a:4:{s:1:\"a\";i:853;s:1:\"b\";s:28:\"restore_separation_clearance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:829;a:4:{s:1:\"a\";i:854;s:1:\"b\";s:32:\"restore_any_separation_clearance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:830;a:4:{s:1:\"a\";i:855;s:1:\"b\";s:30:\"replicate_separation_clearance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:831;a:4:{s:1:\"a\";i:856;s:1:\"b\";s:28:\"reorder_separation_clearance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:832;a:4:{s:1:\"a\";i:857;s:1:\"b\";s:27:\"delete_separation_clearance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:833;a:4:{s:1:\"a\";i:858;s:1:\"b\";s:31:\"delete_any_separation_clearance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:834;a:4:{s:1:\"a\";i:859;s:1:\"b\";s:33:\"force_delete_separation_clearance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:835;a:4:{s:1:\"a\";i:860;s:1:\"b\";s:37:\"force_delete_any_separation_clearance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:836;a:4:{s:1:\"a\";i:861;s:1:\"b\";s:19:\"view_exit_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:837;a:4:{s:1:\"a\";i:862;s:1:\"b\";s:23:\"view_any_exit_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:838;a:4:{s:1:\"a\";i:863;s:1:\"b\";s:21:\"create_exit_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:839;a:4:{s:1:\"a\";i:864;s:1:\"b\";s:21:\"update_exit_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:840;a:4:{s:1:\"a\";i:865;s:1:\"b\";s:22:\"restore_exit_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:841;a:4:{s:1:\"a\";i:866;s:1:\"b\";s:26:\"restore_any_exit_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:842;a:4:{s:1:\"a\";i:867;s:1:\"b\";s:24:\"replicate_exit_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:843;a:4:{s:1:\"a\";i:868;s:1:\"b\";s:22:\"reorder_exit_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:844;a:4:{s:1:\"a\";i:869;s:1:\"b\";s:21:\"delete_exit_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:845;a:4:{s:1:\"a\";i:870;s:1:\"b\";s:25:\"delete_any_exit_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:846;a:4:{s:1:\"a\";i:871;s:1:\"b\";s:27:\"force_delete_exit_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:847;a:4:{s:1:\"a\";i:872;s:1:\"b\";s:31:\"force_delete_any_exit_interview\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:848;a:4:{s:1:\"a\";i:873;s:1:\"b\";s:21:\"view_final_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:849;a:4:{s:1:\"a\";i:874;s:1:\"b\";s:25:\"view_any_final_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:850;a:4:{s:1:\"a\";i:875;s:1:\"b\";s:23:\"create_final_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:851;a:4:{s:1:\"a\";i:876;s:1:\"b\";s:23:\"update_final_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:852;a:4:{s:1:\"a\";i:877;s:1:\"b\";s:24:\"restore_final_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:853;a:4:{s:1:\"a\";i:878;s:1:\"b\";s:28:\"restore_any_final_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:854;a:4:{s:1:\"a\";i:879;s:1:\"b\";s:26:\"replicate_final_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:855;a:4:{s:1:\"a\";i:880;s:1:\"b\";s:24:\"reorder_final_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:856;a:4:{s:1:\"a\";i:881;s:1:\"b\";s:23:\"delete_final_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:857;a:4:{s:1:\"a\";i:882;s:1:\"b\";s:27:\"delete_any_final_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:858;a:4:{s:1:\"a\";i:883;s:1:\"b\";s:29:\"force_delete_final_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:859;a:4:{s:1:\"a\";i:884;s:1:\"b\";s:33:\"force_delete_any_final_settlement\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:860;a:4:{s:1:\"a\";i:885;s:1:\"b\";s:17:\"view_loan_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:861;a:4:{s:1:\"a\";i:886;s:1:\"b\";s:21:\"view_any_loan_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:862;a:4:{s:1:\"a\";i:887;s:1:\"b\";s:19:\"create_loan_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:863;a:4:{s:1:\"a\";i:888;s:1:\"b\";s:19:\"update_loan_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:864;a:4:{s:1:\"a\";i:889;s:1:\"b\";s:20:\"restore_loan_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:865;a:4:{s:1:\"a\";i:890;s:1:\"b\";s:24:\"restore_any_loan_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:866;a:4:{s:1:\"a\";i:891;s:1:\"b\";s:22:\"replicate_loan_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:867;a:4:{s:1:\"a\";i:892;s:1:\"b\";s:20:\"reorder_loan_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:868;a:4:{s:1:\"a\";i:893;s:1:\"b\";s:19:\"delete_loan_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:869;a:4:{s:1:\"a\";i:894;s:1:\"b\";s:23:\"delete_any_loan_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:870;a:4:{s:1:\"a\";i:895;s:1:\"b\";s:25:\"force_delete_loan_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:871;a:4:{s:1:\"a\";i:896;s:1:\"b\";s:29:\"force_delete_any_loan_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:872;a:4:{s:1:\"a\";i:897;s:1:\"b\";s:22:\"view_promotion_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:873;a:4:{s:1:\"a\";i:898;s:1:\"b\";s:26:\"view_any_promotion_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:874;a:4:{s:1:\"a\";i:899;s:1:\"b\";s:24:\"create_promotion_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:875;a:4:{s:1:\"a\";i:900;s:1:\"b\";s:24:\"update_promotion_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:876;a:4:{s:1:\"a\";i:901;s:1:\"b\";s:25:\"restore_promotion_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:877;a:4:{s:1:\"a\";i:902;s:1:\"b\";s:29:\"restore_any_promotion_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:878;a:4:{s:1:\"a\";i:903;s:1:\"b\";s:27:\"replicate_promotion_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:879;a:4:{s:1:\"a\";i:904;s:1:\"b\";s:25:\"reorder_promotion_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:880;a:4:{s:1:\"a\";i:905;s:1:\"b\";s:24:\"delete_promotion_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:881;a:4:{s:1:\"a\";i:906;s:1:\"b\";s:28:\"delete_any_promotion_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:882;a:4:{s:1:\"a\";i:907;s:1:\"b\";s:30:\"force_delete_promotion_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:883;a:4:{s:1:\"a\";i:908;s:1:\"b\";s:34:\"force_delete_any_promotion_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:884;a:4:{s:1:\"a\";i:909;s:1:\"b\";s:21:\"view_transfer_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:885;a:4:{s:1:\"a\";i:910;s:1:\"b\";s:25:\"view_any_transfer_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:886;a:4:{s:1:\"a\";i:911;s:1:\"b\";s:23:\"create_transfer_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:887;a:4:{s:1:\"a\";i:912;s:1:\"b\";s:23:\"update_transfer_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:888;a:4:{s:1:\"a\";i:913;s:1:\"b\";s:24:\"restore_transfer_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:889;a:4:{s:1:\"a\";i:914;s:1:\"b\";s:28:\"restore_any_transfer_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:890;a:4:{s:1:\"a\";i:915;s:1:\"b\";s:26:\"replicate_transfer_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:891;a:4:{s:1:\"a\";i:916;s:1:\"b\";s:24:\"reorder_transfer_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:892;a:4:{s:1:\"a\";i:917;s:1:\"b\";s:23:\"delete_transfer_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:893;a:4:{s:1:\"a\";i:918;s:1:\"b\";s:27:\"delete_any_transfer_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:894;a:4:{s:1:\"a\";i:919;s:1:\"b\";s:29:\"force_delete_transfer_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:895;a:4:{s:1:\"a\";i:920;s:1:\"b\";s:33:\"force_delete_any_transfer_request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:896;a:4:{s:1:\"a\";i:921;s:1:\"b\";s:14:\"view_hr_policy\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:897;a:4:{s:1:\"a\";i:922;s:1:\"b\";s:18:\"view_any_hr_policy\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:898;a:4:{s:1:\"a\";i:923;s:1:\"b\";s:16:\"create_hr_policy\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:899;a:4:{s:1:\"a\";i:924;s:1:\"b\";s:16:\"update_hr_policy\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:900;a:4:{s:1:\"a\";i:925;s:1:\"b\";s:17:\"restore_hr_policy\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:901;a:4:{s:1:\"a\";i:926;s:1:\"b\";s:21:\"restore_any_hr_policy\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:902;a:4:{s:1:\"a\";i:927;s:1:\"b\";s:19:\"replicate_hr_policy\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:903;a:4:{s:1:\"a\";i:928;s:1:\"b\";s:17:\"reorder_hr_policy\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:904;a:4:{s:1:\"a\";i:929;s:1:\"b\";s:16:\"delete_hr_policy\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:905;a:4:{s:1:\"a\";i:930;s:1:\"b\";s:20:\"delete_any_hr_policy\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:906;a:4:{s:1:\"a\";i:931;s:1:\"b\";s:22:\"force_delete_hr_policy\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:907;a:4:{s:1:\"a\";i:932;s:1:\"b\";s:26:\"force_delete_any_hr_policy\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:908;a:4:{s:1:\"a\";i:933;s:1:\"b\";s:28:\"view_attendance_rule_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:909;a:4:{s:1:\"a\";i:934;s:1:\"b\";s:32:\"view_any_attendance_rule_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:910;a:4:{s:1:\"a\";i:935;s:1:\"b\";s:30:\"create_attendance_rule_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:911;a:4:{s:1:\"a\";i:936;s:1:\"b\";s:30:\"update_attendance_rule_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:912;a:4:{s:1:\"a\";i:937;s:1:\"b\";s:31:\"restore_attendance_rule_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:913;a:4:{s:1:\"a\";i:938;s:1:\"b\";s:35:\"restore_any_attendance_rule_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:914;a:4:{s:1:\"a\";i:939;s:1:\"b\";s:33:\"replicate_attendance_rule_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:915;a:4:{s:1:\"a\";i:940;s:1:\"b\";s:31:\"reorder_attendance_rule_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:916;a:4:{s:1:\"a\";i:941;s:1:\"b\";s:30:\"delete_attendance_rule_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:917;a:4:{s:1:\"a\";i:942;s:1:\"b\";s:34:\"delete_any_attendance_rule_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:918;a:4:{s:1:\"a\";i:943;s:1:\"b\";s:36:\"force_delete_attendance_rule_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:919;a:4:{s:1:\"a\";i:944;s:1:\"b\";s:40:\"force_delete_any_attendance_rule_setting\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:920;a:4:{s:1:\"a\";i:945;s:1:\"b\";s:13:\"view_tax_slab\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:921;a:4:{s:1:\"a\";i:946;s:1:\"b\";s:17:\"view_any_tax_slab\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:922;a:4:{s:1:\"a\";i:947;s:1:\"b\";s:15:\"create_tax_slab\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:923;a:4:{s:1:\"a\";i:948;s:1:\"b\";s:15:\"update_tax_slab\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:924;a:4:{s:1:\"a\";i:949;s:1:\"b\";s:16:\"restore_tax_slab\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:925;a:4:{s:1:\"a\";i:950;s:1:\"b\";s:20:\"restore_any_tax_slab\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:926;a:4:{s:1:\"a\";i:951;s:1:\"b\";s:18:\"replicate_tax_slab\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:927;a:4:{s:1:\"a\";i:952;s:1:\"b\";s:16:\"reorder_tax_slab\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:928;a:4:{s:1:\"a\";i:953;s:1:\"b\";s:15:\"delete_tax_slab\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:929;a:4:{s:1:\"a\";i:954;s:1:\"b\";s:19:\"delete_any_tax_slab\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:930;a:4:{s:1:\"a\";i:955;s:1:\"b\";s:21:\"force_delete_tax_slab\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:931;a:4:{s:1:\"a\";i:956;s:1:\"b\";s:25:\"force_delete_any_tax_slab\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:932;a:4:{s:1:\"a\";i:957;s:1:\"b\";s:22:\"view_document_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:933;a:4:{s:1:\"a\";i:958;s:1:\"b\";s:26:\"view_any_document_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:934;a:4:{s:1:\"a\";i:959;s:1:\"b\";s:24:\"create_document_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:935;a:4:{s:1:\"a\";i:960;s:1:\"b\";s:24:\"update_document_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:936;a:4:{s:1:\"a\";i:961;s:1:\"b\";s:25:\"restore_document_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:937;a:4:{s:1:\"a\";i:962;s:1:\"b\";s:29:\"restore_any_document_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:938;a:4:{s:1:\"a\";i:963;s:1:\"b\";s:27:\"replicate_document_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:939;a:4:{s:1:\"a\";i:964;s:1:\"b\";s:25:\"reorder_document_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:940;a:4:{s:1:\"a\";i:965;s:1:\"b\";s:24:\"delete_document_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:941;a:4:{s:1:\"a\";i:966;s:1:\"b\";s:28:\"delete_any_document_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:942;a:4:{s:1:\"a\";i:967;s:1:\"b\";s:30:\"force_delete_document_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}i:943;a:4:{s:1:\"a\";i:968;s:1:\"b\";s:34:\"force_delete_any_document_template\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:8;i:1;i:11;i:2;i:14;i:3;i:15;i:4;i:17;i:5;i:20;i:6;i:23;}}}s:5:\"roles\";a:9:{i:0;a:5:{s:1:\"a\";i:1;s:1:\"j\";i:2;s:1:\"b\";s:11:\"super_admin\";s:1:\"c\";s:3:\"web\";s:1:\"k\";s:3:\"all\";}i:1;a:5:{s:1:\"a\";i:11;s:1:\"j\";i:3;s:1:\"b\";s:11:\"super_admin\";s:1:\"c\";s:3:\"web\";s:1:\"k\";s:3:\"all\";}i:2;a:5:{s:1:\"a\";i:14;s:1:\"j\";i:6;s:1:\"b\";s:11:\"super_admin\";s:1:\"c\";s:3:\"web\";s:1:\"k\";s:3:\"all\";}i:3;a:5:{s:1:\"a\";i:15;s:1:\"j\";i:7;s:1:\"b\";s:11:\"super_admin\";s:1:\"c\";s:3:\"web\";s:1:\"k\";s:3:\"all\";}i:4;a:5:{s:1:\"a\";i:8;s:1:\"j\";i:2;s:1:\"b\";s:15:\"demo_hr_manager\";s:1:\"c\";s:3:\"web\";s:1:\"k\";s:3:\"all\";}i:5;a:5:{s:1:\"a\";i:17;s:1:\"j\";i:3;s:1:\"b\";s:10:\"hr_manager\";s:1:\"c\";s:3:\"web\";s:1:\"k\";s:3:\"all\";}i:6;a:5:{s:1:\"a\";i:20;s:1:\"j\";i:6;s:1:\"b\";s:10:\"hr_manager\";s:1:\"c\";s:3:\"web\";s:1:\"k\";s:3:\"all\";}i:7;a:5:{s:1:\"a\";i:23;s:1:\"j\";i:7;s:1:\"b\";s:10:\"hr_manager\";s:1:\"c\";s:3:\"web\";s:1:\"k\";s:3:\"all\";}i:8;a:5:{s:1:\"a\";i:10;s:1:\"j\";i:2;s:1:\"b\";s:13:\"demo_employee\";s:1:\"c\";s:3:\"web\";s:1:\"k\";s:3:\"own\";}}}',1792391824);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `candidates`
--

DROP TABLE IF EXISTS `candidates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `job_position_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resume_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stage` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'applied',
  `applied_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `candidates_company_id_foreign` (`company_id`),
  KEY `candidates_job_position_id_foreign` (`job_position_id`),
  KEY `candidates_stage_index` (`stage`),
  KEY `candidates_branch_id_foreign` (`branch_id`),
  CONSTRAINT `candidates_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `candidates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `candidates_job_position_id_foreign` FOREIGN KEY (`job_position_id`) REFERENCES `job_positions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `candidates`
--

LOCK TABLES `candidates` WRITE;
/*!40000 ALTER TABLE `candidates` DISABLE KEYS */;
/*!40000 ALTER TABLE `candidates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certifications`
--

DROP TABLE IF EXISTS `certifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `certifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issuing_body` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credential_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `certifications_employee_id_foreign` (`employee_id`),
  KEY `certifications_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `certifications_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `certifications_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certifications`
--

LOCK TABLES `certifications` WRITE;
/*!40000 ALTER TABLE `certifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `certifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comp_offs`
--

DROP TABLE IF EXISTS `comp_offs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comp_offs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `worked_date` date NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comp_offs_employee_id_foreign` (`employee_id`),
  KEY `comp_offs_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `comp_offs_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `comp_offs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comp_offs_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comp_offs_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comp_offs`
--

LOCK TABLES `comp_offs` WRITE;
/*!40000 ALTER TABLE `comp_offs` DISABLE KEYS */;
INSERT INTO `comp_offs` VALUES (6,2,17,'2026-09-28','Weekend Production  Release','pending',NULL,NULL,NULL,'2026-09-28 00:46:56','2026-09-28 00:46:56');
/*!40000 ALTER TABLE `comp_offs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `legal_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `timezone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UTC',
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `footer_text` text COLLATE utf8mb4_unicode_ci,
  `login_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `app_access` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `max_users` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `companies_name_index` (`name`),
  KEY `companies_is_active_index` (`is_active`),
  CONSTRAINT `companies_chk_1` CHECK (json_valid(`app_access`))
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (2,'One Suite',NULL,NULL,NULL,NULL,NULL,NULL,'USD','UTC',NULL,'One Suite','One Suite','One Suite ERP',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-08-02 06:29:24','2026-09-25 07:15:22'),(3,'Acme Corp',NULL,NULL,NULL,'contact@acme.test',NULL,NULL,'USD','UTC',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'[\"settings\",\"hrm\"]',NULL,'2026-09-19 10:21:56','2026-09-19 10:26:33'),(6,'Client 3 Corp',NULL,NULL,NULL,'hello@client3.test',NULL,NULL,'USD','UTC',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'[\"settings\",\"hrm\"]',NULL,'2026-09-21 21:55:49','2026-09-21 21:55:49'),(7,'Client 4 Corp',NULL,NULL,NULL,NULL,NULL,NULL,'USD','UTC',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2026-09-21 21:57:40','2026-09-25 06:48:29');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_mail_accounts`
--

DROP TABLE IF EXISTS `company_mail_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_mail_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'google',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_token` text COLLATE utf8mb4_unicode_ci,
  `refresh_token` text COLLATE utf8mb4_unicode_ci,
  `token_expires_at` timestamp NULL DEFAULT NULL,
  `settings` text COLLATE utf8mb4_unicode_ci,
  `connected_by` bigint unsigned DEFAULT NULL,
  `needs_reconnect` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_mail_accounts_company_id_unique` (`company_id`),
  KEY `company_mail_accounts_connected_by_foreign` (`connected_by`),
  CONSTRAINT `company_mail_accounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_mail_accounts_connected_by_foreign` FOREIGN KEY (`connected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_mail_accounts`
--

LOCK TABLES `company_mail_accounts` WRITE;
/*!40000 ALTER TABLE `company_mail_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `company_mail_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cost_centers`
--

DROP TABLE IF EXISTS `cost_centers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cost_centers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `budget` decimal(14,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cost_centers_company_id_code_unique` (`company_id`,`code`),
  KEY `cost_centers_department_id_foreign` (`department_id`),
  KEY `cost_centers_name_index` (`name`),
  KEY `cost_centers_is_active_index` (`is_active`),
  CONSTRAINT `cost_centers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cost_centers_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cost_centers`
--

LOCK TABLES `cost_centers` WRITE;
/*!40000 ALTER TABLE `cost_centers` DISABLE KEYS */;
/*!40000 ALTER TABLE `cost_centers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_hours` decimal(5,2) DEFAULT NULL,
  `delivery_mode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'online',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `courses_company_id_foreign` (`company_id`),
  KEY `courses_branch_id_foreign` (`branch_id`),
  CONSTRAINT `courses_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `courses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_account_shares`
--

DROP TABLE IF EXISTS `crm_account_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_account_shares` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned DEFAULT NULL,
  `team_id` bigint unsigned DEFAULT NULL,
  `access_level` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'view',
  `shared_by_employee_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_account_shares_company_id_foreign` (`company_id`),
  KEY `crm_account_shares_account_id_foreign` (`account_id`),
  KEY `crm_account_shares_employee_id_foreign` (`employee_id`),
  KEY `crm_account_shares_team_id_foreign` (`team_id`),
  KEY `crm_account_shares_shared_by_employee_id_foreign` (`shared_by_employee_id`),
  CONSTRAINT `crm_account_shares_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `crm_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_account_shares_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_account_shares_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_account_shares_shared_by_employee_id_foreign` FOREIGN KEY (`shared_by_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_account_shares_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `crm_teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_account_shares`
--

LOCK TABLES `crm_account_shares` WRITE;
/*!40000 ALTER TABLE `crm_account_shares` DISABLE KEYS */;
INSERT INTO `crm_account_shares` VALUES (1,2,1,4,NULL,'view',4,'2026-10-18 01:27:02','2026-10-18 01:27:02');
/*!40000 ALTER TABLE `crm_account_shares` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_accounts`
--

DROP TABLE IF EXISTS `crm_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `industry` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `billing_address` text COLLATE utf8mb4_unicode_ci,
  `shipping_address` text COLLATE utf8mb4_unicode_ci,
  `parent_account_id` bigint unsigned DEFAULT NULL,
  `owner_employee_id` bigint unsigned DEFAULT NULL,
  `customer_segment_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_accounts_parent_account_id_foreign` (`parent_account_id`),
  KEY `crm_accounts_owner_employee_id_foreign` (`owner_employee_id`),
  KEY `crm_accounts_customer_segment_id_foreign` (`customer_segment_id`),
  KEY `crm_accounts_company_id_parent_account_id_index` (`company_id`,`parent_account_id`),
  KEY `crm_accounts_branch_id_foreign` (`branch_id`),
  CONSTRAINT `crm_accounts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_accounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_accounts_customer_segment_id_foreign` FOREIGN KEY (`customer_segment_id`) REFERENCES `crm_customer_segments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_accounts_owner_employee_id_foreign` FOREIGN KEY (`owner_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_accounts_parent_account_id_foreign` FOREIGN KEY (`parent_account_id`) REFERENCES `crm_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_accounts`
--

LOCK TABLES `crm_accounts` WRITE;
/*!40000 ALTER TABLE `crm_accounts` DISABLE KEYS */;
INSERT INTO `crm_accounts` VALUES (1,2,NULL,'Acme Corp','Manufacturing',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-17 10:03:09','2026-10-17 10:03:09'),(2,2,NULL,'Acme Corp',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-17 21:57:33','2026-10-17 21:57:33');
/*!40000 ALTER TABLE `crm_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_activities`
--

DROP TABLE IF EXISTS `crm_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `related_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_id` bigint unsigned DEFAULT NULL,
  `due_at` datetime DEFAULT NULL,
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `priority` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `direction` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_employee_id` bigint unsigned DEFAULT NULL,
  `reminder_at` datetime DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT '0',
  `recurrence_frequency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recurrence_parent_id` bigint unsigned DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_activities_owner_employee_id_foreign` (`owner_employee_id`),
  KEY `crm_activities_related_type_related_id_index` (`related_type`,`related_id`),
  KEY `crm_activities_company_id_type_status_index` (`company_id`,`type`,`status`),
  KEY `crm_activities_recurrence_parent_id_foreign` (`recurrence_parent_id`),
  CONSTRAINT `crm_activities_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_activities_owner_employee_id_foreign` FOREIGN KEY (`owner_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_activities_recurrence_parent_id_foreign` FOREIGN KEY (`recurrence_parent_id`) REFERENCES `crm_activities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_activities`
--

LOCK TABLES `crm_activities` WRITE;
/*!40000 ALTER TABLE `crm_activities` DISABLE KEYS */;
INSERT INTO `crm_activities` VALUES (1,2,'task','Follow up with Acme on renewal',NULL,'App\\Models\\Crm\\Account',1,'2026-10-20 10:00:00',NULL,NULL,'pending','high',NULL,NULL,'2026-10-18 09:00:00',0,NULL,NULL,NULL,'2026-10-17 20:19:26','2026-10-17 20:19:26'),(2,2,'task','Weekly check-in call',NULL,NULL,NULL,NULL,NULL,NULL,'completed','medium',NULL,NULL,NULL,1,'weekly',NULL,'2026-10-18 03:27:51','2026-10-17 21:57:49','2026-10-17 21:57:51'),(3,2,'task','Weekly check-in call',NULL,NULL,NULL,'2026-10-25 03:27:51',NULL,NULL,'pending','medium',NULL,NULL,NULL,1,'weekly',2,NULL,'2026-10-17 21:57:51','2026-10-17 21:57:51'),(4,2,'task','Call within 24h',NULL,'App\\Models\\Crm\\Lead',1,'2026-10-19 03:46:39',NULL,NULL,'pending','medium',NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-10-17 22:16:39','2026-10-17 22:16:39'),(5,2,'task','Call within 24h',NULL,'App\\Models\\Crm\\Lead',1,'2026-10-19 03:46:59',NULL,NULL,'pending','medium',NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-10-17 22:16:59','2026-10-17 22:16:59'),(6,2,'task','Follow up on payment',NULL,'App\\Models\\Crm\\SalesOrder',2,'2026-10-20 10:00:00',NULL,NULL,'completed','high',NULL,NULL,NULL,0,NULL,NULL,'2026-10-19 01:24:15','2026-10-18 19:53:47','2026-10-18 19:54:15'),(7,2,'call','Discussed renewal',NULL,'App\\Models\\Crm\\Contract',3,'2026-10-19 09:00:00',NULL,NULL,'completed','medium','outbound',NULL,NULL,0,NULL,NULL,'2026-10-19 01:24:05','2026-10-18 19:54:05','2026-10-18 19:54:05'),(8,2,'task','Follow up on Q-2026-0001',NULL,'App\\Models\\Crm\\Quotation',1,'2026-10-23 06:31:13',NULL,NULL,'pending','medium',NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-10-22 01:01:13','2026-10-22 01:01:13');
/*!40000 ALTER TABLE `crm_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_automation_rules`
--

DROP TABLE IF EXISTS `crm_automation_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_automation_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trigger_event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condition_field` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condition_operator` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condition_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_employee_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `run_count` int unsigned NOT NULL DEFAULT '0',
  `last_run_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_automation_rules_action_employee_id_foreign` (`action_employee_id`),
  KEY `crm_automation_rules_company_id_category_index` (`company_id`,`category`),
  CONSTRAINT `crm_automation_rules_action_employee_id_foreign` FOREIGN KEY (`action_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_automation_rules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_automation_rules`
--

LOCK TABLES `crm_automation_rules` WRITE;
/*!40000 ALTER TABLE `crm_automation_rules` DISABLE KEYS */;
INSERT INTO `crm_automation_rules` VALUES (1,2,'assignment','Round robin new leads','lead',NULL,NULL,NULL,NULL,NULL,4,1,2,'2026-10-18 02:33:42','2026-10-17 21:03:39','2026-10-17 21:03:42'),(2,2,'follow_up','Follow up stalled quotes','quotation','quotation_stalled',NULL,NULL,'7',NULL,NULL,1,1,'2026-10-22 06:31:13','2026-10-22 01:01:01','2026-10-22 01:01:13');
/*!40000 ALTER TABLE `crm_automation_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_campaign_members`
--

DROP TABLE IF EXISTS `crm_campaign_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_campaign_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `campaign_id` bigint unsigned NOT NULL,
  `member_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `member_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'added',
  `added_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_campaign_members_campaign_id_member_type_member_id_unique` (`campaign_id`,`member_type`,`member_id`),
  KEY `crm_campaign_members_company_id_foreign` (`company_id`),
  CONSTRAINT `crm_campaign_members_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `crm_campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_campaign_members_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_campaign_members`
--

LOCK TABLES `crm_campaign_members` WRITE;
/*!40000 ALTER TABLE `crm_campaign_members` DISABLE KEYS */;
INSERT INTO `crm_campaign_members` VALUES (1,2,1,'App\\Models\\Crm\\Lead',1,'clicked','2026-10-17 20:42:52','2026-10-17 20:42:42','2026-10-17 20:43:46'),(2,2,1,'App\\Models\\Crm\\Lead',4,'converted','2026-10-17 20:43:13','2026-10-17 20:43:13','2026-10-17 20:43:13');
/*!40000 ALTER TABLE `crm_campaign_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_campaigns`
--

DROP TABLE IF EXISTS `crm_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_campaigns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message_body` text COLLATE utf8mb4_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `budget` decimal(14,2) NOT NULL DEFAULT '0.00',
  `actual_cost` decimal(14,2) NOT NULL DEFAULT '0.00',
  `owner_employee_id` bigint unsigned DEFAULT NULL,
  `sent_count` int unsigned NOT NULL DEFAULT '0',
  `delivered_count` int unsigned NOT NULL DEFAULT '0',
  `opened_count` int unsigned NOT NULL DEFAULT '0',
  `clicked_count` int unsigned NOT NULL DEFAULT '0',
  `responded_count` int unsigned NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_campaigns_owner_employee_id_foreign` (`owner_employee_id`),
  KEY `crm_campaigns_company_id_type_status_index` (`company_id`,`type`,`status`),
  KEY `crm_campaigns_branch_id_foreign` (`branch_id`),
  CONSTRAINT `crm_campaigns_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_campaigns_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_campaigns_owner_employee_id_foreign` FOREIGN KEY (`owner_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_campaigns`
--

LOCK TABLES `crm_campaigns` WRITE;
/*!40000 ALTER TABLE `crm_campaigns` DISABLE KEYS */;
INSERT INTO `crm_campaigns` VALUES (1,2,NULL,'October Newsletter','email','active','Big October Deals','Hello {{name}}, check our offers!',NULL,NULL,5000.00,1200.00,NULL,2,2,2,2,1,NULL,'2026-10-17 20:42:20','2026-10-17 20:43:46');
/*!40000 ALTER TABLE `crm_campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_cases`
--

DROP TABLE IF EXISTS `crm_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_cases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `case_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'case',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `priority` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `contact_id` bigint unsigned DEFAULT NULL,
  `sales_order_id` bigint unsigned DEFAULT NULL,
  `assigned_to_employee_id` bigint unsigned DEFAULT NULL,
  `sla_policy_id` bigint unsigned DEFAULT NULL,
  `response_due_at` datetime DEFAULT NULL,
  `resolution_due_at` datetime DEFAULT NULL,
  `first_responded_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `is_escalated` tinyint(1) NOT NULL DEFAULT '0',
  `escalated_to_employee_id` bigint unsigned DEFAULT NULL,
  `escalated_at` datetime DEFAULT NULL,
  `escalation_reason` text COLLATE utf8mb4_unicode_ci,
  `csat_rating` tinyint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_cases_case_number_unique` (`case_number`),
  KEY `crm_cases_account_id_foreign` (`account_id`),
  KEY `crm_cases_contact_id_foreign` (`contact_id`),
  KEY `crm_cases_assigned_to_employee_id_foreign` (`assigned_to_employee_id`),
  KEY `crm_cases_sla_policy_id_foreign` (`sla_policy_id`),
  KEY `crm_cases_escalated_to_employee_id_foreign` (`escalated_to_employee_id`),
  KEY `crm_cases_company_id_type_status_index` (`company_id`,`type`,`status`),
  KEY `crm_cases_branch_id_foreign` (`branch_id`),
  KEY `crm_cases_sales_order_id_foreign` (`sales_order_id`),
  CONSTRAINT `crm_cases_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `crm_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_cases_assigned_to_employee_id_foreign` FOREIGN KEY (`assigned_to_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_cases_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_cases_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_cases_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_cases_escalated_to_employee_id_foreign` FOREIGN KEY (`escalated_to_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_cases_sales_order_id_foreign` FOREIGN KEY (`sales_order_id`) REFERENCES `crm_sales_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_cases_sla_policy_id_foreign` FOREIGN KEY (`sla_policy_id`) REFERENCES `crm_sla_policies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_cases`
--

LOCK TABLES `crm_cases` WRITE;
/*!40000 ALTER TABLE `crm_cases` DISABLE KEYS */;
INSERT INTO `crm_cases` VALUES (1,2,NULL,'CASE-2026-00001','case','Login not working',NULL,'open','high',NULL,NULL,NULL,NULL,NULL,1,'2026-10-18 04:20:04','2026-10-18 03:20:04',NULL,NULL,1,4,'2026-10-18 02:20:52','Customer very upset',NULL,'2026-10-17 20:50:04','2026-10-17 20:50:52'),(2,2,NULL,'CASE-2026-00002','complaint','Late delivery','Package arrived 3 days late','open','high',NULL,1,NULL,4,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,'2026-10-18 20:15:37','2026-10-18 20:15:37');
/*!40000 ALTER TABLE `crm_cases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_competitors`
--

DROP TABLE IF EXISTS `crm_competitors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_competitors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `strengths` text COLLATE utf8mb4_unicode_ci,
  `weaknesses` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_competitors_company_id_name_unique` (`company_id`,`name`),
  CONSTRAINT `crm_competitors_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_competitors`
--

LOCK TABLES `crm_competitors` WRITE;
/*!40000 ALTER TABLE `crm_competitors` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_competitors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_contact_relationships`
--

DROP TABLE IF EXISTS `crm_contact_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_contact_relationships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `contact_id` bigint unsigned NOT NULL,
  `related_contact_id` bigint unsigned NOT NULL,
  `relationship_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_contact_relationships_contact_id_related_contact_id_unique` (`contact_id`,`related_contact_id`),
  KEY `crm_contact_relationships_company_id_foreign` (`company_id`),
  KEY `crm_contact_relationships_related_contact_id_foreign` (`related_contact_id`),
  CONSTRAINT `crm_contact_relationships_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_contact_relationships_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_contact_relationships_related_contact_id_foreign` FOREIGN KEY (`related_contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_contact_relationships`
--

LOCK TABLES `crm_contact_relationships` WRITE;
/*!40000 ALTER TABLE `crm_contact_relationships` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_contact_relationships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_contacts`
--

DROP TABLE IF EXISTS `crm_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_employee_id` bigint unsigned DEFAULT NULL,
  `customer_segment_id` bigint unsigned DEFAULT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_contacts_company_id_foreign` (`company_id`),
  KEY `crm_contacts_account_id_foreign` (`account_id`),
  KEY `crm_contacts_owner_employee_id_foreign` (`owner_employee_id`),
  KEY `crm_contacts_customer_segment_id_foreign` (`customer_segment_id`),
  CONSTRAINT `crm_contacts_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `crm_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_contacts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_contacts_customer_segment_id_foreign` FOREIGN KEY (`customer_segment_id`) REFERENCES `crm_customer_segments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_contacts_owner_employee_id_foreign` FOREIGN KEY (`owner_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_contacts`
--

LOCK TABLES `crm_contacts` WRITE;
/*!40000 ALTER TABLE `crm_contacts` DISABLE KEYS */;
INSERT INTO `crm_contacts` VALUES (1,2,NULL,'Test Lead Co',NULL,'test@lead.com',NULL,NULL,NULL,NULL,'2026-10-17 03:22:38','2026-10-17 03:22:38'),(2,2,1,'John Procurement','Procurement Lead',NULL,NULL,NULL,NULL,NULL,'2026-10-17 10:03:15','2026-10-17 10:03:15');
/*!40000 ALTER TABLE `crm_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_contracts`
--

DROP TABLE IF EXISTS `crm_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_contracts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `contract_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `sales_order_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `value` decimal(14,2) NOT NULL DEFAULT '0.00',
  `billing_frequency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'one_time',
  `next_billing_date` date DEFAULT NULL,
  `owner_employee_id` bigint unsigned DEFAULT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT '0',
  `renewed_from_contract_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_contracts_company_id_contract_number_unique` (`company_id`,`contract_number`),
  KEY `crm_contracts_account_id_foreign` (`account_id`),
  KEY `crm_contracts_sales_order_id_foreign` (`sales_order_id`),
  KEY `crm_contracts_owner_employee_id_foreign` (`owner_employee_id`),
  KEY `crm_contracts_renewed_from_contract_id_foreign` (`renewed_from_contract_id`),
  CONSTRAINT `crm_contracts_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `crm_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_contracts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_contracts_owner_employee_id_foreign` FOREIGN KEY (`owner_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_contracts_renewed_from_contract_id_foreign` FOREIGN KEY (`renewed_from_contract_id`) REFERENCES `crm_contracts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_contracts_sales_order_id_foreign` FOREIGN KEY (`sales_order_id`) REFERENCES `crm_sales_orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_contracts`
--

LOCK TABLES `crm_contracts` WRITE;
/*!40000 ALTER TABLE `crm_contracts` DISABLE KEYS */;
INSERT INTO `crm_contracts` VALUES (1,2,'C-2026-0001','SO-2026-0001 Contract',1,1,'active','2026-01-01','2026-11-01',15000.00,'one_time',NULL,NULL,0,NULL,'2026-10-17 11:18:02','2026-10-17 11:18:02'),(2,2,'C-2026-0002','SO-2026-0001 Contract',1,NULL,'draft','2026-11-02','2027-09-02',15000.00,'one_time',NULL,NULL,0,1,'2026-10-17 11:18:20','2026-10-17 11:18:20'),(3,2,'C-2026-0003','Annual Support Retainer',1,NULL,'active','2026-01-01','2026-12-31',5000.00,'monthly','2026-02-01',NULL,0,NULL,'2026-10-18 19:32:46','2026-10-18 19:32:58'),(4,2,'C-2026-0004','Annual Support Retainer',1,NULL,'draft','2027-01-01','2027-12-31',5000.00,'monthly','2027-01-01',NULL,0,3,'2026-10-18 19:33:09','2026-10-18 19:33:09');
/*!40000 ALTER TABLE `crm_contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_custom_fields`
--

DROP TABLE IF EXISTS `crm_custom_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_custom_fields` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `entity` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `options` json DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_custom_fields_company_id_entity_field_name_unique` (`company_id`,`entity`,`field_name`),
  CONSTRAINT `crm_custom_fields_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_custom_fields`
--

LOCK TABLES `crm_custom_fields` WRITE;
/*!40000 ALTER TABLE `crm_custom_fields` DISABLE KEYS */;
INSERT INTO `crm_custom_fields` VALUES (1,2,'lead','loyalty_tier','Loyalty Tier','select','[\"Gold\", \"Silver\", \"Bronze\"]',0,1,'2026-10-17 21:17:15','2026-10-17 21:17:15');
/*!40000 ALTER TABLE `crm_custom_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_custom_objects`
--

DROP TABLE IF EXISTS `crm_custom_objects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_custom_objects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `fields_json` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_custom_objects_company_id_foreign` (`company_id`),
  CONSTRAINT `crm_custom_objects_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_custom_objects`
--

LOCK TABLES `crm_custom_objects` WRITE;
/*!40000 ALTER TABLE `crm_custom_objects` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_custom_objects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_custom_reports`
--

DROP TABLE IF EXISTS `crm_custom_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_custom_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `report_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'table',
  `config` json DEFAULT NULL,
  `columns` json DEFAULT NULL,
  `filters` json DEFAULT NULL,
  `owner_employee_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_custom_reports_company_id_foreign` (`company_id`),
  KEY `crm_custom_reports_owner_employee_id_foreign` (`owner_employee_id`),
  CONSTRAINT `crm_custom_reports_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_custom_reports_owner_employee_id_foreign` FOREIGN KEY (`owner_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_custom_reports`
--

LOCK TABLES `crm_custom_reports` WRITE;
/*!40000 ALTER TABLE `crm_custom_reports` DISABLE KEYS */;
INSERT INTO `crm_custom_reports` VALUES (1,2,'All Leads Report','leads','table',NULL,'[\"name\", \"status\", \"created_at\"]',NULL,NULL,'2026-10-17 20:56:07','2026-10-17 20:56:07'),(2,2,'Leads by Status','leads','pivot','{\"pivot_agg\": \"count\", \"pivot_row\": \"status\", \"pivot_value\": \"score\"}',NULL,NULL,NULL,'2026-10-18 01:11:24','2026-10-18 01:11:24'),(3,2,'Pipeline Value by Stage','opportunities','chart','{\"chart_x\": \"sales_stage_id\", \"chart_y\": \"amount\", \"chart_agg\": \"sum\", \"chart_type\": \"bar\"}',NULL,NULL,NULL,'2026-10-18 01:13:51','2026-10-18 01:13:51');
/*!40000 ALTER TABLE `crm_custom_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_customer_feedback`
--

DROP TABLE IF EXISTS `crm_customer_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_customer_feedback` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `contact_id` bigint unsigned DEFAULT NULL,
  `case_id` bigint unsigned DEFAULT NULL,
  `rating` tinyint unsigned NOT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_customer_feedback_company_id_foreign` (`company_id`),
  KEY `crm_customer_feedback_account_id_foreign` (`account_id`),
  KEY `crm_customer_feedback_contact_id_foreign` (`contact_id`),
  KEY `crm_customer_feedback_case_id_foreign` (`case_id`),
  CONSTRAINT `crm_customer_feedback_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `crm_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_customer_feedback_case_id_foreign` FOREIGN KEY (`case_id`) REFERENCES `crm_cases` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_customer_feedback_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_customer_feedback_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_customer_feedback`
--

LOCK TABLES `crm_customer_feedback` WRITE;
/*!40000 ALTER TABLE `crm_customer_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_customer_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_customer_segments`
--

DROP TABLE IF EXISTS `crm_customer_segments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_customer_segments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_customer_segments_company_id_name_unique` (`company_id`,`name`),
  CONSTRAINT `crm_customer_segments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_customer_segments`
--

LOCK TABLES `crm_customer_segments` WRITE;
/*!40000 ALTER TABLE `crm_customer_segments` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_customer_segments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_deal_approval_rules`
--

DROP TABLE IF EXISTS `crm_deal_approval_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_deal_approval_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` tinyint unsigned NOT NULL DEFAULT '1',
  `min_amount` decimal(14,2) DEFAULT NULL,
  `max_amount` decimal(14,2) DEFAULT NULL,
  `approver_employee_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_deal_approval_rules_company_id_foreign` (`company_id`),
  KEY `crm_deal_approval_rules_approver_employee_id_foreign` (`approver_employee_id`),
  CONSTRAINT `crm_deal_approval_rules_approver_employee_id_foreign` FOREIGN KEY (`approver_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_deal_approval_rules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_deal_approval_rules`
--

LOCK TABLES `crm_deal_approval_rules` WRITE;
/*!40000 ALTER TABLE `crm_deal_approval_rules` DISABLE KEYS */;
INSERT INTO `crm_deal_approval_rules` VALUES (1,2,'Big deal sign-off',1,100.00,NULL,4,1,'2026-10-18 01:05:05','2026-10-18 01:05:05');
/*!40000 ALTER TABLE `crm_deal_approval_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_deal_approvals`
--

DROP TABLE IF EXISTS `crm_deal_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_deal_approvals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `opportunity_id` bigint unsigned NOT NULL,
  `rule_id` bigint unsigned NOT NULL,
  `approver_employee_id` bigint unsigned DEFAULT NULL,
  `requested_by_employee_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reason` text COLLATE utf8mb4_unicode_ci,
  `requested_at` datetime DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_deal_approvals_opportunity_id_foreign` (`opportunity_id`),
  KEY `crm_deal_approvals_rule_id_foreign` (`rule_id`),
  KEY `crm_deal_approvals_approver_employee_id_foreign` (`approver_employee_id`),
  KEY `crm_deal_approvals_requested_by_employee_id_foreign` (`requested_by_employee_id`),
  KEY `crm_deal_approvals_company_id_opportunity_id_status_index` (`company_id`,`opportunity_id`,`status`),
  CONSTRAINT `crm_deal_approvals_approver_employee_id_foreign` FOREIGN KEY (`approver_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_deal_approvals_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_deal_approvals_opportunity_id_foreign` FOREIGN KEY (`opportunity_id`) REFERENCES `crm_opportunities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_deal_approvals_requested_by_employee_id_foreign` FOREIGN KEY (`requested_by_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_deal_approvals_rule_id_foreign` FOREIGN KEY (`rule_id`) REFERENCES `crm_deal_approval_rules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_deal_approvals`
--

LOCK TABLES `crm_deal_approvals` WRITE;
/*!40000 ALTER TABLE `crm_deal_approvals` DISABLE KEYS */;
INSERT INTO `crm_deal_approvals` VALUES (1,2,2,1,4,4,'approved',NULL,'2026-10-18 06:36:43','2026-10-18 06:37:27','2026-10-18 01:06:43','2026-10-18 01:07:27');
/*!40000 ALTER TABLE `crm_deal_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_discount_approvals`
--

DROP TABLE IF EXISTS `crm_discount_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_discount_approvals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `quotation_id` bigint unsigned NOT NULL,
  `requested_discount_percent` tinyint unsigned NOT NULL,
  `requested_by_employee_id` bigint unsigned DEFAULT NULL,
  `approver_employee_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reason` text COLLATE utf8mb4_unicode_ci,
  `decided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_discount_approvals_company_id_foreign` (`company_id`),
  KEY `crm_discount_approvals_quotation_id_foreign` (`quotation_id`),
  KEY `crm_discount_approvals_requested_by_employee_id_foreign` (`requested_by_employee_id`),
  KEY `crm_discount_approvals_approver_employee_id_foreign` (`approver_employee_id`),
  CONSTRAINT `crm_discount_approvals_approver_employee_id_foreign` FOREIGN KEY (`approver_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_discount_approvals_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_discount_approvals_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `crm_quotations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_discount_approvals_requested_by_employee_id_foreign` FOREIGN KEY (`requested_by_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_discount_approvals`
--

LOCK TABLES `crm_discount_approvals` WRITE;
/*!40000 ALTER TABLE `crm_discount_approvals` DISABLE KEYS */;
INSERT INTO `crm_discount_approvals` VALUES (1,2,1,25,4,4,'approved',NULL,'2026-10-17 11:17:30','2026-10-17 11:17:13','2026-10-17 11:17:30'),(2,2,2,20,4,4,'approved',NULL,'2026-10-18 11:05:07','2026-10-18 11:04:45','2026-10-18 11:05:07');
/*!40000 ALTER TABLE `crm_discount_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_email_sequence_enrollments`
--

DROP TABLE IF EXISTS `crm_email_sequence_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_email_sequence_enrollments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `sequence_id` bigint unsigned NOT NULL,
  `lead_id` bigint unsigned DEFAULT NULL,
  `contact_id` bigint unsigned DEFAULT NULL,
  `current_step` int unsigned NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `next_step_due_at` datetime DEFAULT NULL,
  `enrolled_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_email_sequence_enrollments_company_id_foreign` (`company_id`),
  KEY `crm_email_sequence_enrollments_sequence_id_foreign` (`sequence_id`),
  KEY `crm_email_sequence_enrollments_lead_id_foreign` (`lead_id`),
  KEY `crm_email_sequence_enrollments_contact_id_foreign` (`contact_id`),
  CONSTRAINT `crm_email_sequence_enrollments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_email_sequence_enrollments_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_email_sequence_enrollments_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `crm_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_email_sequence_enrollments_sequence_id_foreign` FOREIGN KEY (`sequence_id`) REFERENCES `crm_email_sequences` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_email_sequence_enrollments`
--

LOCK TABLES `crm_email_sequence_enrollments` WRITE;
/*!40000 ALTER TABLE `crm_email_sequence_enrollments` DISABLE KEYS */;
INSERT INTO `crm_email_sequence_enrollments` VALUES (1,2,1,1,NULL,1,'active','2026-10-21 02:34:01','2026-10-18 02:33:58','2026-10-17 21:03:58','2026-10-17 21:04:01');
/*!40000 ALTER TABLE `crm_email_sequence_enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_email_sequence_steps`
--

DROP TABLE IF EXISTS `crm_email_sequence_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_email_sequence_steps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sequence_id` bigint unsigned NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `delay_days` int unsigned NOT NULL DEFAULT '1',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_email_sequence_steps_sequence_id_foreign` (`sequence_id`),
  CONSTRAINT `crm_email_sequence_steps_sequence_id_foreign` FOREIGN KEY (`sequence_id`) REFERENCES `crm_email_sequences` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_email_sequence_steps`
--

LOCK TABLES `crm_email_sequence_steps` WRITE;
/*!40000 ALTER TABLE `crm_email_sequence_steps` DISABLE KEYS */;
INSERT INTO `crm_email_sequence_steps` VALUES (1,1,0,0,'Welcome!','Thanks for signing up','2026-10-17 21:03:56','2026-10-17 21:03:56'),(2,1,1,3,'Checking in','How is it going?','2026-10-17 21:03:56','2026-10-17 21:03:56');
/*!40000 ALTER TABLE `crm_email_sequence_steps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_email_sequences`
--

DROP TABLE IF EXISTS `crm_email_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_email_sequences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_email_sequences_company_id_foreign` (`company_id`),
  CONSTRAINT `crm_email_sequences_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_email_sequences`
--

LOCK TABLES `crm_email_sequences` WRITE;
/*!40000 ALTER TABLE `crm_email_sequences` DISABLE KEYS */;
INSERT INTO `crm_email_sequences` VALUES (1,2,'Welcome Series',1,'2026-10-17 21:03:56','2026-10-17 21:03:56');
/*!40000 ALTER TABLE `crm_email_sequences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_esignature_requests`
--

DROP TABLE IF EXISTS `crm_esignature_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_esignature_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `signable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signable_id` bigint unsigned NOT NULL,
  `signer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signer_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `signed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_esignature_requests_company_id_foreign` (`company_id`),
  KEY `crm_esignature_requests_signable_type_signable_id_index` (`signable_type`,`signable_id`),
  CONSTRAINT `crm_esignature_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_esignature_requests`
--

LOCK TABLES `crm_esignature_requests` WRITE;
/*!40000 ALTER TABLE `crm_esignature_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_esignature_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_integrations`
--

DROP TABLE IF EXISTS `crm_integrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_integrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `config` json DEFAULT NULL,
  `is_connected` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_integrations_company_id_foreign` (`company_id`),
  CONSTRAINT `crm_integrations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_integrations`
--

LOCK TABLES `crm_integrations` WRITE;
/*!40000 ALTER TABLE `crm_integrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_integrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_knowledge_articles`
--

DROP TABLE IF EXISTS `crm_knowledge_articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_knowledge_articles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `views_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_knowledge_articles_slug_unique` (`slug`),
  KEY `crm_knowledge_articles_company_id_foreign` (`company_id`),
  CONSTRAINT `crm_knowledge_articles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_knowledge_articles`
--

LOCK TABLES `crm_knowledge_articles` WRITE;
/*!40000 ALTER TABLE `crm_knowledge_articles` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_knowledge_articles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_landing_pages`
--

DROP TABLE IF EXISTS `crm_landing_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_landing_pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `campaign_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `headline` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `views_count` int unsigned NOT NULL DEFAULT '0',
  `conversions_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_landing_pages_slug_unique` (`slug`),
  KEY `crm_landing_pages_company_id_foreign` (`company_id`),
  KEY `crm_landing_pages_campaign_id_foreign` (`campaign_id`),
  CONSTRAINT `crm_landing_pages_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `crm_campaigns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_landing_pages_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_landing_pages`
--

LOCK TABLES `crm_landing_pages` WRITE;
/*!40000 ALTER TABLE `crm_landing_pages` DISABLE KEYS */;
INSERT INTO `crm_landing_pages` VALUES (1,2,1,'October Promo','october-promo','Save Big This October','Limited time offer on all plans.','published',1,0,'2026-10-17 20:43:26','2026-10-17 20:43:28');
/*!40000 ALTER TABLE `crm_landing_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_layouts`
--

DROP TABLE IF EXISTS `crm_layouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_layouts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `entity` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fields_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_layouts_company_id_entity_unique` (`company_id`,`entity`),
  CONSTRAINT `crm_layouts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_layouts`
--

LOCK TABLES `crm_layouts` WRITE;
/*!40000 ALTER TABLE `crm_layouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_layouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_lead_sources`
--

DROP TABLE IF EXISTS `crm_lead_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_lead_sources` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_lead_sources_company_id_name_unique` (`company_id`,`name`),
  CONSTRAINT `crm_lead_sources_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_lead_sources`
--

LOCK TABLES `crm_lead_sources` WRITE;
/*!40000 ALTER TABLE `crm_lead_sources` DISABLE KEYS */;
INSERT INTO `crm_lead_sources` VALUES (1,2,'Website (Organic)',1,'2026-10-17 03:22:23','2026-10-17 05:46:12');
/*!40000 ALTER TABLE `crm_lead_sources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_leads`
--

DROP TABLE IF EXISTS `crm_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_leads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `team_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lead_source_id` bigint unsigned DEFAULT NULL,
  `campaign_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `score` tinyint unsigned NOT NULL DEFAULT '0',
  `assigned_to_employee_id` bigint unsigned DEFAULT NULL,
  `is_duplicate_of_lead_id` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `budget` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `has_need` tinyint(1) DEFAULT NULL,
  `has_authority` tinyint(1) DEFAULT NULL,
  `timeline` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_recycled` tinyint(1) NOT NULL DEFAULT '0',
  `recycled_at` datetime DEFAULT NULL,
  `recycle_reason` text COLLATE utf8mb4_unicode_ci,
  `converted_at` timestamp NULL DEFAULT NULL,
  `converted_contact_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_leads_lead_source_id_foreign` (`lead_source_id`),
  KEY `crm_leads_assigned_to_employee_id_foreign` (`assigned_to_employee_id`),
  KEY `crm_leads_is_duplicate_of_lead_id_foreign` (`is_duplicate_of_lead_id`),
  KEY `crm_leads_converted_contact_id_foreign` (`converted_contact_id`),
  KEY `crm_leads_company_id_status_index` (`company_id`,`status`),
  KEY `crm_leads_campaign_id_foreign` (`campaign_id`),
  KEY `crm_leads_branch_id_foreign` (`branch_id`),
  KEY `crm_leads_team_id_foreign` (`team_id`),
  CONSTRAINT `crm_leads_assigned_to_employee_id_foreign` FOREIGN KEY (`assigned_to_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_leads_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_leads_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `crm_campaigns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_leads_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_leads_converted_contact_id_foreign` FOREIGN KEY (`converted_contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_leads_is_duplicate_of_lead_id_foreign` FOREIGN KEY (`is_duplicate_of_lead_id`) REFERENCES `crm_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_leads_lead_source_id_foreign` FOREIGN KEY (`lead_source_id`) REFERENCES `crm_lead_sources` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_leads_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `crm_teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_leads`
--

LOCK TABLES `crm_leads` WRITE;
/*!40000 ALTER TABLE `crm_leads` DISABLE KEYS */;
INSERT INTO `crm_leads` VALUES (1,2,NULL,NULL,'Test Lead Co Updated','test@lead.com','+918210991424','abc',1,NULL,'new',60,NULL,NULL,NULL,'50000',1,1,'immediate',1,'2026-10-18 03:12:50','No budget this year','2026-10-17 03:22:38',1,'2026-10-17 03:22:25','2026-10-17 21:42:50'),(2,2,NULL,NULL,'Vivek Kumar Pandey','vivek65k@gmail.com','08210991424','ABC PVT LTD',1,NULL,'new',50,4,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,'2026-10-17 05:25:39','2026-10-17 21:09:43'),(3,2,NULL,NULL,'Vivek Kumar Pandey','vivek65k@gmail.com','08210991424','ABC PVT LTD',1,NULL,'new',50,18,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,'2026-10-17 05:25:40','2026-10-17 21:09:43'),(4,2,NULL,NULL,'Jane Visitor','jane@example.com',NULL,NULL,NULL,1,'new',30,4,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,'2026-10-17 20:43:13','2026-10-17 21:09:43'),(5,2,NULL,NULL,'John Imported','john@import.com','555-1234','Acme Co',NULL,NULL,'new',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,'2026-10-17 21:18:09','2026-10-17 21:18:09'),(6,2,NULL,NULL,'Jane Excel','jane@excel.com','555-9999',NULL,NULL,NULL,'new',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,'2026-10-17 21:49:17','2026-10-17 21:49:17');
/*!40000 ALTER TABLE `crm_leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_notes`
--

DROP TABLE IF EXISTS `crm_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `notable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notable_id` bigint unsigned NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_employee_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_notes_company_id_foreign` (`company_id`),
  KEY `crm_notes_owner_employee_id_foreign` (`owner_employee_id`),
  KEY `crm_notes_notable_type_notable_id_index` (`notable_type`,`notable_id`),
  CONSTRAINT `crm_notes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_notes_owner_employee_id_foreign` FOREIGN KEY (`owner_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_notes`
--

LOCK TABLES `crm_notes` WRITE;
/*!40000 ALTER TABLE `crm_notes` DISABLE KEYS */;
INSERT INTO `crm_notes` VALUES (1,2,'App\\Models\\Crm\\Account',1,'Discussed renewal terms, client wants a 10% discount.',NULL,NULL,4,'2026-10-17 20:19:51','2026-10-17 20:19:51');
/*!40000 ALTER TABLE `crm_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_opportunities`
--

DROP TABLE IF EXISTS `crm_opportunities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_opportunities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `team_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `contact_id` bigint unsigned DEFAULT NULL,
  `sales_stage_id` bigint unsigned NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `probability` tinyint unsigned DEFAULT NULL,
  `expected_close_date` date DEFAULT NULL,
  `owner_employee_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `lost_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lead_id` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `risks_blockers` text COLLATE utf8mb4_unicode_ci,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_opportunities_account_id_foreign` (`account_id`),
  KEY `crm_opportunities_contact_id_foreign` (`contact_id`),
  KEY `crm_opportunities_sales_stage_id_foreign` (`sales_stage_id`),
  KEY `crm_opportunities_owner_employee_id_foreign` (`owner_employee_id`),
  KEY `crm_opportunities_lead_id_foreign` (`lead_id`),
  KEY `crm_opportunities_company_id_status_index` (`company_id`,`status`),
  KEY `crm_opportunities_branch_id_foreign` (`branch_id`),
  KEY `crm_opportunities_team_id_foreign` (`team_id`),
  CONSTRAINT `crm_opportunities_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `crm_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_opportunities_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_opportunities_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_opportunities_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_opportunities_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `crm_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_opportunities_owner_employee_id_foreign` FOREIGN KEY (`owner_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_opportunities_sales_stage_id_foreign` FOREIGN KEY (`sales_stage_id`) REFERENCES `crm_sales_stages` (`id`),
  CONSTRAINT `crm_opportunities_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `crm_teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_opportunities`
--

LOCK TABLES `crm_opportunities` WRITE;
/*!40000 ALTER TABLE `crm_opportunities` DISABLE KEYS */;
INSERT INTO `crm_opportunities` VALUES (1,2,NULL,NULL,'Acme Expansion Deal',NULL,NULL,5,50000.00,NULL,NULL,NULL,'won',NULL,NULL,NULL,NULL,'2026-10-17 11:00:17','2026-10-17 10:59:24','2026-10-17 11:00:17'),(2,2,NULL,NULL,'Big Test Deal',NULL,NULL,1,5000.00,NULL,NULL,NULL,'won',NULL,NULL,NULL,NULL,'2026-10-18 01:07:31','2026-10-18 01:06:31','2026-10-18 01:07:31');
/*!40000 ALTER TABLE `crm_opportunities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_opportunity_competitor`
--

DROP TABLE IF EXISTS `crm_opportunity_competitor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_opportunity_competitor` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `opportunity_id` bigint unsigned NOT NULL,
  `competitor_id` bigint unsigned NOT NULL,
  `outcome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_opportunity_competitor_opportunity_id_competitor_id_unique` (`opportunity_id`,`competitor_id`),
  KEY `crm_opportunity_competitor_company_id_foreign` (`company_id`),
  KEY `crm_opportunity_competitor_competitor_id_foreign` (`competitor_id`),
  CONSTRAINT `crm_opportunity_competitor_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_opportunity_competitor_competitor_id_foreign` FOREIGN KEY (`competitor_id`) REFERENCES `crm_competitors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_opportunity_competitor_opportunity_id_foreign` FOREIGN KEY (`opportunity_id`) REFERENCES `crm_opportunities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_opportunity_competitor`
--

LOCK TABLES `crm_opportunity_competitor` WRITE;
/*!40000 ALTER TABLE `crm_opportunity_competitor` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_opportunity_competitor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_opportunity_products`
--

DROP TABLE IF EXISTS `crm_opportunity_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_opportunity_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `opportunity_id` bigint unsigned NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_opportunity_products_company_id_foreign` (`company_id`),
  KEY `crm_opportunity_products_opportunity_id_foreign` (`opportunity_id`),
  CONSTRAINT `crm_opportunity_products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_opportunity_products_opportunity_id_foreign` FOREIGN KEY (`opportunity_id`) REFERENCES `crm_opportunities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_opportunity_products`
--

LOCK TABLES `crm_opportunity_products` WRITE;
/*!40000 ALTER TABLE `crm_opportunity_products` DISABLE KEYS */;
INSERT INTO `crm_opportunity_products` VALUES (1,2,1,'Annual License',2.00,15000.00,'2026-10-17 10:59:47','2026-10-17 10:59:47');
/*!40000 ALTER TABLE `crm_opportunity_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_price_book_entries`
--

DROP TABLE IF EXISTS `crm_price_book_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_price_book_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `price_book_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `price` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_price_book_entries_price_book_id_product_id_unique` (`price_book_id`,`product_id`),
  KEY `crm_price_book_entries_product_id_foreign` (`product_id`),
  CONSTRAINT `crm_price_book_entries_price_book_id_foreign` FOREIGN KEY (`price_book_id`) REFERENCES `crm_price_books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_price_book_entries_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `crm_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_price_book_entries`
--

LOCK TABLES `crm_price_book_entries` WRITE;
/*!40000 ALTER TABLE `crm_price_book_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_price_book_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_price_books`
--

DROP TABLE IF EXISTS `crm_price_books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_price_books` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INR',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_price_books_company_id_name_unique` (`company_id`,`name`),
  CONSTRAINT `crm_price_books_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_price_books`
--

LOCK TABLES `crm_price_books` WRITE;
/*!40000 ALTER TABLE `crm_price_books` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_price_books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_products`
--

DROP TABLE IF EXISTS `crm_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inventory_item_id` bigint unsigned DEFAULT NULL,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `unit_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_products_company_id_foreign` (`company_id`),
  KEY `crm_products_inventory_item_id_foreign` (`inventory_item_id`),
  CONSTRAINT `crm_products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_products_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inv_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_products`
--

LOCK TABLES `crm_products` WRITE;
/*!40000 ALTER TABLE `crm_products` DISABLE KEYS */;
INSERT INTO `crm_products` VALUES (1,9,2,'Annual Support Plan','SUP-001',NULL,20000.00,1,'2026-10-17 11:16:45','2026-10-18 19:25:00');
/*!40000 ALTER TABLE `crm_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_proposals`
--

DROP TABLE IF EXISTS `crm_proposals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_proposals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `quotation_id` bigint unsigned DEFAULT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `owner_employee_id` bigint unsigned DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_proposals_company_id_foreign` (`company_id`),
  KEY `crm_proposals_quotation_id_foreign` (`quotation_id`),
  KEY `crm_proposals_account_id_foreign` (`account_id`),
  KEY `crm_proposals_owner_employee_id_foreign` (`owner_employee_id`),
  CONSTRAINT `crm_proposals_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `crm_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_proposals_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_proposals_owner_employee_id_foreign` FOREIGN KEY (`owner_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_proposals_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `crm_quotations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_proposals`
--

LOCK TABLES `crm_proposals` WRITE;
/*!40000 ALTER TABLE `crm_proposals` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_proposals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_quotas`
--

DROP TABLE IF EXISTS `crm_quotas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_quotas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned DEFAULT NULL,
  `team_id` bigint unsigned DEFAULT NULL,
  `period` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'month',
  `period_start` date NOT NULL,
  `target_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `incentive_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_quotas_company_id_foreign` (`company_id`),
  KEY `crm_quotas_employee_id_foreign` (`employee_id`),
  KEY `crm_quotas_team_id_foreign` (`team_id`),
  CONSTRAINT `crm_quotas_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_quotas_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_quotas_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `crm_teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_quotas`
--

LOCK TABLES `crm_quotas` WRITE;
/*!40000 ALTER TABLE `crm_quotas` DISABLE KEYS */;
INSERT INTO `crm_quotas` VALUES (1,2,4,NULL,'month','2026-10-01',50000.00,0.00,'2026-10-17 21:17:37','2026-10-17 21:17:37'),(2,2,4,NULL,'month','2026-10-01',50000.00,10.00,'2026-10-18 20:04:01','2026-10-18 20:04:01');
/*!40000 ALTER TABLE `crm_quotas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_quotation_lines`
--

DROP TABLE IF EXISTS `crm_quotation_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_quotation_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `quotation_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `discount_percent` tinyint unsigned NOT NULL DEFAULT '0',
  `line_total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_quotation_lines_quotation_id_foreign` (`quotation_id`),
  KEY `crm_quotation_lines_product_id_foreign` (`product_id`),
  CONSTRAINT `crm_quotation_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `crm_products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_quotation_lines_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `crm_quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_quotation_lines`
--

LOCK TABLES `crm_quotation_lines` WRITE;
/*!40000 ALTER TABLE `crm_quotation_lines` DISABLE KEYS */;
INSERT INTO `crm_quotation_lines` VALUES (1,1,NULL,'Annual Support Plan',1.00,20000.00,0,20000.00,'2026-10-17 11:17:08','2026-10-17 11:17:08'),(2,2,NULL,'Business Laptop',50.00,1000.00,0,50000.00,'2026-10-18 11:04:26','2026-10-18 11:04:26');
/*!40000 ALTER TABLE `crm_quotation_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_quotation_template_lines`
--

DROP TABLE IF EXISTS `crm_quotation_template_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_quotation_template_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(14,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_quotation_template_lines_template_id_foreign` (`template_id`),
  KEY `crm_quotation_template_lines_product_id_foreign` (`product_id`),
  CONSTRAINT `crm_quotation_template_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `crm_products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_quotation_template_lines_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `crm_quotation_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_quotation_template_lines`
--

LOCK TABLES `crm_quotation_template_lines` WRITE;
/*!40000 ALTER TABLE `crm_quotation_template_lines` DISABLE KEYS */;
INSERT INTO `crm_quotation_template_lines` VALUES (1,1,NULL,'Business Laptop',50.00,1000.00,'2026-10-18 11:04:21','2026-10-18 11:04:21');
/*!40000 ALTER TABLE `crm_quotation_template_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_quotation_templates`
--

DROP TABLE IF EXISTS `crm_quotation_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_quotation_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `discount_percent` int NOT NULL DEFAULT '0',
  `tax_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by_employee_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_quotation_templates_company_id_foreign` (`company_id`),
  KEY `crm_quotation_templates_created_by_employee_id_foreign` (`created_by_employee_id`),
  CONSTRAINT `crm_quotation_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_quotation_templates_created_by_employee_id_foreign` FOREIGN KEY (`created_by_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_quotation_templates`
--

LOCK TABLES `crm_quotation_templates` WRITE;
/*!40000 ALTER TABLE `crm_quotation_templates` DISABLE KEYS */;
INSERT INTO `crm_quotation_templates` VALUES (1,2,'Standard Laptop Deal',NULL,5,18.00,NULL,4,1,'2026-10-18 11:04:04','2026-10-18 11:04:04');
/*!40000 ALTER TABLE `crm_quotation_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_quotation_versions`
--

DROP TABLE IF EXISTS `crm_quotation_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_quotation_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `quotation_id` bigint unsigned NOT NULL,
  `version_number` int unsigned NOT NULL,
  `snapshot` json NOT NULL,
  `changed_by_employee_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `crm_quotation_versions_quotation_id_foreign` (`quotation_id`),
  KEY `crm_quotation_versions_changed_by_employee_id_foreign` (`changed_by_employee_id`),
  CONSTRAINT `crm_quotation_versions_changed_by_employee_id_foreign` FOREIGN KEY (`changed_by_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_quotation_versions_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `crm_quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_quotation_versions`
--

LOCK TABLES `crm_quotation_versions` WRITE;
/*!40000 ALTER TABLE `crm_quotation_versions` DISABLE KEYS */;
INSERT INTO `crm_quotation_versions` VALUES (1,2,1,'{\"lines\": [{\"quantity\": \"50.00\", \"line_total\": \"50000.00\", \"unit_price\": \"1000.00\", \"description\": \"Business Laptop\", \"discount_percent\": 0}], \"notes\": null, \"total\": 56050, \"status\": \"draft\", \"subtotal\": 50000, \"tax_percent\": \"18\", \"discount_amount\": 2500, \"discount_percent\": \"5\"}',4,'2026-10-18 11:04:26'),(2,2,2,'{\"lines\": [{\"quantity\": \"50.00\", \"line_total\": \"50000.00\", \"unit_price\": \"1000.00\", \"description\": \"Business Laptop\", \"discount_percent\": 0}], \"notes\": null, \"total\": 47200, \"status\": \"draft\", \"subtotal\": 50000, \"tax_percent\": \"18\", \"discount_amount\": 10000, \"discount_percent\": \"20\"}',4,'2026-10-18 11:04:45');
/*!40000 ALTER TABLE `crm_quotation_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_quotations`
--

DROP TABLE IF EXISTS `crm_quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_quotations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `quotation_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `contact_id` bigint unsigned DEFAULT NULL,
  `opportunity_id` bigint unsigned DEFAULT NULL,
  `price_book_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `valid_until` date DEFAULT NULL,
  `owner_employee_id` bigint unsigned DEFAULT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT '0.00',
  `discount_percent` tinyint unsigned NOT NULL DEFAULT '0',
  `discount_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tax_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_quotations_company_id_quotation_number_unique` (`company_id`,`quotation_number`),
  KEY `crm_quotations_account_id_foreign` (`account_id`),
  KEY `crm_quotations_contact_id_foreign` (`contact_id`),
  KEY `crm_quotations_opportunity_id_foreign` (`opportunity_id`),
  KEY `crm_quotations_price_book_id_foreign` (`price_book_id`),
  KEY `crm_quotations_owner_employee_id_foreign` (`owner_employee_id`),
  KEY `crm_quotations_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `crm_quotations_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `crm_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_quotations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_quotations_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_quotations_opportunity_id_foreign` FOREIGN KEY (`opportunity_id`) REFERENCES `crm_opportunities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_quotations_owner_employee_id_foreign` FOREIGN KEY (`owner_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_quotations_price_book_id_foreign` FOREIGN KEY (`price_book_id`) REFERENCES `crm_price_books` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_quotations`
--

LOCK TABLES `crm_quotations` WRITE;
/*!40000 ALTER TABLE `crm_quotations` DISABLE KEYS */;
INSERT INTO `crm_quotations` VALUES (1,2,'Q-2026-0001',1,NULL,NULL,NULL,'sent',NULL,NULL,20000.00,25,5000.00,0.00,15000.00,NULL,'2026-10-17 11:16:56','2026-10-12 01:00:50'),(2,2,'Q-2026-0002',1,NULL,NULL,NULL,'accepted',NULL,NULL,50000.00,20,10000.00,18.00,47200.00,NULL,'2026-10-18 11:04:26','2026-10-18 11:05:30'),(3,2,'Q-2026-0003',1,NULL,NULL,NULL,'draft',NULL,NULL,0.00,0,0.00,0.00,0.00,NULL,'2026-10-22 01:46:17','2026-10-22 01:46:17'),(4,2,'QUOTE-0500',1,NULL,NULL,NULL,'draft',NULL,NULL,0.00,0,0.00,0.00,0.00,NULL,'2026-10-22 01:46:32','2026-10-22 01:46:32');
/*!40000 ALTER TABLE `crm_quotations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_role_assignments`
--

DROP TABLE IF EXISTS `crm_role_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_role_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_role_assignments_role_id_employee_id_unique` (`role_id`,`employee_id`),
  KEY `crm_role_assignments_employee_id_foreign` (`employee_id`),
  CONSTRAINT `crm_role_assignments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_role_assignments_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `crm_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_role_assignments`
--

LOCK TABLES `crm_role_assignments` WRITE;
/*!40000 ALTER TABLE `crm_role_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_role_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_roles`
--

DROP TABLE IF EXISTS `crm_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permissions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_roles_company_id_foreign` (`company_id`),
  CONSTRAINT `crm_roles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_roles`
--

LOCK TABLES `crm_roles` WRITE;
/*!40000 ALTER TABLE `crm_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_sales_order_events`
--

DROP TABLE IF EXISTS `crm_sales_order_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_sales_order_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sales_order_id` bigint unsigned NOT NULL,
  `event_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `crm_sales_order_events_sales_order_id_foreign` (`sales_order_id`),
  KEY `crm_sales_order_events_employee_id_foreign` (`employee_id`),
  CONSTRAINT `crm_sales_order_events_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_sales_order_events_sales_order_id_foreign` FOREIGN KEY (`sales_order_id`) REFERENCES `crm_sales_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_sales_order_events`
--

LOCK TABLES `crm_sales_order_events` WRITE;
/*!40000 ALTER TABLE `crm_sales_order_events` DISABLE KEYS */;
INSERT INTO `crm_sales_order_events` VALUES (1,2,'status_change','Order created.',4,'2026-10-18 11:05:34'),(2,2,'status_change','Status changed from Draft to Confirmed.',4,'2026-10-18 11:05:54'),(3,2,'fulfilment','Business Laptop: 30 of 50.00 fulfilled.',4,'2026-10-18 11:06:01'),(4,2,'status_change','Status changed from Confirmed to Partially Fulfilled.',4,'2026-10-18 11:06:01'),(5,2,'fulfilment','Business Laptop: 50 of 50.00 fulfilled.',4,'2026-10-18 11:06:21'),(6,2,'status_change','Status changed from Partially Fulfilled to Fulfilled.',4,'2026-10-18 11:06:21'),(7,2,'status_change','Status changed from Fulfilled to Closed.',4,'2026-10-18 11:06:25'),(8,3,'status_change','Order created.',4,'2026-10-18 11:06:43'),(9,3,'status_change','Status changed from Draft to Cancelled.',4,'2026-10-18 11:07:01'),(10,3,'cancellation','Order cancelled: Customer changed their mind',4,'2026-10-18 11:07:01'),(11,4,'status_change','Order created.',4,'2026-10-18 19:25:08'),(12,4,'amendment','Line added: Laptop Charger (qty 10).',4,'2026-10-18 19:25:18'),(13,4,'status_change','Status changed from Draft to Confirmed.',4,'2026-10-18 19:25:29'),(14,4,'status_change','Status changed from Confirmed to Fulfilled.',4,'2026-10-18 19:26:04'),(15,5,'status_change','Order created.',4,'2026-10-18 19:32:58'),(16,6,'status_change','Order created.',4,'2026-10-18 20:15:26');
/*!40000 ALTER TABLE `crm_sales_order_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_sales_order_lines`
--

DROP TABLE IF EXISTS `crm_sales_order_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_sales_order_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sales_order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `fulfilled_qty` decimal(14,2) NOT NULL DEFAULT '0.00',
  `reservation_id` bigint unsigned DEFAULT NULL,
  `unit_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `line_total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_sales_order_lines_sales_order_id_foreign` (`sales_order_id`),
  KEY `crm_sales_order_lines_product_id_foreign` (`product_id`),
  KEY `crm_sales_order_lines_reservation_id_foreign` (`reservation_id`),
  CONSTRAINT `crm_sales_order_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `crm_products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_sales_order_lines_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `inv_stock_reservations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_sales_order_lines_sales_order_id_foreign` FOREIGN KEY (`sales_order_id`) REFERENCES `crm_sales_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_sales_order_lines`
--

LOCK TABLES `crm_sales_order_lines` WRITE;
/*!40000 ALTER TABLE `crm_sales_order_lines` DISABLE KEYS */;
INSERT INTO `crm_sales_order_lines` VALUES (1,1,NULL,'Annual Support Plan',1.00,0.00,NULL,20000.00,20000.00,'2026-10-17 11:17:49','2026-10-17 11:17:49'),(2,2,NULL,'Business Laptop',50.00,50.00,NULL,1000.00,50000.00,'2026-10-18 11:05:34','2026-10-18 11:06:21'),(3,4,1,'Laptop Charger',10.00,10.00,2,100.00,1000.00,'2026-10-18 19:25:18','2026-10-18 19:26:04'),(4,5,NULL,'Annual Support Retainer - monthly billing',1.00,0.00,NULL,5000.00,5000.00,'2026-10-18 19:32:58','2026-10-18 19:32:58'),(5,6,NULL,'Replacement - Laptop Charger',2.00,0.00,NULL,100.00,200.00,'2026-10-18 20:15:26','2026-10-18 20:15:26');
/*!40000 ALTER TABLE `crm_sales_order_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_sales_orders`
--

DROP TABLE IF EXISTS `crm_sales_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_sales_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `order_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quotation_id` bigint unsigned DEFAULT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `warehouse_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `cancelled_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `owner_employee_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_sales_orders_company_id_order_number_unique` (`company_id`,`order_number`),
  KEY `crm_sales_orders_quotation_id_foreign` (`quotation_id`),
  KEY `crm_sales_orders_account_id_foreign` (`account_id`),
  KEY `crm_sales_orders_owner_employee_id_foreign` (`owner_employee_id`),
  KEY `crm_sales_orders_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `crm_sales_orders_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `crm_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_sales_orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_sales_orders_owner_employee_id_foreign` FOREIGN KEY (`owner_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_sales_orders_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `crm_quotations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_sales_orders_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_sales_orders`
--

LOCK TABLES `crm_sales_orders` WRITE;
/*!40000 ALTER TABLE `crm_sales_orders` DISABLE KEYS */;
INSERT INTO `crm_sales_orders` VALUES (1,2,'SO-2026-0001',1,1,NULL,'confirmed',NULL,NULL,NULL,'2026-10-17',15000.00,NULL,'2026-10-17 11:17:49','2026-10-17 11:17:49'),(2,2,'SO-2026-0002',2,1,NULL,'closed',NULL,NULL,'2026-10-18 16:36:25','2026-10-18',47200.00,4,'2026-10-18 11:05:34','2026-10-18 20:03:52'),(3,2,'SO-2026-0003',NULL,1,NULL,'cancelled','Customer changed their mind','2026-10-18 16:37:01',NULL,'2026-10-18',0.00,NULL,'2026-10-18 11:06:43','2026-10-18 11:07:01'),(4,2,'SO-2026-0004',NULL,1,1,'fulfilled',NULL,NULL,NULL,'2026-10-19',1000.00,NULL,'2026-10-18 19:25:08','2026-10-18 19:26:04'),(5,2,'SO-2026-0005',NULL,1,NULL,'draft',NULL,NULL,NULL,'2026-10-19',5000.00,NULL,'2026-10-18 19:32:58','2026-10-18 19:32:58'),(6,2,'SO-2026-0006',NULL,1,NULL,'draft',NULL,NULL,NULL,'2026-10-19',200.00,NULL,'2026-10-18 20:15:26','2026-10-18 20:15:26');
/*!40000 ALTER TABLE `crm_sales_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_sales_stages`
--

DROP TABLE IF EXISTS `crm_sales_stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_sales_stages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `win_probability` tinyint unsigned NOT NULL DEFAULT '0',
  `is_won` tinyint(1) NOT NULL DEFAULT '0',
  `is_lost` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_sales_stages_company_id_name_unique` (`company_id`,`name`),
  KEY `crm_sales_stages_branch_id_foreign` (`branch_id`),
  CONSTRAINT `crm_sales_stages_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_sales_stages_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_sales_stages`
--

LOCK TABLES `crm_sales_stages` WRITE;
/*!40000 ALTER TABLE `crm_sales_stages` DISABLE KEYS */;
INSERT INTO `crm_sales_stages` VALUES (1,2,NULL,'Qualification',2,10,0,0,'2026-10-17 10:56:32','2026-10-17 20:09:41'),(2,2,NULL,'Needs Analysis',0,25,0,0,'2026-10-17 10:56:32','2026-10-17 20:09:38'),(3,2,NULL,'Proposal Sent',1,50,0,0,'2026-10-17 10:56:32','2026-10-17 20:09:41'),(4,2,NULL,'Negotiation',3,75,0,0,'2026-10-17 10:56:32','2026-10-17 10:56:32'),(5,2,NULL,'Closed Won',4,100,1,0,'2026-10-17 10:56:32','2026-10-17 10:56:32'),(6,2,NULL,'Closed Lost',5,0,0,1,'2026-10-17 10:56:32','2026-10-17 10:56:32');
/*!40000 ALTER TABLE `crm_sales_stages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_scheduled_jobs`
--

DROP TABLE IF EXISTS `crm_scheduled_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_scheduled_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'daily',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_run_at` datetime DEFAULT NULL,
  `next_run_at` datetime DEFAULT NULL,
  `last_result` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_scheduled_jobs_company_id_foreign` (`company_id`),
  CONSTRAINT `crm_scheduled_jobs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_scheduled_jobs`
--

LOCK TABLES `crm_scheduled_jobs` WRITE;
/*!40000 ALTER TABLE `crm_scheduled_jobs` DISABLE KEYS */;
INSERT INTO `crm_scheduled_jobs` VALUES (1,2,'Nightly Campaign Sync','recalculate_campaign_counts','daily',1,'2026-10-18 02:34:14','2026-10-19 02:34:14','Recalculated counts for 1 campaign(s).','2026-10-17 21:04:12','2026-10-17 21:04:14');
/*!40000 ALTER TABLE `crm_scheduled_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_scheduled_reports`
--

DROP TABLE IF EXISTS `crm_scheduled_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_scheduled_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `custom_report_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'weekly',
  `recipients` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_run_at` datetime DEFAULT NULL,
  `next_run_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_scheduled_reports_company_id_foreign` (`company_id`),
  KEY `crm_scheduled_reports_custom_report_id_foreign` (`custom_report_id`),
  CONSTRAINT `crm_scheduled_reports_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_scheduled_reports_custom_report_id_foreign` FOREIGN KEY (`custom_report_id`) REFERENCES `crm_custom_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_scheduled_reports`
--

LOCK TABLES `crm_scheduled_reports` WRITE;
/*!40000 ALTER TABLE `crm_scheduled_reports` DISABLE KEYS */;
INSERT INTO `crm_scheduled_reports` VALUES (1,2,1,'Weekly Lead Report','weekly','manager@example.com',1,'2026-10-18 06:40:04','2026-10-25 06:40:04','2026-10-17 20:56:10','2026-10-18 01:10:04');
/*!40000 ALTER TABLE `crm_scheduled_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_settings`
--

DROP TABLE IF EXISTS `crm_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `default_currency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `fiscal_year_start_month` tinyint unsigned NOT NULL DEFAULT '1',
  `business_hours_start` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '09:00',
  `business_hours_end` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '18:00',
  `default_sla_policy_id` bigint unsigned DEFAULT NULL,
  `pipeline_mode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'shared',
  `auto_assign_leads` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_settings_company_id_branch_id_unique` (`company_id`,`branch_id`),
  KEY `crm_settings_default_sla_policy_id_foreign` (`default_sla_policy_id`),
  KEY `crm_settings_branch_id_foreign` (`branch_id`),
  CONSTRAINT `crm_settings_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_settings_default_sla_policy_id_foreign` FOREIGN KEY (`default_sla_policy_id`) REFERENCES `crm_sla_policies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_settings`
--

LOCK TABLES `crm_settings` WRITE;
/*!40000 ALTER TABLE `crm_settings` DISABLE KEYS */;
INSERT INTO `crm_settings` VALUES (1,2,NULL,'USD',4,'09:00','17:00',NULL,'shared',1,'2026-10-17 21:16:14','2026-10-17 21:16:40'),(2,2,1,'INR',4,'10:00','19:00',NULL,'per_branch',1,'2026-10-18 01:27:58','2026-10-18 01:28:38');
/*!40000 ALTER TABLE `crm_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_sla_policies`
--

DROP TABLE IF EXISTS `crm_sla_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_sla_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_hours` int unsigned NOT NULL DEFAULT '4',
  `resolution_hours` int unsigned NOT NULL DEFAULT '24',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_sla_policies_company_id_foreign` (`company_id`),
  CONSTRAINT `crm_sla_policies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_sla_policies`
--

LOCK TABLES `crm_sla_policies` WRITE;
/*!40000 ALTER TABLE `crm_sla_policies` DISABLE KEYS */;
INSERT INTO `crm_sla_policies` VALUES (1,2,'Standard SLA','high',2,1,1,'2026-10-17 20:50:02','2026-10-17 20:50:02');
/*!40000 ALTER TABLE `crm_sla_policies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_team_members`
--

DROP TABLE IF EXISTS `crm_team_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_team_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_team_members_team_id_employee_id_unique` (`team_id`,`employee_id`),
  KEY `crm_team_members_employee_id_foreign` (`employee_id`),
  CONSTRAINT `crm_team_members_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_team_members_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `crm_teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_team_members`
--

LOCK TABLES `crm_team_members` WRITE;
/*!40000 ALTER TABLE `crm_team_members` DISABLE KEYS */;
INSERT INTO `crm_team_members` VALUES (1,1,4,'2026-10-17 21:17:35','2026-10-17 21:17:35'),(2,2,4,'2026-10-18 20:04:18','2026-10-18 20:04:18');
/*!40000 ALTER TABLE `crm_team_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_teams`
--

DROP TABLE IF EXISTS `crm_teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_teams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `manager_employee_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_teams_company_id_foreign` (`company_id`),
  KEY `crm_teams_manager_employee_id_foreign` (`manager_employee_id`),
  CONSTRAINT `crm_teams_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_teams_manager_employee_id_foreign` FOREIGN KEY (`manager_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_teams`
--

LOCK TABLES `crm_teams` WRITE;
/*!40000 ALTER TABLE `crm_teams` DISABLE KEYS */;
INSERT INTO `crm_teams` VALUES (1,2,'Enterprise Sales',4,'2026-10-17 21:17:33','2026-10-17 21:17:33'),(2,2,'Enterprise Sales',NULL,'2026-10-18 20:03:42','2026-10-18 20:03:42');
/*!40000 ALTER TABLE `crm_teams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_territories`
--

DROP TABLE IF EXISTS `crm_territories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_territories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `team_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `criteria` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_territories_company_id_foreign` (`company_id`),
  KEY `crm_territories_team_id_foreign` (`team_id`),
  CONSTRAINT `crm_territories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_territories_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `crm_teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_territories`
--

LOCK TABLES `crm_territories` WRITE;
/*!40000 ALTER TABLE `crm_territories` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_territories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_web_form_submissions`
--

DROP TABLE IF EXISTS `crm_web_form_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_web_form_submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `web_form_id` bigint unsigned NOT NULL,
  `data` json NOT NULL,
  `lead_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_web_form_submissions_web_form_id_foreign` (`web_form_id`),
  KEY `crm_web_form_submissions_lead_id_foreign` (`lead_id`),
  CONSTRAINT `crm_web_form_submissions_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `crm_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_web_form_submissions_web_form_id_foreign` FOREIGN KEY (`web_form_id`) REFERENCES `crm_web_forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_web_form_submissions`
--

LOCK TABLES `crm_web_form_submissions` WRITE;
/*!40000 ALTER TABLE `crm_web_form_submissions` DISABLE KEYS */;
INSERT INTO `crm_web_form_submissions` VALUES (1,1,'{\"name\": \"Jane Visitor\", \"email\": \"jane@example.com\"}',4,'2026-10-17 20:43:13','2026-10-17 20:43:13');
/*!40000 ALTER TABLE `crm_web_form_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_web_forms`
--

DROP TABLE IF EXISTS `crm_web_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_web_forms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `campaign_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fields_json` json DEFAULT NULL,
  `redirect_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `submissions_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_web_forms_slug_unique` (`slug`),
  KEY `crm_web_forms_company_id_foreign` (`company_id`),
  KEY `crm_web_forms_campaign_id_foreign` (`campaign_id`),
  CONSTRAINT `crm_web_forms_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `crm_campaigns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_web_forms_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_web_forms`
--

LOCK TABLES `crm_web_forms` WRITE;
/*!40000 ALTER TABLE `crm_web_forms` DISABLE KEYS */;
INSERT INTO `crm_web_forms` VALUES (1,2,1,'Contact Us','contact-us','[{\"name\": \"name\", \"type\": \"text\", \"label\": \"Full Name\", \"required\": true}, {\"name\": \"email\", \"type\": \"email\", \"label\": \"Email\", \"required\": true}]',NULL,1,1,'2026-10-17 20:43:01','2026-10-17 20:43:13');
/*!40000 ALTER TABLE `crm_web_forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_workflow_executions`
--

DROP TABLE IF EXISTS `crm_workflow_executions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_workflow_executions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `workflow_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success',
  `trigger_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `context_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `context_id` bigint unsigned DEFAULT NULL,
  `context_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `steps_log` json DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_workflow_executions_workflow_id_foreign` (`workflow_id`),
  KEY `crm_workflow_executions_company_id_workflow_id_status_index` (`company_id`,`workflow_id`,`status`),
  CONSTRAINT `crm_workflow_executions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_workflow_executions_workflow_id_foreign` FOREIGN KEY (`workflow_id`) REFERENCES `crm_workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_workflow_executions`
--

LOCK TABLES `crm_workflow_executions` WRITE;
/*!40000 ALTER TABLE `crm_workflow_executions` DISABLE KEYS */;
INSERT INTO `crm_workflow_executions` VALUES (1,2,1,'success','manual','App\\Models\\Crm\\Lead',1,'Test Lead Co Updated','[{\"message\": \"Simulated - no live email/notification provider is connected in this environment.\", \"outcome\": \"simulated\", \"step_id\": 1, \"summary\": \"Send welcome email\", \"step_type\": \"send_email\"}, {\"message\": \"Created task #4.\", \"outcome\": \"success\", \"step_id\": 2, \"summary\": \"Call within 24h\", \"step_type\": \"create_task\"}]',NULL,'2026-10-18 03:46:39','2026-10-18 03:46:39','2026-10-17 22:16:39','2026-10-17 22:16:39'),(2,2,1,'success','retry','App\\Models\\Crm\\Lead',1,'Test Lead Co Updated','[{\"message\": \"Simulated - no live email/notification provider is connected in this environment.\", \"outcome\": \"simulated\", \"step_id\": 1, \"summary\": \"Send welcome email\", \"step_type\": \"send_email\"}, {\"message\": \"Created task #5.\", \"outcome\": \"success\", \"step_id\": 2, \"summary\": \"Call within 24h\", \"step_type\": \"create_task\"}]',NULL,'2026-10-18 03:46:59','2026-10-18 03:46:59','2026-10-17 22:16:59','2026-10-17 22:16:59');
/*!40000 ALTER TABLE `crm_workflow_executions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_workflow_steps`
--

DROP TABLE IF EXISTS `crm_workflow_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_workflow_steps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `workflow_id` bigint unsigned NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `step_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `summary` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `config` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_workflow_steps_workflow_id_foreign` (`workflow_id`),
  CONSTRAINT `crm_workflow_steps_workflow_id_foreign` FOREIGN KEY (`workflow_id`) REFERENCES `crm_workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_workflow_steps`
--

LOCK TABLES `crm_workflow_steps` WRITE;
/*!40000 ALTER TABLE `crm_workflow_steps` DISABLE KEYS */;
INSERT INTO `crm_workflow_steps` VALUES (1,1,0,'send_email','Send welcome email',NULL,'2026-10-17 21:03:35','2026-10-17 21:03:35'),(2,1,1,'create_task','Call within 24h',NULL,'2026-10-17 21:03:35','2026-10-17 21:03:35');
/*!40000 ALTER TABLE `crm_workflow_steps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_workflows`
--

DROP TABLE IF EXISTS `crm_workflows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_workflows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `trigger_event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_workflows_company_id_foreign` (`company_id`),
  CONSTRAINT `crm_workflows_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_workflows`
--

LOCK TABLES `crm_workflows` WRITE;
/*!40000 ALTER TABLE `crm_workflows` DISABLE KEYS */;
INSERT INTO `crm_workflows` VALUES (1,2,'New Lead Nurture','lead_created',1,'2026-10-17 21:03:35','2026-10-17 21:03:35');
/*!40000 ALTER TABLE `crm_workflows` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deductions`
--

DROP TABLE IF EXISTS `deductions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deductions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `payroll_run_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `period_month` tinyint unsigned NOT NULL,
  `period_year` smallint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deductions_employee_id_foreign` (`employee_id`),
  KEY `deductions_company_id_status_index` (`company_id`,`status`),
  KEY `deductions_payroll_run_id_index` (`payroll_run_id`),
  CONSTRAINT `deductions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deductions_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deductions`
--

LOCK TABLES `deductions` WRITE;
/*!40000 ALTER TABLE `deductions` DISABLE KEYS */;
INSERT INTO `deductions` VALUES (31,2,19,NULL,'Asset damage',2000.00,11,2026,'pending',NULL,'2026-10-06 07:27:00','2026-10-06 07:27:00');
/*!40000 ALTER TABLE `deductions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_company_id_code_unique` (`company_id`,`code`),
  KEY `departments_parent_id_foreign` (`parent_id`),
  KEY `departments_name_index` (`name`),
  KEY `departments_is_active_index` (`is_active`),
  KEY `departments_branch_id_foreign` (`branch_id`),
  CONSTRAINT `departments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `departments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `departments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (3,2,NULL,NULL,'IT','001','Information Technology',1,'2026-08-05 06:05:18','2026-08-05 06:05:18'),(9,2,NULL,1,'IT','IT',NULL,1,'2026-09-18 20:01:38','2026-09-18 20:01:38'),(10,2,NULL,1,'Human Resources','HUMA',NULL,1,'2026-09-18 20:01:38','2026-09-18 20:01:38'),(11,2,NULL,1,'Sales','SALE',NULL,1,'2026-09-18 20:01:38','2026-09-18 20:01:38'),(12,2,NULL,5,'Finance','FINA',NULL,1,'2026-09-18 20:01:38','2026-09-18 20:01:38');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_templates`
--

DROP TABLE IF EXISTS `document_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `document_templates_company_id_foreign` (`company_id`),
  KEY `document_templates_branch_id_foreign` (`branch_id`),
  CONSTRAINT `document_templates_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_templates`
--

LOCK TABLES `document_templates` WRITE;
/*!40000 ALTER TABLE `document_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `domains`
--

DROP TABLE IF EXISTS `domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `domains` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenant_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `domains_domain_unique` (`domain`),
  KEY `domains_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `domains_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `domains`
--

LOCK TABLES `domains` WRITE;
/*!40000 ALTER TABLE `domains` DISABLE KEYS */;
/*!40000 ALTER TABLE `domains` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_templates`
--

DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email_templates_company_id_foreign` (`company_id`),
  CONSTRAINT `email_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_templates`
--

LOCK TABLES `email_templates` WRITE;
/*!40000 ALTER TABLE `email_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_assets`
--

DROP TABLE IF EXISTS `employee_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_assets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned DEFAULT NULL,
  `asset_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `asset_tag` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `identifier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brand_model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `returned_date` date DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'assigned',
  `condition_at_return` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_assets_employee_id_foreign` (`employee_id`),
  KEY `employee_assets_company_id_foreign` (`company_id`),
  CONSTRAINT `employee_assets_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_assets_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_assets`
--

LOCK TABLES `employee_assets` WRITE;
/*!40000 ALTER TABLE `employee_assets` DISABLE KEYS */;
INSERT INTO `employee_assets` VALUES (44,17,2,'Dell 5420','laptop','LAP-042',NULL,NULL,'2026-10-07',NULL,'assigned',NULL,NULL,'2026-10-07 05:46:00','2026-10-07 05:46:00');
/*!40000 ALTER TABLE `employee_assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_documents`
--

DROP TABLE IF EXISTS `employee_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_documents_employee_id_foreign` (`employee_id`),
  KEY `employee_documents_company_id_category_index` (`company_id`,`category`),
  CONSTRAINT `employee_documents_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_documents_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_documents`
--

LOCK TABLES `employee_documents` WRITE;
/*!40000 ALTER TABLE `employee_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_educations`
--

DROP TABLE IF EXISTS `employee_educations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_educations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `qualification` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `institution` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_of_study` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_year` smallint unsigned DEFAULT NULL,
  `end_year` smallint unsigned DEFAULT NULL,
  `grade` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_educations_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_educations_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_educations`
--

LOCK TABLES `employee_educations` WRITE;
/*!40000 ALTER TABLE `employee_educations` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_educations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_experiences`
--

DROP TABLE IF EXISTS `employee_experiences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_experiences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `designation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_experiences_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_experiences_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_experiences`
--

LOCK TABLES `employee_experiences` WRITE;
/*!40000 ALTER TABLE `employee_experiences` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_experiences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_family_members`
--

DROP TABLE IF EXISTS `employee_family_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_family_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `relation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_family_members_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_family_members_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_family_members`
--

LOCK TABLES `employee_family_members` WRITE;
/*!40000 ALTER TABLE `employee_family_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_family_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_kpis`
--

DROP TABLE IF EXISTS `employee_kpis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_kpis` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `kpi_id` bigint unsigned NOT NULL,
  `appraisal_cycle_id` bigint unsigned DEFAULT NULL,
  `target_value` decimal(12,2) NOT NULL,
  `actual_value` decimal(12,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_kpis_company_id_foreign` (`company_id`),
  KEY `employee_kpis_employee_id_foreign` (`employee_id`),
  KEY `employee_kpis_kpi_id_foreign` (`kpi_id`),
  KEY `employee_kpis_appraisal_cycle_id_foreign` (`appraisal_cycle_id`),
  CONSTRAINT `employee_kpis_appraisal_cycle_id_foreign` FOREIGN KEY (`appraisal_cycle_id`) REFERENCES `appraisal_cycles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_kpis_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_kpis_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_kpis_kpi_id_foreign` FOREIGN KEY (`kpi_id`) REFERENCES `kpis` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_kpis`
--

LOCK TABLES `employee_kpis` WRITE;
/*!40000 ALTER TABLE `employee_kpis` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_kpis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_promotions`
--

DROP TABLE IF EXISTS `employee_promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_promotions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `from_designation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_designation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `promotion_date` date NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_promotions_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_promotions_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_promotions`
--

LOCK TABLES `employee_promotions` WRITE;
/*!40000 ALTER TABLE `employee_promotions` DISABLE KEYS */;
INSERT INTO `employee_promotions` VALUES (7,17,'Sales Manager','Senior Sales Manager','2026-10-01','Took on a bigger role','2026-10-06 22:39:59','2026-10-06 22:39:59');
/*!40000 ALTER TABLE `employee_promotions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_salaries`
--

DROP TABLE IF EXISTS `employee_salaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_salaries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `effective_date` date NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_salaries_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_salaries_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_salaries`
--

LOCK TABLES `employee_salaries` WRITE;
/*!40000 ALTER TABLE `employee_salaries` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_salaries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_skills`
--

DROP TABLE IF EXISTS `employee_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_skills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `skill_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `proficiency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'intermediate',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_skills_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_skills_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_skills`
--

LOCK TABLES `employee_skills` WRITE;
/*!40000 ALTER TABLE `employee_skills` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_transfers`
--

DROP TABLE IF EXISTS `employee_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_transfers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `from_department_id` bigint unsigned DEFAULT NULL,
  `to_department_id` bigint unsigned DEFAULT NULL,
  `from_branch_id` bigint unsigned DEFAULT NULL,
  `to_branch_id` bigint unsigned DEFAULT NULL,
  `transfer_date` date NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_transfers_employee_id_foreign` (`employee_id`),
  KEY `employee_transfers_from_department_id_foreign` (`from_department_id`),
  KEY `employee_transfers_to_department_id_foreign` (`to_department_id`),
  KEY `employee_transfers_from_branch_id_foreign` (`from_branch_id`),
  KEY `employee_transfers_to_branch_id_foreign` (`to_branch_id`),
  CONSTRAINT `employee_transfers_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_transfers_from_branch_id_foreign` FOREIGN KEY (`from_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_transfers_from_department_id_foreign` FOREIGN KEY (`from_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_transfers_to_branch_id_foreign` FOREIGN KEY (`to_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_transfers_to_department_id_foreign` FOREIGN KEY (`to_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_transfers`
--

LOCK TABLES `employee_transfers` WRITE;
/*!40000 ALTER TABLE `employee_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `employee_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `manager_id` bigint unsigned DEFAULT NULL,
  `designation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employment_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employees_company_id_employee_code_unique` (`company_id`,`employee_code`),
  KEY `employees_user_id_foreign` (`user_id`),
  KEY `employees_department_id_foreign` (`department_id`),
  KEY `employees_branch_id_foreign` (`branch_id`),
  KEY `employees_status_index` (`status`),
  KEY `employees_name_index` (`name`),
  KEY `employees_manager_id_foreign` (`manager_id`),
  CONSTRAINT `employees_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employees_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (4,2,1,'001','Vivek Kumar Pandey','vivek65k@gmail.com','8210991424','male','1995-05-29',3,1,NULL,'IT','Full-time','2026-08-05','active','avatars/2/Q0khtnI60mNzTVp11JuROdVp1rkqbEu3XMv8WwfT.png','1124','Ranchi','Jharkhand','India','834002','2026-08-05 06:58:13','2026-10-09 22:33:55'),(16,2,21,'EMP-HR01','Priya Sharma','priya.sharma@onesuite.test','9810000001','female',NULL,10,1,17,'HR Manager','full_time','2025-09-19','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-18 20:01:40','2026-10-07 04:46:56'),(17,2,22,'EMP-MG01','Arjun Mehta','arjun.mehta@onesuite.test','9810000002','male',NULL,11,1,4,'Senior Sales Manager','full_time','2025-11-19','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-18 20:01:40','2026-10-07 04:45:47'),(18,2,23,'EMP-SL01','Kavya Reddy','kavya.reddy@onesuite.test','9810000003','female',NULL,11,1,17,'Sales Executive','full_time','2025-06-19','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-18 20:01:41','2026-10-07 04:39:41'),(19,2,24,'EMP-IT01','Rohan Gupta','rohan.gupta@onesuite.test','9810000004','male',NULL,9,1,16,'Software Engineer','full_time','2024-11-19','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-18 20:01:41','2026-10-07 04:47:39'),(20,2,25,'EMP-FN01','Sneha Iyer','sneha.iyer@onesuite.test','9810000005','female',NULL,12,5,NULL,'Finance Executive','full_time','2025-09-19','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-18 20:01:41','2026-09-18 20:01:41');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `escalation_rules`
--

DROP TABLE IF EXISTS `escalation_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `escalation_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `after_hours` int unsigned NOT NULL,
  `escalate_to_role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `escalation_rules_company_id_foreign` (`company_id`),
  CONSTRAINT `escalation_rules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `escalation_rules`
--

LOCK TABLES `escalation_rules` WRITE;
/*!40000 ALTER TABLE `escalation_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `escalation_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `esi_settings`
--

DROP TABLE IF EXISTS `esi_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `esi_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `employee_contribution_percent` decimal(5,2) NOT NULL DEFAULT '0.75',
  `employer_contribution_percent` decimal(5,2) NOT NULL DEFAULT '3.25',
  `wage_ceiling` decimal(12,2) NOT NULL DEFAULT '21000.00',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `esi_settings_company_id_branch_id_unique` (`company_id`,`branch_id`),
  KEY `esi_settings_branch_id_foreign` (`branch_id`),
  CONSTRAINT `esi_settings_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `esi_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `esi_settings`
--

LOCK TABLES `esi_settings` WRITE;
/*!40000 ALTER TABLE `esi_settings` DISABLE KEYS */;
INSERT INTO `esi_settings` VALUES (2,2,1,0.75,3.25,21000.00,1,'2026-09-17 06:11:37','2026-09-17 06:11:37');
/*!40000 ALTER TABLE `esi_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exit_interviews`
--

DROP TABLE IF EXISTS `exit_interviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exit_interviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `resignation_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `interview_date` date DEFAULT NULL,
  `conducted_by_user_id` bigint unsigned DEFAULT NULL,
  `feedback` text COLLATE utf8mb4_unicode_ci,
  `rating` tinyint unsigned DEFAULT NULL,
  `would_rehire` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exit_interviews_resignation_id_unique` (`resignation_id`),
  KEY `exit_interviews_company_id_foreign` (`company_id`),
  KEY `exit_interviews_employee_id_foreign` (`employee_id`),
  KEY `exit_interviews_conducted_by_user_id_foreign` (`conducted_by_user_id`),
  CONSTRAINT `exit_interviews_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exit_interviews_conducted_by_user_id_foreign` FOREIGN KEY (`conducted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exit_interviews_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exit_interviews_resignation_id_foreign` FOREIGN KEY (`resignation_id`) REFERENCES `resignations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exit_interviews`
--

LOCK TABLES `exit_interviews` WRITE;
/*!40000 ALTER TABLE `exit_interviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `exit_interviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_categories`
--

DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_categories_company_id_branch_id_name_unique` (`company_id`,`branch_id`,`name`),
  KEY `expense_categories_branch_id_foreign` (`branch_id`),
  CONSTRAINT `expense_categories_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expense_categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_categories`
--

LOCK TABLES `expense_categories` WRITE;
/*!40000 ALTER TABLE `expense_categories` DISABLE KEYS */;
INSERT INTO `expense_categories` VALUES (1,2,1,'Travel',NULL,1,'2026-09-18 20:01:40','2026-09-18 20:01:40'),(2,2,1,'Office Supplies',NULL,1,'2026-09-18 20:01:40','2026-09-18 20:01:40'),(3,2,1,'Client Entertainment',NULL,1,'2026-09-18 20:01:40','2026-09-18 20:01:40'),(4,2,1,'Software & Tools',NULL,1,'2026-09-18 20:01:40','2026-09-18 20:01:40'),(5,2,5,'Travel',NULL,1,'2026-09-18 20:01:40','2026-09-18 20:01:40'),(6,2,5,'Office Supplies',NULL,1,'2026-09-18 20:01:40','2026-09-18 20:01:40'),(7,2,5,'Client Entertainment',NULL,1,'2026-09-18 20:01:40','2026-09-18 20:01:40'),(8,2,5,'Software & Tools',NULL,1,'2026-09-18 20:01:40','2026-09-18 20:01:40');
/*!40000 ALTER TABLE `expense_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_claims`
--

DROP TABLE IF EXISTS `expense_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_claims` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `expense_category_id` bigint unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `expense_date` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `reimbursed_by_user_id` bigint unsigned DEFAULT NULL,
  `reimbursed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expense_claims_employee_id_foreign` (`employee_id`),
  KEY `expense_claims_expense_category_id_foreign` (`expense_category_id`),
  KEY `expense_claims_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `expense_claims_reimbursed_by_user_id_foreign` (`reimbursed_by_user_id`),
  KEY `expense_claims_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `expense_claims_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expense_claims_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expense_claims_expense_category_id_foreign` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expense_claims_reimbursed_by_user_id_foreign` FOREIGN KEY (`reimbursed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expense_claims_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_claims`
--

LOCK TABLES `expense_claims` WRITE;
/*!40000 ALTER TABLE `expense_claims` DISABLE KEYS */;
INSERT INTO `expense_claims` VALUES (3,2,18,1,2500.00,'2026-09-17','Client visit - cab fare.','pending',NULL,NULL,NULL,NULL,NULL,'2026-09-18 20:01:42','2026-09-18 20:01:42'),(4,2,19,4,1200.00,'2026-09-12','Annual IDE license renewal.','approved',NULL,'2026-09-12 20:01:42',NULL,NULL,NULL,'2026-09-18 20:01:42','2026-09-18 20:01:42');
/*!40000 ALTER TABLE `expense_claims` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_360s`
--

DROP TABLE IF EXISTS `feedback_360s`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback_360s` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `appraisal_id` bigint unsigned NOT NULL,
  `reviewer_employee_id` bigint unsigned NOT NULL,
  `relationship` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` tinyint unsigned NOT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `feedback_360s_company_id_foreign` (`company_id`),
  KEY `feedback_360s_appraisal_id_foreign` (`appraisal_id`),
  KEY `feedback_360s_reviewer_employee_id_foreign` (`reviewer_employee_id`),
  CONSTRAINT `feedback_360s_appraisal_id_foreign` FOREIGN KEY (`appraisal_id`) REFERENCES `appraisals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedback_360s_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedback_360s_reviewer_employee_id_foreign` FOREIGN KEY (`reviewer_employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_360s`
--

LOCK TABLES `feedback_360s` WRITE;
/*!40000 ALTER TABLE `feedback_360s` DISABLE KEYS */;
INSERT INTO `feedback_360s` VALUES (8,2,25,4,'peer',5,'Doing Well','2026-10-06 22:30:33','2026-10-06 22:30:33');
/*!40000 ALTER TABLE `feedback_360s` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `final_settlements`
--

DROP TABLE IF EXISTS `final_settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `final_settlements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `resignation_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `gross_dues` decimal(12,2) NOT NULL DEFAULT '0.00',
  `deductions` decimal(12,2) NOT NULL DEFAULT '0.00',
  `net_payable` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `processed_by_user_id` bigint unsigned DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `final_settlements_resignation_id_unique` (`resignation_id`),
  KEY `final_settlements_company_id_foreign` (`company_id`),
  KEY `final_settlements_employee_id_foreign` (`employee_id`),
  KEY `final_settlements_processed_by_user_id_foreign` (`processed_by_user_id`),
  CONSTRAINT `final_settlements_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `final_settlements_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `final_settlements_processed_by_user_id_foreign` FOREIGN KEY (`processed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `final_settlements_resignation_id_foreign` FOREIGN KEY (`resignation_id`) REFERENCES `resignations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `final_settlements`
--

LOCK TABLES `final_settlements` WRITE;
/*!40000 ALTER TABLE `final_settlements` DISABLE KEYS */;
/*!40000 ALTER TABLE `final_settlements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goals`
--

DROP TABLE IF EXISTS `goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `appraisal_cycle_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `weight` decimal(5,2) DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_started',
  `progress_percent` tinyint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goals_employee_id_foreign` (`employee_id`),
  KEY `goals_appraisal_cycle_id_foreign` (`appraisal_cycle_id`),
  KEY `goals_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `goals_appraisal_cycle_id_foreign` FOREIGN KEY (`appraisal_cycle_id`) REFERENCES `appraisal_cycles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `goals_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `goals_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goals`
--

LOCK TABLES `goals` WRITE;
/*!40000 ALTER TABLE `goals` DISABLE KEYS */;
/*!40000 ALTER TABLE `goals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holidays`
--

DROP TABLE IF EXISTS `holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `holidays` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `holidays_company_id_foreign` (`company_id`),
  KEY `holidays_date_index` (`date`),
  CONSTRAINT `holidays_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holidays`
--

LOCK TABLES `holidays` WRITE;
/*!40000 ALTER TABLE `holidays` DISABLE KEYS */;
INSERT INTO `holidays` VALUES (2,2,'1st January','2026-01-01',1,'2026-08-05 06:06:06','2026-08-05 06:06:14'),(13,2,'Gandhi Jayanti','2026-10-02',1,'2026-09-28 02:19:11','2026-09-28 02:19:11'),(14,2,'Republic Day','2027-01-26',1,'2026-09-28 02:19:21','2026-09-28 02:19:21'),(15,2,'Labour Day','2027-05-01',1,'2026-09-28 02:19:29','2026-09-28 02:19:29'),(16,2,'Christmas Day','2026-12-25',1,'2026-09-28 02:19:38','2026-09-28 02:19:38'),(17,2,'Independence Day','2027-08-15',1,'2026-09-28 02:20:00','2026-09-28 02:20:00');
/*!40000 ALTER TABLE `holidays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_policies`
--

DROP TABLE IF EXISTS `hr_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hr_policies_company_id_foreign` (`company_id`),
  KEY `hr_policies_branch_id_foreign` (`branch_id`),
  CONSTRAINT `hr_policies_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hr_policies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_policies`
--

LOCK TABLES `hr_policies` WRITE;
/*!40000 ALTER TABLE `hr_policies` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_policies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `incentives`
--

DROP TABLE IF EXISTS `incentives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `incentives` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `payroll_run_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `period_month` tinyint unsigned NOT NULL,
  `period_year` smallint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incentives_employee_id_foreign` (`employee_id`),
  KEY `incentives_company_id_status_index` (`company_id`,`status`),
  KEY `incentives_payroll_run_id_index` (`payroll_run_id`),
  CONSTRAINT `incentives_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `incentives_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `incentives`
--

LOCK TABLES `incentives` WRITE;
/*!40000 ALTER TABLE `incentives` DISABLE KEYS */;
INSERT INTO `incentives` VALUES (13,2,16,NULL,'Sales incentive',5000.00,11,2026,'pending',NULL,'2026-10-06 07:26:03','2026-10-06 07:26:03');
/*!40000 ALTER TABLE `incentives` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `increments`
--

DROP TABLE IF EXISTS `increments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `increments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `appraisal_id` bigint unsigned DEFAULT NULL,
  `previous_amount` decimal(14,2) NOT NULL,
  `increment_percent` decimal(5,2) NOT NULL,
  `new_amount` decimal(14,2) NOT NULL,
  `effective_date` date NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `increments_company_id_foreign` (`company_id`),
  KEY `increments_employee_id_foreign` (`employee_id`),
  KEY `increments_appraisal_id_foreign` (`appraisal_id`),
  CONSTRAINT `increments_appraisal_id_foreign` FOREIGN KEY (`appraisal_id`) REFERENCES `appraisals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `increments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `increments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `increments`
--

LOCK TABLES `increments` WRITE;
/*!40000 ALTER TABLE `increments` DISABLE KEYS */;
/*!40000 ALTER TABLE `increments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interview_feedback`
--

DROP TABLE IF EXISTS `interview_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `interview_feedback` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `interview_id` bigint unsigned NOT NULL,
  `interviewer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` tinyint unsigned DEFAULT NULL,
  `recommendation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'maybe',
  `comments` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interview_feedback_interview_id_foreign` (`interview_id`),
  CONSTRAINT `interview_feedback_interview_id_foreign` FOREIGN KEY (`interview_id`) REFERENCES `interviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interview_feedback`
--

LOCK TABLES `interview_feedback` WRITE;
/*!40000 ALTER TABLE `interview_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `interview_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interviews`
--

DROP TABLE IF EXISTS `interviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `interviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `candidate_id` bigint unsigned NOT NULL,
  `round` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `interviewer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interviews_candidate_id_foreign` (`candidate_id`),
  KEY `interviews_status_index` (`status`),
  CONSTRAINT `interviews_candidate_id_foreign` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interviews`
--

LOCK TABLES `interviews` WRITE;
/*!40000 ALTER TABLE `interviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `interviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_approval_rules`
--

DROP TABLE IF EXISTS `inv_approval_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_approval_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `action_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `threshold_qty` decimal(14,2) DEFAULT NULL,
  `threshold_value` decimal(14,2) DEFAULT NULL,
  `approver_role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_approval_rules_company_id_foreign` (`company_id`),
  CONSTRAINT `inv_approval_rules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_approval_rules`
--

LOCK TABLES `inv_approval_rules` WRITE;
/*!40000 ALTER TABLE `inv_approval_rules` DISABLE KEYS */;
INSERT INTO `inv_approval_rules` VALUES (1,2,'adjustment',5.00,NULL,'Warehouse Manager',1,'2026-10-18 10:03:15','2026-10-18 10:03:15');
/*!40000 ALTER TABLE `inv_approval_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_audit_logs`
--

DROP TABLE IF EXISTS `inv_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inv_audit_logs_company_id_foreign` (`company_id`),
  KEY `inv_audit_logs_employee_id_foreign` (`employee_id`),
  CONSTRAINT `inv_audit_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_audit_logs_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_audit_logs`
--

LOCK TABLES `inv_audit_logs` WRITE;
/*!40000 ALTER TABLE `inv_audit_logs` DISABLE KEYS */;
INSERT INTO `inv_audit_logs` VALUES (1,2,4,'updated','InventorySetting',1,'Inventory policies updated.','2026-10-18 10:02:06'),(2,2,4,'updated','NumberingSeries',1,'Numbering series for grn set to prefix GRNX.','2026-10-18 10:02:22'),(3,2,4,'created','CustomFieldDefinition',1,'Custom field \'HSN Code\' added for products.','2026-10-18 10:02:40'),(4,2,4,'created','ApprovalRule',1,'Approval rule for adjustment created.','2026-10-18 10:03:15'),(5,2,4,'updated','IntegrationSetting',1,'finance integration enabled.','2026-10-18 10:04:04'),(6,2,4,'updated','NumberingSeries',2,'Numbering series for quotation set to prefix QUOTE.','2026-10-22 01:46:31');
/*!40000 ALTER TABLE `inv_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_barcodes`
--

DROP TABLE IF EXISTS `inv_barcodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_barcodes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `barcode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CODE128',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_barcodes_company_id_foreign` (`company_id`),
  KEY `inv_barcodes_item_id_foreign` (`item_id`),
  CONSTRAINT `inv_barcodes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_barcodes_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_barcodes`
--

LOCK TABLES `inv_barcodes` WRITE;
/*!40000 ALTER TABLE `inv_barcodes` DISABLE KEYS */;
INSERT INTO `inv_barcodes` VALUES (1,2,2,'8901234567890','EAN13',1,'2026-10-18 02:06:25','2026-10-18 02:06:25');
/*!40000 ALTER TABLE `inv_barcodes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_batch_disposals`
--

DROP TABLE IF EXISTS `inv_batch_disposals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_batch_disposals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `batch_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `quantity` decimal(14,2) NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_approval',
  `requested_by_employee_id` bigint unsigned DEFAULT NULL,
  `approved_by_employee_id` bigint unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_batch_disposals_company_id_foreign` (`company_id`),
  KEY `inv_batch_disposals_batch_id_foreign` (`batch_id`),
  KEY `inv_batch_disposals_item_id_foreign` (`item_id`),
  KEY `inv_batch_disposals_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_batch_disposals_requested_by_employee_id_foreign` (`requested_by_employee_id`),
  KEY `inv_batch_disposals_approved_by_employee_id_foreign` (`approved_by_employee_id`),
  CONSTRAINT `inv_batch_disposals_approved_by_employee_id_foreign` FOREIGN KEY (`approved_by_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_batch_disposals_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `inv_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_batch_disposals_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_batch_disposals_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_batch_disposals_requested_by_employee_id_foreign` FOREIGN KEY (`requested_by_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_batch_disposals_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_batch_disposals`
--

LOCK TABLES `inv_batch_disposals` WRITE;
/*!40000 ALTER TABLE `inv_batch_disposals` DISABLE KEYS */;
INSERT INTO `inv_batch_disposals` VALUES (1,2,2,9,1,10.00,'Expired','approved',4,4,'2026-10-18 15:13:50','2026-10-18 09:43:38','2026-10-18 09:43:50');
/*!40000 ALTER TABLE `inv_batch_disposals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_batches`
--

DROP TABLE IF EXISTS `inv_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned DEFAULT NULL,
  `batch_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `manufacture_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `supplier_lot` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(14,2) NOT NULL DEFAULT '0.00',
  `unit_cost` decimal(14,2) DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `warranty_months` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_batches_company_id_foreign` (`company_id`),
  KEY `inv_batches_item_id_foreign` (`item_id`),
  KEY `inv_batches_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `inv_batches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_batches_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_batches_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_batches`
--

LOCK TABLES `inv_batches` WRITE;
/*!40000 ALTER TABLE `inv_batches` DISABLE KEYS */;
INSERT INTO `inv_batches` VALUES (1,2,2,NULL,'LOT-2026-01',NULL,'2026-12-31',NULL,100.00,NULL,'active',NULL,'2026-10-18 02:06:30','2026-10-18 02:06:30'),(2,2,9,1,'LOT-EXP-01',NULL,'2026-10-13',NULL,0.00,NULL,'disposed',NULL,'2026-10-18 09:43:27','2026-10-18 09:43:50');
/*!40000 ALTER TABLE `inv_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_brands`
--

DROP TABLE IF EXISTS `inv_brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_brands` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `manufacturer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_brands_company_id_foreign` (`company_id`),
  CONSTRAINT `inv_brands_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_brands`
--

LOCK TABLES `inv_brands` WRITE;
/*!40000 ALTER TABLE `inv_brands` DISABLE KEYS */;
INSERT INTO `inv_brands` VALUES (1,2,'Acme Wear',NULL,'2026-10-18 02:04:47','2026-10-18 02:04:47');
/*!40000 ALTER TABLE `inv_brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_categories`
--

DROP TABLE IF EXISTS `inv_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `default_storage_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `handling_rules` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_categories_company_id_foreign` (`company_id`),
  KEY `inv_categories_parent_id_foreign` (`parent_id`),
  CONSTRAINT `inv_categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `inv_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_categories`
--

LOCK TABLES `inv_categories` WRITE;
/*!40000 ALTER TABLE `inv_categories` DISABLE KEYS */;
INSERT INTO `inv_categories` VALUES (1,2,NULL,'Apparel',NULL,NULL,'2026-10-18 02:04:45','2026-10-18 02:04:45');
/*!40000 ALTER TABLE `inv_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_count_schedules`
--

DROP TABLE IF EXISTS `inv_count_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_count_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cycle',
  `frequency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `abc_class` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_count_schedules_company_id_foreign` (`company_id`),
  KEY `inv_count_schedules_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `inv_count_schedules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_count_schedules_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_count_schedules`
--

LOCK TABLES `inv_count_schedules` WRITE;
/*!40000 ALTER TABLE `inv_count_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `inv_count_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_count_sheet_lines`
--

DROP TABLE IF EXISTS `inv_count_sheet_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_count_sheet_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sheet_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `system_qty` decimal(14,2) NOT NULL,
  `counted_qty` decimal(14,2) DEFAULT NULL,
  `variance` decimal(14,2) DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_count_sheet_lines_sheet_id_foreign` (`sheet_id`),
  KEY `inv_count_sheet_lines_item_id_foreign` (`item_id`),
  KEY `inv_count_sheet_lines_location_id_foreign` (`location_id`),
  CONSTRAINT `inv_count_sheet_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_count_sheet_lines_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `inv_locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_count_sheet_lines_sheet_id_foreign` FOREIGN KEY (`sheet_id`) REFERENCES `inv_count_sheets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_count_sheet_lines`
--

LOCK TABLES `inv_count_sheet_lines` WRITE;
/*!40000 ALTER TABLE `inv_count_sheet_lines` DISABLE KEYS */;
INSERT INTO `inv_count_sheet_lines` VALUES (1,1,1,NULL,20.00,18.00,-2.00,'approved','2026-10-18 02:55:37','2026-10-18 02:55:59');
/*!40000 ALTER TABLE `inv_count_sheet_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_count_sheets`
--

DROP TABLE IF EXISTS `inv_count_sheets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_count_sheets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `schedule_id` bigint unsigned DEFAULT NULL,
  `count_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cycle',
  `blind_count` tinyint(1) NOT NULL DEFAULT '1',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'counting',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_count_sheets_count_number_unique` (`count_number`),
  KEY `inv_count_sheets_company_id_foreign` (`company_id`),
  KEY `inv_count_sheets_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_count_sheets_schedule_id_foreign` (`schedule_id`),
  CONSTRAINT `inv_count_sheets_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_count_sheets_schedule_id_foreign` FOREIGN KEY (`schedule_id`) REFERENCES `inv_count_schedules` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_count_sheets_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_count_sheets`
--

LOCK TABLES `inv_count_sheets` WRITE;
/*!40000 ALTER TABLE `inv_count_sheets` DISABLE KEYS */;
INSERT INTO `inv_count_sheets` VALUES (1,2,1,NULL,'CNT-2026-00001','cycle',1,'approved','2026-10-18 02:55:37','2026-10-18 02:55:59');
/*!40000 ALTER TABLE `inv_count_sheets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_custom_field_definitions`
--

DROP TABLE IF EXISTS `inv_custom_field_definitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_custom_field_definitions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `entity_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'item',
  `field_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `options` text COLLATE utf8mb4_unicode_ci,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_cfd_company_entity_key_unique` (`company_id`,`entity_type`,`field_key`),
  CONSTRAINT `inv_custom_field_definitions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_custom_field_definitions`
--

LOCK TABLES `inv_custom_field_definitions` WRITE;
/*!40000 ALTER TABLE `inv_custom_field_definitions` DISABLE KEYS */;
INSERT INTO `inv_custom_field_definitions` VALUES (1,2,'item','hsn_code','HSN Code','text',NULL,0,1,'2026-10-18 10:02:39','2026-10-18 10:02:39');
/*!40000 ALTER TABLE `inv_custom_field_definitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_custom_field_values`
--

DROP TABLE IF EXISTS `inv_custom_field_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_custom_field_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `field_id` bigint unsigned NOT NULL,
  `entity_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_custom_field_values_field_id_entity_type_entity_id_unique` (`field_id`,`entity_type`,`entity_id`),
  KEY `inv_custom_field_values_company_id_foreign` (`company_id`),
  CONSTRAINT `inv_custom_field_values_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_custom_field_values_field_id_foreign` FOREIGN KEY (`field_id`) REFERENCES `inv_custom_field_definitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_custom_field_values`
--

LOCK TABLES `inv_custom_field_values` WRITE;
/*!40000 ALTER TABLE `inv_custom_field_values` DISABLE KEYS */;
INSERT INTO `inv_custom_field_values` VALUES (1,2,1,'item',9,'85044090','2026-10-18 10:03:02','2026-10-18 10:03:02');
/*!40000 ALTER TABLE `inv_custom_field_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_customer_return_lines`
--

DROP TABLE IF EXISTS `inv_customer_return_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_customer_return_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `return_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `quantity` decimal(14,2) NOT NULL,
  `batch_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_customer_return_lines_return_id_foreign` (`return_id`),
  KEY `inv_customer_return_lines_item_id_foreign` (`item_id`),
  CONSTRAINT `inv_customer_return_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_customer_return_lines_return_id_foreign` FOREIGN KEY (`return_id`) REFERENCES `inv_customer_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_customer_return_lines`
--

LOCK TABLES `inv_customer_return_lines` WRITE;
/*!40000 ALTER TABLE `inv_customer_return_lines` DISABLE KEYS */;
INSERT INTO `inv_customer_return_lines` VALUES (1,1,1,3.00,NULL,'2026-10-18 02:39:31','2026-10-18 02:39:31'),(2,2,9,2.00,NULL,'2026-10-18 20:14:51','2026-10-18 20:14:51');
/*!40000 ALTER TABLE `inv_customer_return_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_customer_returns`
--

DROP TABLE IF EXISTS `inv_customer_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_customer_returns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `sales_order_id` bigint unsigned DEFAULT NULL,
  `replacement_order_id` bigint unsigned DEFAULT NULL,
  `dispatch_id` bigint unsigned DEFAULT NULL,
  `return_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `received_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_customer_returns_return_number_unique` (`return_number`),
  KEY `inv_customer_returns_company_id_foreign` (`company_id`),
  KEY `inv_customer_returns_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_customer_returns_dispatch_id_foreign` (`dispatch_id`),
  KEY `inv_customer_returns_sales_order_id_foreign` (`sales_order_id`),
  KEY `inv_customer_returns_replacement_order_id_foreign` (`replacement_order_id`),
  CONSTRAINT `inv_customer_returns_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_customer_returns_dispatch_id_foreign` FOREIGN KEY (`dispatch_id`) REFERENCES `inv_dispatches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_customer_returns_replacement_order_id_foreign` FOREIGN KEY (`replacement_order_id`) REFERENCES `crm_sales_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_customer_returns_sales_order_id_foreign` FOREIGN KEY (`sales_order_id`) REFERENCES `crm_sales_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_customer_returns_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_customer_returns`
--

LOCK TABLES `inv_customer_returns` WRITE;
/*!40000 ALTER TABLE `inv_customer_returns` DISABLE KEYS */;
INSERT INTO `inv_customer_returns` VALUES (1,2,1,NULL,NULL,1,'CRTN-2026-00001','Wrong size','received','2026-10-18 08:09:50','2026-10-18 02:39:31','2026-10-18 02:39:50'),(2,2,1,4,6,NULL,'CRTN-2026-00002','Defective unit','received','2026-10-19 01:45:15','2026-10-18 20:14:51','2026-10-18 20:15:26');
/*!40000 ALTER TABLE `inv_customer_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_demand_forecasts`
--

DROP TABLE IF EXISTS `inv_demand_forecasts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_demand_forecasts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `period_label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `historical_avg_qty` decimal(14,2) NOT NULL,
  `forecast_qty` decimal(14,2) NOT NULL,
  `method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'moving_average',
  `generated_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_demand_forecasts_company_id_foreign` (`company_id`),
  KEY `inv_demand_forecasts_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_demand_forecasts_item_id_foreign` (`item_id`),
  CONSTRAINT `inv_demand_forecasts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_demand_forecasts_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_demand_forecasts_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_demand_forecasts`
--

LOCK TABLES `inv_demand_forecasts` WRITE;
/*!40000 ALTER TABLE `inv_demand_forecasts` DISABLE KEYS */;
/*!40000 ALTER TABLE `inv_demand_forecasts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_dispatches`
--

DROP TABLE IF EXISTS `inv_dispatches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_dispatches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `package_id` bigint unsigned NOT NULL,
  `dispatch_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `carrier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dispatched',
  `dispatched_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `received_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_dispatches_dispatch_number_unique` (`dispatch_number`),
  KEY `inv_dispatches_company_id_foreign` (`company_id`),
  KEY `inv_dispatches_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_dispatches_package_id_foreign` (`package_id`),
  CONSTRAINT `inv_dispatches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_dispatches_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `inv_packages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_dispatches_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_dispatches`
--

LOCK TABLES `inv_dispatches` WRITE;
/*!40000 ALTER TABLE `inv_dispatches` DISABLE KEYS */;
INSERT INTO `inv_dispatches` VALUES (1,2,1,1,'DSP-2026-00001','BlueDart','BD12345','delivered','2026-10-18 08:09:05','2026-10-18 08:09:18','John Doe','J.Doe','2026-10-18 02:39:05','2026-10-18 02:39:18'),(2,2,1,2,'DSP-2026-00002','BlueDart',NULL,'dispatched','2026-10-19 00:56:04',NULL,NULL,NULL,'2026-10-18 19:26:04','2026-10-18 19:26:04');
/*!40000 ALTER TABLE `inv_dispatches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_expected_receipt_lines`
--

DROP TABLE IF EXISTS `inv_expected_receipt_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_expected_receipt_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `receipt_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `expected_qty` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_expected_receipt_lines_receipt_id_foreign` (`receipt_id`),
  KEY `inv_expected_receipt_lines_item_id_foreign` (`item_id`),
  CONSTRAINT `inv_expected_receipt_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_expected_receipt_lines_receipt_id_foreign` FOREIGN KEY (`receipt_id`) REFERENCES `inv_expected_receipts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_expected_receipt_lines`
--

LOCK TABLES `inv_expected_receipt_lines` WRITE;
/*!40000 ALTER TABLE `inv_expected_receipt_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `inv_expected_receipt_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_expected_receipts`
--

DROP TABLE IF EXISTS `inv_expected_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_expected_receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `reference_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `po_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expected_date` date DEFAULT NULL,
  `vehicle_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_asn` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_expected_receipts_reference_number_unique` (`reference_number`),
  KEY `inv_expected_receipts_company_id_foreign` (`company_id`),
  KEY `inv_expected_receipts_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_expected_receipts_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `inv_expected_receipts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_expected_receipts_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `inv_suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_expected_receipts_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_expected_receipts`
--

LOCK TABLES `inv_expected_receipts` WRITE;
/*!40000 ALTER TABLE `inv_expected_receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `inv_expected_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_grn_lines`
--

DROP TABLE IF EXISTS `inv_grn_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_grn_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `grn_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `received_qty` decimal(14,2) NOT NULL DEFAULT '0.00',
  `damaged_qty` decimal(14,2) NOT NULL DEFAULT '0.00',
  `batch_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `inspection_required` tinyint(1) NOT NULL DEFAULT '0',
  `inspection_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_required',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_grn_lines_grn_id_foreign` (`grn_id`),
  KEY `inv_grn_lines_item_id_foreign` (`item_id`),
  CONSTRAINT `inv_grn_lines_grn_id_foreign` FOREIGN KEY (`grn_id`) REFERENCES `inv_grns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_grn_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_grn_lines`
--

LOCK TABLES `inv_grn_lines` WRITE;
/*!40000 ALTER TABLE `inv_grn_lines` DISABLE KEYS */;
INSERT INTO `inv_grn_lines` VALUES (1,1,1,20.00,0.00,NULL,NULL,0,'not_required','2026-10-18 02:27:13','2026-10-18 02:27:13'),(2,1,2,5.00,0.00,NULL,NULL,1,'passed','2026-10-18 02:27:13','2026-10-18 02:27:49'),(3,2,1,5.00,0.00,NULL,NULL,0,'not_required','2026-10-18 02:54:20','2026-10-18 02:54:20'),(4,3,1,1.00,0.00,NULL,NULL,0,'not_required','2026-10-18 10:02:25','2026-10-18 10:02:25');
/*!40000 ALTER TABLE `inv_grn_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_grns`
--

DROP TABLE IF EXISTS `inv_grns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_grns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `expected_receipt_id` bigint unsigned DEFAULT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `grn_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `posted_by_employee_id` bigint unsigned DEFAULT NULL,
  `posted_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_grns_grn_number_unique` (`grn_number`),
  KEY `inv_grns_company_id_foreign` (`company_id`),
  KEY `inv_grns_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_grns_expected_receipt_id_foreign` (`expected_receipt_id`),
  KEY `inv_grns_supplier_id_foreign` (`supplier_id`),
  KEY `inv_grns_posted_by_employee_id_foreign` (`posted_by_employee_id`),
  CONSTRAINT `inv_grns_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_grns_expected_receipt_id_foreign` FOREIGN KEY (`expected_receipt_id`) REFERENCES `inv_expected_receipts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_grns_posted_by_employee_id_foreign` FOREIGN KEY (`posted_by_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_grns_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `inv_suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_grns_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_grns`
--

LOCK TABLES `inv_grns` WRITE;
/*!40000 ALTER TABLE `inv_grns` DISABLE KEYS */;
INSERT INTO `inv_grns` VALUES (1,2,1,NULL,NULL,'GRN-2026-00001','posted',4,'2026-10-18 07:57:30','2026-10-18 02:27:13','2026-10-18 02:27:30'),(2,2,1,NULL,NULL,'GRN-2026-00002','posted',4,'2026-10-18 08:24:21','2026-10-18 02:54:20','2026-10-18 02:54:21'),(3,2,1,NULL,NULL,'GRNX-0500','draft',NULL,NULL,'2026-10-18 10:02:25','2026-10-18 10:02:25');
/*!40000 ALTER TABLE `inv_grns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_integration_settings`
--

DROP TABLE IF EXISTS `inv_integration_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_integration_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `integration_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `endpoint_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_integration_settings_company_id_integration_key_unique` (`company_id`,`integration_key`),
  CONSTRAINT `inv_integration_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_integration_settings`
--

LOCK TABLES `inv_integration_settings` WRITE;
/*!40000 ALTER TABLE `inv_integration_settings` DISABLE KEYS */;
INSERT INTO `inv_integration_settings` VALUES (1,2,'finance',1,'https://finance.internal/api',NULL,'Posts inventory valuation','2026-10-18 10:04:04','2026-10-18 10:04:04');
/*!40000 ALTER TABLE `inv_integration_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_item_variant_values`
--

DROP TABLE IF EXISTS `inv_item_variant_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_item_variant_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` bigint unsigned NOT NULL,
  `variant_value_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_item_variant_values_item_id_foreign` (`item_id`),
  KEY `inv_item_variant_values_variant_value_id_foreign` (`variant_value_id`),
  CONSTRAINT `inv_item_variant_values_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_item_variant_values_variant_value_id_foreign` FOREIGN KEY (`variant_value_id`) REFERENCES `inv_variant_values` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_item_variant_values`
--

LOCK TABLES `inv_item_variant_values` WRITE;
/*!40000 ALTER TABLE `inv_item_variant_values` DISABLE KEYS */;
INSERT INTO `inv_item_variant_values` VALUES (1,3,1,NULL,NULL),(2,3,4,NULL,NULL),(3,4,1,NULL,NULL),(4,4,5,NULL,NULL),(5,5,2,NULL,NULL),(6,5,4,NULL,NULL),(7,6,2,NULL,NULL),(8,6,5,NULL,NULL),(9,7,3,NULL,NULL),(10,7,4,NULL,NULL),(11,8,3,NULL,NULL),(12,8,5,NULL,NULL);
/*!40000 ALTER TABLE `inv_item_variant_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_items`
--

DROP TABLE IF EXISTS `inv_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `brand_id` bigint unsigned DEFAULT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pcs',
  `purchase_uom_id` bigint unsigned DEFAULT NULL,
  `stock_uom_id` bigint unsigned DEFAULT NULL,
  `sales_uom_id` bigint unsigned DEFAULT NULL,
  `cost_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `costing_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'moving_avg',
  `abc_class` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `xyz_class` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sale_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `reorder_level` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tax_category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `track_stock` tinyint(1) NOT NULL DEFAULT '1',
  `track_batch` tinyint(1) NOT NULL DEFAULT '0',
  `track_serial` tinyint(1) NOT NULL DEFAULT '0',
  `track_expiry` tinyint(1) NOT NULL DEFAULT '0',
  `default_warehouse_id` bigint unsigned DEFAULT NULL,
  `parent_item_id` bigint unsigned DEFAULT NULL,
  `is_variant` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_items_company_id_sku_unique` (`company_id`,`sku`),
  KEY `inv_items_category_id_foreign` (`category_id`),
  KEY `inv_items_brand_id_foreign` (`brand_id`),
  KEY `inv_items_purchase_uom_id_foreign` (`purchase_uom_id`),
  KEY `inv_items_stock_uom_id_foreign` (`stock_uom_id`),
  KEY `inv_items_sales_uom_id_foreign` (`sales_uom_id`),
  KEY `inv_items_default_warehouse_id_foreign` (`default_warehouse_id`),
  KEY `inv_items_parent_item_id_foreign` (`parent_item_id`),
  CONSTRAINT `inv_items_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `inv_brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `inv_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_items_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_items_default_warehouse_id_foreign` FOREIGN KEY (`default_warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_items_parent_item_id_foreign` FOREIGN KEY (`parent_item_id`) REFERENCES `inv_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_items_purchase_uom_id_foreign` FOREIGN KEY (`purchase_uom_id`) REFERENCES `inv_uoms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_items_sales_uom_id_foreign` FOREIGN KEY (`sales_uom_id`) REFERENCES `inv_uoms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_items_stock_uom_id_foreign` FOREIGN KEY (`stock_uom_id`) REFERENCES `inv_uoms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_items`
--

LOCK TABLES `inv_items` WRITE;
/*!40000 ALTER TABLE `inv_items` DISABLE KEYS */;
INSERT INTO `inv_items` VALUES (1,2,'STL-10','Steel Rod 10mm',NULL,'Raw Materials',NULL,NULL,'pcs',NULL,NULL,NULL,50.00,'moving_avg','C','Z',75.00,20,1,'2026-10-18 01:53:29','2026-10-18 09:43:15',NULL,1,0,0,0,NULL,NULL,0),(2,2,'TSHIRT','T-Shirt',NULL,NULL,1,1,'pcs',NULL,NULL,NULL,10.00,'moving_avg','C','Z',20.00,0,1,'2026-10-18 02:05:05','2026-10-18 09:43:15',NULL,0,1,0,0,NULL,NULL,0),(3,2,'TSHIRT-s-red','T-Shirt (S / Red)',NULL,NULL,1,1,'pcs',NULL,NULL,NULL,10.00,'moving_avg',NULL,NULL,20.00,0,1,'2026-10-18 02:05:56','2026-10-18 02:05:56',NULL,1,0,0,0,NULL,2,1),(4,2,'TSHIRT-s-blue','T-Shirt (S / Blue)',NULL,NULL,1,1,'pcs',NULL,NULL,NULL,10.00,'moving_avg',NULL,NULL,20.00,0,1,'2026-10-18 02:05:57','2026-10-18 02:05:57',NULL,1,0,0,0,NULL,2,1),(5,2,'TSHIRT-m-red','T-Shirt (M / Red)',NULL,NULL,1,1,'pcs',NULL,NULL,NULL,10.00,'moving_avg',NULL,NULL,20.00,0,1,'2026-10-18 02:05:57','2026-10-18 02:05:57',NULL,1,0,0,0,NULL,2,1),(6,2,'TSHIRT-m-blue','T-Shirt (M / Blue)',NULL,NULL,1,1,'pcs',NULL,NULL,NULL,10.00,'moving_avg',NULL,NULL,20.00,0,1,'2026-10-18 02:05:57','2026-10-18 02:05:57',NULL,1,0,0,0,NULL,2,1),(7,2,'TSHIRT-l-red','T-Shirt (L / Red)',NULL,NULL,1,1,'pcs',NULL,NULL,NULL,10.00,'moving_avg',NULL,NULL,20.00,0,1,'2026-10-18 02:05:57','2026-10-18 02:05:57',NULL,1,0,0,0,NULL,2,1),(8,2,'TSHIRT-l-blue','T-Shirt (L / Blue)',NULL,NULL,1,1,'pcs',NULL,NULL,NULL,10.00,'moving_avg',NULL,NULL,20.00,0,1,'2026-10-18 02:05:57','2026-10-18 02:05:57',NULL,1,0,0,0,NULL,2,1),(9,2,'LAPCHG01','Laptop Charger',NULL,NULL,NULL,NULL,'pcs',NULL,NULL,NULL,50.00,'moving_avg','B','Z',100.00,0,1,'2026-10-18 09:42:18','2026-10-18 10:03:02',NULL,0,0,0,0,NULL,NULL,0);
/*!40000 ALTER TABLE `inv_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_locations`
--

DROP TABLE IF EXISTS `inv_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `level` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_weight` decimal(12,2) DEFAULT NULL,
  `max_volume` decimal(12,2) DEFAULT NULL,
  `storage_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_blocked` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_locations_company_id_foreign` (`company_id`),
  KEY `inv_locations_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_locations_parent_id_foreign` (`parent_id`),
  CONSTRAINT `inv_locations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_locations_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `inv_locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_locations_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_locations`
--

LOCK TABLES `inv_locations` WRITE;
/*!40000 ALTER TABLE `inv_locations` DISABLE KEYS */;
INSERT INTO `inv_locations` VALUES (1,2,1,NULL,'zone','A','A',NULL,NULL,NULL,0,'2026-10-18 02:14:34','2026-10-18 02:14:34'),(2,2,1,1,'rack','R-03','A/R-03',NULL,NULL,NULL,0,'2026-10-18 02:14:39','2026-10-18 02:14:39'),(3,2,1,2,'bin','B-12','A/R-03/B-12',NULL,NULL,NULL,0,'2026-10-18 02:14:44','2026-10-18 02:14:44');
/*!40000 ALTER TABLE `inv_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_numbering_series`
--

DROP TABLE IF EXISTS `inv_numbering_series`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_numbering_series` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `document_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prefix` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `next_number` int unsigned NOT NULL DEFAULT '1',
  `padding_digits` tinyint unsigned NOT NULL DEFAULT '5',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_numbering_series_company_id_document_type_unique` (`company_id`,`document_type`),
  CONSTRAINT `inv_numbering_series_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_numbering_series`
--

LOCK TABLES `inv_numbering_series` WRITE;
/*!40000 ALTER TABLE `inv_numbering_series` DISABLE KEYS */;
INSERT INTO `inv_numbering_series` VALUES (1,2,'grn','GRNX',501,4,1,'2026-10-18 10:02:22','2026-10-18 10:02:25'),(2,2,'quotation','QUOTE',501,4,1,'2026-10-22 01:46:31','2026-10-22 01:46:32');
/*!40000 ALTER TABLE `inv_numbering_series` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_package_lines`
--

DROP TABLE IF EXISTS `inv_package_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_package_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `package_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `quantity` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_package_lines_package_id_foreign` (`package_id`),
  KEY `inv_package_lines_item_id_foreign` (`item_id`),
  CONSTRAINT `inv_package_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_package_lines_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `inv_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_package_lines`
--

LOCK TABLES `inv_package_lines` WRITE;
/*!40000 ALTER TABLE `inv_package_lines` DISABLE KEYS */;
INSERT INTO `inv_package_lines` VALUES (1,1,1,10.00,'2026-10-18 02:38:51','2026-10-18 02:38:51'),(2,2,9,10.00,'2026-10-18 19:25:53','2026-10-18 19:25:53');
/*!40000 ALTER TABLE `inv_package_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_packages`
--

DROP TABLE IF EXISTS `inv_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `pick_list_id` bigint unsigned DEFAULT NULL,
  `package_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `box_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'packed',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_packages_package_number_unique` (`package_number`),
  KEY `inv_packages_company_id_foreign` (`company_id`),
  KEY `inv_packages_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_packages_pick_list_id_foreign` (`pick_list_id`),
  CONSTRAINT `inv_packages_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_packages_pick_list_id_foreign` FOREIGN KEY (`pick_list_id`) REFERENCES `inv_pick_lists` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_packages_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_packages`
--

LOCK TABLES `inv_packages` WRITE;
/*!40000 ALTER TABLE `inv_packages` DISABLE KEYS */;
INSERT INTO `inv_packages` VALUES (1,2,1,1,'PKG-2026-00001',2.50,'Medium carton','dispatched','2026-10-18 02:38:51','2026-10-18 02:39:05'),(2,2,1,2,'PKG-2026-00002',1.00,'Small','dispatched','2026-10-18 19:25:53','2026-10-18 19:26:04');
/*!40000 ALTER TABLE `inv_packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_pick_list_lines`
--

DROP TABLE IF EXISTS `inv_pick_list_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_pick_list_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pick_list_id` bigint unsigned NOT NULL,
  `reservation_id` bigint unsigned DEFAULT NULL,
  `item_id` bigint unsigned NOT NULL,
  `requested_qty` decimal(14,2) NOT NULL,
  `picked_qty` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_pick_list_lines_pick_list_id_foreign` (`pick_list_id`),
  KEY `inv_pick_list_lines_reservation_id_foreign` (`reservation_id`),
  KEY `inv_pick_list_lines_item_id_foreign` (`item_id`),
  CONSTRAINT `inv_pick_list_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_pick_list_lines_pick_list_id_foreign` FOREIGN KEY (`pick_list_id`) REFERENCES `inv_pick_lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_pick_list_lines_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `inv_stock_reservations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_pick_list_lines`
--

LOCK TABLES `inv_pick_list_lines` WRITE;
/*!40000 ALTER TABLE `inv_pick_list_lines` DISABLE KEYS */;
INSERT INTO `inv_pick_list_lines` VALUES (1,1,1,1,10.00,10.00,'picked','2026-10-18 02:38:25','2026-10-18 02:38:37'),(2,2,2,9,10.00,10.00,'picked','2026-10-18 19:25:40','2026-10-18 19:25:51');
/*!40000 ALTER TABLE `inv_pick_list_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_pick_lists`
--

DROP TABLE IF EXISTS `inv_pick_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_pick_lists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `picker_employee_id` bigint unsigned DEFAULT NULL,
  `pick_list_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `strategy` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_pick_lists_pick_list_number_unique` (`pick_list_number`),
  KEY `inv_pick_lists_company_id_foreign` (`company_id`),
  KEY `inv_pick_lists_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_pick_lists_picker_employee_id_foreign` (`picker_employee_id`),
  CONSTRAINT `inv_pick_lists_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_pick_lists_picker_employee_id_foreign` FOREIGN KEY (`picker_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_pick_lists_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_pick_lists`
--

LOCK TABLES `inv_pick_lists` WRITE;
/*!40000 ALTER TABLE `inv_pick_lists` DISABLE KEYS */;
INSERT INTO `inv_pick_lists` VALUES (1,2,1,NULL,'PICK-2026-00001','fifo','picked','2026-10-18 02:38:25','2026-10-18 02:38:38'),(2,2,1,NULL,'PICK-2026-00002','fifo','picked','2026-10-18 19:25:40','2026-10-18 19:25:51');
/*!40000 ALTER TABLE `inv_pick_lists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_purchase_suggestions`
--

DROP TABLE IF EXISTS `inv_purchase_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_purchase_suggestions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `reorder_rule_id` bigint unsigned DEFAULT NULL,
  `on_hand_qty` decimal(14,2) NOT NULL,
  `reserved_qty` decimal(14,2) NOT NULL,
  `available_qty` decimal(14,2) NOT NULL,
  `incoming_qty` decimal(14,2) NOT NULL DEFAULT '0.00',
  `suggested_qty` decimal(14,2) NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'suggested',
  `approved_by_employee_id` bigint unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_purchase_suggestions_company_id_foreign` (`company_id`),
  KEY `inv_purchase_suggestions_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_purchase_suggestions_item_id_foreign` (`item_id`),
  KEY `inv_purchase_suggestions_reorder_rule_id_foreign` (`reorder_rule_id`),
  KEY `inv_purchase_suggestions_approved_by_employee_id_foreign` (`approved_by_employee_id`),
  CONSTRAINT `inv_purchase_suggestions_approved_by_employee_id_foreign` FOREIGN KEY (`approved_by_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_purchase_suggestions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_purchase_suggestions_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_purchase_suggestions_reorder_rule_id_foreign` FOREIGN KEY (`reorder_rule_id`) REFERENCES `inv_reorder_rules` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_purchase_suggestions_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_purchase_suggestions`
--

LOCK TABLES `inv_purchase_suggestions` WRITE;
/*!40000 ALTER TABLE `inv_purchase_suggestions` DISABLE KEYS */;
INSERT INTO `inv_purchase_suggestions` VALUES (1,2,1,9,1,120.00,35.00,85.00,0.00,215.00,'approved',4,'2026-10-18 15:12:51','2026-10-18 09:42:35','2026-10-18 09:42:51');
/*!40000 ALTER TABLE `inv_purchase_suggestions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_putaway_rules`
--

DROP TABLE IF EXISTS `inv_putaway_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_putaway_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `strategy` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'flexible',
  `fixed_location_id` bigint unsigned DEFAULT NULL,
  `is_hazardous` tinyint(1) NOT NULL DEFAULT '0',
  `is_temp_controlled` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_putaway_rules_company_id_foreign` (`company_id`),
  KEY `inv_putaway_rules_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_putaway_rules_category_id_foreign` (`category_id`),
  KEY `inv_putaway_rules_fixed_location_id_foreign` (`fixed_location_id`),
  CONSTRAINT `inv_putaway_rules_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `inv_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_putaway_rules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_putaway_rules_fixed_location_id_foreign` FOREIGN KEY (`fixed_location_id`) REFERENCES `inv_locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_putaway_rules_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_putaway_rules`
--

LOCK TABLES `inv_putaway_rules` WRITE;
/*!40000 ALTER TABLE `inv_putaway_rules` DISABLE KEYS */;
INSERT INTO `inv_putaway_rules` VALUES (1,2,1,1,'fixed',3,0,0,1,'2026-10-18 02:15:59','2026-10-18 02:15:59');
/*!40000 ALTER TABLE `inv_putaway_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_putaway_tasks`
--

DROP TABLE IF EXISTS `inv_putaway_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_putaway_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `quantity` decimal(14,2) NOT NULL,
  `suggested_location_id` bigint unsigned DEFAULT NULL,
  `confirmed_location_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `source_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exception_note` text COLLATE utf8mb4_unicode_ci,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_putaway_tasks_company_id_foreign` (`company_id`),
  KEY `inv_putaway_tasks_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_putaway_tasks_item_id_foreign` (`item_id`),
  KEY `inv_putaway_tasks_suggested_location_id_foreign` (`suggested_location_id`),
  KEY `inv_putaway_tasks_confirmed_location_id_foreign` (`confirmed_location_id`),
  CONSTRAINT `inv_putaway_tasks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_putaway_tasks_confirmed_location_id_foreign` FOREIGN KEY (`confirmed_location_id`) REFERENCES `inv_locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_putaway_tasks_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_putaway_tasks_suggested_location_id_foreign` FOREIGN KEY (`suggested_location_id`) REFERENCES `inv_locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_putaway_tasks_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_putaway_tasks`
--

LOCK TABLES `inv_putaway_tasks` WRITE;
/*!40000 ALTER TABLE `inv_putaway_tasks` DISABLE KEYS */;
INSERT INTO `inv_putaway_tasks` VALUES (1,2,1,2,10.00,3,3,'completed','Manual',NULL,'2026-10-18 07:46:04','2026-10-18 02:16:01','2026-10-18 02:16:04'),(2,2,1,1,20.00,NULL,NULL,'pending','GRN GRN-2026-00001',NULL,NULL,'2026-10-18 02:27:30','2026-10-18 02:27:30'),(3,2,1,2,3.00,3,NULL,'pending','QC release - GRN-2026-00001',NULL,NULL,'2026-10-18 02:27:49','2026-10-18 02:27:49'),(4,2,1,1,5.00,NULL,NULL,'pending','GRN GRN-2026-00002',NULL,NULL,'2026-10-18 02:54:21','2026-10-18 02:54:21');
/*!40000 ALTER TABLE `inv_putaway_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_quality_inspections`
--

DROP TABLE IF EXISTS `inv_quality_inspections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_quality_inspections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `grn_line_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `checklist_notes` text COLLATE utf8mb4_unicode_ci,
  `sample_size` int unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `result` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `defect_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accepted_qty` decimal(14,2) DEFAULT NULL,
  `rejected_qty` decimal(14,2) DEFAULT NULL,
  `inspected_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_quality_inspections_company_id_foreign` (`company_id`),
  KEY `inv_quality_inspections_grn_line_id_foreign` (`grn_line_id`),
  KEY `inv_quality_inspections_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_quality_inspections_item_id_foreign` (`item_id`),
  CONSTRAINT `inv_quality_inspections_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_quality_inspections_grn_line_id_foreign` FOREIGN KEY (`grn_line_id`) REFERENCES `inv_grn_lines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_quality_inspections_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_quality_inspections_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_quality_inspections`
--

LOCK TABLES `inv_quality_inspections` WRITE;
/*!40000 ALTER TABLE `inv_quality_inspections` DISABLE KEYS */;
INSERT INTO `inv_quality_inspections` VALUES (1,2,2,1,2,NULL,NULL,'completed','partial','torn label',3.00,2.00,'2026-10-18 07:57:49','2026-10-18 02:27:30','2026-10-18 02:27:49');
/*!40000 ALTER TABLE `inv_quality_inspections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_quarantine_stock`
--

DROP TABLE IF EXISTS `inv_quarantine_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_quarantine_stock` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `grn_line_id` bigint unsigned NOT NULL,
  `quantity` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_quarantine_stock_company_id_foreign` (`company_id`),
  KEY `inv_quarantine_stock_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_quarantine_stock_item_id_foreign` (`item_id`),
  KEY `inv_quarantine_stock_grn_line_id_foreign` (`grn_line_id`),
  CONSTRAINT `inv_quarantine_stock_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_quarantine_stock_grn_line_id_foreign` FOREIGN KEY (`grn_line_id`) REFERENCES `inv_grn_lines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_quarantine_stock_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_quarantine_stock_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_quarantine_stock`
--

LOCK TABLES `inv_quarantine_stock` WRITE;
/*!40000 ALTER TABLE `inv_quarantine_stock` DISABLE KEYS */;
/*!40000 ALTER TABLE `inv_quarantine_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_reorder_rules`
--

DROP TABLE IF EXISTS `inv_reorder_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_reorder_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `reorder_point` decimal(14,2) NOT NULL,
  `target_stock_level` decimal(14,2) NOT NULL,
  `lead_time_days` int unsigned NOT NULL DEFAULT '0',
  `safety_stock` decimal(14,2) NOT NULL DEFAULT '0.00',
  `auto_create_po` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_reorder_rules_company_id_foreign` (`company_id`),
  KEY `inv_reorder_rules_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_reorder_rules_item_id_foreign` (`item_id`),
  CONSTRAINT `inv_reorder_rules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_reorder_rules_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_reorder_rules_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_reorder_rules`
--

LOCK TABLES `inv_reorder_rules` WRITE;
/*!40000 ALTER TABLE `inv_reorder_rules` DISABLE KEYS */;
INSERT INTO `inv_reorder_rules` VALUES (1,2,1,9,100.00,300.00,7,0.00,0,1,'2026-10-18 09:42:32','2026-10-18 09:42:32');
/*!40000 ALTER TABLE `inv_reorder_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_replenishment_rules`
--

DROP TABLE IF EXISTS `inv_replenishment_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_replenishment_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `bin_location_id` bigint unsigned NOT NULL,
  `source_location_id` bigint unsigned DEFAULT NULL,
  `min_qty` decimal(14,2) NOT NULL,
  `max_qty` decimal(14,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_replenishment_rules_company_id_foreign` (`company_id`),
  KEY `inv_replenishment_rules_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_replenishment_rules_item_id_foreign` (`item_id`),
  KEY `inv_replenishment_rules_bin_location_id_foreign` (`bin_location_id`),
  KEY `inv_replenishment_rules_source_location_id_foreign` (`source_location_id`),
  CONSTRAINT `inv_replenishment_rules_bin_location_id_foreign` FOREIGN KEY (`bin_location_id`) REFERENCES `inv_locations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_replenishment_rules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_replenishment_rules_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_replenishment_rules_source_location_id_foreign` FOREIGN KEY (`source_location_id`) REFERENCES `inv_locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_replenishment_rules_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_replenishment_rules`
--

LOCK TABLES `inv_replenishment_rules` WRITE;
/*!40000 ALTER TABLE `inv_replenishment_rules` DISABLE KEYS */;
INSERT INTO `inv_replenishment_rules` VALUES (1,2,1,1,3,2,5.00,20.00,1,'2026-10-18 02:55:13','2026-10-18 02:55:13');
/*!40000 ALTER TABLE `inv_replenishment_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_replenishment_tasks`
--

DROP TABLE IF EXISTS `inv_replenishment_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_replenishment_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `replenishment_rule_id` bigint unsigned DEFAULT NULL,
  `from_location_id` bigint unsigned DEFAULT NULL,
  `to_location_id` bigint unsigned DEFAULT NULL,
  `suggested_qty` decimal(14,2) NOT NULL,
  `priority` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_replenishment_tasks_company_id_foreign` (`company_id`),
  KEY `inv_replenishment_tasks_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_replenishment_tasks_item_id_foreign` (`item_id`),
  KEY `inv_replenishment_tasks_replenishment_rule_id_foreign` (`replenishment_rule_id`),
  KEY `inv_replenishment_tasks_from_location_id_foreign` (`from_location_id`),
  KEY `inv_replenishment_tasks_to_location_id_foreign` (`to_location_id`),
  CONSTRAINT `inv_replenishment_tasks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_replenishment_tasks_from_location_id_foreign` FOREIGN KEY (`from_location_id`) REFERENCES `inv_locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_replenishment_tasks_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_replenishment_tasks_replenishment_rule_id_foreign` FOREIGN KEY (`replenishment_rule_id`) REFERENCES `inv_replenishment_rules` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_replenishment_tasks_to_location_id_foreign` FOREIGN KEY (`to_location_id`) REFERENCES `inv_locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_replenishment_tasks_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_replenishment_tasks`
--

LOCK TABLES `inv_replenishment_tasks` WRITE;
/*!40000 ALTER TABLE `inv_replenishment_tasks` DISABLE KEYS */;
INSERT INTO `inv_replenishment_tasks` VALUES (1,2,1,1,1,2,3,15.00,'normal','completed','2026-10-18 08:25:27','2026-10-18 02:55:25','2026-10-18 02:55:27');
/*!40000 ALTER TABLE `inv_replenishment_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_serials`
--

DROP TABLE IF EXISTS `inv_serials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_serials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned DEFAULT NULL,
  `serial_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_stock',
  `warranty_expiry` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_serials_item_id_serial_number_unique` (`item_id`,`serial_number`),
  KEY `inv_serials_company_id_foreign` (`company_id`),
  KEY `inv_serials_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `inv_serials_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_serials_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_serials_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_serials`
--

LOCK TABLES `inv_serials` WRITE;
/*!40000 ALTER TABLE `inv_serials` DISABLE KEYS */;
/*!40000 ALTER TABLE `inv_serials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_settings`
--

DROP TABLE IF EXISTS `inv_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `allow_negative_stock` tinyint(1) NOT NULL DEFAULT '0',
  `allow_backorder` tinyint(1) NOT NULL DEFAULT '0',
  `require_batch_number_validation` tinyint(1) NOT NULL DEFAULT '0',
  `require_serial_number_validation` tinyint(1) NOT NULL DEFAULT '0',
  `default_costing_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'moving_avg',
  `landed_cost_overhead_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_settings_company_id_unique` (`company_id`),
  CONSTRAINT `inv_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_settings`
--

LOCK TABLES `inv_settings` WRITE;
/*!40000 ALTER TABLE `inv_settings` DISABLE KEYS */;
INSERT INTO `inv_settings` VALUES (1,2,1,0,0,0,'fifo',5.00,'2026-10-18 10:01:43','2026-10-18 10:02:06');
/*!40000 ALTER TABLE `inv_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_stock`
--

DROP TABLE IF EXISTS `inv_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_stock` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `quantity` decimal(14,2) NOT NULL DEFAULT '0.00',
  `reserved_quantity` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_stock_warehouse_id_item_id_unique` (`warehouse_id`,`item_id`),
  KEY `inv_stock_company_id_foreign` (`company_id`),
  KEY `inv_stock_item_id_foreign` (`item_id`),
  CONSTRAINT `inv_stock_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_stock_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_stock_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_stock`
--

LOCK TABLES `inv_stock` WRITE;
/*!40000 ALTER TABLE `inv_stock` DISABLE KEYS */;
INSERT INTO `inv_stock` VALUES (1,2,1,1,18.00,0.00,'2026-10-18 01:53:35','2026-10-18 02:55:59'),(2,2,2,1,5.00,0.00,'2026-10-18 02:15:37','2026-10-18 02:15:37'),(3,2,1,2,3.00,0.00,'2026-10-18 02:27:02','2026-10-18 02:27:49'),(4,2,1,9,100.00,35.00,'2026-10-18 09:42:19','2026-10-18 20:15:15');
/*!40000 ALTER TABLE `inv_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_stock_adjustments`
--

DROP TABLE IF EXISTS `inv_stock_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_stock_adjustments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `adjustment_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `system_qty` decimal(14,2) NOT NULL,
  `physical_qty` decimal(14,2) NOT NULL,
  `adjustment_qty` decimal(14,2) NOT NULL,
  `reason_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason_notes` text COLLATE utf8mb4_unicode_ci,
  `supporting_document` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_approval',
  `requested_by_employee_id` bigint unsigned DEFAULT NULL,
  `approved_by_employee_id` bigint unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_stock_adjustments_adjustment_number_unique` (`adjustment_number`),
  KEY `inv_stock_adjustments_company_id_foreign` (`company_id`),
  KEY `inv_stock_adjustments_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_stock_adjustments_item_id_foreign` (`item_id`),
  KEY `inv_stock_adjustments_requested_by_employee_id_foreign` (`requested_by_employee_id`),
  KEY `inv_stock_adjustments_approved_by_employee_id_foreign` (`approved_by_employee_id`),
  CONSTRAINT `inv_stock_adjustments_approved_by_employee_id_foreign` FOREIGN KEY (`approved_by_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_stock_adjustments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_stock_adjustments_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_stock_adjustments_requested_by_employee_id_foreign` FOREIGN KEY (`requested_by_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_stock_adjustments_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_stock_adjustments`
--

LOCK TABLES `inv_stock_adjustments` WRITE;
/*!40000 ALTER TABLE `inv_stock_adjustments` DISABLE KEYS */;
INSERT INTO `inv_stock_adjustments` VALUES (1,2,1,1,'ADJ-2026-00001',24.00,20.00,-4.00,'damage','Found damaged cartons',NULL,'approved',4,4,'2026-10-18 08:24:56',NULL,'2026-10-18 02:54:45','2026-10-18 02:54:56'),(2,2,1,9,'ADJ-2026-00002',110.00,108.00,-2.00,'damage',NULL,NULL,'approved',4,4,'2026-10-18 15:33:30',NULL,'2026-10-18 10:03:30','2026-10-18 10:03:30'),(3,2,1,9,'ADJ-2026-00003',108.00,90.00,-18.00,'damage',NULL,NULL,'pending_approval',4,NULL,NULL,NULL,'2026-10-18 10:03:46','2026-10-18 10:03:46');
/*!40000 ALTER TABLE `inv_stock_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_stock_ledger`
--

DROP TABLE IF EXISTS `inv_stock_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_stock_ledger` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `batch_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity_before` decimal(14,2) NOT NULL,
  `quantity_change` decimal(14,2) NOT NULL,
  `quantity_after` decimal(14,2) NOT NULL,
  `source_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_id` bigint unsigned DEFAULT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_stock_ledger_company_id_foreign` (`company_id`),
  KEY `inv_stock_ledger_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_stock_ledger_item_id_foreign` (`item_id`),
  KEY `inv_stock_ledger_location_id_foreign` (`location_id`),
  KEY `inv_stock_ledger_employee_id_foreign` (`employee_id`),
  CONSTRAINT `inv_stock_ledger_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_stock_ledger_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_stock_ledger_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_stock_ledger_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `inv_locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_stock_ledger_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_stock_ledger`
--

LOCK TABLES `inv_stock_ledger` WRITE;
/*!40000 ALTER TABLE `inv_stock_ledger` DISABLE KEYS */;
INSERT INTO `inv_stock_ledger` VALUES (1,2,1,1,NULL,NULL,NULL,'receipt',19.00,5.00,24.00,'Grn','GRN GRN-2026-00002',4,NULL,'2026-10-18 02:54:21','2026-10-18 02:54:21'),(2,2,1,1,NULL,NULL,NULL,'adjustment',24.00,-4.00,20.00,'StockAdjustment','ADJ-2026-00001',4,'damage','2026-10-18 02:54:56','2026-10-18 02:54:56'),(3,2,1,1,NULL,NULL,NULL,'count_adjustment',20.00,-2.00,18.00,'CountSheet','CNT-2026-00001',4,'Cycle count variance','2026-10-18 02:55:59','2026-10-18 02:55:59'),(4,2,1,9,NULL,'LOT-EXP-01',NULL,'adjustment',120.00,-10.00,110.00,'BatchDisposal','Disposal #1',4,'Expired','2026-10-18 09:43:50','2026-10-18 09:43:50'),(5,2,1,9,NULL,NULL,NULL,'adjustment',110.00,-2.00,108.00,'StockAdjustment','ADJ-2026-00002',4,'damage','2026-10-18 10:03:30','2026-10-18 10:03:30'),(6,2,1,9,NULL,NULL,NULL,'issue',108.00,-10.00,98.00,'Dispatch','DSP-2026-00002',4,NULL,'2026-10-18 19:26:04','2026-10-18 19:26:04'),(7,2,1,9,NULL,NULL,NULL,'return_in',98.00,2.00,100.00,'CustomerReturn','CRTN-2026-00002',4,'Defective unit','2026-10-18 20:15:15','2026-10-18 20:15:15');
/*!40000 ALTER TABLE `inv_stock_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_stock_reservations`
--

DROP TABLE IF EXISTS `inv_stock_reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_stock_reservations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `reservation_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(14,2) NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reserved',
  `released_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_stock_reservations_reservation_number_unique` (`reservation_number`),
  KEY `inv_stock_reservations_company_id_foreign` (`company_id`),
  KEY `inv_stock_reservations_warehouse_id_foreign` (`warehouse_id`),
  KEY `inv_stock_reservations_item_id_foreign` (`item_id`),
  CONSTRAINT `inv_stock_reservations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_stock_reservations_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_stock_reservations_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_stock_reservations`
--

LOCK TABLES `inv_stock_reservations` WRITE;
/*!40000 ALTER TABLE `inv_stock_reservations` DISABLE KEYS */;
INSERT INTO `inv_stock_reservations` VALUES (1,2,1,1,'RSV-2026-00001','SO-1001',10.00,'fulfilled',NULL,'2026-10-18 02:38:11','2026-10-18 02:39:05'),(2,2,1,9,'RSV-2026-00002','SO-2026-0004',10.00,'fulfilled',NULL,'2026-10-18 19:25:29','2026-10-18 19:26:04');
/*!40000 ALTER TABLE `inv_stock_reservations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_supplier_products`
--

DROP TABLE IF EXISTS `inv_supplier_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_supplier_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `supplier_sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cost_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `lead_time_days` int unsigned NOT NULL DEFAULT '0',
  `is_preferred` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_supplier_products_supplier_id_item_id_unique` (`supplier_id`,`item_id`),
  KEY `inv_supplier_products_company_id_foreign` (`company_id`),
  KEY `inv_supplier_products_item_id_foreign` (`item_id`),
  CONSTRAINT `inv_supplier_products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_supplier_products_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_supplier_products_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `inv_suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_supplier_products`
--

LOCK TABLES `inv_supplier_products` WRITE;
/*!40000 ALTER TABLE `inv_supplier_products` DISABLE KEYS */;
INSERT INTO `inv_supplier_products` VALUES (1,2,1,2,NULL,9.50,14,1,'2026-10-18 02:06:36','2026-10-18 02:06:36');
/*!40000 ALTER TABLE `inv_supplier_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_supplier_return_lines`
--

DROP TABLE IF EXISTS `inv_supplier_return_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_supplier_return_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `return_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `quantity` decimal(14,2) NOT NULL,
  `batch_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_supplier_return_lines_return_id_foreign` (`return_id`),
  KEY `inv_supplier_return_lines_item_id_foreign` (`item_id`),
  CONSTRAINT `inv_supplier_return_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_supplier_return_lines_return_id_foreign` FOREIGN KEY (`return_id`) REFERENCES `inv_supplier_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_supplier_return_lines`
--

LOCK TABLES `inv_supplier_return_lines` WRITE;
/*!40000 ALTER TABLE `inv_supplier_return_lines` DISABLE KEYS */;
INSERT INTO `inv_supplier_return_lines` VALUES (1,1,1,4.00,NULL,NULL,'2026-10-18 02:28:16','2026-10-18 02:28:16');
/*!40000 ALTER TABLE `inv_supplier_return_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_supplier_returns`
--

DROP TABLE IF EXISTS `inv_supplier_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_supplier_returns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `return_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `credit_note_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dispatched_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_supplier_returns_return_number_unique` (`return_number`),
  KEY `inv_supplier_returns_company_id_foreign` (`company_id`),
  KEY `inv_supplier_returns_supplier_id_foreign` (`supplier_id`),
  KEY `inv_supplier_returns_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `inv_supplier_returns_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_supplier_returns_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `inv_suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_supplier_returns_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_supplier_returns`
--

LOCK TABLES `inv_supplier_returns` WRITE;
/*!40000 ALTER TABLE `inv_supplier_returns` DISABLE KEYS */;
INSERT INTO `inv_supplier_returns` VALUES (1,2,1,1,'RTN-2026-00001','Damaged','dispatched','CN-1001',NULL,'2026-10-18 07:58:45','2026-10-18 02:28:16','2026-10-18 02:28:45');
/*!40000 ALTER TABLE `inv_supplier_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_suppliers`
--

DROP TABLE IF EXISTS `inv_suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_suppliers_company_id_foreign` (`company_id`),
  CONSTRAINT `inv_suppliers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_suppliers`
--

LOCK TABLES `inv_suppliers` WRITE;
/*!40000 ALTER TABLE `inv_suppliers` DISABLE KEYS */;
INSERT INTO `inv_suppliers` VALUES (1,2,'Textile Importers Ltd',NULL,NULL,NULL,NULL,'2026-10-18 02:06:33','2026-10-18 02:06:33');
/*!40000 ALTER TABLE `inv_suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_uom_conversions`
--

DROP TABLE IF EXISTS `inv_uom_conversions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_uom_conversions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` bigint unsigned NOT NULL,
  `from_uom_id` bigint unsigned NOT NULL,
  `to_uom_id` bigint unsigned NOT NULL,
  `conversion_factor` decimal(14,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_uom_conversions_item_id_foreign` (`item_id`),
  KEY `inv_uom_conversions_from_uom_id_foreign` (`from_uom_id`),
  KEY `inv_uom_conversions_to_uom_id_foreign` (`to_uom_id`),
  CONSTRAINT `inv_uom_conversions_from_uom_id_foreign` FOREIGN KEY (`from_uom_id`) REFERENCES `inv_uoms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_uom_conversions_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_uom_conversions_to_uom_id_foreign` FOREIGN KEY (`to_uom_id`) REFERENCES `inv_uoms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_uom_conversions`
--

LOCK TABLES `inv_uom_conversions` WRITE;
/*!40000 ALTER TABLE `inv_uom_conversions` DISABLE KEYS */;
INSERT INTO `inv_uom_conversions` VALUES (1,2,2,1,24.0000,'2026-10-18 02:05:09','2026-10-18 02:05:09');
/*!40000 ALTER TABLE `inv_uom_conversions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_uoms`
--

DROP TABLE IF EXISTS `inv_uoms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_uoms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `decimal_precision` tinyint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_uoms_company_id_foreign` (`company_id`),
  CONSTRAINT `inv_uoms_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_uoms`
--

LOCK TABLES `inv_uoms` WRITE;
/*!40000 ALTER TABLE `inv_uoms` DISABLE KEYS */;
INSERT INTO `inv_uoms` VALUES (1,2,'Pieces','PCS',0,'2026-10-18 02:04:48','2026-10-18 02:04:48'),(2,2,'Carton','CTN',0,'2026-10-18 02:04:49','2026-10-18 02:04:49');
/*!40000 ALTER TABLE `inv_uoms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_variant_attributes`
--

DROP TABLE IF EXISTS `inv_variant_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_variant_attributes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_variant_attributes_company_id_foreign` (`company_id`),
  CONSTRAINT `inv_variant_attributes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_variant_attributes`
--

LOCK TABLES `inv_variant_attributes` WRITE;
/*!40000 ALTER TABLE `inv_variant_attributes` DISABLE KEYS */;
INSERT INTO `inv_variant_attributes` VALUES (1,2,'Size','2026-10-18 02:05:24','2026-10-18 02:05:24'),(2,2,'Color','2026-10-18 02:05:26','2026-10-18 02:05:26');
/*!40000 ALTER TABLE `inv_variant_attributes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_variant_values`
--

DROP TABLE IF EXISTS `inv_variant_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_variant_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attribute_id` bigint unsigned NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_variant_values_attribute_id_foreign` (`attribute_id`),
  CONSTRAINT `inv_variant_values_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `inv_variant_attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_variant_values`
--

LOCK TABLES `inv_variant_values` WRITE;
/*!40000 ALTER TABLE `inv_variant_values` DISABLE KEYS */;
INSERT INTO `inv_variant_values` VALUES (1,1,'S','2026-10-18 02:05:31','2026-10-18 02:05:31'),(2,1,'M','2026-10-18 02:05:32','2026-10-18 02:05:32'),(3,1,'L','2026-10-18 02:05:34','2026-10-18 02:05:34'),(4,2,'Red','2026-10-18 02:05:35','2026-10-18 02:05:35'),(5,2,'Blue','2026-10-18 02:05:36','2026-10-18 02:05:36');
/*!40000 ALTER TABLE `inv_variant_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_warehouse_transfer_lines`
--

DROP TABLE IF EXISTS `inv_warehouse_transfer_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_warehouse_transfer_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transfer_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `requested_qty` decimal(14,2) NOT NULL DEFAULT '0.00',
  `dispatched_qty` decimal(14,2) DEFAULT NULL,
  `received_qty` decimal(14,2) DEFAULT NULL,
  `discrepancy_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_warehouse_transfer_lines_transfer_id_foreign` (`transfer_id`),
  KEY `inv_warehouse_transfer_lines_item_id_foreign` (`item_id`),
  CONSTRAINT `inv_warehouse_transfer_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_warehouse_transfer_lines_transfer_id_foreign` FOREIGN KEY (`transfer_id`) REFERENCES `inv_warehouse_transfers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_warehouse_transfer_lines`
--

LOCK TABLES `inv_warehouse_transfer_lines` WRITE;
/*!40000 ALTER TABLE `inv_warehouse_transfer_lines` DISABLE KEYS */;
INSERT INTO `inv_warehouse_transfer_lines` VALUES (1,1,1,5.00,5.00,5.00,NULL,'2026-10-18 02:15:04','2026-10-18 02:15:37');
/*!40000 ALTER TABLE `inv_warehouse_transfer_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_warehouse_transfers`
--

DROP TABLE IF EXISTS `inv_warehouse_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_warehouse_transfers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `transfer_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_warehouse_id` bigint unsigned NOT NULL,
  `dest_warehouse_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'requested',
  `requested_by_employee_id` bigint unsigned DEFAULT NULL,
  `approved_by_employee_id` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `requested_at` datetime DEFAULT NULL,
  `dispatched_at` datetime DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_warehouse_transfers_transfer_number_unique` (`transfer_number`),
  KEY `inv_warehouse_transfers_company_id_foreign` (`company_id`),
  KEY `inv_warehouse_transfers_source_warehouse_id_foreign` (`source_warehouse_id`),
  KEY `inv_warehouse_transfers_dest_warehouse_id_foreign` (`dest_warehouse_id`),
  KEY `inv_warehouse_transfers_requested_by_employee_id_foreign` (`requested_by_employee_id`),
  KEY `inv_warehouse_transfers_approved_by_employee_id_foreign` (`approved_by_employee_id`),
  CONSTRAINT `inv_warehouse_transfers_approved_by_employee_id_foreign` FOREIGN KEY (`approved_by_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_warehouse_transfers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_warehouse_transfers_dest_warehouse_id_foreign` FOREIGN KEY (`dest_warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_warehouse_transfers_requested_by_employee_id_foreign` FOREIGN KEY (`requested_by_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_warehouse_transfers_source_warehouse_id_foreign` FOREIGN KEY (`source_warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_warehouse_transfers`
--

LOCK TABLES `inv_warehouse_transfers` WRITE;
/*!40000 ALTER TABLE `inv_warehouse_transfers` DISABLE KEYS */;
INSERT INTO `inv_warehouse_transfers` VALUES (1,2,'TRF-2026-00001',1,2,'received',4,4,NULL,'2026-10-18 07:45:04','2026-10-18 07:45:31','2026-10-18 07:45:37','2026-10-18 02:15:04','2026-10-18 02:15:37');
/*!40000 ALTER TABLE `inv_warehouse_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inv_warehouses`
--

DROP TABLE IF EXISTS `inv_warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_warehouses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `manager_employee_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `operating_hours` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'storage',
  `picking_strategy` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fifo',
  `default_receiving_zone_id` bigint unsigned DEFAULT NULL,
  `default_dispatch_zone_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_warehouses_company_id_foreign` (`company_id`),
  KEY `inv_warehouses_branch_id_foreign` (`branch_id`),
  KEY `inv_warehouses_manager_employee_id_foreign` (`manager_employee_id`),
  KEY `inv_warehouses_default_receiving_zone_id_foreign` (`default_receiving_zone_id`),
  KEY `inv_warehouses_default_dispatch_zone_id_foreign` (`default_dispatch_zone_id`),
  CONSTRAINT `inv_warehouses_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_warehouses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_warehouses_default_dispatch_zone_id_foreign` FOREIGN KEY (`default_dispatch_zone_id`) REFERENCES `inv_locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_warehouses_default_receiving_zone_id_foreign` FOREIGN KEY (`default_receiving_zone_id`) REFERENCES `inv_locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inv_warehouses_manager_employee_id_foreign` FOREIGN KEY (`manager_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_warehouses`
--

LOCK TABLES `inv_warehouses` WRITE;
/*!40000 ALTER TABLE `inv_warehouses` DISABLE KEYS */;
INSERT INTO `inv_warehouses` VALUES (1,2,NULL,NULL,'Main Warehouse','WH-01',NULL,NULL,'storage','fifo',NULL,NULL,1,'2026-10-18 01:53:27','2026-10-18 01:53:27'),(2,2,NULL,NULL,'Ranchi Warehouse',NULL,NULL,NULL,'storage','fefo',NULL,NULL,1,'2026-10-18 02:14:16','2026-10-18 02:14:16');
/*!40000 ALTER TABLE `inv_warehouses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_positions`
--

DROP TABLE IF EXISTS `job_positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_positions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employment_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `headcount` int unsigned NOT NULL DEFAULT '1',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `min_salary` decimal(14,2) DEFAULT NULL,
  `max_salary` decimal(14,2) DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_positions_company_id_foreign` (`company_id`),
  KEY `job_positions_department_id_foreign` (`department_id`),
  KEY `job_positions_branch_id_foreign` (`branch_id`),
  KEY `job_positions_status_index` (`status`),
  CONSTRAINT `job_positions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `job_positions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_positions_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_positions`
--

LOCK TABLES `job_positions` WRITE;
/*!40000 ALTER TABLE `job_positions` DISABLE KEYS */;
INSERT INTO `job_positions` VALUES (2,2,9,1,'Senior Software Engineer','full_time',2,'open',NULL,NULL,NULL,'2026-09-18 20:01:40','2026-09-18 20:01:40'),(3,2,11,1,'Sales Executive','full_time',1,'open',NULL,NULL,NULL,'2026-09-18 20:01:40','2026-09-18 20:01:40'),(4,2,12,5,'Accountant','full_time',1,'open',NULL,NULL,NULL,'2026-09-18 20:01:40','2026-09-18 20:01:40');
/*!40000 ALTER TABLE `job_positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kpis`
--

DROP TABLE IF EXISTS `kpis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kpis` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kpis_company_id_name_unique` (`company_id`,`name`),
  CONSTRAINT `kpis_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kpis`
--

LOCK TABLES `kpis` WRITE;
/*!40000 ALTER TABLE `kpis` DISABLE KEYS */;
/*!40000 ALTER TABLE `kpis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_balances`
--

DROP TABLE IF EXISTS `leave_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_balances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `leave_type_id` bigint unsigned NOT NULL,
  `year` smallint unsigned NOT NULL,
  `allocated` decimal(5,2) NOT NULL DEFAULT '0.00',
  `carried_forward` decimal(5,2) NOT NULL DEFAULT '0.00',
  `adjusted` decimal(5,2) NOT NULL DEFAULT '0.00',
  `used` decimal(5,2) NOT NULL DEFAULT '0.00',
  `encashed` decimal(5,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_balances_employee_id_leave_type_id_year_unique` (`employee_id`,`leave_type_id`,`year`),
  KEY `leave_balances_company_id_foreign` (`company_id`),
  KEY `leave_balances_leave_type_id_foreign` (`leave_type_id`),
  CONSTRAINT `leave_balances_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_balances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_balances_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_balances`
--

LOCK TABLES `leave_balances` WRITE;
/*!40000 ALTER TABLE `leave_balances` DISABLE KEYS */;
INSERT INTO `leave_balances` VALUES (5,2,16,3,2026,12.00,0.00,0.00,0.00,0.00,'2026-09-18 20:01:41','2026-09-18 20:01:41'),(6,2,16,4,2026,8.00,0.00,0.00,0.00,0.00,'2026-09-18 20:01:41','2026-09-18 20:01:41'),(7,2,16,5,2026,15.00,0.00,0.00,0.00,0.00,'2026-09-18 20:01:41','2026-09-18 20:01:41'),(8,2,17,3,2026,12.00,0.00,0.00,0.00,1.00,'2026-09-18 20:01:41','2026-09-28 00:52:11'),(9,2,17,4,2026,8.00,0.00,0.00,0.00,0.00,'2026-09-18 20:01:41','2026-09-18 20:01:41'),(10,2,17,5,2026,15.00,0.00,0.00,0.00,0.00,'2026-09-18 20:01:41','2026-09-18 20:01:41'),(11,2,18,3,2026,12.00,0.00,0.00,0.00,0.00,'2026-09-18 20:01:41','2026-09-18 20:01:41'),(12,2,18,4,2026,8.00,0.00,0.00,0.00,0.00,'2026-09-18 20:01:41','2026-09-18 20:01:41'),(13,2,18,5,2026,15.00,0.00,0.00,0.00,0.00,'2026-09-18 20:01:41','2026-09-18 20:01:41'),(14,2,19,3,2026,12.00,0.00,0.00,0.00,0.00,'2026-09-18 20:01:41','2026-09-18 20:01:41'),(15,2,19,4,2026,8.00,0.00,0.00,1.00,0.00,'2026-09-18 20:01:41','2026-09-18 20:01:42'),(16,2,19,5,2026,15.00,0.00,0.00,0.00,0.00,'2026-09-18 20:01:41','2026-09-18 20:01:41'),(17,2,20,6,2026,12.00,0.00,0.00,0.00,0.00,'2026-09-18 20:01:41','2026-09-18 20:01:41'),(18,2,20,7,2026,8.00,0.00,0.00,0.00,0.00,'2026-09-18 20:01:41','2026-09-18 20:01:41'),(19,2,20,8,2026,15.00,0.00,0.00,0.00,0.00,'2026-09-18 20:01:41','2026-09-18 20:01:41'),(40,2,4,3,2026,12.00,0.00,0.00,0.00,0.00,'2026-09-28 00:25:37','2026-09-28 00:25:37'),(41,2,4,4,2026,8.00,0.00,0.00,0.00,0.00,'2026-09-28 00:25:37','2026-09-28 00:25:37'),(42,2,4,5,2026,15.00,0.00,0.00,0.00,0.00,'2026-09-28 00:25:37','2026-09-28 00:25:37');
/*!40000 ALTER TABLE `leave_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_encashments`
--

DROP TABLE IF EXISTS `leave_encashments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_encashments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `leave_type_id` bigint unsigned NOT NULL,
  `year` smallint unsigned NOT NULL,
  `days` decimal(5,2) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_encashments_employee_id_foreign` (`employee_id`),
  KEY `leave_encashments_leave_type_id_foreign` (`leave_type_id`),
  KEY `leave_encashments_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `leave_encashments_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `leave_encashments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_encashments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_encashments_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_encashments_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_encashments`
--

LOCK TABLES `leave_encashments` WRITE;
/*!40000 ALTER TABLE `leave_encashments` DISABLE KEYS */;
INSERT INTO `leave_encashments` VALUES (1,2,17,3,2026,1.00,NULL,'Full & final settlement','approved',1,'2026-09-28 00:52:11',NULL,'2026-09-28 00:48:43','2026-09-28 00:52:11');
/*!40000 ALTER TABLE `leave_encashments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_policy_settings`
--

DROP TABLE IF EXISTS `leave_policy_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_policy_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `exclude_weekends` tinyint(1) NOT NULL DEFAULT '1',
  `exclude_holidays` tinyint(1) NOT NULL DEFAULT '1',
  `sandwich_rule` tinyint(1) NOT NULL DEFAULT '0',
  `allow_half_day` tinyint(1) NOT NULL DEFAULT '1',
  `min_notice_days` smallint unsigned NOT NULL DEFAULT '0',
  `max_backdated_days` smallint unsigned DEFAULT NULL,
  `max_consecutive_days` smallint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_policy_settings_company_id_branch_id_unique` (`company_id`,`branch_id`),
  KEY `leave_policy_settings_branch_id_foreign` (`branch_id`),
  CONSTRAINT `leave_policy_settings_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_policy_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_policy_settings`
--

LOCK TABLES `leave_policy_settings` WRITE;
/*!40000 ALTER TABLE `leave_policy_settings` DISABLE KEYS */;
INSERT INTO `leave_policy_settings` VALUES (2,2,1,1,1,0,1,0,30,NULL,'2026-10-17 03:07:13','2026-10-17 03:07:13');
/*!40000 ALTER TABLE `leave_policy_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `leave_type_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days` decimal(5,2) NOT NULL,
  `half_day` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_requests_employee_id_foreign` (`employee_id`),
  KEY `leave_requests_leave_type_id_foreign` (`leave_type_id`),
  KEY `leave_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `leave_requests_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `leave_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
INSERT INTO `leave_requests` VALUES (8,2,18,3,'2026-09-24','2026-09-25',2.00,NULL,'Family function out of town.','pending',NULL,NULL,NULL,'2026-09-18 20:01:41','2026-09-18 20:01:41'),(9,2,19,4,'2026-09-09','2026-09-09',1.00,NULL,'Fever, resting at home.','approved',NULL,'2026-09-09 20:01:42',NULL,'2026-09-18 20:01:42','2026-09-18 20:01:42'),(10,2,20,6,'2026-10-09','2026-10-11',3.00,NULL,'Personal travel.','rejected',NULL,'2026-09-17 20:01:42','Insufficient notice for peak month-end.','2026-09-18 20:01:42','2026-09-18 20:01:42'),(84,2,4,4,'2026-10-12','2026-10-12',1.00,NULL,'Feeling unwell','pending',NULL,NULL,NULL,'2026-10-09 22:29:29','2026-10-09 22:29:29');
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `days_per_year` decimal(5,2) NOT NULL DEFAULT '0.00',
  `is_paid` tinyint(1) NOT NULL DEFAULT '1',
  `carry_forward` tinyint(1) NOT NULL DEFAULT '0',
  `max_carry_forward_days` decimal(5,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_types_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `leave_types_branch_id_foreign` (`branch_id`),
  CONSTRAINT `leave_types_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_types`
--

LOCK TABLES `leave_types` WRITE;
/*!40000 ALTER TABLE `leave_types` DISABLE KEYS */;
INSERT INTO `leave_types` VALUES (3,2,1,'Casual Leave','CL',12.00,1,0,NULL,1,'2026-09-18 20:01:40','2026-09-18 20:01:40'),(4,2,1,'Sick Leave','SL',8.00,1,0,NULL,1,'2026-09-18 20:01:40','2026-09-18 20:01:40'),(5,2,1,'Earned Leave','EL',15.00,1,1,NULL,1,'2026-09-18 20:01:40','2026-09-18 20:01:40'),(6,2,5,'Casual Leave','CL',12.00,1,0,NULL,1,'2026-09-18 20:01:40','2026-09-18 20:01:40'),(7,2,5,'Sick Leave','SL',8.00,1,0,NULL,1,'2026-09-18 20:01:40','2026-09-18 20:01:40'),(8,2,5,'Earned Leave','EL',15.00,1,1,NULL,1,'2026-09-18 20:01:40','2026-09-18 20:01:40');
/*!40000 ALTER TABLE `leave_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_requests`
--

DROP TABLE IF EXISTS `loan_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `loan_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `principal_amount` decimal(12,2) NOT NULL,
  `monthly_installment` decimal(12,2) NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_requests_employee_id_foreign` (`employee_id`),
  KEY `loan_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `loan_requests_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `loan_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_requests`
--

LOCK TABLES `loan_requests` WRITE;
/*!40000 ALTER TABLE `loan_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `loan_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `principal_amount` decimal(12,2) NOT NULL,
  `monthly_installment` decimal(12,2) NOT NULL,
  `outstanding_amount` decimal(12,2) NOT NULL,
  `disbursed_date` date NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loans_employee_id_foreign` (`employee_id`),
  KEY `loans_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `loans_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loans_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loans`
--

LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
INSERT INTO `loans` VALUES (11,2,17,'Personal loan',10000.00,5000.00,10000.00,'2026-10-06','active',NULL,'2026-10-06 07:33:15','2026-10-06 07:33:15');
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=158 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2019_09_15_000010_create_tenants_table',1),(5,'2019_09_15_000020_create_domains_table',1),(6,'2026_08_02_054905_add_is_super_admin_to_users_table',1),(7,'2026_08_02_060732_add_erp_profile_fields_to_users_table',1),(8,'2026_08_02_093210_create_permission_tables',1),(9,'2026_08_02_101302_add_performance_indexes_to_users_table',1),(10,'2026_08_02_113824_create_companies_table',1),(11,'2026_08_02_113825_create_departments_table',1),(12,'2026_08_02_113826_create_branches_table',1),(13,'2026_08_02_113827_create_locations_table',1),(14,'2026_08_02_113828_create_business_units_table',1),(15,'2026_08_02_113829_create_cost_centers_table',1),(16,'2026_08_02_115259_drop_locations_table',1),(17,'2026_08_02_115339_add_timezone_to_companies_table',1),(18,'2026_08_02_115340_add_is_default_to_branches_table',1),(19,'2026_08_03_084739_add_branding_fields_to_companies_table',1),(20,'2026_08_03_084740_create_working_hours_table',1),(21,'2026_08_03_084741_create_holidays_table',1),(22,'2026_08_03_100527_create_workflows_table',1),(23,'2026_08_03_100528_create_workflow_steps_table',1),(24,'2026_08_03_100529_create_approval_rules_table',1),(25,'2026_08_03_100530_create_escalation_rules_table',1),(26,'2026_08_03_100531_create_automation_rules_table',1),(27,'2026_08_03_100532_create_email_templates_table',1),(28,'2026_08_03_100533_create_sms_templates_table',1),(29,'2026_08_03_100534_create_whatsapp_templates_table',1),(30,'2026_08_03_100535_create_notification_rules_table',1),(31,'2026_08_03_104234_create_employees_table',1),(32,'2026_08_03_104235_create_employee_documents_table',1),(33,'2026_08_03_104236_create_employee_assets_table',1),(34,'2026_08_03_104237_create_employee_family_members_table',1),(35,'2026_08_03_104238_create_employee_educations_table',1),(36,'2026_08_03_104239_create_employee_experiences_table',1),(37,'2026_08_03_104240_create_employee_skills_table',1),(38,'2026_08_03_104241_create_employee_salaries_table',1),(39,'2026_08_03_104242_create_employee_promotions_table',1),(40,'2026_08_03_104243_create_employee_transfers_table',1),(41,'2026_08_05_114711_create_branch_user_table',1),(42,'2026_08_05_114712_add_branch_id_to_departments_table',1),(43,'2026_08_05_120745_add_manager_id_to_employees_table',1),(44,'2026_08_15_092315_create_job_positions_table',1),(45,'2026_08_15_092316_create_candidates_table',1),(46,'2026_08_15_092317_create_resume_bank_entries_table',1),(47,'2026_08_15_092318_create_interviews_table',1),(48,'2026_08_15_092319_create_interview_feedback_table',1),(49,'2026_08_15_092320_create_offer_letters_table',1),(50,'2026_08_19_120055_create_onboarding_tasks_table',1),(51,'2026_08_19_120056_create_probation_records_table',1),(52,'2026_08_20_063727_create_attendance_records_table',1),(53,'2026_08_20_063728_create_regularization_requests_table',1),(54,'2026_08_20_063729_create_wfh_requests_table',1),(55,'2026_08_20_063730_create_overtime_requests_table',1),(56,'2026_08_20_121835_create_leave_types_table',1),(57,'2026_08_20_121836_create_leave_balances_table',1),(58,'2026_08_20_121837_create_leave_requests_table',1),(59,'2026_08_20_121838_create_comp_offs_table',1),(60,'2026_08_20_121839_create_leave_encashments_table',1),(61,'2026_08_20_125611_create_shifts_table',1),(62,'2026_08_20_125612_create_shift_rosters_table',1),(63,'2026_08_20_125613_create_shift_swap_requests_table',1),(64,'2026_08_20_125614_create_overtime_rules_table',1),(65,'2026_08_20_131033_create_salary_components_table',1),(66,'2026_08_20_131034_create_salary_structures_table',1),(67,'2026_08_20_131035_create_salary_structure_items_table',1),(68,'2026_08_20_131036_create_bonuses_table',1),(69,'2026_08_20_131037_create_incentives_table',1),(70,'2026_08_20_131038_create_deductions_table',1),(71,'2026_08_20_131039_create_loans_table',1),(72,'2026_08_20_131040_create_tax_declarations_table',1),(73,'2026_08_20_131041_create_pf_settings_table',1),(74,'2026_08_20_131042_create_esi_settings_table',1),(75,'2026_08_20_131043_create_payroll_runs_table',1),(76,'2026_08_20_131044_create_payslips_table',1),(77,'2026_08_20_131045_create_payslip_items_table',1),(78,'2026_08_20_134130_create_appraisal_cycles_table',1),(79,'2026_08_20_134131_create_goals_table',1),(80,'2026_08_20_134132_create_kpis_table',1),(81,'2026_08_20_134133_create_employee_kpis_table',1),(82,'2026_08_20_134134_create_appraisals_table',1),(83,'2026_08_20_134135_create_feedback_360s_table',1),(84,'2026_08_20_134136_create_increments_table',1),(85,'2026_08_20_155929_create_trainers_table',1),(86,'2026_08_20_155930_create_courses_table',1),(87,'2026_08_20_155931_create_training_sessions_table',1),(88,'2026_08_20_155932_create_training_enrollments_table',1),(89,'2026_08_20_155933_create_certifications_table',1),(90,'2026_08_20_155934_create_assessments_table',1),(91,'2026_08_20_155935_create_assessment_results_table',1),(92,'2026_08_20_170127_add_asset_details_to_employee_assets_table',1),(93,'2026_08_20_191910_create_travel_requests_table',1),(94,'2026_08_20_191911_create_travel_bookings_table',1),(95,'2026_08_20_191913_create_travel_expenses_table',1),(96,'2026_08_20_191914_create_travel_settlements_table',1),(97,'2026_08_20_191920_create_expense_categories_table',1),(98,'2026_08_20_191921_create_expense_claims_table',1),(99,'2026_09_16_014912_add_company_id_and_expiry_date_to_employee_documents_table',1),(100,'2026_09_16_031940_create_resignations_table',1),(101,'2026_09_16_031942_create_separation_clearances_table',1),(102,'2026_09_16_031943_create_exit_interviews_table',1),(103,'2026_09_16_031944_create_final_settlements_table',1),(104,'2026_09_17_115043_create_loan_requests_table',1),(105,'2026_09_17_115045_create_promotion_requests_table',1),(106,'2026_09_17_115046_create_transfer_requests_table',1),(107,'2026_09_17_120047_create_hr_policies_table',1),(108,'2026_09_17_120050_create_attendance_rule_settings_table',1),(109,'2026_09_17_120052_create_tax_slabs_table',1),(110,'2026_09_17_120053_create_document_templates_table',1),(111,'2026_09_18_031400_add_branch_id_to_hrm_settings_tables',1),(112,'2026_09_18_040000_add_branch_id_to_recruitment_training_payroll_tables',1),(113,'2026_09_18_050000_add_app_access_to_users_table',1),(114,'2026_09_19_010000_create_notifications_table',1),(115,'2026_09_20_010000_add_tenancy_to_users_table',1),(116,'2026_09_20_010100_add_app_access_to_companies_table',1),(117,'2026_09_20_010200_add_company_id_to_permission_tables',1),(118,'2026_09_22_032308_add_invitation_accepted_at_to_users_table',1),(119,'2026_09_25_124228_add_max_users_to_companies_table',1),(120,'2026_09_28_120000_create_leave_policy_settings_table',2),(121,'2026_10_07_100000_create_mail_setup_tables',3),(122,'2026_10_07_110000_add_smtp_settings_to_company_mail_accounts_table',4),(123,'2026_10_07_090137_add_data_scope_to_roles_table',5),(124,'2026_10_07_120000_create_approval_levels_tables',6),(125,'2026_10_17_120000_create_crm_tables',7),(126,'2026_10_17_130000_create_crm_accounts_contacts_tables',8),(127,'2026_10_17_140000_create_crm_pipeline_tables',9),(128,'2026_10_17_150000_create_crm_quotes_sales_tables',10),(129,'2026_10_18_090000_create_crm_activities_notes_tables',11),(130,'2026_10_18_100000_create_crm_marketing_tables',12),(131,'2026_10_18_110000_create_crm_service_tables',13),(132,'2026_10_18_120000_create_crm_reporting_tables',14),(133,'2026_10_18_130000_create_crm_automation_tables',15),(134,'2026_10_18_140000_create_crm_configuration_tables',16),(135,'2026_10_18_150000_add_lead_management_fields',17),(136,'2026_10_18_160000_add_accounts_opportunities_activities_fields',18),(137,'2026_10_18_170000_create_crm_workflow_executions_table',19),(138,'2026_10_18_180000_create_crm_deal_approval_tables',20),(139,'2026_10_18_190000_add_pivot_chart_fields_to_custom_reports',21),(140,'2026_10_18_200000_add_multi_branch_scope_to_crm',22),(141,'2026_10_19_100000_create_inventory_overview_tables',23),(142,'2026_10_19_110000_create_inventory_product_master_tables',24),(143,'2026_10_19_110001_add_description_to_inv_items',25),(144,'2026_10_19_120000_create_inventory_warehouse_management_tables',26),(145,'2026_10_19_130000_create_inventory_inbound_tables',27),(146,'2026_10_19_140000_create_inventory_outbound_tables',28),(147,'2026_10_19_150000_create_inventory_stock_operations_tables',29),(148,'2026_10_19_160000_create_inventory_planning_valuation_tables',30),(149,'2026_10_19_170000_create_inventory_configuration_tables',31),(150,'2026_10_19_180000_create_sales_core_tables',32),(151,'2026_10_19_190000_create_sales_quotation_order_extensions',33),(152,'2026_10_19_200000_create_sales_fulfilment_bridge',34),(153,'2026_10_19_210000_add_recurring_billing_to_crm_contracts',35),(154,'2026_10_19_220000_create_sales_invoice_requests',36),(155,'2026_10_19_230000_add_incentive_to_crm_quotas',37),(156,'2026_10_19_240000_create_sales_returns_complaints_bridge',38),(157,'2026_10_22_000000_create_sales_settings',39);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`company_id`,`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  KEY `model_has_permissions_permission_id_foreign` (`permission_id`),
  KEY `model_has_permissions_team_foreign_key_index` (`company_id`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
INSERT INTO `model_has_permissions` VALUES (7,'App\\Models\\User',7,2),(8,'App\\Models\\User',7,2),(9,'App\\Models\\User',7,2),(10,'App\\Models\\User',7,2),(67,'App\\Models\\User',7,2),(68,'App\\Models\\User',7,2),(69,'App\\Models\\User',7,2),(70,'App\\Models\\User',7,2),(201,'App\\Models\\User',7,2),(202,'App\\Models\\User',7,2),(203,'App\\Models\\User',7,2),(204,'App\\Models\\User',7,2);
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`company_id`,`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  KEY `model_has_roles_role_id_foreign` (`role_id`),
  KEY `model_has_roles_team_foreign_key_index` (`company_id`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1,2),(8,'App\\Models\\User',21,2),(9,'App\\Models\\User',22,2),(10,'App\\Models\\User',23,2),(10,'App\\Models\\User',24,2),(10,'App\\Models\\User',25,2),(11,'App\\Models\\User',27,3),(12,'App\\Models\\User',28,4),(13,'App\\Models\\User',29,5),(14,'App\\Models\\User',30,6),(15,'App\\Models\\User',31,7);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_rules`
--

DROP TABLE IF EXISTS `notification_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `via_email` tinyint(1) NOT NULL DEFAULT '0',
  `via_sms` tinyint(1) NOT NULL DEFAULT '0',
  `via_whatsapp` tinyint(1) NOT NULL DEFAULT '0',
  `via_in_app` tinyint(1) NOT NULL DEFAULT '1',
  `email_template_id` bigint unsigned DEFAULT NULL,
  `sms_template_id` bigint unsigned DEFAULT NULL,
  `whatsapp_template_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notification_rules_company_id_foreign` (`company_id`),
  KEY `notification_rules_email_template_id_foreign` (`email_template_id`),
  KEY `notification_rules_sms_template_id_foreign` (`sms_template_id`),
  KEY `notification_rules_whatsapp_template_id_foreign` (`whatsapp_template_id`),
  CONSTRAINT `notification_rules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notification_rules_email_template_id_foreign` FOREIGN KEY (`email_template_id`) REFERENCES `email_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notification_rules_sms_template_id_foreign` FOREIGN KEY (`sms_template_id`) REFERENCES `sms_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notification_rules_whatsapp_template_id_foreign` FOREIGN KEY (`whatsapp_template_id`) REFERENCES `whatsapp_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_rules`
--

LOCK TABLES `notification_rules` WRITE;
/*!40000 ALTER TABLE `notification_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES ('1f37b68a-5da2-4a4e-83a7-e6fe70aeb26b','App\\Notifications\\SimpleNotification','App\\Models\\User',1,'{\"title\":\"New leave request\",\"message\":\"Vivek Kumar Pandey requested 1 day of Sick Leave from Oct 12.\",\"url\":\"http:\\/\\/127.0.0.1:8000\\/hrm\\/leave-requests\\/approval\",\"icon\":\"ti ti-calendar-event\",\"app\":\"hrm\"}',NULL,'2026-10-09 22:29:29','2026-10-09 22:29:29'),('2fc74fcb-c4f0-460f-9c76-dae761f06615','App\\Notifications\\SimpleNotification','App\\Models\\User',21,'{\"title\":\"New expense claim\",\"message\":\"Kavya Reddy submitted a claim for 2,500.00.\",\"url\":\"http:\\/\\/localhost:8000\\/hrm\\/expense-approval\",\"icon\":\"ti ti-receipt-2\",\"app\":\"hrm\"}',NULL,'2026-09-18 20:01:42','2026-09-18 20:01:42'),('300f6daa-0dcd-4bee-8988-9d2bb27f33cc','App\\Notifications\\SimpleNotification','App\\Models\\User',25,'{\"title\":\"Leave request rejected\",\"message\":\"Your leave request starting Oct 9, 2026 was rejected. Reason: Insufficient notice for peak month-end.\",\"url\":\"http:\\/\\/localhost:8000\\/hrm\\/my-leave\",\"icon\":\"ti ti-circle-x\",\"app\":\"hrm\"}',NULL,'2026-09-18 20:01:42','2026-09-18 20:01:42'),('3da3d060-2047-427f-9bf0-8cd7afa092b1','App\\Notifications\\SimpleNotification','App\\Models\\User',1,'{\"title\":\"New travel request\",\"message\":\"Vivek Kumar Pandey asked to travel from Delhi to Mumbai, Oct 1 \\u2013 Oct 1, 2026.\",\"url\":\"http:\\/\\/127.0.0.1:8000\\/hrm\\/travel-approval\",\"icon\":\"ti ti-plane\",\"app\":\"hrm\"}','2026-10-09 23:31:04','2026-10-09 23:30:44','2026-10-09 23:31:04'),('53b2b70c-078b-465f-8932-89a23e58f106','App\\Notifications\\SimpleNotification','App\\Models\\User',24,'{\"title\":\"Leave request approved\",\"message\":\"Your leave request for 1.00 day(s) starting Sep 9, 2026 was approved.\",\"url\":\"http:\\/\\/localhost:8000\\/hrm\\/my-leave\",\"icon\":\"ti ti-circle-check\",\"app\":\"hrm\"}',NULL,'2026-09-18 20:01:42','2026-09-18 20:01:42'),('580ec45d-a372-4e36-afa6-63cbf14fb787','App\\Notifications\\SimpleNotification','App\\Models\\User',1,'{\"title\":\"New leave request\",\"message\":\"Kavya Reddy requested 2.00 day(s) of leave.\",\"url\":\"http:\\/\\/localhost:8000\\/hrm\\/leave-requests\\/approval\",\"icon\":\"ti ti-calendar-event\",\"app\":\"hrm\"}',NULL,'2026-09-18 20:01:42','2026-09-18 20:01:42'),('66e34954-61ee-48ae-9e3e-9ee883203593','App\\Notifications\\SimpleNotification','App\\Models\\User',21,'{\"title\":\"New leave request\",\"message\":\"Vivek Kumar Pandey requested 1 day of Sick Leave from Oct 12.\",\"url\":\"http:\\/\\/127.0.0.1:8000\\/hrm\\/leave-requests\\/approval\",\"icon\":\"ti ti-calendar-event\",\"app\":\"hrm\"}',NULL,'2026-10-09 22:29:29','2026-10-09 22:29:29'),('900bea9e-997b-41a8-aee3-d1f3402890d8','App\\Notifications\\SimpleNotification','App\\Models\\User',21,'{\"title\":\"New leave request\",\"message\":\"Kavya Reddy requested 2.00 day(s) of leave.\",\"url\":\"http:\\/\\/localhost:8000\\/hrm\\/leave-requests\\/approval\",\"icon\":\"ti ti-calendar-event\",\"app\":\"hrm\"}',NULL,'2026-09-18 20:01:42','2026-09-18 20:01:42'),('9d805489-5008-40d9-a2be-8cebfdace7e6','App\\Notifications\\SimpleNotification','App\\Models\\User',21,'{\"title\":\"New travel request\",\"message\":\"Vivek Kumar Pandey asked to travel from Delhi to Mumbai, Oct 1 \\u2013 Oct 1, 2026.\",\"url\":\"http:\\/\\/127.0.0.1:8000\\/hrm\\/travel-approval\",\"icon\":\"ti ti-plane\",\"app\":\"hrm\"}',NULL,'2026-10-09 23:30:44','2026-10-09 23:30:44'),('ceb5c093-7441-47c3-8cbe-73f37bd411cc','App\\Notifications\\SimpleNotification','App\\Models\\User',24,'{\"title\":\"Expense claim approved\",\"message\":\"Your claim for 1,200.00 was approved.\",\"url\":\"http:\\/\\/localhost:8000\\/hrm\\/my-expenses\",\"icon\":\"ti ti-circle-check\",\"app\":\"hrm\"}',NULL,'2026-09-18 20:01:42','2026-09-18 20:01:42'),('e6ec175b-00cb-4598-88a4-ee719da7cce3','App\\Notifications\\SimpleNotification','App\\Models\\User',1,'{\"title\":\"New expense claim\",\"message\":\"Kavya Reddy submitted a claim for 2,500.00.\",\"url\":\"http:\\/\\/localhost:8000\\/hrm\\/expense-approval\",\"icon\":\"ti ti-receipt-2\",\"app\":\"hrm\"}','2026-09-18 20:03:15','2026-09-18 20:01:42','2026-09-18 20:03:15');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `offer_letters`
--

DROP TABLE IF EXISTS `offer_letters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `offer_letters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `candidate_id` bigint unsigned NOT NULL,
  `job_position_id` bigint unsigned DEFAULT NULL,
  `salary_offered` decimal(14,2) DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `offer_date` date DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `offer_letters_candidate_id_foreign` (`candidate_id`),
  KEY `offer_letters_job_position_id_foreign` (`job_position_id`),
  KEY `offer_letters_status_index` (`status`),
  CONSTRAINT `offer_letters_candidate_id_foreign` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `offer_letters_job_position_id_foreign` FOREIGN KEY (`job_position_id`) REFERENCES `job_positions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offer_letters`
--

LOCK TABLES `offer_letters` WRITE;
/*!40000 ALTER TABLE `offer_letters` DISABLE KEYS */;
/*!40000 ALTER TABLE `offer_letters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `onboarding_tasks`
--

DROP TABLE IF EXISTS `onboarding_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `onboarding_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `assigned_role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_to_user_id` bigint unsigned DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT '0',
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `onboarding_tasks_company_id_foreign` (`company_id`),
  KEY `onboarding_tasks_employee_id_foreign` (`employee_id`),
  KEY `onboarding_tasks_assigned_to_user_id_foreign` (`assigned_to_user_id`),
  KEY `onboarding_tasks_completed_by_user_id_foreign` (`completed_by_user_id`),
  KEY `onboarding_tasks_category_index` (`category`),
  KEY `onboarding_tasks_assigned_role_assigned_to_user_id_index` (`assigned_role`,`assigned_to_user_id`),
  CONSTRAINT `onboarding_tasks_assigned_to_user_id_foreign` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `onboarding_tasks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `onboarding_tasks_completed_by_user_id_foreign` FOREIGN KEY (`completed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `onboarding_tasks_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `onboarding_tasks`
--

LOCK TABLES `onboarding_tasks` WRITE;
/*!40000 ALTER TABLE `onboarding_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `onboarding_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overtime_requests`
--

DROP TABLE IF EXISTS `overtime_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overtime_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `hours` decimal(4,2) NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `overtime_requests_employee_id_foreign` (`employee_id`),
  KEY `overtime_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `overtime_requests_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `overtime_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `overtime_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `overtime_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overtime_requests`
--

LOCK TABLES `overtime_requests` WRITE;
/*!40000 ALTER TABLE `overtime_requests` DISABLE KEYS */;
INSERT INTO `overtime_requests` VALUES (17,2,16,'2026-10-06',3.00,'Project deadline','approved',1,'2026-10-17 01:08:00',NULL,'2026-10-07 02:21:08','2026-10-17 01:08:00');
/*!40000 ALTER TABLE `overtime_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overtime_rules`
--

DROP TABLE IF EXISTS `overtime_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overtime_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `daily_threshold_hours` decimal(4,2) NOT NULL,
  `multiplier` decimal(4,2) NOT NULL DEFAULT '1.50',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `overtime_rules_company_id_foreign` (`company_id`),
  KEY `overtime_rules_shift_id_foreign` (`shift_id`),
  KEY `overtime_rules_branch_id_foreign` (`branch_id`),
  CONSTRAINT `overtime_rules_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `overtime_rules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `overtime_rules_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overtime_rules`
--

LOCK TABLES `overtime_rules` WRITE;
/*!40000 ALTER TABLE `overtime_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `overtime_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_runs`
--

DROP TABLE IF EXISTS `payroll_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `month` tinyint unsigned NOT NULL,
  `year` smallint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'processed',
  `run_date` date NOT NULL,
  `processed_by_user_id` bigint unsigned DEFAULT NULL,
  `employee_count` int unsigned NOT NULL DEFAULT '0',
  `total_gross` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_deductions` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_net` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_runs_company_id_branch_id_month_year_unique` (`company_id`,`branch_id`,`month`,`year`),
  KEY `payroll_runs_processed_by_user_id_foreign` (`processed_by_user_id`),
  KEY `payroll_runs_branch_id_foreign` (`branch_id`),
  CONSTRAINT `payroll_runs_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_runs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_runs_processed_by_user_id_foreign` FOREIGN KEY (`processed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_runs`
--

LOCK TABLES `payroll_runs` WRITE;
/*!40000 ALTER TABLE `payroll_runs` DISABLE KEYS */;
INSERT INTO `payroll_runs` VALUES (12,2,1,10,2026,'processed','2026-10-06',1,5,283500.00,18041.25,265458.75,'2026-10-06 07:00:47','2026-10-06 07:00:47');
/*!40000 ALTER TABLE `payroll_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payslip_items`
--

DROP TABLE IF EXISTS `payslip_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payslip_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payslip_id` bigint unsigned NOT NULL,
  `component_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payslip_items_payslip_id_foreign` (`payslip_id`),
  CONSTRAINT `payslip_items_payslip_id_foreign` FOREIGN KEY (`payslip_id`) REFERENCES `payslips` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=805 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payslip_items`
--

LOCK TABLES `payslip_items` WRITE;
/*!40000 ALTER TABLE `payslip_items` DISABLE KEYS */;
INSERT INTO `payslip_items` VALUES (53,18,'Basic','earning',1500.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(54,18,'House Rent Allowance','earning',4000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(55,18,'Conveyance Allowance','earning',2000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(56,18,'Medical Allowance','earning',2000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(57,18,'Special Allowance','earning',1000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(58,18,'PF (Employee)','deduction',180.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(59,18,'ESI (Employee)','deduction',78.75,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(60,19,'Basic','earning',60000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(61,19,'House Rent Allowance','earning',80000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(62,19,'Conveyance Allowance','earning',3000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(63,19,'Medical Allowance','earning',4000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(64,19,'Special Allowance','earning',4000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(65,19,'PF (Employee)','deduction',7200.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(66,20,'Basic','earning',25000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(67,20,'House Rent Allowance','earning',5000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(68,20,'Conveyance Allowance','earning',2000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(69,20,'Medical Allowance','earning',3000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(70,20,'Special Allowance','earning',2000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(71,20,'PF (Employee)','deduction',3000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(72,21,'Basic','earning',50000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(73,21,'House Rent Allowance','earning',8000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(74,21,'Conveyance Allowance','earning',3000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(75,21,'Medical Allowance','earning',3000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(76,21,'Special Allowance','earning',2000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(77,21,'PF (Employee)','deduction',6000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(78,22,'Basic','earning',12000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(79,22,'House Rent Allowance','earning',3000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(80,22,'Conveyance Allowance','earning',1000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(81,22,'Medical Allowance','earning',2000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(82,22,'Special Allowance','earning',1000.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(83,22,'PF (Employee)','deduction',1440.00,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(84,22,'ESI (Employee)','deduction',142.50,'2026-10-06 07:00:47','2026-10-06 07:00:47');
/*!40000 ALTER TABLE `payslip_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payslips`
--

DROP TABLE IF EXISTS `payslips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payslips` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `payroll_run_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `gross_earnings` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pf_employee_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pf_employer_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `esi_employee_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `esi_employer_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `loan_deduction_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `other_deduction_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_deductions` decimal(12,2) NOT NULL DEFAULT '0.00',
  `net_pay` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'generated',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payslips_payroll_run_id_employee_id_unique` (`payroll_run_id`,`employee_id`),
  KEY `payslips_company_id_foreign` (`company_id`),
  KEY `payslips_employee_id_foreign` (`employee_id`),
  CONSTRAINT `payslips_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payslips_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payslips_payroll_run_id_foreign` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=155 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payslips`
--

LOCK TABLES `payslips` WRITE;
/*!40000 ALTER TABLE `payslips` DISABLE KEYS */;
INSERT INTO `payslips` VALUES (18,2,12,4,10500.00,180.00,180.00,78.75,341.25,0.00,0.00,0.00,258.75,10241.25,'generated',NULL,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(19,2,12,16,151000.00,7200.00,7200.00,0.00,0.00,0.00,0.00,0.00,7200.00,143800.00,'generated',NULL,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(20,2,12,17,37000.00,3000.00,3000.00,0.00,0.00,0.00,0.00,0.00,3000.00,34000.00,'generated',NULL,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(21,2,12,18,66000.00,6000.00,6000.00,0.00,0.00,0.00,0.00,0.00,6000.00,60000.00,'generated',NULL,'2026-10-06 07:00:47','2026-10-06 07:00:47'),(22,2,12,19,19000.00,1440.00,1440.00,142.50,617.50,0.00,0.00,0.00,1582.50,17417.50,'generated',NULL,'2026-10-06 07:00:47','2026-10-06 07:00:47');
/*!40000 ALTER TABLE `payslips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=969 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'view_role','web','2026-08-02 04:03:09','2026-08-02 04:03:09'),(2,'view_any_role','web','2026-08-02 04:03:09','2026-08-02 04:03:09'),(3,'create_role','web','2026-08-02 04:03:09','2026-08-02 04:03:09'),(4,'update_role','web','2026-08-02 04:03:09','2026-08-02 04:03:09'),(5,'delete_role','web','2026-08-02 04:03:09','2026-08-02 04:03:09'),(6,'delete_any_role','web','2026-08-02 04:03:09','2026-08-02 04:03:09'),(7,'view_user','web','2026-08-02 04:03:09','2026-08-02 04:03:09'),(8,'view_any_user','web','2026-08-02 04:03:09','2026-08-02 04:03:09'),(9,'create_user','web','2026-08-02 04:03:09','2026-08-02 04:03:09'),(10,'update_user','web','2026-08-02 04:03:09','2026-08-02 04:03:09'),(11,'restore_user','web','2026-08-02 04:03:09','2026-08-02 04:03:09'),(12,'restore_any_user','web','2026-08-02 04:03:09','2026-08-02 04:03:09'),(13,'replicate_user','web','2026-08-02 04:03:09','2026-08-02 04:03:09'),(14,'reorder_user','web','2026-08-02 04:03:09','2026-08-02 04:03:09'),(15,'delete_user','web','2026-08-02 04:03:09','2026-08-02 04:03:09'),(16,'delete_any_user','web','2026-08-02 04:03:09','2026-08-02 04:03:09'),(17,'force_delete_user','web','2026-08-02 04:03:09','2026-08-02 04:03:09'),(18,'force_delete_any_user','web','2026-08-02 04:03:09','2026-08-02 04:03:09'),(19,'view_branch','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(20,'view_any_branch','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(21,'create_branch','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(22,'update_branch','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(23,'restore_branch','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(24,'restore_any_branch','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(25,'replicate_branch','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(26,'reorder_branch','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(27,'delete_branch','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(28,'delete_any_branch','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(29,'force_delete_branch','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(30,'force_delete_any_branch','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(31,'view_business::unit','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(32,'view_any_business::unit','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(33,'create_business::unit','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(34,'update_business::unit','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(35,'restore_business::unit','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(36,'restore_any_business::unit','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(37,'replicate_business::unit','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(38,'reorder_business::unit','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(39,'delete_business::unit','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(40,'delete_any_business::unit','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(41,'force_delete_business::unit','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(42,'force_delete_any_business::unit','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(55,'view_cost::center','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(56,'view_any_cost::center','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(57,'create_cost::center','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(58,'update_cost::center','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(59,'restore_cost::center','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(60,'restore_any_cost::center','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(61,'replicate_cost::center','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(62,'reorder_cost::center','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(63,'delete_cost::center','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(64,'delete_any_cost::center','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(65,'force_delete_cost::center','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(66,'force_delete_any_cost::center','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(67,'view_department','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(68,'view_any_department','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(69,'create_department','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(70,'update_department','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(71,'restore_department','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(72,'restore_any_department','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(73,'replicate_department','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(74,'reorder_department','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(75,'delete_department','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(76,'delete_any_department','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(77,'force_delete_department','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(78,'force_delete_any_department','web','2026-08-02 06:14:17','2026-08-02 06:14:17'),(91,'page_GeneralSettings','web','2026-08-02 06:27:56','2026-08-02 06:27:56'),(92,'view_holiday','web','2026-08-03 03:19:31','2026-08-03 03:19:31'),(93,'view_any_holiday','web','2026-08-03 03:19:31','2026-08-03 03:19:31'),(94,'create_holiday','web','2026-08-03 03:19:31','2026-08-03 03:19:31'),(95,'update_holiday','web','2026-08-03 03:19:31','2026-08-03 03:19:31'),(96,'restore_holiday','web','2026-08-03 03:19:31','2026-08-03 03:19:31'),(97,'restore_any_holiday','web','2026-08-03 03:19:31','2026-08-03 03:19:31'),(98,'replicate_holiday','web','2026-08-03 03:19:31','2026-08-03 03:19:31'),(99,'reorder_holiday','web','2026-08-03 03:19:31','2026-08-03 03:19:31'),(100,'delete_holiday','web','2026-08-03 03:19:31','2026-08-03 03:19:31'),(101,'delete_any_holiday','web','2026-08-03 03:19:31','2026-08-03 03:19:31'),(102,'force_delete_holiday','web','2026-08-03 03:19:31','2026-08-03 03:19:31'),(103,'force_delete_any_holiday','web','2026-08-03 03:19:31','2026-08-03 03:19:31'),(104,'page_WorkingHours','web','2026-08-03 03:19:31','2026-08-03 03:19:31'),(105,'view_workflow','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(106,'view_any_workflow','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(107,'create_workflow','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(108,'update_workflow','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(109,'restore_workflow','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(110,'restore_any_workflow','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(111,'replicate_workflow','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(112,'reorder_workflow','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(113,'delete_workflow','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(114,'delete_any_workflow','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(115,'force_delete_workflow','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(116,'force_delete_any_workflow','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(117,'view_approval_rule','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(118,'view_any_approval_rule','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(119,'create_approval_rule','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(120,'update_approval_rule','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(121,'restore_approval_rule','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(122,'restore_any_approval_rule','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(123,'replicate_approval_rule','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(124,'reorder_approval_rule','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(125,'delete_approval_rule','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(126,'delete_any_approval_rule','web','2026-08-03 04:39:12','2026-08-03 04:39:12'),(127,'force_delete_approval_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(128,'force_delete_any_approval_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(129,'view_escalation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(130,'view_any_escalation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(131,'create_escalation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(132,'update_escalation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(133,'restore_escalation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(134,'restore_any_escalation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(135,'replicate_escalation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(136,'reorder_escalation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(137,'delete_escalation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(138,'delete_any_escalation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(139,'force_delete_escalation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(140,'force_delete_any_escalation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(141,'view_automation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(142,'view_any_automation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(143,'create_automation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(144,'update_automation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(145,'restore_automation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(146,'restore_any_automation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(147,'replicate_automation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(148,'reorder_automation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(149,'delete_automation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(150,'delete_any_automation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(151,'force_delete_automation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(152,'force_delete_any_automation_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(153,'view_email_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(154,'view_any_email_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(155,'create_email_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(156,'update_email_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(157,'restore_email_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(158,'restore_any_email_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(159,'replicate_email_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(160,'reorder_email_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(161,'delete_email_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(162,'delete_any_email_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(163,'force_delete_email_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(164,'force_delete_any_email_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(165,'view_sms_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(166,'view_any_sms_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(167,'create_sms_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(168,'update_sms_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(169,'restore_sms_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(170,'restore_any_sms_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(171,'replicate_sms_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(172,'reorder_sms_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(173,'delete_sms_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(174,'delete_any_sms_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(175,'force_delete_sms_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(176,'force_delete_any_sms_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(177,'view_whatsapp_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(178,'view_any_whatsapp_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(179,'create_whatsapp_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(180,'update_whatsapp_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(181,'restore_whatsapp_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(182,'restore_any_whatsapp_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(183,'replicate_whatsapp_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(184,'reorder_whatsapp_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(185,'delete_whatsapp_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(186,'delete_any_whatsapp_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(187,'force_delete_whatsapp_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(188,'force_delete_any_whatsapp_template','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(189,'view_notification_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(190,'view_any_notification_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(191,'create_notification_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(192,'update_notification_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(193,'restore_notification_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(194,'restore_any_notification_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(195,'replicate_notification_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(196,'reorder_notification_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(197,'delete_notification_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(198,'delete_any_notification_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(199,'force_delete_notification_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(200,'force_delete_any_notification_rule','web','2026-08-03 04:39:13','2026-08-03 04:39:13'),(201,'view_employee','web','2026-08-03 05:15:51','2026-08-03 05:15:51'),(202,'view_any_employee','web','2026-08-03 05:15:51','2026-08-03 05:15:51'),(203,'create_employee','web','2026-08-03 05:15:51','2026-08-03 05:15:51'),(204,'update_employee','web','2026-08-03 05:15:51','2026-08-03 05:15:51'),(205,'restore_employee','web','2026-08-03 05:15:51','2026-08-03 05:15:51'),(206,'restore_any_employee','web','2026-08-03 05:15:51','2026-08-03 05:15:51'),(207,'replicate_employee','web','2026-08-03 05:15:51','2026-08-03 05:15:51'),(208,'reorder_employee','web','2026-08-03 05:15:51','2026-08-03 05:15:51'),(209,'delete_employee','web','2026-08-03 05:15:51','2026-08-03 05:15:51'),(210,'delete_any_employee','web','2026-08-03 05:15:51','2026-08-03 05:15:51'),(211,'force_delete_employee','web','2026-08-03 05:15:51','2026-08-03 05:15:51'),(212,'force_delete_any_employee','web','2026-08-03 05:15:51','2026-08-03 05:15:51'),(213,'view_job_position','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(214,'view_any_job_position','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(215,'create_job_position','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(216,'update_job_position','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(217,'restore_job_position','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(218,'restore_any_job_position','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(219,'replicate_job_position','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(220,'reorder_job_position','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(221,'delete_job_position','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(222,'delete_any_job_position','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(223,'force_delete_job_position','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(224,'force_delete_any_job_position','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(225,'view_candidate','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(226,'view_any_candidate','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(227,'create_candidate','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(228,'update_candidate','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(229,'restore_candidate','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(230,'restore_any_candidate','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(231,'replicate_candidate','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(232,'reorder_candidate','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(233,'delete_candidate','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(234,'delete_any_candidate','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(235,'force_delete_candidate','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(236,'force_delete_any_candidate','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(237,'view_resume_bank_entry','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(238,'view_any_resume_bank_entry','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(239,'create_resume_bank_entry','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(240,'update_resume_bank_entry','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(241,'restore_resume_bank_entry','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(242,'restore_any_resume_bank_entry','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(243,'replicate_resume_bank_entry','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(244,'reorder_resume_bank_entry','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(245,'delete_resume_bank_entry','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(246,'delete_any_resume_bank_entry','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(247,'force_delete_resume_bank_entry','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(248,'force_delete_any_resume_bank_entry','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(249,'view_interview','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(250,'view_any_interview','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(251,'create_interview','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(252,'update_interview','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(253,'restore_interview','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(254,'restore_any_interview','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(255,'replicate_interview','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(256,'reorder_interview','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(257,'delete_interview','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(258,'delete_any_interview','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(259,'force_delete_interview','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(260,'force_delete_any_interview','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(261,'view_offer_letter','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(262,'view_any_offer_letter','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(263,'create_offer_letter','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(264,'update_offer_letter','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(265,'restore_offer_letter','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(266,'restore_any_offer_letter','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(267,'replicate_offer_letter','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(268,'reorder_offer_letter','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(269,'delete_offer_letter','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(270,'delete_any_offer_letter','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(271,'force_delete_offer_letter','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(272,'force_delete_any_offer_letter','web','2026-08-15 03:56:03','2026-08-15 03:56:03'),(273,'view_onboarding_task','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(274,'view_any_onboarding_task','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(275,'create_onboarding_task','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(276,'update_onboarding_task','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(277,'restore_onboarding_task','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(278,'restore_any_onboarding_task','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(279,'replicate_onboarding_task','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(280,'reorder_onboarding_task','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(281,'delete_onboarding_task','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(282,'delete_any_onboarding_task','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(283,'force_delete_onboarding_task','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(284,'force_delete_any_onboarding_task','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(285,'view_probation_record','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(286,'view_any_probation_record','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(287,'create_probation_record','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(288,'update_probation_record','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(289,'restore_probation_record','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(290,'restore_any_probation_record','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(291,'replicate_probation_record','web','2026-08-19 06:34:27','2026-08-19 06:34:27'),(292,'reorder_probation_record','web','2026-08-19 06:34:28','2026-08-19 06:34:28'),(293,'delete_probation_record','web','2026-08-19 06:34:28','2026-08-19 06:34:28'),(294,'delete_any_probation_record','web','2026-08-19 06:34:28','2026-08-19 06:34:28'),(295,'force_delete_probation_record','web','2026-08-19 06:34:28','2026-08-19 06:34:28'),(296,'force_delete_any_probation_record','web','2026-08-19 06:34:28','2026-08-19 06:34:28'),(297,'view_attendance_record','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(298,'view_any_attendance_record','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(299,'create_attendance_record','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(300,'update_attendance_record','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(301,'restore_attendance_record','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(302,'restore_any_attendance_record','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(303,'replicate_attendance_record','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(304,'reorder_attendance_record','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(305,'delete_attendance_record','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(306,'delete_any_attendance_record','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(307,'force_delete_attendance_record','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(308,'force_delete_any_attendance_record','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(309,'view_regularization_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(310,'view_any_regularization_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(311,'create_regularization_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(312,'update_regularization_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(313,'restore_regularization_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(314,'restore_any_regularization_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(315,'replicate_regularization_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(316,'reorder_regularization_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(317,'delete_regularization_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(318,'delete_any_regularization_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(319,'force_delete_regularization_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(320,'force_delete_any_regularization_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(321,'view_wfh_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(322,'view_any_wfh_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(323,'create_wfh_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(324,'update_wfh_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(325,'restore_wfh_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(326,'restore_any_wfh_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(327,'replicate_wfh_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(328,'reorder_wfh_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(329,'delete_wfh_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(330,'delete_any_wfh_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(331,'force_delete_wfh_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(332,'force_delete_any_wfh_request','web','2026-08-20 01:17:14','2026-08-20 01:17:14'),(333,'view_overtime_request','web','2026-08-20 01:17:15','2026-08-20 01:17:15'),(334,'view_any_overtime_request','web','2026-08-20 01:17:15','2026-08-20 01:17:15'),(335,'create_overtime_request','web','2026-08-20 01:17:15','2026-08-20 01:17:15'),(336,'update_overtime_request','web','2026-08-20 01:17:15','2026-08-20 01:17:15'),(337,'restore_overtime_request','web','2026-08-20 01:17:15','2026-08-20 01:17:15'),(338,'restore_any_overtime_request','web','2026-08-20 01:17:15','2026-08-20 01:17:15'),(339,'replicate_overtime_request','web','2026-08-20 01:17:15','2026-08-20 01:17:15'),(340,'reorder_overtime_request','web','2026-08-20 01:17:15','2026-08-20 01:17:15'),(341,'delete_overtime_request','web','2026-08-20 01:17:15','2026-08-20 01:17:15'),(342,'delete_any_overtime_request','web','2026-08-20 01:17:15','2026-08-20 01:17:15'),(343,'force_delete_overtime_request','web','2026-08-20 01:17:15','2026-08-20 01:17:15'),(344,'force_delete_any_overtime_request','web','2026-08-20 01:17:15','2026-08-20 01:17:15'),(345,'view_leave_type','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(346,'view_any_leave_type','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(347,'create_leave_type','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(348,'update_leave_type','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(349,'restore_leave_type','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(350,'restore_any_leave_type','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(351,'replicate_leave_type','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(352,'reorder_leave_type','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(353,'delete_leave_type','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(354,'delete_any_leave_type','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(355,'force_delete_leave_type','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(356,'force_delete_any_leave_type','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(357,'view_leave_balance','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(358,'view_any_leave_balance','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(359,'create_leave_balance','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(360,'update_leave_balance','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(361,'restore_leave_balance','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(362,'restore_any_leave_balance','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(363,'replicate_leave_balance','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(364,'reorder_leave_balance','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(365,'delete_leave_balance','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(366,'delete_any_leave_balance','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(367,'force_delete_leave_balance','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(368,'force_delete_any_leave_balance','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(369,'view_leave_request','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(370,'view_any_leave_request','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(371,'create_leave_request','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(372,'update_leave_request','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(373,'restore_leave_request','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(374,'restore_any_leave_request','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(375,'replicate_leave_request','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(376,'reorder_leave_request','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(377,'delete_leave_request','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(378,'delete_any_leave_request','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(379,'force_delete_leave_request','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(380,'force_delete_any_leave_request','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(381,'view_comp_off','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(382,'view_any_comp_off','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(383,'create_comp_off','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(384,'update_comp_off','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(385,'restore_comp_off','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(386,'restore_any_comp_off','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(387,'replicate_comp_off','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(388,'reorder_comp_off','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(389,'delete_comp_off','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(390,'delete_any_comp_off','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(391,'force_delete_comp_off','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(392,'force_delete_any_comp_off','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(393,'view_leave_encashment','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(394,'view_any_leave_encashment','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(395,'create_leave_encashment','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(396,'update_leave_encashment','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(397,'restore_leave_encashment','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(398,'restore_any_leave_encashment','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(399,'replicate_leave_encashment','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(400,'reorder_leave_encashment','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(401,'delete_leave_encashment','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(402,'delete_any_leave_encashment','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(403,'force_delete_leave_encashment','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(404,'force_delete_any_leave_encashment','web','2026-08-20 07:00:03','2026-08-20 07:00:03'),(405,'view_shift','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(406,'view_any_shift','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(407,'create_shift','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(408,'update_shift','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(409,'restore_shift','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(410,'restore_any_shift','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(411,'replicate_shift','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(412,'reorder_shift','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(413,'delete_shift','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(414,'delete_any_shift','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(415,'force_delete_shift','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(416,'force_delete_any_shift','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(417,'view_shift_roster','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(418,'view_any_shift_roster','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(419,'create_shift_roster','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(420,'update_shift_roster','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(421,'restore_shift_roster','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(422,'restore_any_shift_roster','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(423,'replicate_shift_roster','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(424,'reorder_shift_roster','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(425,'delete_shift_roster','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(426,'delete_any_shift_roster','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(427,'force_delete_shift_roster','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(428,'force_delete_any_shift_roster','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(429,'view_shift_swap_request','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(430,'view_any_shift_swap_request','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(431,'create_shift_swap_request','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(432,'update_shift_swap_request','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(433,'restore_shift_swap_request','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(434,'restore_any_shift_swap_request','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(435,'replicate_shift_swap_request','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(436,'reorder_shift_swap_request','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(437,'delete_shift_swap_request','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(438,'delete_any_shift_swap_request','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(439,'force_delete_shift_swap_request','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(440,'force_delete_any_shift_swap_request','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(441,'view_overtime_rule','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(442,'view_any_overtime_rule','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(443,'create_overtime_rule','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(444,'update_overtime_rule','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(445,'restore_overtime_rule','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(446,'restore_any_overtime_rule','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(447,'replicate_overtime_rule','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(448,'reorder_overtime_rule','web','2026-08-20 07:31:55','2026-08-20 07:31:55'),(449,'delete_overtime_rule','web','2026-08-20 07:31:56','2026-08-20 07:31:56'),(450,'delete_any_overtime_rule','web','2026-08-20 07:31:56','2026-08-20 07:31:56'),(451,'force_delete_overtime_rule','web','2026-08-20 07:31:56','2026-08-20 07:31:56'),(452,'force_delete_any_overtime_rule','web','2026-08-20 07:31:56','2026-08-20 07:31:56'),(453,'view_salary_component','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(454,'view_any_salary_component','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(455,'create_salary_component','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(456,'update_salary_component','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(457,'restore_salary_component','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(458,'restore_any_salary_component','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(459,'replicate_salary_component','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(460,'reorder_salary_component','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(461,'delete_salary_component','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(462,'delete_any_salary_component','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(463,'force_delete_salary_component','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(464,'force_delete_any_salary_component','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(465,'view_salary_structure','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(466,'view_any_salary_structure','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(467,'create_salary_structure','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(468,'update_salary_structure','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(469,'restore_salary_structure','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(470,'restore_any_salary_structure','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(471,'replicate_salary_structure','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(472,'reorder_salary_structure','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(473,'delete_salary_structure','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(474,'delete_any_salary_structure','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(475,'force_delete_salary_structure','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(476,'force_delete_any_salary_structure','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(477,'view_bonus','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(478,'view_any_bonus','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(479,'create_bonus','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(480,'update_bonus','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(481,'restore_bonus','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(482,'restore_any_bonus','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(483,'replicate_bonus','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(484,'reorder_bonus','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(485,'delete_bonus','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(486,'delete_any_bonus','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(487,'force_delete_bonus','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(488,'force_delete_any_bonus','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(489,'view_incentive','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(490,'view_any_incentive','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(491,'create_incentive','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(492,'update_incentive','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(493,'restore_incentive','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(494,'restore_any_incentive','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(495,'replicate_incentive','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(496,'reorder_incentive','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(497,'delete_incentive','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(498,'delete_any_incentive','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(499,'force_delete_incentive','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(500,'force_delete_any_incentive','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(501,'view_deduction','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(502,'view_any_deduction','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(503,'create_deduction','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(504,'update_deduction','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(505,'restore_deduction','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(506,'restore_any_deduction','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(507,'replicate_deduction','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(508,'reorder_deduction','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(509,'delete_deduction','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(510,'delete_any_deduction','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(511,'force_delete_deduction','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(512,'force_delete_any_deduction','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(513,'view_loan','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(514,'view_any_loan','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(515,'create_loan','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(516,'update_loan','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(517,'restore_loan','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(518,'restore_any_loan','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(519,'replicate_loan','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(520,'reorder_loan','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(521,'delete_loan','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(522,'delete_any_loan','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(523,'force_delete_loan','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(524,'force_delete_any_loan','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(525,'view_tax_declaration','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(526,'view_any_tax_declaration','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(527,'create_tax_declaration','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(528,'update_tax_declaration','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(529,'restore_tax_declaration','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(530,'restore_any_tax_declaration','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(531,'replicate_tax_declaration','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(532,'reorder_tax_declaration','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(533,'delete_tax_declaration','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(534,'delete_any_tax_declaration','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(535,'force_delete_tax_declaration','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(536,'force_delete_any_tax_declaration','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(537,'view_pf_setting','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(538,'view_any_pf_setting','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(539,'create_pf_setting','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(540,'update_pf_setting','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(541,'restore_pf_setting','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(542,'restore_any_pf_setting','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(543,'replicate_pf_setting','web','2026-08-20 08:03:09','2026-08-20 08:03:09'),(544,'reorder_pf_setting','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(545,'delete_pf_setting','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(546,'delete_any_pf_setting','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(547,'force_delete_pf_setting','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(548,'force_delete_any_pf_setting','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(549,'view_esi_setting','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(550,'view_any_esi_setting','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(551,'create_esi_setting','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(552,'update_esi_setting','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(553,'restore_esi_setting','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(554,'restore_any_esi_setting','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(555,'replicate_esi_setting','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(556,'reorder_esi_setting','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(557,'delete_esi_setting','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(558,'delete_any_esi_setting','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(559,'force_delete_esi_setting','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(560,'force_delete_any_esi_setting','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(561,'view_payroll_run','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(562,'view_any_payroll_run','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(563,'create_payroll_run','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(564,'update_payroll_run','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(565,'restore_payroll_run','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(566,'restore_any_payroll_run','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(567,'replicate_payroll_run','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(568,'reorder_payroll_run','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(569,'delete_payroll_run','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(570,'delete_any_payroll_run','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(571,'force_delete_payroll_run','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(572,'force_delete_any_payroll_run','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(573,'view_payslip','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(574,'view_any_payslip','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(575,'create_payslip','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(576,'update_payslip','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(577,'restore_payslip','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(578,'restore_any_payslip','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(579,'replicate_payslip','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(580,'reorder_payslip','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(581,'delete_payslip','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(582,'delete_any_payslip','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(583,'force_delete_payslip','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(584,'force_delete_any_payslip','web','2026-08-20 08:03:10','2026-08-20 08:03:10'),(585,'view_goal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(586,'view_any_goal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(587,'create_goal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(588,'update_goal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(589,'restore_goal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(590,'restore_any_goal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(591,'replicate_goal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(592,'reorder_goal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(593,'delete_goal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(594,'delete_any_goal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(595,'force_delete_goal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(596,'force_delete_any_goal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(597,'view_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(598,'view_any_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(599,'create_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(600,'update_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(601,'restore_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(602,'restore_any_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(603,'replicate_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(604,'reorder_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(605,'delete_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(606,'delete_any_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(607,'force_delete_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(608,'force_delete_any_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(609,'view_employee_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(610,'view_any_employee_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(611,'create_employee_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(612,'update_employee_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(613,'restore_employee_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(614,'restore_any_employee_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(615,'replicate_employee_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(616,'reorder_employee_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(617,'delete_employee_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(618,'delete_any_employee_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(619,'force_delete_employee_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(620,'force_delete_any_employee_kpi','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(621,'view_appraisal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(622,'view_any_appraisal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(623,'create_appraisal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(624,'update_appraisal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(625,'restore_appraisal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(626,'restore_any_appraisal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(627,'replicate_appraisal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(628,'reorder_appraisal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(629,'delete_appraisal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(630,'delete_any_appraisal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(631,'force_delete_appraisal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(632,'force_delete_any_appraisal','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(633,'view_feedback_360','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(634,'view_any_feedback_360','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(635,'create_feedback_360','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(636,'update_feedback_360','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(637,'restore_feedback_360','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(638,'restore_any_feedback_360','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(639,'replicate_feedback_360','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(640,'reorder_feedback_360','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(641,'delete_feedback_360','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(642,'delete_any_feedback_360','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(643,'force_delete_feedback_360','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(644,'force_delete_any_feedback_360','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(645,'view_increment','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(646,'view_any_increment','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(647,'create_increment','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(648,'update_increment','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(649,'restore_increment','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(650,'restore_any_increment','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(651,'replicate_increment','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(652,'reorder_increment','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(653,'delete_increment','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(654,'delete_any_increment','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(655,'force_delete_increment','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(656,'force_delete_any_increment','web','2026-08-20 08:20:14','2026-08-20 08:20:14'),(657,'view_trainer','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(658,'view_any_trainer','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(659,'create_trainer','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(660,'update_trainer','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(661,'restore_trainer','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(662,'restore_any_trainer','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(663,'replicate_trainer','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(664,'reorder_trainer','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(665,'delete_trainer','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(666,'delete_any_trainer','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(667,'force_delete_trainer','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(668,'force_delete_any_trainer','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(669,'view_course','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(670,'view_any_course','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(671,'create_course','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(672,'update_course','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(673,'restore_course','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(674,'restore_any_course','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(675,'replicate_course','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(676,'reorder_course','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(677,'delete_course','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(678,'delete_any_course','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(679,'force_delete_course','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(680,'force_delete_any_course','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(681,'view_training_session','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(682,'view_any_training_session','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(683,'create_training_session','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(684,'update_training_session','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(685,'restore_training_session','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(686,'restore_any_training_session','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(687,'replicate_training_session','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(688,'reorder_training_session','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(689,'delete_training_session','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(690,'delete_any_training_session','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(691,'force_delete_training_session','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(692,'force_delete_any_training_session','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(693,'view_training_enrollment','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(694,'view_any_training_enrollment','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(695,'create_training_enrollment','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(696,'update_training_enrollment','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(697,'restore_training_enrollment','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(698,'restore_any_training_enrollment','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(699,'replicate_training_enrollment','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(700,'reorder_training_enrollment','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(701,'delete_training_enrollment','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(702,'delete_any_training_enrollment','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(703,'force_delete_training_enrollment','web','2026-08-20 10:37:56','2026-08-20 10:37:56'),(704,'force_delete_any_training_enrollment','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(705,'view_certification','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(706,'view_any_certification','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(707,'create_certification','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(708,'update_certification','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(709,'restore_certification','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(710,'restore_any_certification','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(711,'replicate_certification','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(712,'reorder_certification','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(713,'delete_certification','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(714,'delete_any_certification','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(715,'force_delete_certification','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(716,'force_delete_any_certification','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(717,'view_assessment','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(718,'view_any_assessment','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(719,'create_assessment','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(720,'update_assessment','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(721,'restore_assessment','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(722,'restore_any_assessment','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(723,'replicate_assessment','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(724,'reorder_assessment','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(725,'delete_assessment','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(726,'delete_any_assessment','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(727,'force_delete_assessment','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(728,'force_delete_any_assessment','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(729,'view_assessment_result','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(730,'view_any_assessment_result','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(731,'create_assessment_result','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(732,'update_assessment_result','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(733,'restore_assessment_result','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(734,'restore_any_assessment_result','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(735,'replicate_assessment_result','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(736,'reorder_assessment_result','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(737,'delete_assessment_result','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(738,'delete_any_assessment_result','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(739,'force_delete_assessment_result','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(740,'force_delete_any_assessment_result','web','2026-08-20 10:37:57','2026-08-20 10:37:57'),(741,'view_employee_asset','web','2026-08-20 11:35:12','2026-08-20 11:35:12'),(742,'view_any_employee_asset','web','2026-08-20 11:35:12','2026-08-20 11:35:12'),(743,'create_employee_asset','web','2026-08-20 11:35:12','2026-08-20 11:35:12'),(744,'update_employee_asset','web','2026-08-20 11:35:12','2026-08-20 11:35:12'),(745,'restore_employee_asset','web','2026-08-20 11:35:12','2026-08-20 11:35:12'),(746,'restore_any_employee_asset','web','2026-08-20 11:35:12','2026-08-20 11:35:12'),(747,'replicate_employee_asset','web','2026-08-20 11:35:12','2026-08-20 11:35:12'),(748,'reorder_employee_asset','web','2026-08-20 11:35:12','2026-08-20 11:35:12'),(749,'delete_employee_asset','web','2026-08-20 11:35:12','2026-08-20 11:35:12'),(750,'delete_any_employee_asset','web','2026-08-20 11:35:12','2026-08-20 11:35:12'),(751,'force_delete_employee_asset','web','2026-08-20 11:35:12','2026-08-20 11:35:12'),(752,'force_delete_any_employee_asset','web','2026-08-20 11:35:12','2026-08-20 11:35:12'),(753,'view_travel_request','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(754,'view_any_travel_request','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(755,'create_travel_request','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(756,'update_travel_request','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(757,'restore_travel_request','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(758,'restore_any_travel_request','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(759,'replicate_travel_request','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(760,'reorder_travel_request','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(761,'delete_travel_request','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(762,'delete_any_travel_request','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(763,'force_delete_travel_request','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(764,'force_delete_any_travel_request','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(765,'view_travel_booking','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(766,'view_any_travel_booking','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(767,'create_travel_booking','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(768,'update_travel_booking','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(769,'restore_travel_booking','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(770,'restore_any_travel_booking','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(771,'replicate_travel_booking','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(772,'reorder_travel_booking','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(773,'delete_travel_booking','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(774,'delete_any_travel_booking','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(775,'force_delete_travel_booking','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(776,'force_delete_any_travel_booking','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(777,'view_travel_expense','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(778,'view_any_travel_expense','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(779,'create_travel_expense','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(780,'update_travel_expense','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(781,'restore_travel_expense','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(782,'restore_any_travel_expense','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(783,'replicate_travel_expense','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(784,'reorder_travel_expense','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(785,'delete_travel_expense','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(786,'delete_any_travel_expense','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(787,'force_delete_travel_expense','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(788,'force_delete_any_travel_expense','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(789,'view_travel_settlement','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(790,'view_any_travel_settlement','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(791,'create_travel_settlement','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(792,'update_travel_settlement','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(793,'restore_travel_settlement','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(794,'restore_any_travel_settlement','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(795,'replicate_travel_settlement','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(796,'reorder_travel_settlement','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(797,'delete_travel_settlement','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(798,'delete_any_travel_settlement','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(799,'force_delete_travel_settlement','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(800,'force_delete_any_travel_settlement','web','2026-08-20 13:54:31','2026-08-20 13:54:31'),(801,'view_expense_category','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(802,'view_any_expense_category','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(803,'create_expense_category','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(804,'update_expense_category','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(805,'restore_expense_category','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(806,'restore_any_expense_category','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(807,'replicate_expense_category','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(808,'reorder_expense_category','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(809,'delete_expense_category','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(810,'delete_any_expense_category','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(811,'force_delete_expense_category','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(812,'force_delete_any_expense_category','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(813,'view_expense_claim','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(814,'view_any_expense_claim','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(815,'create_expense_claim','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(816,'update_expense_claim','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(817,'restore_expense_claim','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(818,'restore_any_expense_claim','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(819,'replicate_expense_claim','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(820,'reorder_expense_claim','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(821,'delete_expense_claim','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(822,'delete_any_expense_claim','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(823,'force_delete_expense_claim','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(824,'force_delete_any_expense_claim','web','2026-09-15 20:06:48','2026-09-15 20:06:48'),(825,'view_employee_document','web','2026-09-15 20:21:51','2026-09-15 20:21:51'),(826,'view_any_employee_document','web','2026-09-15 20:21:51','2026-09-15 20:21:51'),(827,'create_employee_document','web','2026-09-15 20:21:51','2026-09-15 20:21:51'),(828,'update_employee_document','web','2026-09-15 20:21:51','2026-09-15 20:21:51'),(829,'restore_employee_document','web','2026-09-15 20:21:51','2026-09-15 20:21:51'),(830,'restore_any_employee_document','web','2026-09-15 20:21:51','2026-09-15 20:21:51'),(831,'replicate_employee_document','web','2026-09-15 20:21:51','2026-09-15 20:21:51'),(832,'reorder_employee_document','web','2026-09-15 20:21:51','2026-09-15 20:21:51'),(833,'delete_employee_document','web','2026-09-15 20:21:51','2026-09-15 20:21:51'),(834,'delete_any_employee_document','web','2026-09-15 20:21:51','2026-09-15 20:21:51'),(835,'force_delete_employee_document','web','2026-09-15 20:21:51','2026-09-15 20:21:51'),(836,'force_delete_any_employee_document','web','2026-09-15 20:21:51','2026-09-15 20:21:51'),(837,'view_resignation','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(838,'view_any_resignation','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(839,'create_resignation','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(840,'update_resignation','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(841,'restore_resignation','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(842,'restore_any_resignation','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(843,'replicate_resignation','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(844,'reorder_resignation','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(845,'delete_resignation','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(846,'delete_any_resignation','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(847,'force_delete_resignation','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(848,'force_delete_any_resignation','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(849,'view_separation_clearance','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(850,'view_any_separation_clearance','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(851,'create_separation_clearance','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(852,'update_separation_clearance','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(853,'restore_separation_clearance','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(854,'restore_any_separation_clearance','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(855,'replicate_separation_clearance','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(856,'reorder_separation_clearance','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(857,'delete_separation_clearance','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(858,'delete_any_separation_clearance','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(859,'force_delete_separation_clearance','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(860,'force_delete_any_separation_clearance','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(861,'view_exit_interview','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(862,'view_any_exit_interview','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(863,'create_exit_interview','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(864,'update_exit_interview','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(865,'restore_exit_interview','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(866,'restore_any_exit_interview','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(867,'replicate_exit_interview','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(868,'reorder_exit_interview','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(869,'delete_exit_interview','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(870,'delete_any_exit_interview','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(871,'force_delete_exit_interview','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(872,'force_delete_any_exit_interview','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(873,'view_final_settlement','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(874,'view_any_final_settlement','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(875,'create_final_settlement','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(876,'update_final_settlement','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(877,'restore_final_settlement','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(878,'restore_any_final_settlement','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(879,'replicate_final_settlement','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(880,'reorder_final_settlement','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(881,'delete_final_settlement','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(882,'delete_any_final_settlement','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(883,'force_delete_final_settlement','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(884,'force_delete_any_final_settlement','web','2026-09-15 21:55:46','2026-09-15 21:55:46'),(885,'view_loan_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(886,'view_any_loan_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(887,'create_loan_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(888,'update_loan_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(889,'restore_loan_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(890,'restore_any_loan_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(891,'replicate_loan_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(892,'reorder_loan_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(893,'delete_loan_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(894,'delete_any_loan_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(895,'force_delete_loan_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(896,'force_delete_any_loan_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(897,'view_promotion_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(898,'view_any_promotion_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(899,'create_promotion_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(900,'update_promotion_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(901,'restore_promotion_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(902,'restore_any_promotion_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(903,'replicate_promotion_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(904,'reorder_promotion_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(905,'delete_promotion_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(906,'delete_any_promotion_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(907,'force_delete_promotion_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(908,'force_delete_any_promotion_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(909,'view_transfer_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(910,'view_any_transfer_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(911,'create_transfer_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(912,'update_transfer_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(913,'restore_transfer_request','web','2026-09-17 06:25:19','2026-09-17 06:25:19'),(914,'restore_any_transfer_request','web','2026-09-17 06:25:20','2026-09-17 06:25:20'),(915,'replicate_transfer_request','web','2026-09-17 06:25:20','2026-09-17 06:25:20'),(916,'reorder_transfer_request','web','2026-09-17 06:25:20','2026-09-17 06:25:20'),(917,'delete_transfer_request','web','2026-09-17 06:25:20','2026-09-17 06:25:20'),(918,'delete_any_transfer_request','web','2026-09-17 06:25:20','2026-09-17 06:25:20'),(919,'force_delete_transfer_request','web','2026-09-17 06:25:20','2026-09-17 06:25:20'),(920,'force_delete_any_transfer_request','web','2026-09-17 06:25:20','2026-09-17 06:25:20'),(921,'view_hr_policy','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(922,'view_any_hr_policy','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(923,'create_hr_policy','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(924,'update_hr_policy','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(925,'restore_hr_policy','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(926,'restore_any_hr_policy','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(927,'replicate_hr_policy','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(928,'reorder_hr_policy','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(929,'delete_hr_policy','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(930,'delete_any_hr_policy','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(931,'force_delete_hr_policy','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(932,'force_delete_any_hr_policy','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(933,'view_attendance_rule_setting','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(934,'view_any_attendance_rule_setting','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(935,'create_attendance_rule_setting','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(936,'update_attendance_rule_setting','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(937,'restore_attendance_rule_setting','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(938,'restore_any_attendance_rule_setting','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(939,'replicate_attendance_rule_setting','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(940,'reorder_attendance_rule_setting','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(941,'delete_attendance_rule_setting','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(942,'delete_any_attendance_rule_setting','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(943,'force_delete_attendance_rule_setting','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(944,'force_delete_any_attendance_rule_setting','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(945,'view_tax_slab','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(946,'view_any_tax_slab','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(947,'create_tax_slab','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(948,'update_tax_slab','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(949,'restore_tax_slab','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(950,'restore_any_tax_slab','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(951,'replicate_tax_slab','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(952,'reorder_tax_slab','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(953,'delete_tax_slab','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(954,'delete_any_tax_slab','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(955,'force_delete_tax_slab','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(956,'force_delete_any_tax_slab','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(957,'view_document_template','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(958,'view_any_document_template','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(959,'create_document_template','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(960,'update_document_template','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(961,'restore_document_template','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(962,'restore_any_document_template','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(963,'replicate_document_template','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(964,'reorder_document_template','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(965,'delete_document_template','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(966,'delete_any_document_template','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(967,'force_delete_document_template','web','2026-09-17 06:35:32','2026-09-17 06:35:32'),(968,'force_delete_any_document_template','web','2026-09-17 06:35:32','2026-09-17 06:35:32');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pf_settings`
--

DROP TABLE IF EXISTS `pf_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pf_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `employee_contribution_percent` decimal(5,2) NOT NULL DEFAULT '12.00',
  `employer_contribution_percent` decimal(5,2) NOT NULL DEFAULT '12.00',
  `wage_ceiling` decimal(12,2) DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pf_settings_company_id_branch_id_unique` (`company_id`,`branch_id`),
  KEY `pf_settings_branch_id_foreign` (`branch_id`),
  CONSTRAINT `pf_settings_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pf_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pf_settings`
--

LOCK TABLES `pf_settings` WRITE;
/*!40000 ALTER TABLE `pf_settings` DISABLE KEYS */;
INSERT INTO `pf_settings` VALUES (2,2,1,12.00,12.00,NULL,1,'2026-09-17 06:11:37','2026-09-17 06:11:37');
/*!40000 ALTER TABLE `pf_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_settings`
--

DROP TABLE IF EXISTS `platform_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_settings`
--

LOCK TABLES `platform_settings` WRITE;
/*!40000 ALTER TABLE `platform_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `platform_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `probation_records`
--

DROP TABLE IF EXISTS `probation_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `probation_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'on_probation',
  `extended_to` date DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `probation_records_company_id_foreign` (`company_id`),
  KEY `probation_records_employee_id_foreign` (`employee_id`),
  KEY `probation_records_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `probation_records_status_index` (`status`),
  CONSTRAINT `probation_records_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `probation_records_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `probation_records_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `probation_records`
--

LOCK TABLES `probation_records` WRITE;
/*!40000 ALTER TABLE `probation_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `probation_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotion_requests`
--

DROP TABLE IF EXISTS `promotion_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `from_designation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_designation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `promotion_date` date NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promotion_requests_employee_id_foreign` (`employee_id`),
  KEY `promotion_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `promotion_requests_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `promotion_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promotion_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promotion_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotion_requests`
--

LOCK TABLES `promotion_requests` WRITE;
/*!40000 ALTER TABLE `promotion_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `promotion_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `regularization_requests`
--

DROP TABLE IF EXISTS `regularization_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `regularization_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `requested_check_in` time DEFAULT NULL,
  `requested_check_out` time DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `regularization_requests_employee_id_foreign` (`employee_id`),
  KEY `regularization_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `regularization_requests_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `regularization_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `regularization_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `regularization_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `regularization_requests`
--

LOCK TABLES `regularization_requests` WRITE;
/*!40000 ALTER TABLE `regularization_requests` DISABLE KEYS */;
INSERT INTO `regularization_requests` VALUES (11,2,4,'2026-10-09','09:07:00','15:07:00','Weekend Production  Release','pending',NULL,NULL,NULL,'2026-10-09 22:07:52','2026-10-09 22:07:52');
/*!40000 ALTER TABLE `regularization_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resignations`
--

DROP TABLE IF EXISTS `resignations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resignations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `resignation_date` date NOT NULL,
  `notice_period_days` int unsigned NOT NULL DEFAULT '0',
  `last_working_day` date NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resignations_employee_id_foreign` (`employee_id`),
  KEY `resignations_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `resignations_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `resignations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resignations_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resignations_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resignations`
--

LOCK TABLES `resignations` WRITE;
/*!40000 ALTER TABLE `resignations` DISABLE KEYS */;
/*!40000 ALTER TABLE `resignations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resume_bank_entries`
--

DROP TABLE IF EXISTS `resume_bank_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resume_bank_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resume_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `skills` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resume_bank_entries_company_id_foreign` (`company_id`),
  KEY `resume_bank_entries_branch_id_foreign` (`branch_id`),
  CONSTRAINT `resume_bank_entries_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resume_bank_entries_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resume_bank_entries`
--

LOCK TABLES `resume_bank_entries` WRITE;
/*!40000 ALTER TABLE `resume_bank_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `resume_bank_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(15,1),(16,1),(17,1),(18,1),(19,1),(20,1),(21,1),(22,1),(23,1),(24,1),(25,1),(26,1),(27,1),(28,1),(29,1),(30,1),(31,1),(32,1),(33,1),(34,1),(35,1),(36,1),(37,1),(38,1),(39,1),(40,1),(41,1),(42,1),(55,1),(56,1),(57,1),(58,1),(59,1),(60,1),(61,1),(62,1),(63,1),(64,1),(65,1),(66,1),(67,1),(68,1),(69,1),(70,1),(71,1),(72,1),(73,1),(74,1),(75,1),(76,1),(77,1),(78,1),(91,1),(92,1),(93,1),(94,1),(95,1),(96,1),(97,1),(98,1),(99,1),(100,1),(101,1),(102,1),(103,1),(104,1),(105,1),(106,1),(107,1),(108,1),(109,1),(110,1),(111,1),(112,1),(113,1),(114,1),(115,1),(116,1),(117,1),(118,1),(119,1),(120,1),(121,1),(122,1),(123,1),(124,1),(125,1),(126,1),(127,1),(128,1),(129,1),(130,1),(131,1),(132,1),(133,1),(134,1),(135,1),(136,1),(137,1),(138,1),(139,1),(140,1),(141,1),(142,1),(143,1),(144,1),(145,1),(146,1),(147,1),(148,1),(149,1),(150,1),(151,1),(152,1),(153,1),(154,1),(155,1),(156,1),(157,1),(158,1),(159,1),(160,1),(161,1),(162,1),(163,1),(164,1),(165,1),(166,1),(167,1),(168,1),(169,1),(170,1),(171,1),(172,1),(173,1),(174,1),(175,1),(176,1),(177,1),(178,1),(179,1),(180,1),(181,1),(182,1),(183,1),(184,1),(185,1),(186,1),(187,1),(188,1),(189,1),(190,1),(191,1),(192,1),(193,1),(194,1),(195,1),(196,1),(197,1),(198,1),(199,1),(200,1),(201,1),(202,1),(203,1),(204,1),(205,1),(206,1),(207,1),(208,1),(209,1),(210,1),(211,1),(212,1),(213,1),(214,1),(215,1),(216,1),(217,1),(218,1),(219,1),(220,1),(221,1),(222,1),(223,1),(224,1),(225,1),(226,1),(227,1),(228,1),(229,1),(230,1),(231,1),(232,1),(233,1),(234,1),(235,1),(236,1),(237,1),(238,1),(239,1),(240,1),(241,1),(242,1),(243,1),(244,1),(245,1),(246,1),(247,1),(248,1),(249,1),(250,1),(251,1),(252,1),(253,1),(254,1),(255,1),(256,1),(257,1),(258,1),(259,1),(260,1),(261,1),(262,1),(263,1),(264,1),(265,1),(266,1),(267,1),(268,1),(269,1),(270,1),(271,1),(272,1),(19,8),(20,8),(21,8),(22,8),(23,8),(24,8),(25,8),(26,8),(27,8),(28,8),(29,8),(30,8),(31,8),(32,8),(33,8),(34,8),(35,8),(36,8),(37,8),(38,8),(39,8),(40,8),(41,8),(42,8),(55,8),(56,8),(57,8),(58,8),(59,8),(60,8),(61,8),(62,8),(63,8),(64,8),(65,8),(66,8),(67,8),(68,8),(69,8),(70,8),(71,8),(72,8),(73,8),(74,8),(75,8),(76,8),(77,8),(78,8),(91,8),(92,8),(93,8),(94,8),(95,8),(96,8),(97,8),(98,8),(99,8),(100,8),(101,8),(102,8),(103,8),(104,8),(105,8),(106,8),(107,8),(108,8),(109,8),(110,8),(111,8),(112,8),(113,8),(114,8),(115,8),(116,8),(117,8),(118,8),(119,8),(120,8),(121,8),(122,8),(123,8),(124,8),(125,8),(126,8),(127,8),(128,8),(129,8),(130,8),(131,8),(132,8),(133,8),(134,8),(135,8),(136,8),(137,8),(138,8),(139,8),(140,8),(141,8),(142,8),(143,8),(144,8),(145,8),(146,8),(147,8),(148,8),(149,8),(150,8),(151,8),(152,8),(153,8),(154,8),(155,8),(156,8),(157,8),(158,8),(159,8),(160,8),(161,8),(162,8),(163,8),(164,8),(165,8),(166,8),(167,8),(168,8),(169,8),(170,8),(171,8),(172,8),(173,8),(174,8),(175,8),(176,8),(177,8),(178,8),(179,8),(180,8),(181,8),(182,8),(183,8),(184,8),(185,8),(186,8),(187,8),(188,8),(189,8),(190,8),(191,8),(192,8),(193,8),(194,8),(195,8),(196,8),(197,8),(198,8),(199,8),(200,8),(201,8),(202,8),(203,8),(204,8),(205,8),(206,8),(207,8),(208,8),(209,8),(210,8),(211,8),(212,8),(213,8),(214,8),(215,8),(216,8),(217,8),(218,8),(219,8),(220,8),(221,8),(222,8),(223,8),(224,8),(225,8),(226,8),(227,8),(228,8),(229,8),(230,8),(231,8),(232,8),(233,8),(234,8),(235,8),(236,8),(237,8),(238,8),(239,8),(240,8),(241,8),(242,8),(243,8),(244,8),(245,8),(246,8),(247,8),(248,8),(249,8),(250,8),(251,8),(252,8),(253,8),(254,8),(255,8),(256,8),(257,8),(258,8),(259,8),(260,8),(261,8),(262,8),(263,8),(264,8),(265,8),(266,8),(267,8),(268,8),(269,8),(270,8),(271,8),(272,8),(273,8),(274,8),(275,8),(276,8),(277,8),(278,8),(279,8),(280,8),(281,8),(282,8),(283,8),(284,8),(285,8),(286,8),(287,8),(288,8),(289,8),(290,8),(291,8),(292,8),(293,8),(294,8),(295,8),(296,8),(297,8),(298,8),(299,8),(300,8),(301,8),(302,8),(303,8),(304,8),(305,8),(306,8),(307,8),(308,8),(309,8),(310,8),(311,8),(312,8),(313,8),(314,8),(315,8),(316,8),(317,8),(318,8),(319,8),(320,8),(321,8),(322,8),(323,8),(324,8),(325,8),(326,8),(327,8),(328,8),(329,8),(330,8),(331,8),(332,8),(333,8),(334,8),(335,8),(336,8),(337,8),(338,8),(339,8),(340,8),(341,8),(342,8),(343,8),(344,8),(345,8),(346,8),(347,8),(348,8),(349,8),(350,8),(351,8),(352,8),(353,8),(354,8),(355,8),(356,8),(357,8),(358,8),(359,8),(360,8),(361,8),(362,8),(363,8),(364,8),(365,8),(366,8),(367,8),(368,8),(369,8),(370,8),(371,8),(372,8),(373,8),(374,8),(375,8),(376,8),(377,8),(378,8),(379,8),(380,8),(381,8),(382,8),(383,8),(384,8),(385,8),(386,8),(387,8),(388,8),(389,8),(390,8),(391,8),(392,8),(393,8),(394,8),(395,8),(396,8),(397,8),(398,8),(399,8),(400,8),(401,8),(402,8),(403,8),(404,8),(405,8),(406,8),(407,8),(408,8),(409,8),(410,8),(411,8),(412,8),(413,8),(414,8),(415,8),(416,8),(417,8),(418,8),(419,8),(420,8),(421,8),(422,8),(423,8),(424,8),(425,8),(426,8),(427,8),(428,8),(429,8),(430,8),(431,8),(432,8),(433,8),(434,8),(435,8),(436,8),(437,8),(438,8),(439,8),(440,8),(441,8),(442,8),(443,8),(444,8),(445,8),(446,8),(447,8),(448,8),(449,8),(450,8),(451,8),(452,8),(453,8),(454,8),(455,8),(456,8),(457,8),(458,8),(459,8),(460,8),(461,8),(462,8),(463,8),(464,8),(465,8),(466,8),(467,8),(468,8),(469,8),(470,8),(471,8),(472,8),(473,8),(474,8),(475,8),(476,8),(477,8),(478,8),(479,8),(480,8),(481,8),(482,8),(483,8),(484,8),(485,8),(486,8),(487,8),(488,8),(489,8),(490,8),(491,8),(492,8),(493,8),(494,8),(495,8),(496,8),(497,8),(498,8),(499,8),(500,8),(501,8),(502,8),(503,8),(504,8),(505,8),(506,8),(507,8),(508,8),(509,8),(510,8),(511,8),(512,8),(513,8),(514,8),(515,8),(516,8),(517,8),(518,8),(519,8),(520,8),(521,8),(522,8),(523,8),(524,8),(525,8),(526,8),(527,8),(528,8),(529,8),(530,8),(531,8),(532,8),(533,8),(534,8),(535,8),(536,8),(537,8),(538,8),(539,8),(540,8),(541,8),(542,8),(543,8),(544,8),(545,8),(546,8),(547,8),(548,8),(549,8),(550,8),(551,8),(552,8),(553,8),(554,8),(555,8),(556,8),(557,8),(558,8),(559,8),(560,8),(561,8),(562,8),(563,8),(564,8),(565,8),(566,8),(567,8),(568,8),(569,8),(570,8),(571,8),(572,8),(573,8),(574,8),(575,8),(576,8),(577,8),(578,8),(579,8),(580,8),(581,8),(582,8),(583,8),(584,8),(585,8),(586,8),(587,8),(588,8),(589,8),(590,8),(591,8),(592,8),(593,8),(594,8),(595,8),(596,8),(597,8),(598,8),(599,8),(600,8),(601,8),(602,8),(603,8),(604,8),(605,8),(606,8),(607,8),(608,8),(609,8),(610,8),(611,8),(612,8),(613,8),(614,8),(615,8),(616,8),(617,8),(618,8),(619,8),(620,8),(621,8),(622,8),(623,8),(624,8),(625,8),(626,8),(627,8),(628,8),(629,8),(630,8),(631,8),(632,8),(633,8),(634,8),(635,8),(636,8),(637,8),(638,8),(639,8),(640,8),(641,8),(642,8),(643,8),(644,8),(645,8),(646,8),(647,8),(648,8),(649,8),(650,8),(651,8),(652,8),(653,8),(654,8),(655,8),(656,8),(657,8),(658,8),(659,8),(660,8),(661,8),(662,8),(663,8),(664,8),(665,8),(666,8),(667,8),(668,8),(669,8),(670,8),(671,8),(672,8),(673,8),(674,8),(675,8),(676,8),(677,8),(678,8),(679,8),(680,8),(681,8),(682,8),(683,8),(684,8),(685,8),(686,8),(687,8),(688,8),(689,8),(690,8),(691,8),(692,8),(693,8),(694,8),(695,8),(696,8),(697,8),(698,8),(699,8),(700,8),(701,8),(702,8),(703,8),(704,8),(705,8),(706,8),(707,8),(708,8),(709,8),(710,8),(711,8),(712,8),(713,8),(714,8),(715,8),(716,8),(717,8),(718,8),(719,8),(720,8),(721,8),(722,8),(723,8),(724,8),(725,8),(726,8),(727,8),(728,8),(729,8),(730,8),(731,8),(732,8),(733,8),(734,8),(735,8),(736,8),(737,8),(738,8),(739,8),(740,8),(741,8),(742,8),(743,8),(744,8),(745,8),(746,8),(747,8),(748,8),(749,8),(750,8),(751,8),(752,8),(753,8),(754,8),(755,8),(756,8),(757,8),(758,8),(759,8),(760,8),(761,8),(762,8),(763,8),(764,8),(765,8),(766,8),(767,8),(768,8),(769,8),(770,8),(771,8),(772,8),(773,8),(774,8),(775,8),(776,8),(777,8),(778,8),(779,8),(780,8),(781,8),(782,8),(783,8),(784,8),(785,8),(786,8),(787,8),(788,8),(789,8),(790,8),(791,8),(792,8),(793,8),(794,8),(795,8),(796,8),(797,8),(798,8),(799,8),(800,8),(801,8),(802,8),(803,8),(804,8),(805,8),(806,8),(807,8),(808,8),(809,8),(810,8),(811,8),(812,8),(813,8),(814,8),(815,8),(816,8),(817,8),(818,8),(819,8),(820,8),(821,8),(822,8),(823,8),(824,8),(825,8),(826,8),(827,8),(828,8),(829,8),(830,8),(831,8),(832,8),(833,8),(834,8),(835,8),(836,8),(837,8),(838,8),(839,8),(840,8),(841,8),(842,8),(843,8),(844,8),(845,8),(846,8),(847,8),(848,8),(849,8),(850,8),(851,8),(852,8),(853,8),(854,8),(855,8),(856,8),(857,8),(858,8),(859,8),(860,8),(861,8),(862,8),(863,8),(864,8),(865,8),(866,8),(867,8),(868,8),(869,8),(870,8),(871,8),(872,8),(873,8),(874,8),(875,8),(876,8),(877,8),(878,8),(879,8),(880,8),(881,8),(882,8),(883,8),(884,8),(885,8),(886,8),(887,8),(888,8),(889,8),(890,8),(891,8),(892,8),(893,8),(894,8),(895,8),(896,8),(897,8),(898,8),(899,8),(900,8),(901,8),(902,8),(903,8),(904,8),(905,8),(906,8),(907,8),(908,8),(909,8),(910,8),(911,8),(912,8),(913,8),(914,8),(915,8),(916,8),(917,8),(918,8),(919,8),(920,8),(921,8),(922,8),(923,8),(924,8),(925,8),(926,8),(927,8),(928,8),(929,8),(930,8),(931,8),(932,8),(933,8),(934,8),(935,8),(936,8),(937,8),(938,8),(939,8),(940,8),(941,8),(942,8),(943,8),(944,8),(945,8),(946,8),(947,8),(948,8),(949,8),(950,8),(951,8),(952,8),(953,8),(954,8),(955,8),(956,8),(957,8),(958,8),(959,8),(960,8),(961,8),(962,8),(963,8),(964,8),(965,8),(966,8),(967,8),(968,8),(297,10),(298,10),(299,10),(300,10),(309,10),(310,10),(311,10),(312,10),(317,10),(321,10),(322,10),(323,10),(324,10),(329,10),(333,10),(334,10),(335,10),(336,10),(341,10),(369,10),(370,10),(371,10),(372,10),(377,10),(1,11),(2,11),(3,11),(4,11),(5,11),(6,11),(7,11),(8,11),(9,11),(10,11),(11,11),(12,11),(13,11),(14,11),(15,11),(16,11),(17,11),(18,11),(19,11),(20,11),(21,11),(22,11),(23,11),(24,11),(25,11),(26,11),(27,11),(28,11),(29,11),(30,11),(31,11),(32,11),(33,11),(34,11),(35,11),(36,11),(37,11),(38,11),(39,11),(40,11),(41,11),(42,11),(55,11),(56,11),(57,11),(58,11),(59,11),(60,11),(61,11),(62,11),(63,11),(64,11),(65,11),(66,11),(67,11),(68,11),(69,11),(70,11),(71,11),(72,11),(73,11),(74,11),(75,11),(76,11),(77,11),(78,11),(91,11),(92,11),(93,11),(94,11),(95,11),(96,11),(97,11),(98,11),(99,11),(100,11),(101,11),(102,11),(103,11),(104,11),(105,11),(106,11),(107,11),(108,11),(109,11),(110,11),(111,11),(112,11),(113,11),(114,11),(115,11),(116,11),(117,11),(118,11),(119,11),(120,11),(121,11),(122,11),(123,11),(124,11),(125,11),(126,11),(127,11),(128,11),(129,11),(130,11),(131,11),(132,11),(133,11),(134,11),(135,11),(136,11),(137,11),(138,11),(139,11),(140,11),(141,11),(142,11),(143,11),(144,11),(145,11),(146,11),(147,11),(148,11),(149,11),(150,11),(151,11),(152,11),(153,11),(154,11),(155,11),(156,11),(157,11),(158,11),(159,11),(160,11),(161,11),(162,11),(163,11),(164,11),(165,11),(166,11),(167,11),(168,11),(169,11),(170,11),(171,11),(172,11),(173,11),(174,11),(175,11),(176,11),(177,11),(178,11),(179,11),(180,11),(181,11),(182,11),(183,11),(184,11),(185,11),(186,11),(187,11),(188,11),(189,11),(190,11),(191,11),(192,11),(193,11),(194,11),(195,11),(196,11),(197,11),(198,11),(199,11),(200,11),(201,11),(202,11),(203,11),(204,11),(205,11),(206,11),(207,11),(208,11),(209,11),(210,11),(211,11),(212,11),(213,11),(214,11),(215,11),(216,11),(217,11),(218,11),(219,11),(220,11),(221,11),(222,11),(223,11),(224,11),(225,11),(226,11),(227,11),(228,11),(229,11),(230,11),(231,11),(232,11),(233,11),(234,11),(235,11),(236,11),(237,11),(238,11),(239,11),(240,11),(241,11),(242,11),(243,11),(244,11),(245,11),(246,11),(247,11),(248,11),(249,11),(250,11),(251,11),(252,11),(253,11),(254,11),(255,11),(256,11),(257,11),(258,11),(259,11),(260,11),(261,11),(262,11),(263,11),(264,11),(265,11),(266,11),(267,11),(268,11),(269,11),(270,11),(271,11),(272,11),(273,11),(274,11),(275,11),(276,11),(277,11),(278,11),(279,11),(280,11),(281,11),(282,11),(283,11),(284,11),(285,11),(286,11),(287,11),(288,11),(289,11),(290,11),(291,11),(292,11),(293,11),(294,11),(295,11),(296,11),(297,11),(298,11),(299,11),(300,11),(301,11),(302,11),(303,11),(304,11),(305,11),(306,11),(307,11),(308,11),(309,11),(310,11),(311,11),(312,11),(313,11),(314,11),(315,11),(316,11),(317,11),(318,11),(319,11),(320,11),(321,11),(322,11),(323,11),(324,11),(325,11),(326,11),(327,11),(328,11),(329,11),(330,11),(331,11),(332,11),(333,11),(334,11),(335,11),(336,11),(337,11),(338,11),(339,11),(340,11),(341,11),(342,11),(343,11),(344,11),(345,11),(346,11),(347,11),(348,11),(349,11),(350,11),(351,11),(352,11),(353,11),(354,11),(355,11),(356,11),(357,11),(358,11),(359,11),(360,11),(361,11),(362,11),(363,11),(364,11),(365,11),(366,11),(367,11),(368,11),(369,11),(370,11),(371,11),(372,11),(373,11),(374,11),(375,11),(376,11),(377,11),(378,11),(379,11),(380,11),(381,11),(382,11),(383,11),(384,11),(385,11),(386,11),(387,11),(388,11),(389,11),(390,11),(391,11),(392,11),(393,11),(394,11),(395,11),(396,11),(397,11),(398,11),(399,11),(400,11),(401,11),(402,11),(403,11),(404,11),(405,11),(406,11),(407,11),(408,11),(409,11),(410,11),(411,11),(412,11),(413,11),(414,11),(415,11),(416,11),(417,11),(418,11),(419,11),(420,11),(421,11),(422,11),(423,11),(424,11),(425,11),(426,11),(427,11),(428,11),(429,11),(430,11),(431,11),(432,11),(433,11),(434,11),(435,11),(436,11),(437,11),(438,11),(439,11),(440,11),(441,11),(442,11),(443,11),(444,11),(445,11),(446,11),(447,11),(448,11),(449,11),(450,11),(451,11),(452,11),(453,11),(454,11),(455,11),(456,11),(457,11),(458,11),(459,11),(460,11),(461,11),(462,11),(463,11),(464,11),(465,11),(466,11),(467,11),(468,11),(469,11),(470,11),(471,11),(472,11),(473,11),(474,11),(475,11),(476,11),(477,11),(478,11),(479,11),(480,11),(481,11),(482,11),(483,11),(484,11),(485,11),(486,11),(487,11),(488,11),(489,11),(490,11),(491,11),(492,11),(493,11),(494,11),(495,11),(496,11),(497,11),(498,11),(499,11),(500,11),(501,11),(502,11),(503,11),(504,11),(505,11),(506,11),(507,11),(508,11),(509,11),(510,11),(511,11),(512,11),(513,11),(514,11),(515,11),(516,11),(517,11),(518,11),(519,11),(520,11),(521,11),(522,11),(523,11),(524,11),(525,11),(526,11),(527,11),(528,11),(529,11),(530,11),(531,11),(532,11),(533,11),(534,11),(535,11),(536,11),(537,11),(538,11),(539,11),(540,11),(541,11),(542,11),(543,11),(544,11),(545,11),(546,11),(547,11),(548,11),(549,11),(550,11),(551,11),(552,11),(553,11),(554,11),(555,11),(556,11),(557,11),(558,11),(559,11),(560,11),(561,11),(562,11),(563,11),(564,11),(565,11),(566,11),(567,11),(568,11),(569,11),(570,11),(571,11),(572,11),(573,11),(574,11),(575,11),(576,11),(577,11),(578,11),(579,11),(580,11),(581,11),(582,11),(583,11),(584,11),(585,11),(586,11),(587,11),(588,11),(589,11),(590,11),(591,11),(592,11),(593,11),(594,11),(595,11),(596,11),(597,11),(598,11),(599,11),(600,11),(601,11),(602,11),(603,11),(604,11),(605,11),(606,11),(607,11),(608,11),(609,11),(610,11),(611,11),(612,11),(613,11),(614,11),(615,11),(616,11),(617,11),(618,11),(619,11),(620,11),(621,11),(622,11),(623,11),(624,11),(625,11),(626,11),(627,11),(628,11),(629,11),(630,11),(631,11),(632,11),(633,11),(634,11),(635,11),(636,11),(637,11),(638,11),(639,11),(640,11),(641,11),(642,11),(643,11),(644,11),(645,11),(646,11),(647,11),(648,11),(649,11),(650,11),(651,11),(652,11),(653,11),(654,11),(655,11),(656,11),(657,11),(658,11),(659,11),(660,11),(661,11),(662,11),(663,11),(664,11),(665,11),(666,11),(667,11),(668,11),(669,11),(670,11),(671,11),(672,11),(673,11),(674,11),(675,11),(676,11),(677,11),(678,11),(679,11),(680,11),(681,11),(682,11),(683,11),(684,11),(685,11),(686,11),(687,11),(688,11),(689,11),(690,11),(691,11),(692,11),(693,11),(694,11),(695,11),(696,11),(697,11),(698,11),(699,11),(700,11),(701,11),(702,11),(703,11),(704,11),(705,11),(706,11),(707,11),(708,11),(709,11),(710,11),(711,11),(712,11),(713,11),(714,11),(715,11),(716,11),(717,11),(718,11),(719,11),(720,11),(721,11),(722,11),(723,11),(724,11),(725,11),(726,11),(727,11),(728,11),(729,11),(730,11),(731,11),(732,11),(733,11),(734,11),(735,11),(736,11),(737,11),(738,11),(739,11),(740,11),(741,11),(742,11),(743,11),(744,11),(745,11),(746,11),(747,11),(748,11),(749,11),(750,11),(751,11),(752,11),(753,11),(754,11),(755,11),(756,11),(757,11),(758,11),(759,11),(760,11),(761,11),(762,11),(763,11),(764,11),(765,11),(766,11),(767,11),(768,11),(769,11),(770,11),(771,11),(772,11),(773,11),(774,11),(775,11),(776,11),(777,11),(778,11),(779,11),(780,11),(781,11),(782,11),(783,11),(784,11),(785,11),(786,11),(787,11),(788,11),(789,11),(790,11),(791,11),(792,11),(793,11),(794,11),(795,11),(796,11),(797,11),(798,11),(799,11),(800,11),(801,11),(802,11),(803,11),(804,11),(805,11),(806,11),(807,11),(808,11),(809,11),(810,11),(811,11),(812,11),(813,11),(814,11),(815,11),(816,11),(817,11),(818,11),(819,11),(820,11),(821,11),(822,11),(823,11),(824,11),(825,11),(826,11),(827,11),(828,11),(829,11),(830,11),(831,11),(832,11),(833,11),(834,11),(835,11),(836,11),(837,11),(838,11),(839,11),(840,11),(841,11),(842,11),(843,11),(844,11),(845,11),(846,11),(847,11),(848,11),(849,11),(850,11),(851,11),(852,11),(853,11),(854,11),(855,11),(856,11),(857,11),(858,11),(859,11),(860,11),(861,11),(862,11),(863,11),(864,11),(865,11),(866,11),(867,11),(868,11),(869,11),(870,11),(871,11),(872,11),(873,11),(874,11),(875,11),(876,11),(877,11),(878,11),(879,11),(880,11),(881,11),(882,11),(883,11),(884,11),(885,11),(886,11),(887,11),(888,11),(889,11),(890,11),(891,11),(892,11),(893,11),(894,11),(895,11),(896,11),(897,11),(898,11),(899,11),(900,11),(901,11),(902,11),(903,11),(904,11),(905,11),(906,11),(907,11),(908,11),(909,11),(910,11),(911,11),(912,11),(913,11),(914,11),(915,11),(916,11),(917,11),(918,11),(919,11),(920,11),(921,11),(922,11),(923,11),(924,11),(925,11),(926,11),(927,11),(928,11),(929,11),(930,11),(931,11),(932,11),(933,11),(934,11),(935,11),(936,11),(937,11),(938,11),(939,11),(940,11),(941,11),(942,11),(943,11),(944,11),(945,11),(946,11),(947,11),(948,11),(949,11),(950,11),(951,11),(952,11),(953,11),(954,11),(955,11),(956,11),(957,11),(958,11),(959,11),(960,11),(961,11),(962,11),(963,11),(964,11),(965,11),(966,11),(967,11),(968,11),(1,14),(2,14),(3,14),(4,14),(5,14),(6,14),(7,14),(8,14),(9,14),(10,14),(11,14),(12,14),(13,14),(14,14),(15,14),(16,14),(17,14),(18,14),(19,14),(20,14),(21,14),(22,14),(23,14),(24,14),(25,14),(26,14),(27,14),(28,14),(29,14),(30,14),(31,14),(32,14),(33,14),(34,14),(35,14),(36,14),(37,14),(38,14),(39,14),(40,14),(41,14),(42,14),(55,14),(56,14),(57,14),(58,14),(59,14),(60,14),(61,14),(62,14),(63,14),(64,14),(65,14),(66,14),(67,14),(68,14),(69,14),(70,14),(71,14),(72,14),(73,14),(74,14),(75,14),(76,14),(77,14),(78,14),(91,14),(92,14),(93,14),(94,14),(95,14),(96,14),(97,14),(98,14),(99,14),(100,14),(101,14),(102,14),(103,14),(104,14),(105,14),(106,14),(107,14),(108,14),(109,14),(110,14),(111,14),(112,14),(113,14),(114,14),(115,14),(116,14),(117,14),(118,14),(119,14),(120,14),(121,14),(122,14),(123,14),(124,14),(125,14),(126,14),(127,14),(128,14),(129,14),(130,14),(131,14),(132,14),(133,14),(134,14),(135,14),(136,14),(137,14),(138,14),(139,14),(140,14),(141,14),(142,14),(143,14),(144,14),(145,14),(146,14),(147,14),(148,14),(149,14),(150,14),(151,14),(152,14),(153,14),(154,14),(155,14),(156,14),(157,14),(158,14),(159,14),(160,14),(161,14),(162,14),(163,14),(164,14),(165,14),(166,14),(167,14),(168,14),(169,14),(170,14),(171,14),(172,14),(173,14),(174,14),(175,14),(176,14),(177,14),(178,14),(179,14),(180,14),(181,14),(182,14),(183,14),(184,14),(185,14),(186,14),(187,14),(188,14),(189,14),(190,14),(191,14),(192,14),(193,14),(194,14),(195,14),(196,14),(197,14),(198,14),(199,14),(200,14),(201,14),(202,14),(203,14),(204,14),(205,14),(206,14),(207,14),(208,14),(209,14),(210,14),(211,14),(212,14),(213,14),(214,14),(215,14),(216,14),(217,14),(218,14),(219,14),(220,14),(221,14),(222,14),(223,14),(224,14),(225,14),(226,14),(227,14),(228,14),(229,14),(230,14),(231,14),(232,14),(233,14),(234,14),(235,14),(236,14),(237,14),(238,14),(239,14),(240,14),(241,14),(242,14),(243,14),(244,14),(245,14),(246,14),(247,14),(248,14),(249,14),(250,14),(251,14),(252,14),(253,14),(254,14),(255,14),(256,14),(257,14),(258,14),(259,14),(260,14),(261,14),(262,14),(263,14),(264,14),(265,14),(266,14),(267,14),(268,14),(269,14),(270,14),(271,14),(272,14),(273,14),(274,14),(275,14),(276,14),(277,14),(278,14),(279,14),(280,14),(281,14),(282,14),(283,14),(284,14),(285,14),(286,14),(287,14),(288,14),(289,14),(290,14),(291,14),(292,14),(293,14),(294,14),(295,14),(296,14),(297,14),(298,14),(299,14),(300,14),(301,14),(302,14),(303,14),(304,14),(305,14),(306,14),(307,14),(308,14),(309,14),(310,14),(311,14),(312,14),(313,14),(314,14),(315,14),(316,14),(317,14),(318,14),(319,14),(320,14),(321,14),(322,14),(323,14),(324,14),(325,14),(326,14),(327,14),(328,14),(329,14),(330,14),(331,14),(332,14),(333,14),(334,14),(335,14),(336,14),(337,14),(338,14),(339,14),(340,14),(341,14),(342,14),(343,14),(344,14),(345,14),(346,14),(347,14),(348,14),(349,14),(350,14),(351,14),(352,14),(353,14),(354,14),(355,14),(356,14),(357,14),(358,14),(359,14),(360,14),(361,14),(362,14),(363,14),(364,14),(365,14),(366,14),(367,14),(368,14),(369,14),(370,14),(371,14),(372,14),(373,14),(374,14),(375,14),(376,14),(377,14),(378,14),(379,14),(380,14),(381,14),(382,14),(383,14),(384,14),(385,14),(386,14),(387,14),(388,14),(389,14),(390,14),(391,14),(392,14),(393,14),(394,14),(395,14),(396,14),(397,14),(398,14),(399,14),(400,14),(401,14),(402,14),(403,14),(404,14),(405,14),(406,14),(407,14),(408,14),(409,14),(410,14),(411,14),(412,14),(413,14),(414,14),(415,14),(416,14),(417,14),(418,14),(419,14),(420,14),(421,14),(422,14),(423,14),(424,14),(425,14),(426,14),(427,14),(428,14),(429,14),(430,14),(431,14),(432,14),(433,14),(434,14),(435,14),(436,14),(437,14),(438,14),(439,14),(440,14),(441,14),(442,14),(443,14),(444,14),(445,14),(446,14),(447,14),(448,14),(449,14),(450,14),(451,14),(452,14),(453,14),(454,14),(455,14),(456,14),(457,14),(458,14),(459,14),(460,14),(461,14),(462,14),(463,14),(464,14),(465,14),(466,14),(467,14),(468,14),(469,14),(470,14),(471,14),(472,14),(473,14),(474,14),(475,14),(476,14),(477,14),(478,14),(479,14),(480,14),(481,14),(482,14),(483,14),(484,14),(485,14),(486,14),(487,14),(488,14),(489,14),(490,14),(491,14),(492,14),(493,14),(494,14),(495,14),(496,14),(497,14),(498,14),(499,14),(500,14),(501,14),(502,14),(503,14),(504,14),(505,14),(506,14),(507,14),(508,14),(509,14),(510,14),(511,14),(512,14),(513,14),(514,14),(515,14),(516,14),(517,14),(518,14),(519,14),(520,14),(521,14),(522,14),(523,14),(524,14),(525,14),(526,14),(527,14),(528,14),(529,14),(530,14),(531,14),(532,14),(533,14),(534,14),(535,14),(536,14),(537,14),(538,14),(539,14),(540,14),(541,14),(542,14),(543,14),(544,14),(545,14),(546,14),(547,14),(548,14),(549,14),(550,14),(551,14),(552,14),(553,14),(554,14),(555,14),(556,14),(557,14),(558,14),(559,14),(560,14),(561,14),(562,14),(563,14),(564,14),(565,14),(566,14),(567,14),(568,14),(569,14),(570,14),(571,14),(572,14),(573,14),(574,14),(575,14),(576,14),(577,14),(578,14),(579,14),(580,14),(581,14),(582,14),(583,14),(584,14),(585,14),(586,14),(587,14),(588,14),(589,14),(590,14),(591,14),(592,14),(593,14),(594,14),(595,14),(596,14),(597,14),(598,14),(599,14),(600,14),(601,14),(602,14),(603,14),(604,14),(605,14),(606,14),(607,14),(608,14),(609,14),(610,14),(611,14),(612,14),(613,14),(614,14),(615,14),(616,14),(617,14),(618,14),(619,14),(620,14),(621,14),(622,14),(623,14),(624,14),(625,14),(626,14),(627,14),(628,14),(629,14),(630,14),(631,14),(632,14),(633,14),(634,14),(635,14),(636,14),(637,14),(638,14),(639,14),(640,14),(641,14),(642,14),(643,14),(644,14),(645,14),(646,14),(647,14),(648,14),(649,14),(650,14),(651,14),(652,14),(653,14),(654,14),(655,14),(656,14),(657,14),(658,14),(659,14),(660,14),(661,14),(662,14),(663,14),(664,14),(665,14),(666,14),(667,14),(668,14),(669,14),(670,14),(671,14),(672,14),(673,14),(674,14),(675,14),(676,14),(677,14),(678,14),(679,14),(680,14),(681,14),(682,14),(683,14),(684,14),(685,14),(686,14),(687,14),(688,14),(689,14),(690,14),(691,14),(692,14),(693,14),(694,14),(695,14),(696,14),(697,14),(698,14),(699,14),(700,14),(701,14),(702,14),(703,14),(704,14),(705,14),(706,14),(707,14),(708,14),(709,14),(710,14),(711,14),(712,14),(713,14),(714,14),(715,14),(716,14),(717,14),(718,14),(719,14),(720,14),(721,14),(722,14),(723,14),(724,14),(725,14),(726,14),(727,14),(728,14),(729,14),(730,14),(731,14),(732,14),(733,14),(734,14),(735,14),(736,14),(737,14),(738,14),(739,14),(740,14),(741,14),(742,14),(743,14),(744,14),(745,14),(746,14),(747,14),(748,14),(749,14),(750,14),(751,14),(752,14),(753,14),(754,14),(755,14),(756,14),(757,14),(758,14),(759,14),(760,14),(761,14),(762,14),(763,14),(764,14),(765,14),(766,14),(767,14),(768,14),(769,14),(770,14),(771,14),(772,14),(773,14),(774,14),(775,14),(776,14),(777,14),(778,14),(779,14),(780,14),(781,14),(782,14),(783,14),(784,14),(785,14),(786,14),(787,14),(788,14),(789,14),(790,14),(791,14),(792,14),(793,14),(794,14),(795,14),(796,14),(797,14),(798,14),(799,14),(800,14),(801,14),(802,14),(803,14),(804,14),(805,14),(806,14),(807,14),(808,14),(809,14),(810,14),(811,14),(812,14),(813,14),(814,14),(815,14),(816,14),(817,14),(818,14),(819,14),(820,14),(821,14),(822,14),(823,14),(824,14),(825,14),(826,14),(827,14),(828,14),(829,14),(830,14),(831,14),(832,14),(833,14),(834,14),(835,14),(836,14),(837,14),(838,14),(839,14),(840,14),(841,14),(842,14),(843,14),(844,14),(845,14),(846,14),(847,14),(848,14),(849,14),(850,14),(851,14),(852,14),(853,14),(854,14),(855,14),(856,14),(857,14),(858,14),(859,14),(860,14),(861,14),(862,14),(863,14),(864,14),(865,14),(866,14),(867,14),(868,14),(869,14),(870,14),(871,14),(872,14),(873,14),(874,14),(875,14),(876,14),(877,14),(878,14),(879,14),(880,14),(881,14),(882,14),(883,14),(884,14),(885,14),(886,14),(887,14),(888,14),(889,14),(890,14),(891,14),(892,14),(893,14),(894,14),(895,14),(896,14),(897,14),(898,14),(899,14),(900,14),(901,14),(902,14),(903,14),(904,14),(905,14),(906,14),(907,14),(908,14),(909,14),(910,14),(911,14),(912,14),(913,14),(914,14),(915,14),(916,14),(917,14),(918,14),(919,14),(920,14),(921,14),(922,14),(923,14),(924,14),(925,14),(926,14),(927,14),(928,14),(929,14),(930,14),(931,14),(932,14),(933,14),(934,14),(935,14),(936,14),(937,14),(938,14),(939,14),(940,14),(941,14),(942,14),(943,14),(944,14),(945,14),(946,14),(947,14),(948,14),(949,14),(950,14),(951,14),(952,14),(953,14),(954,14),(955,14),(956,14),(957,14),(958,14),(959,14),(960,14),(961,14),(962,14),(963,14),(964,14),(965,14),(966,14),(967,14),(968,14),(1,15),(2,15),(3,15),(4,15),(5,15),(6,15),(7,15),(8,15),(9,15),(10,15),(11,15),(12,15),(13,15),(14,15),(15,15),(16,15),(17,15),(18,15),(19,15),(20,15),(21,15),(22,15),(23,15),(24,15),(25,15),(26,15),(27,15),(28,15),(29,15),(30,15),(31,15),(32,15),(33,15),(34,15),(35,15),(36,15),(37,15),(38,15),(39,15),(40,15),(41,15),(42,15),(55,15),(56,15),(57,15),(58,15),(59,15),(60,15),(61,15),(62,15),(63,15),(64,15),(65,15),(66,15),(67,15),(68,15),(69,15),(70,15),(71,15),(72,15),(73,15),(74,15),(75,15),(76,15),(77,15),(78,15),(91,15),(92,15),(93,15),(94,15),(95,15),(96,15),(97,15),(98,15),(99,15),(100,15),(101,15),(102,15),(103,15),(104,15),(105,15),(106,15),(107,15),(108,15),(109,15),(110,15),(111,15),(112,15),(113,15),(114,15),(115,15),(116,15),(117,15),(118,15),(119,15),(120,15),(121,15),(122,15),(123,15),(124,15),(125,15),(126,15),(127,15),(128,15),(129,15),(130,15),(131,15),(132,15),(133,15),(134,15),(135,15),(136,15),(137,15),(138,15),(139,15),(140,15),(141,15),(142,15),(143,15),(144,15),(145,15),(146,15),(147,15),(148,15),(149,15),(150,15),(151,15),(152,15),(153,15),(154,15),(155,15),(156,15),(157,15),(158,15),(159,15),(160,15),(161,15),(162,15),(163,15),(164,15),(165,15),(166,15),(167,15),(168,15),(169,15),(170,15),(171,15),(172,15),(173,15),(174,15),(175,15),(176,15),(177,15),(178,15),(179,15),(180,15),(181,15),(182,15),(183,15),(184,15),(185,15),(186,15),(187,15),(188,15),(189,15),(190,15),(191,15),(192,15),(193,15),(194,15),(195,15),(196,15),(197,15),(198,15),(199,15),(200,15),(201,15),(202,15),(203,15),(204,15),(205,15),(206,15),(207,15),(208,15),(209,15),(210,15),(211,15),(212,15),(213,15),(214,15),(215,15),(216,15),(217,15),(218,15),(219,15),(220,15),(221,15),(222,15),(223,15),(224,15),(225,15),(226,15),(227,15),(228,15),(229,15),(230,15),(231,15),(232,15),(233,15),(234,15),(235,15),(236,15),(237,15),(238,15),(239,15),(240,15),(241,15),(242,15),(243,15),(244,15),(245,15),(246,15),(247,15),(248,15),(249,15),(250,15),(251,15),(252,15),(253,15),(254,15),(255,15),(256,15),(257,15),(258,15),(259,15),(260,15),(261,15),(262,15),(263,15),(264,15),(265,15),(266,15),(267,15),(268,15),(269,15),(270,15),(271,15),(272,15),(273,15),(274,15),(275,15),(276,15),(277,15),(278,15),(279,15),(280,15),(281,15),(282,15),(283,15),(284,15),(285,15),(286,15),(287,15),(288,15),(289,15),(290,15),(291,15),(292,15),(293,15),(294,15),(295,15),(296,15),(297,15),(298,15),(299,15),(300,15),(301,15),(302,15),(303,15),(304,15),(305,15),(306,15),(307,15),(308,15),(309,15),(310,15),(311,15),(312,15),(313,15),(314,15),(315,15),(316,15),(317,15),(318,15),(319,15),(320,15),(321,15),(322,15),(323,15),(324,15),(325,15),(326,15),(327,15),(328,15),(329,15),(330,15),(331,15),(332,15),(333,15),(334,15),(335,15),(336,15),(337,15),(338,15),(339,15),(340,15),(341,15),(342,15),(343,15),(344,15),(345,15),(346,15),(347,15),(348,15),(349,15),(350,15),(351,15),(352,15),(353,15),(354,15),(355,15),(356,15),(357,15),(358,15),(359,15),(360,15),(361,15),(362,15),(363,15),(364,15),(365,15),(366,15),(367,15),(368,15),(369,15),(370,15),(371,15),(372,15),(373,15),(374,15),(375,15),(376,15),(377,15),(378,15),(379,15),(380,15),(381,15),(382,15),(383,15),(384,15),(385,15),(386,15),(387,15),(388,15),(389,15),(390,15),(391,15),(392,15),(393,15),(394,15),(395,15),(396,15),(397,15),(398,15),(399,15),(400,15),(401,15),(402,15),(403,15),(404,15),(405,15),(406,15),(407,15),(408,15),(409,15),(410,15),(411,15),(412,15),(413,15),(414,15),(415,15),(416,15),(417,15),(418,15),(419,15),(420,15),(421,15),(422,15),(423,15),(424,15),(425,15),(426,15),(427,15),(428,15),(429,15),(430,15),(431,15),(432,15),(433,15),(434,15),(435,15),(436,15),(437,15),(438,15),(439,15),(440,15),(441,15),(442,15),(443,15),(444,15),(445,15),(446,15),(447,15),(448,15),(449,15),(450,15),(451,15),(452,15),(453,15),(454,15),(455,15),(456,15),(457,15),(458,15),(459,15),(460,15),(461,15),(462,15),(463,15),(464,15),(465,15),(466,15),(467,15),(468,15),(469,15),(470,15),(471,15),(472,15),(473,15),(474,15),(475,15),(476,15),(477,15),(478,15),(479,15),(480,15),(481,15),(482,15),(483,15),(484,15),(485,15),(486,15),(487,15),(488,15),(489,15),(490,15),(491,15),(492,15),(493,15),(494,15),(495,15),(496,15),(497,15),(498,15),(499,15),(500,15),(501,15),(502,15),(503,15),(504,15),(505,15),(506,15),(507,15),(508,15),(509,15),(510,15),(511,15),(512,15),(513,15),(514,15),(515,15),(516,15),(517,15),(518,15),(519,15),(520,15),(521,15),(522,15),(523,15),(524,15),(525,15),(526,15),(527,15),(528,15),(529,15),(530,15),(531,15),(532,15),(533,15),(534,15),(535,15),(536,15),(537,15),(538,15),(539,15),(540,15),(541,15),(542,15),(543,15),(544,15),(545,15),(546,15),(547,15),(548,15),(549,15),(550,15),(551,15),(552,15),(553,15),(554,15),(555,15),(556,15),(557,15),(558,15),(559,15),(560,15),(561,15),(562,15),(563,15),(564,15),(565,15),(566,15),(567,15),(568,15),(569,15),(570,15),(571,15),(572,15),(573,15),(574,15),(575,15),(576,15),(577,15),(578,15),(579,15),(580,15),(581,15),(582,15),(583,15),(584,15),(585,15),(586,15),(587,15),(588,15),(589,15),(590,15),(591,15),(592,15),(593,15),(594,15),(595,15),(596,15),(597,15),(598,15),(599,15),(600,15),(601,15),(602,15),(603,15),(604,15),(605,15),(606,15),(607,15),(608,15),(609,15),(610,15),(611,15),(612,15),(613,15),(614,15),(615,15),(616,15),(617,15),(618,15),(619,15),(620,15),(621,15),(622,15),(623,15),(624,15),(625,15),(626,15),(627,15),(628,15),(629,15),(630,15),(631,15),(632,15),(633,15),(634,15),(635,15),(636,15),(637,15),(638,15),(639,15),(640,15),(641,15),(642,15),(643,15),(644,15),(645,15),(646,15),(647,15),(648,15),(649,15),(650,15),(651,15),(652,15),(653,15),(654,15),(655,15),(656,15),(657,15),(658,15),(659,15),(660,15),(661,15),(662,15),(663,15),(664,15),(665,15),(666,15),(667,15),(668,15),(669,15),(670,15),(671,15),(672,15),(673,15),(674,15),(675,15),(676,15),(677,15),(678,15),(679,15),(680,15),(681,15),(682,15),(683,15),(684,15),(685,15),(686,15),(687,15),(688,15),(689,15),(690,15),(691,15),(692,15),(693,15),(694,15),(695,15),(696,15),(697,15),(698,15),(699,15),(700,15),(701,15),(702,15),(703,15),(704,15),(705,15),(706,15),(707,15),(708,15),(709,15),(710,15),(711,15),(712,15),(713,15),(714,15),(715,15),(716,15),(717,15),(718,15),(719,15),(720,15),(721,15),(722,15),(723,15),(724,15),(725,15),(726,15),(727,15),(728,15),(729,15),(730,15),(731,15),(732,15),(733,15),(734,15),(735,15),(736,15),(737,15),(738,15),(739,15),(740,15),(741,15),(742,15),(743,15),(744,15),(745,15),(746,15),(747,15),(748,15),(749,15),(750,15),(751,15),(752,15),(753,15),(754,15),(755,15),(756,15),(757,15),(758,15),(759,15),(760,15),(761,15),(762,15),(763,15),(764,15),(765,15),(766,15),(767,15),(768,15),(769,15),(770,15),(771,15),(772,15),(773,15),(774,15),(775,15),(776,15),(777,15),(778,15),(779,15),(780,15),(781,15),(782,15),(783,15),(784,15),(785,15),(786,15),(787,15),(788,15),(789,15),(790,15),(791,15),(792,15),(793,15),(794,15),(795,15),(796,15),(797,15),(798,15),(799,15),(800,15),(801,15),(802,15),(803,15),(804,15),(805,15),(806,15),(807,15),(808,15),(809,15),(810,15),(811,15),(812,15),(813,15),(814,15),(815,15),(816,15),(817,15),(818,15),(819,15),(820,15),(821,15),(822,15),(823,15),(824,15),(825,15),(826,15),(827,15),(828,15),(829,15),(830,15),(831,15),(832,15),(833,15),(834,15),(835,15),(836,15),(837,15),(838,15),(839,15),(840,15),(841,15),(842,15),(843,15),(844,15),(845,15),(846,15),(847,15),(848,15),(849,15),(850,15),(851,15),(852,15),(853,15),(854,15),(855,15),(856,15),(857,15),(858,15),(859,15),(860,15),(861,15),(862,15),(863,15),(864,15),(865,15),(866,15),(867,15),(868,15),(869,15),(870,15),(871,15),(872,15),(873,15),(874,15),(875,15),(876,15),(877,15),(878,15),(879,15),(880,15),(881,15),(882,15),(883,15),(884,15),(885,15),(886,15),(887,15),(888,15),(889,15),(890,15),(891,15),(892,15),(893,15),(894,15),(895,15),(896,15),(897,15),(898,15),(899,15),(900,15),(901,15),(902,15),(903,15),(904,15),(905,15),(906,15),(907,15),(908,15),(909,15),(910,15),(911,15),(912,15),(913,15),(914,15),(915,15),(916,15),(917,15),(918,15),(919,15),(920,15),(921,15),(922,15),(923,15),(924,15),(925,15),(926,15),(927,15),(928,15),(929,15),(930,15),(931,15),(932,15),(933,15),(934,15),(935,15),(936,15),(937,15),(938,15),(939,15),(940,15),(941,15),(942,15),(943,15),(944,15),(945,15),(946,15),(947,15),(948,15),(949,15),(950,15),(951,15),(952,15),(953,15),(954,15),(955,15),(956,15),(957,15),(958,15),(959,15),(960,15),(961,15),(962,15),(963,15),(964,15),(965,15),(966,15),(967,15),(968,15),(19,17),(20,17),(21,17),(22,17),(23,17),(24,17),(25,17),(26,17),(27,17),(28,17),(29,17),(30,17),(31,17),(32,17),(33,17),(34,17),(35,17),(36,17),(37,17),(38,17),(39,17),(40,17),(41,17),(42,17),(55,17),(56,17),(57,17),(58,17),(59,17),(60,17),(61,17),(62,17),(63,17),(64,17),(65,17),(66,17),(67,17),(68,17),(69,17),(70,17),(71,17),(72,17),(73,17),(74,17),(75,17),(76,17),(77,17),(78,17),(91,17),(92,17),(93,17),(94,17),(95,17),(96,17),(97,17),(98,17),(99,17),(100,17),(101,17),(102,17),(103,17),(104,17),(105,17),(106,17),(107,17),(108,17),(109,17),(110,17),(111,17),(112,17),(113,17),(114,17),(115,17),(116,17),(117,17),(118,17),(119,17),(120,17),(121,17),(122,17),(123,17),(124,17),(125,17),(126,17),(127,17),(128,17),(129,17),(130,17),(131,17),(132,17),(133,17),(134,17),(135,17),(136,17),(137,17),(138,17),(139,17),(140,17),(141,17),(142,17),(143,17),(144,17),(145,17),(146,17),(147,17),(148,17),(149,17),(150,17),(151,17),(152,17),(153,17),(154,17),(155,17),(156,17),(157,17),(158,17),(159,17),(160,17),(161,17),(162,17),(163,17),(164,17),(165,17),(166,17),(167,17),(168,17),(169,17),(170,17),(171,17),(172,17),(173,17),(174,17),(175,17),(176,17),(177,17),(178,17),(179,17),(180,17),(181,17),(182,17),(183,17),(184,17),(185,17),(186,17),(187,17),(188,17),(189,17),(190,17),(191,17),(192,17),(193,17),(194,17),(195,17),(196,17),(197,17),(198,17),(199,17),(200,17),(201,17),(202,17),(203,17),(204,17),(205,17),(206,17),(207,17),(208,17),(209,17),(210,17),(211,17),(212,17),(213,17),(214,17),(215,17),(216,17),(217,17),(218,17),(219,17),(220,17),(221,17),(222,17),(223,17),(224,17),(225,17),(226,17),(227,17),(228,17),(229,17),(230,17),(231,17),(232,17),(233,17),(234,17),(235,17),(236,17),(237,17),(238,17),(239,17),(240,17),(241,17),(242,17),(243,17),(244,17),(245,17),(246,17),(247,17),(248,17),(249,17),(250,17),(251,17),(252,17),(253,17),(254,17),(255,17),(256,17),(257,17),(258,17),(259,17),(260,17),(261,17),(262,17),(263,17),(264,17),(265,17),(266,17),(267,17),(268,17),(269,17),(270,17),(271,17),(272,17),(273,17),(274,17),(275,17),(276,17),(277,17),(278,17),(279,17),(280,17),(281,17),(282,17),(283,17),(284,17),(285,17),(286,17),(287,17),(288,17),(289,17),(290,17),(291,17),(292,17),(293,17),(294,17),(295,17),(296,17),(297,17),(298,17),(299,17),(300,17),(301,17),(302,17),(303,17),(304,17),(305,17),(306,17),(307,17),(308,17),(309,17),(310,17),(311,17),(312,17),(313,17),(314,17),(315,17),(316,17),(317,17),(318,17),(319,17),(320,17),(321,17),(322,17),(323,17),(324,17),(325,17),(326,17),(327,17),(328,17),(329,17),(330,17),(331,17),(332,17),(333,17),(334,17),(335,17),(336,17),(337,17),(338,17),(339,17),(340,17),(341,17),(342,17),(343,17),(344,17),(345,17),(346,17),(347,17),(348,17),(349,17),(350,17),(351,17),(352,17),(353,17),(354,17),(355,17),(356,17),(357,17),(358,17),(359,17),(360,17),(361,17),(362,17),(363,17),(364,17),(365,17),(366,17),(367,17),(368,17),(369,17),(370,17),(371,17),(372,17),(373,17),(374,17),(375,17),(376,17),(377,17),(378,17),(379,17),(380,17),(381,17),(382,17),(383,17),(384,17),(385,17),(386,17),(387,17),(388,17),(389,17),(390,17),(391,17),(392,17),(393,17),(394,17),(395,17),(396,17),(397,17),(398,17),(399,17),(400,17),(401,17),(402,17),(403,17),(404,17),(405,17),(406,17),(407,17),(408,17),(409,17),(410,17),(411,17),(412,17),(413,17),(414,17),(415,17),(416,17),(417,17),(418,17),(419,17),(420,17),(421,17),(422,17),(423,17),(424,17),(425,17),(426,17),(427,17),(428,17),(429,17),(430,17),(431,17),(432,17),(433,17),(434,17),(435,17),(436,17),(437,17),(438,17),(439,17),(440,17),(441,17),(442,17),(443,17),(444,17),(445,17),(446,17),(447,17),(448,17),(449,17),(450,17),(451,17),(452,17),(453,17),(454,17),(455,17),(456,17),(457,17),(458,17),(459,17),(460,17),(461,17),(462,17),(463,17),(464,17),(465,17),(466,17),(467,17),(468,17),(469,17),(470,17),(471,17),(472,17),(473,17),(474,17),(475,17),(476,17),(477,17),(478,17),(479,17),(480,17),(481,17),(482,17),(483,17),(484,17),(485,17),(486,17),(487,17),(488,17),(489,17),(490,17),(491,17),(492,17),(493,17),(494,17),(495,17),(496,17),(497,17),(498,17),(499,17),(500,17),(501,17),(502,17),(503,17),(504,17),(505,17),(506,17),(507,17),(508,17),(509,17),(510,17),(511,17),(512,17),(513,17),(514,17),(515,17),(516,17),(517,17),(518,17),(519,17),(520,17),(521,17),(522,17),(523,17),(524,17),(525,17),(526,17),(527,17),(528,17),(529,17),(530,17),(531,17),(532,17),(533,17),(534,17),(535,17),(536,17),(537,17),(538,17),(539,17),(540,17),(541,17),(542,17),(543,17),(544,17),(545,17),(546,17),(547,17),(548,17),(549,17),(550,17),(551,17),(552,17),(553,17),(554,17),(555,17),(556,17),(557,17),(558,17),(559,17),(560,17),(561,17),(562,17),(563,17),(564,17),(565,17),(566,17),(567,17),(568,17),(569,17),(570,17),(571,17),(572,17),(573,17),(574,17),(575,17),(576,17),(577,17),(578,17),(579,17),(580,17),(581,17),(582,17),(583,17),(584,17),(585,17),(586,17),(587,17),(588,17),(589,17),(590,17),(591,17),(592,17),(593,17),(594,17),(595,17),(596,17),(597,17),(598,17),(599,17),(600,17),(601,17),(602,17),(603,17),(604,17),(605,17),(606,17),(607,17),(608,17),(609,17),(610,17),(611,17),(612,17),(613,17),(614,17),(615,17),(616,17),(617,17),(618,17),(619,17),(620,17),(621,17),(622,17),(623,17),(624,17),(625,17),(626,17),(627,17),(628,17),(629,17),(630,17),(631,17),(632,17),(633,17),(634,17),(635,17),(636,17),(637,17),(638,17),(639,17),(640,17),(641,17),(642,17),(643,17),(644,17),(645,17),(646,17),(647,17),(648,17),(649,17),(650,17),(651,17),(652,17),(653,17),(654,17),(655,17),(656,17),(657,17),(658,17),(659,17),(660,17),(661,17),(662,17),(663,17),(664,17),(665,17),(666,17),(667,17),(668,17),(669,17),(670,17),(671,17),(672,17),(673,17),(674,17),(675,17),(676,17),(677,17),(678,17),(679,17),(680,17),(681,17),(682,17),(683,17),(684,17),(685,17),(686,17),(687,17),(688,17),(689,17),(690,17),(691,17),(692,17),(693,17),(694,17),(695,17),(696,17),(697,17),(698,17),(699,17),(700,17),(701,17),(702,17),(703,17),(704,17),(705,17),(706,17),(707,17),(708,17),(709,17),(710,17),(711,17),(712,17),(713,17),(714,17),(715,17),(716,17),(717,17),(718,17),(719,17),(720,17),(721,17),(722,17),(723,17),(724,17),(725,17),(726,17),(727,17),(728,17),(729,17),(730,17),(731,17),(732,17),(733,17),(734,17),(735,17),(736,17),(737,17),(738,17),(739,17),(740,17),(741,17),(742,17),(743,17),(744,17),(745,17),(746,17),(747,17),(748,17),(749,17),(750,17),(751,17),(752,17),(753,17),(754,17),(755,17),(756,17),(757,17),(758,17),(759,17),(760,17),(761,17),(762,17),(763,17),(764,17),(765,17),(766,17),(767,17),(768,17),(769,17),(770,17),(771,17),(772,17),(773,17),(774,17),(775,17),(776,17),(777,17),(778,17),(779,17),(780,17),(781,17),(782,17),(783,17),(784,17),(785,17),(786,17),(787,17),(788,17),(789,17),(790,17),(791,17),(792,17),(793,17),(794,17),(795,17),(796,17),(797,17),(798,17),(799,17),(800,17),(801,17),(802,17),(803,17),(804,17),(805,17),(806,17),(807,17),(808,17),(809,17),(810,17),(811,17),(812,17),(813,17),(814,17),(815,17),(816,17),(817,17),(818,17),(819,17),(820,17),(821,17),(822,17),(823,17),(824,17),(825,17),(826,17),(827,17),(828,17),(829,17),(830,17),(831,17),(832,17),(833,17),(834,17),(835,17),(836,17),(837,17),(838,17),(839,17),(840,17),(841,17),(842,17),(843,17),(844,17),(845,17),(846,17),(847,17),(848,17),(849,17),(850,17),(851,17),(852,17),(853,17),(854,17),(855,17),(856,17),(857,17),(858,17),(859,17),(860,17),(861,17),(862,17),(863,17),(864,17),(865,17),(866,17),(867,17),(868,17),(869,17),(870,17),(871,17),(872,17),(873,17),(874,17),(875,17),(876,17),(877,17),(878,17),(879,17),(880,17),(881,17),(882,17),(883,17),(884,17),(885,17),(886,17),(887,17),(888,17),(889,17),(890,17),(891,17),(892,17),(893,17),(894,17),(895,17),(896,17),(897,17),(898,17),(899,17),(900,17),(901,17),(902,17),(903,17),(904,17),(905,17),(906,17),(907,17),(908,17),(909,17),(910,17),(911,17),(912,17),(913,17),(914,17),(915,17),(916,17),(917,17),(918,17),(919,17),(920,17),(921,17),(922,17),(923,17),(924,17),(925,17),(926,17),(927,17),(928,17),(929,17),(930,17),(931,17),(932,17),(933,17),(934,17),(935,17),(936,17),(937,17),(938,17),(939,17),(940,17),(941,17),(942,17),(943,17),(944,17),(945,17),(946,17),(947,17),(948,17),(949,17),(950,17),(951,17),(952,17),(953,17),(954,17),(955,17),(956,17),(957,17),(958,17),(959,17),(960,17),(961,17),(962,17),(963,17),(964,17),(965,17),(966,17),(967,17),(968,17),(19,20),(20,20),(21,20),(22,20),(23,20),(24,20),(25,20),(26,20),(27,20),(28,20),(29,20),(30,20),(31,20),(32,20),(33,20),(34,20),(35,20),(36,20),(37,20),(38,20),(39,20),(40,20),(41,20),(42,20),(55,20),(56,20),(57,20),(58,20),(59,20),(60,20),(61,20),(62,20),(63,20),(64,20),(65,20),(66,20),(67,20),(68,20),(69,20),(70,20),(71,20),(72,20),(73,20),(74,20),(75,20),(76,20),(77,20),(78,20),(91,20),(92,20),(93,20),(94,20),(95,20),(96,20),(97,20),(98,20),(99,20),(100,20),(101,20),(102,20),(103,20),(104,20),(105,20),(106,20),(107,20),(108,20),(109,20),(110,20),(111,20),(112,20),(113,20),(114,20),(115,20),(116,20),(117,20),(118,20),(119,20),(120,20),(121,20),(122,20),(123,20),(124,20),(125,20),(126,20),(127,20),(128,20),(129,20),(130,20),(131,20),(132,20),(133,20),(134,20),(135,20),(136,20),(137,20),(138,20),(139,20),(140,20),(141,20),(142,20),(143,20),(144,20),(145,20),(146,20),(147,20),(148,20),(149,20),(150,20),(151,20),(152,20),(153,20),(154,20),(155,20),(156,20),(157,20),(158,20),(159,20),(160,20),(161,20),(162,20),(163,20),(164,20),(165,20),(166,20),(167,20),(168,20),(169,20),(170,20),(171,20),(172,20),(173,20),(174,20),(175,20),(176,20),(177,20),(178,20),(179,20),(180,20),(181,20),(182,20),(183,20),(184,20),(185,20),(186,20),(187,20),(188,20),(189,20),(190,20),(191,20),(192,20),(193,20),(194,20),(195,20),(196,20),(197,20),(198,20),(199,20),(200,20),(201,20),(202,20),(203,20),(204,20),(205,20),(206,20),(207,20),(208,20),(209,20),(210,20),(211,20),(212,20),(213,20),(214,20),(215,20),(216,20),(217,20),(218,20),(219,20),(220,20),(221,20),(222,20),(223,20),(224,20),(225,20),(226,20),(227,20),(228,20),(229,20),(230,20),(231,20),(232,20),(233,20),(234,20),(235,20),(236,20),(237,20),(238,20),(239,20),(240,20),(241,20),(242,20),(243,20),(244,20),(245,20),(246,20),(247,20),(248,20),(249,20),(250,20),(251,20),(252,20),(253,20),(254,20),(255,20),(256,20),(257,20),(258,20),(259,20),(260,20),(261,20),(262,20),(263,20),(264,20),(265,20),(266,20),(267,20),(268,20),(269,20),(270,20),(271,20),(272,20),(273,20),(274,20),(275,20),(276,20),(277,20),(278,20),(279,20),(280,20),(281,20),(282,20),(283,20),(284,20),(285,20),(286,20),(287,20),(288,20),(289,20),(290,20),(291,20),(292,20),(293,20),(294,20),(295,20),(296,20),(297,20),(298,20),(299,20),(300,20),(301,20),(302,20),(303,20),(304,20),(305,20),(306,20),(307,20),(308,20),(309,20),(310,20),(311,20),(312,20),(313,20),(314,20),(315,20),(316,20),(317,20),(318,20),(319,20),(320,20),(321,20),(322,20),(323,20),(324,20),(325,20),(326,20),(327,20),(328,20),(329,20),(330,20),(331,20),(332,20),(333,20),(334,20),(335,20),(336,20),(337,20),(338,20),(339,20),(340,20),(341,20),(342,20),(343,20),(344,20),(345,20),(346,20),(347,20),(348,20),(349,20),(350,20),(351,20),(352,20),(353,20),(354,20),(355,20),(356,20),(357,20),(358,20),(359,20),(360,20),(361,20),(362,20),(363,20),(364,20),(365,20),(366,20),(367,20),(368,20),(369,20),(370,20),(371,20),(372,20),(373,20),(374,20),(375,20),(376,20),(377,20),(378,20),(379,20),(380,20),(381,20),(382,20),(383,20),(384,20),(385,20),(386,20),(387,20),(388,20),(389,20),(390,20),(391,20),(392,20),(393,20),(394,20),(395,20),(396,20),(397,20),(398,20),(399,20),(400,20),(401,20),(402,20),(403,20),(404,20),(405,20),(406,20),(407,20),(408,20),(409,20),(410,20),(411,20),(412,20),(413,20),(414,20),(415,20),(416,20),(417,20),(418,20),(419,20),(420,20),(421,20),(422,20),(423,20),(424,20),(425,20),(426,20),(427,20),(428,20),(429,20),(430,20),(431,20),(432,20),(433,20),(434,20),(435,20),(436,20),(437,20),(438,20),(439,20),(440,20),(441,20),(442,20),(443,20),(444,20),(445,20),(446,20),(447,20),(448,20),(449,20),(450,20),(451,20),(452,20),(453,20),(454,20),(455,20),(456,20),(457,20),(458,20),(459,20),(460,20),(461,20),(462,20),(463,20),(464,20),(465,20),(466,20),(467,20),(468,20),(469,20),(470,20),(471,20),(472,20),(473,20),(474,20),(475,20),(476,20),(477,20),(478,20),(479,20),(480,20),(481,20),(482,20),(483,20),(484,20),(485,20),(486,20),(487,20),(488,20),(489,20),(490,20),(491,20),(492,20),(493,20),(494,20),(495,20),(496,20),(497,20),(498,20),(499,20),(500,20),(501,20),(502,20),(503,20),(504,20),(505,20),(506,20),(507,20),(508,20),(509,20),(510,20),(511,20),(512,20),(513,20),(514,20),(515,20),(516,20),(517,20),(518,20),(519,20),(520,20),(521,20),(522,20),(523,20),(524,20),(525,20),(526,20),(527,20),(528,20),(529,20),(530,20),(531,20),(532,20),(533,20),(534,20),(535,20),(536,20),(537,20),(538,20),(539,20),(540,20),(541,20),(542,20),(543,20),(544,20),(545,20),(546,20),(547,20),(548,20),(549,20),(550,20),(551,20),(552,20),(553,20),(554,20),(555,20),(556,20),(557,20),(558,20),(559,20),(560,20),(561,20),(562,20),(563,20),(564,20),(565,20),(566,20),(567,20),(568,20),(569,20),(570,20),(571,20),(572,20),(573,20),(574,20),(575,20),(576,20),(577,20),(578,20),(579,20),(580,20),(581,20),(582,20),(583,20),(584,20),(585,20),(586,20),(587,20),(588,20),(589,20),(590,20),(591,20),(592,20),(593,20),(594,20),(595,20),(596,20),(597,20),(598,20),(599,20),(600,20),(601,20),(602,20),(603,20),(604,20),(605,20),(606,20),(607,20),(608,20),(609,20),(610,20),(611,20),(612,20),(613,20),(614,20),(615,20),(616,20),(617,20),(618,20),(619,20),(620,20),(621,20),(622,20),(623,20),(624,20),(625,20),(626,20),(627,20),(628,20),(629,20),(630,20),(631,20),(632,20),(633,20),(634,20),(635,20),(636,20),(637,20),(638,20),(639,20),(640,20),(641,20),(642,20),(643,20),(644,20),(645,20),(646,20),(647,20),(648,20),(649,20),(650,20),(651,20),(652,20),(653,20),(654,20),(655,20),(656,20),(657,20),(658,20),(659,20),(660,20),(661,20),(662,20),(663,20),(664,20),(665,20),(666,20),(667,20),(668,20),(669,20),(670,20),(671,20),(672,20),(673,20),(674,20),(675,20),(676,20),(677,20),(678,20),(679,20),(680,20),(681,20),(682,20),(683,20),(684,20),(685,20),(686,20),(687,20),(688,20),(689,20),(690,20),(691,20),(692,20),(693,20),(694,20),(695,20),(696,20),(697,20),(698,20),(699,20),(700,20),(701,20),(702,20),(703,20),(704,20),(705,20),(706,20),(707,20),(708,20),(709,20),(710,20),(711,20),(712,20),(713,20),(714,20),(715,20),(716,20),(717,20),(718,20),(719,20),(720,20),(721,20),(722,20),(723,20),(724,20),(725,20),(726,20),(727,20),(728,20),(729,20),(730,20),(731,20),(732,20),(733,20),(734,20),(735,20),(736,20),(737,20),(738,20),(739,20),(740,20),(741,20),(742,20),(743,20),(744,20),(745,20),(746,20),(747,20),(748,20),(749,20),(750,20),(751,20),(752,20),(753,20),(754,20),(755,20),(756,20),(757,20),(758,20),(759,20),(760,20),(761,20),(762,20),(763,20),(764,20),(765,20),(766,20),(767,20),(768,20),(769,20),(770,20),(771,20),(772,20),(773,20),(774,20),(775,20),(776,20),(777,20),(778,20),(779,20),(780,20),(781,20),(782,20),(783,20),(784,20),(785,20),(786,20),(787,20),(788,20),(789,20),(790,20),(791,20),(792,20),(793,20),(794,20),(795,20),(796,20),(797,20),(798,20),(799,20),(800,20),(801,20),(802,20),(803,20),(804,20),(805,20),(806,20),(807,20),(808,20),(809,20),(810,20),(811,20),(812,20),(813,20),(814,20),(815,20),(816,20),(817,20),(818,20),(819,20),(820,20),(821,20),(822,20),(823,20),(824,20),(825,20),(826,20),(827,20),(828,20),(829,20),(830,20),(831,20),(832,20),(833,20),(834,20),(835,20),(836,20),(837,20),(838,20),(839,20),(840,20),(841,20),(842,20),(843,20),(844,20),(845,20),(846,20),(847,20),(848,20),(849,20),(850,20),(851,20),(852,20),(853,20),(854,20),(855,20),(856,20),(857,20),(858,20),(859,20),(860,20),(861,20),(862,20),(863,20),(864,20),(865,20),(866,20),(867,20),(868,20),(869,20),(870,20),(871,20),(872,20),(873,20),(874,20),(875,20),(876,20),(877,20),(878,20),(879,20),(880,20),(881,20),(882,20),(883,20),(884,20),(885,20),(886,20),(887,20),(888,20),(889,20),(890,20),(891,20),(892,20),(893,20),(894,20),(895,20),(896,20),(897,20),(898,20),(899,20),(900,20),(901,20),(902,20),(903,20),(904,20),(905,20),(906,20),(907,20),(908,20),(909,20),(910,20),(911,20),(912,20),(913,20),(914,20),(915,20),(916,20),(917,20),(918,20),(919,20),(920,20),(921,20),(922,20),(923,20),(924,20),(925,20),(926,20),(927,20),(928,20),(929,20),(930,20),(931,20),(932,20),(933,20),(934,20),(935,20),(936,20),(937,20),(938,20),(939,20),(940,20),(941,20),(942,20),(943,20),(944,20),(945,20),(946,20),(947,20),(948,20),(949,20),(950,20),(951,20),(952,20),(953,20),(954,20),(955,20),(956,20),(957,20),(958,20),(959,20),(960,20),(961,20),(962,20),(963,20),(964,20),(965,20),(966,20),(967,20),(968,20),(19,23),(20,23),(21,23),(22,23),(23,23),(24,23),(25,23),(26,23),(27,23),(28,23),(29,23),(30,23),(31,23),(32,23),(33,23),(34,23),(35,23),(36,23),(37,23),(38,23),(39,23),(40,23),(41,23),(42,23),(55,23),(56,23),(57,23),(58,23),(59,23),(60,23),(61,23),(62,23),(63,23),(64,23),(65,23),(66,23),(67,23),(68,23),(69,23),(70,23),(71,23),(72,23),(73,23),(74,23),(75,23),(76,23),(77,23),(78,23),(91,23),(92,23),(93,23),(94,23),(95,23),(96,23),(97,23),(98,23),(99,23),(100,23),(101,23),(102,23),(103,23),(104,23),(105,23),(106,23),(107,23),(108,23),(109,23),(110,23),(111,23),(112,23),(113,23),(114,23),(115,23),(116,23),(117,23),(118,23),(119,23),(120,23),(121,23),(122,23),(123,23),(124,23),(125,23),(126,23),(127,23),(128,23),(129,23),(130,23),(131,23),(132,23),(133,23),(134,23),(135,23),(136,23),(137,23),(138,23),(139,23),(140,23),(141,23),(142,23),(143,23),(144,23),(145,23),(146,23),(147,23),(148,23),(149,23),(150,23),(151,23),(152,23),(153,23),(154,23),(155,23),(156,23),(157,23),(158,23),(159,23),(160,23),(161,23),(162,23),(163,23),(164,23),(165,23),(166,23),(167,23),(168,23),(169,23),(170,23),(171,23),(172,23),(173,23),(174,23),(175,23),(176,23),(177,23),(178,23),(179,23),(180,23),(181,23),(182,23),(183,23),(184,23),(185,23),(186,23),(187,23),(188,23),(189,23),(190,23),(191,23),(192,23),(193,23),(194,23),(195,23),(196,23),(197,23),(198,23),(199,23),(200,23),(201,23),(202,23),(203,23),(204,23),(205,23),(206,23),(207,23),(208,23),(209,23),(210,23),(211,23),(212,23),(213,23),(214,23),(215,23),(216,23),(217,23),(218,23),(219,23),(220,23),(221,23),(222,23),(223,23),(224,23),(225,23),(226,23),(227,23),(228,23),(229,23),(230,23),(231,23),(232,23),(233,23),(234,23),(235,23),(236,23),(237,23),(238,23),(239,23),(240,23),(241,23),(242,23),(243,23),(244,23),(245,23),(246,23),(247,23),(248,23),(249,23),(250,23),(251,23),(252,23),(253,23),(254,23),(255,23),(256,23),(257,23),(258,23),(259,23),(260,23),(261,23),(262,23),(263,23),(264,23),(265,23),(266,23),(267,23),(268,23),(269,23),(270,23),(271,23),(272,23),(273,23),(274,23),(275,23),(276,23),(277,23),(278,23),(279,23),(280,23),(281,23),(282,23),(283,23),(284,23),(285,23),(286,23),(287,23),(288,23),(289,23),(290,23),(291,23),(292,23),(293,23),(294,23),(295,23),(296,23),(297,23),(298,23),(299,23),(300,23),(301,23),(302,23),(303,23),(304,23),(305,23),(306,23),(307,23),(308,23),(309,23),(310,23),(311,23),(312,23),(313,23),(314,23),(315,23),(316,23),(317,23),(318,23),(319,23),(320,23),(321,23),(322,23),(323,23),(324,23),(325,23),(326,23),(327,23),(328,23),(329,23),(330,23),(331,23),(332,23),(333,23),(334,23),(335,23),(336,23),(337,23),(338,23),(339,23),(340,23),(341,23),(342,23),(343,23),(344,23),(345,23),(346,23),(347,23),(348,23),(349,23),(350,23),(351,23),(352,23),(353,23),(354,23),(355,23),(356,23),(357,23),(358,23),(359,23),(360,23),(361,23),(362,23),(363,23),(364,23),(365,23),(366,23),(367,23),(368,23),(369,23),(370,23),(371,23),(372,23),(373,23),(374,23),(375,23),(376,23),(377,23),(378,23),(379,23),(380,23),(381,23),(382,23),(383,23),(384,23),(385,23),(386,23),(387,23),(388,23),(389,23),(390,23),(391,23),(392,23),(393,23),(394,23),(395,23),(396,23),(397,23),(398,23),(399,23),(400,23),(401,23),(402,23),(403,23),(404,23),(405,23),(406,23),(407,23),(408,23),(409,23),(410,23),(411,23),(412,23),(413,23),(414,23),(415,23),(416,23),(417,23),(418,23),(419,23),(420,23),(421,23),(422,23),(423,23),(424,23),(425,23),(426,23),(427,23),(428,23),(429,23),(430,23),(431,23),(432,23),(433,23),(434,23),(435,23),(436,23),(437,23),(438,23),(439,23),(440,23),(441,23),(442,23),(443,23),(444,23),(445,23),(446,23),(447,23),(448,23),(449,23),(450,23),(451,23),(452,23),(453,23),(454,23),(455,23),(456,23),(457,23),(458,23),(459,23),(460,23),(461,23),(462,23),(463,23),(464,23),(465,23),(466,23),(467,23),(468,23),(469,23),(470,23),(471,23),(472,23),(473,23),(474,23),(475,23),(476,23),(477,23),(478,23),(479,23),(480,23),(481,23),(482,23),(483,23),(484,23),(485,23),(486,23),(487,23),(488,23),(489,23),(490,23),(491,23),(492,23),(493,23),(494,23),(495,23),(496,23),(497,23),(498,23),(499,23),(500,23),(501,23),(502,23),(503,23),(504,23),(505,23),(506,23),(507,23),(508,23),(509,23),(510,23),(511,23),(512,23),(513,23),(514,23),(515,23),(516,23),(517,23),(518,23),(519,23),(520,23),(521,23),(522,23),(523,23),(524,23),(525,23),(526,23),(527,23),(528,23),(529,23),(530,23),(531,23),(532,23),(533,23),(534,23),(535,23),(536,23),(537,23),(538,23),(539,23),(540,23),(541,23),(542,23),(543,23),(544,23),(545,23),(546,23),(547,23),(548,23),(549,23),(550,23),(551,23),(552,23),(553,23),(554,23),(555,23),(556,23),(557,23),(558,23),(559,23),(560,23),(561,23),(562,23),(563,23),(564,23),(565,23),(566,23),(567,23),(568,23),(569,23),(570,23),(571,23),(572,23),(573,23),(574,23),(575,23),(576,23),(577,23),(578,23),(579,23),(580,23),(581,23),(582,23),(583,23),(584,23),(585,23),(586,23),(587,23),(588,23),(589,23),(590,23),(591,23),(592,23),(593,23),(594,23),(595,23),(596,23),(597,23),(598,23),(599,23),(600,23),(601,23),(602,23),(603,23),(604,23),(605,23),(606,23),(607,23),(608,23),(609,23),(610,23),(611,23),(612,23),(613,23),(614,23),(615,23),(616,23),(617,23),(618,23),(619,23),(620,23),(621,23),(622,23),(623,23),(624,23),(625,23),(626,23),(627,23),(628,23),(629,23),(630,23),(631,23),(632,23),(633,23),(634,23),(635,23),(636,23),(637,23),(638,23),(639,23),(640,23),(641,23),(642,23),(643,23),(644,23),(645,23),(646,23),(647,23),(648,23),(649,23),(650,23),(651,23),(652,23),(653,23),(654,23),(655,23),(656,23),(657,23),(658,23),(659,23),(660,23),(661,23),(662,23),(663,23),(664,23),(665,23),(666,23),(667,23),(668,23),(669,23),(670,23),(671,23),(672,23),(673,23),(674,23),(675,23),(676,23),(677,23),(678,23),(679,23),(680,23),(681,23),(682,23),(683,23),(684,23),(685,23),(686,23),(687,23),(688,23),(689,23),(690,23),(691,23),(692,23),(693,23),(694,23),(695,23),(696,23),(697,23),(698,23),(699,23),(700,23),(701,23),(702,23),(703,23),(704,23),(705,23),(706,23),(707,23),(708,23),(709,23),(710,23),(711,23),(712,23),(713,23),(714,23),(715,23),(716,23),(717,23),(718,23),(719,23),(720,23),(721,23),(722,23),(723,23),(724,23),(725,23),(726,23),(727,23),(728,23),(729,23),(730,23),(731,23),(732,23),(733,23),(734,23),(735,23),(736,23),(737,23),(738,23),(739,23),(740,23),(741,23),(742,23),(743,23),(744,23),(745,23),(746,23),(747,23),(748,23),(749,23),(750,23),(751,23),(752,23),(753,23),(754,23),(755,23),(756,23),(757,23),(758,23),(759,23),(760,23),(761,23),(762,23),(763,23),(764,23),(765,23),(766,23),(767,23),(768,23),(769,23),(770,23),(771,23),(772,23),(773,23),(774,23),(775,23),(776,23),(777,23),(778,23),(779,23),(780,23),(781,23),(782,23),(783,23),(784,23),(785,23),(786,23),(787,23),(788,23),(789,23),(790,23),(791,23),(792,23),(793,23),(794,23),(795,23),(796,23),(797,23),(798,23),(799,23),(800,23),(801,23),(802,23),(803,23),(804,23),(805,23),(806,23),(807,23),(808,23),(809,23),(810,23),(811,23),(812,23),(813,23),(814,23),(815,23),(816,23),(817,23),(818,23),(819,23),(820,23),(821,23),(822,23),(823,23),(824,23),(825,23),(826,23),(827,23),(828,23),(829,23),(830,23),(831,23),(832,23),(833,23),(834,23),(835,23),(836,23),(837,23),(838,23),(839,23),(840,23),(841,23),(842,23),(843,23),(844,23),(845,23),(846,23),(847,23),(848,23),(849,23),(850,23),(851,23),(852,23),(853,23),(854,23),(855,23),(856,23),(857,23),(858,23),(859,23),(860,23),(861,23),(862,23),(863,23),(864,23),(865,23),(866,23),(867,23),(868,23),(869,23),(870,23),(871,23),(872,23),(873,23),(874,23),(875,23),(876,23),(877,23),(878,23),(879,23),(880,23),(881,23),(882,23),(883,23),(884,23),(885,23),(886,23),(887,23),(888,23),(889,23),(890,23),(891,23),(892,23),(893,23),(894,23),(895,23),(896,23),(897,23),(898,23),(899,23),(900,23),(901,23),(902,23),(903,23),(904,23),(905,23),(906,23),(907,23),(908,23),(909,23),(910,23),(911,23),(912,23),(913,23),(914,23),(915,23),(916,23),(917,23),(918,23),(919,23),(920,23),(921,23),(922,23),(923,23),(924,23),(925,23),(926,23),(927,23),(928,23),(929,23),(930,23),(931,23),(932,23),(933,23),(934,23),(935,23),(936,23),(937,23),(938,23),(939,23),(940,23),(941,23),(942,23),(943,23),(944,23),(945,23),(946,23),(947,23),(948,23),(949,23),(950,23),(951,23),(952,23),(953,23),(954,23),(955,23),(956,23),(957,23),(958,23),(959,23),(960,23),(961,23),(962,23),(963,23),(964,23),(965,23),(966,23),(967,23),(968,23);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_scope` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_company_id_name_guard_name_unique` (`company_id`,`name`,`guard_name`),
  KEY `roles_team_foreign_key_index` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,2,'super_admin','web','all','2026-08-02 04:03:09','2026-08-02 04:03:09'),(8,2,'demo_hr_manager','web','all','2026-09-18 20:01:38','2026-09-18 20:01:38'),(9,2,'demo_manager','web','team','2026-09-18 20:01:40','2026-10-07 03:42:14'),(10,2,'demo_employee','web','own','2026-09-18 20:01:40','2026-10-07 03:42:14'),(11,3,'super_admin','web','all','2026-09-19 10:21:56','2026-09-19 10:21:56'),(12,4,'super_admin','web','all','2026-09-19 10:22:56','2026-09-19 10:22:56'),(13,5,'super_admin','web','all','2026-09-19 10:23:28','2026-09-19 10:23:28'),(14,6,'super_admin','web','all','2026-09-21 21:55:49','2026-09-21 21:55:49'),(15,7,'super_admin','web','all','2026-09-21 21:57:40','2026-09-21 21:57:40'),(17,3,'hr_manager','web','all','2026-10-07 03:42:14','2026-10-07 03:42:14'),(18,3,'manager','web','team','2026-10-07 03:42:15','2026-10-07 03:42:15'),(19,3,'employee','web','own','2026-10-07 03:42:15','2026-10-07 03:42:15'),(20,6,'hr_manager','web','all','2026-10-07 03:42:15','2026-10-07 03:42:15'),(21,6,'manager','web','team','2026-10-07 03:42:15','2026-10-07 03:42:15'),(22,6,'employee','web','own','2026-10-07 03:42:15','2026-10-07 03:42:15'),(23,7,'hr_manager','web','all','2026-10-07 03:42:15','2026-10-07 03:42:15'),(24,7,'manager','web','team','2026-10-07 03:42:15','2026-10-07 03:42:15'),(25,7,'employee','web','own','2026-10-07 03:42:15','2026-10-07 03:42:15');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_components`
--

DROP TABLE IF EXISTS `salary_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary_components` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `salary_components_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `salary_components_branch_id_foreign` (`branch_id`),
  CONSTRAINT `salary_components_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `salary_components_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_components`
--

LOCK TABLES `salary_components` WRITE;
/*!40000 ALTER TABLE `salary_components` DISABLE KEYS */;
INSERT INTO `salary_components` VALUES (6,2,1,'Basic','BASIC','earning',1,'2026-08-20 08:09:35','2026-08-20 08:09:35'),(7,2,1,'House Rent Allowance','HRA','earning',1,'2026-08-20 08:09:35','2026-08-20 08:09:35'),(8,2,1,'Conveyance Allowance','CONVEYANCE','earning',1,'2026-08-20 08:09:35','2026-08-20 08:09:35'),(9,2,1,'Medical Allowance','MEDICAL','earning',1,'2026-08-20 08:09:35','2026-08-20 08:09:35'),(10,2,1,'Special Allowance','SPECIAL','earning',1,'2026-08-20 08:09:35','2026-08-20 08:09:35');
/*!40000 ALTER TABLE `salary_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_structure_items`
--

DROP TABLE IF EXISTS `salary_structure_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary_structure_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `salary_structure_id` bigint unsigned NOT NULL,
  `salary_component_id` bigint unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `salary_structure_items_structure_component_unique` (`salary_structure_id`,`salary_component_id`),
  KEY `salary_structure_items_salary_component_id_foreign` (`salary_component_id`),
  CONSTRAINT `salary_structure_items_salary_component_id_foreign` FOREIGN KEY (`salary_component_id`) REFERENCES `salary_components` (`id`) ON DELETE CASCADE,
  CONSTRAINT `salary_structure_items_salary_structure_id_foreign` FOREIGN KEY (`salary_structure_id`) REFERENCES `salary_structures` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_structure_items`
--

LOCK TABLES `salary_structure_items` WRITE;
/*!40000 ALTER TABLE `salary_structure_items` DISABLE KEYS */;
INSERT INTO `salary_structure_items` VALUES (10,7,6,25000.00,'2026-10-06 06:55:08','2026-10-06 06:55:08'),(11,7,8,2000.00,'2026-10-06 06:55:08','2026-10-06 06:55:08'),(12,7,7,5000.00,'2026-10-06 06:55:08','2026-10-06 06:55:08'),(13,7,9,3000.00,'2026-10-06 06:55:08','2026-10-06 06:55:08'),(14,7,10,2000.00,'2026-10-06 06:55:08','2026-10-06 06:55:08'),(15,8,6,50000.00,'2026-10-06 06:55:41','2026-10-06 06:55:41'),(16,8,8,3000.00,'2026-10-06 06:55:41','2026-10-06 06:55:41'),(17,8,7,8000.00,'2026-10-06 06:55:41','2026-10-06 06:55:41'),(18,8,9,3000.00,'2026-10-06 06:55:41','2026-10-06 06:55:41'),(19,8,10,2000.00,'2026-10-06 06:55:41','2026-10-06 06:55:41'),(20,9,6,60000.00,'2026-10-06 06:56:22','2026-10-06 06:56:22'),(21,9,8,3000.00,'2026-10-06 06:56:22','2026-10-06 06:56:22'),(22,9,7,80000.00,'2026-10-06 06:56:22','2026-10-06 06:56:22'),(23,9,9,4000.00,'2026-10-06 06:56:22','2026-10-06 06:56:22'),(24,9,10,4000.00,'2026-10-06 06:56:22','2026-10-06 06:56:22'),(25,10,6,12000.00,'2026-10-06 06:56:54','2026-10-06 06:56:54'),(26,10,8,1000.00,'2026-10-06 06:56:54','2026-10-06 06:56:54'),(27,10,7,3000.00,'2026-10-06 06:56:54','2026-10-06 06:56:54'),(28,10,9,2000.00,'2026-10-06 06:56:54','2026-10-06 06:56:54'),(29,10,10,1000.00,'2026-10-06 06:56:54','2026-10-06 06:56:54'),(30,11,6,1500.00,'2026-10-06 06:57:17','2026-10-06 06:57:17'),(31,11,8,2000.00,'2026-10-06 06:57:17','2026-10-06 06:57:17'),(32,11,7,4000.00,'2026-10-06 06:57:17','2026-10-06 06:57:17'),(33,11,9,2000.00,'2026-10-06 06:57:17','2026-10-06 06:57:17'),(34,11,10,1000.00,'2026-10-06 06:57:17','2026-10-06 06:57:17');
/*!40000 ALTER TABLE `salary_structure_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_structures`
--

DROP TABLE IF EXISTS `salary_structures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary_structures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `effective_date` date NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `salary_structures_company_id_foreign` (`company_id`),
  KEY `salary_structures_employee_id_foreign` (`employee_id`),
  CONSTRAINT `salary_structures_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `salary_structures_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_structures`
--

LOCK TABLES `salary_structures` WRITE;
/*!40000 ALTER TABLE `salary_structures` DISABLE KEYS */;
INSERT INTO `salary_structures` VALUES (7,2,17,'2026-10-06',1,'2026-10-06 06:55:08','2026-10-06 06:55:08'),(8,2,18,'2026-10-06',1,'2026-10-06 06:55:41','2026-10-06 06:55:41'),(9,2,16,'2026-10-06',1,'2026-10-06 06:56:22','2026-10-06 06:56:22'),(10,2,19,'2026-10-06',1,'2026-10-06 06:56:54','2026-10-06 06:56:54'),(11,2,4,'2026-10-06',1,'2026-10-06 06:57:17','2026-10-06 06:57:17');
/*!40000 ALTER TABLE `salary_structures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_customer_prices`
--

DROP TABLE IF EXISTS `sales_customer_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_customer_prices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `unit_price` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_customer_prices_account_id_item_id_unique` (`account_id`,`item_id`),
  KEY `sales_customer_prices_company_id_foreign` (`company_id`),
  KEY `sales_customer_prices_item_id_foreign` (`item_id`),
  CONSTRAINT `sales_customer_prices_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `crm_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_customer_prices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_customer_prices_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_customer_prices`
--

LOCK TABLES `sales_customer_prices` WRITE;
/*!40000 ALTER TABLE `sales_customer_prices` DISABLE KEYS */;
INSERT INTO `sales_customer_prices` VALUES (1,2,1,1,42.00,'2026-10-18 10:44:27','2026-10-18 10:44:27');
/*!40000 ALTER TABLE `sales_customer_prices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_customer_profiles`
--

DROP TABLE IF EXISTS `sales_customer_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_customer_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `credit_limit` decimal(14,2) NOT NULL DEFAULT '0.00',
  `payment_terms_days` int unsigned NOT NULL DEFAULT '0',
  `price_list_id` bigint unsigned DEFAULT NULL,
  `salesperson_employee_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `outstanding_balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_customer_profiles_account_id_unique` (`account_id`),
  KEY `sales_customer_profiles_company_id_foreign` (`company_id`),
  KEY `sales_customer_profiles_price_list_id_foreign` (`price_list_id`),
  KEY `sales_customer_profiles_salesperson_employee_id_foreign` (`salesperson_employee_id`),
  CONSTRAINT `sales_customer_profiles_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `crm_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_customer_profiles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_customer_profiles_price_list_id_foreign` FOREIGN KEY (`price_list_id`) REFERENCES `sales_price_lists` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_customer_profiles_salesperson_employee_id_foreign` FOREIGN KEY (`salesperson_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_customer_profiles`
--

LOCK TABLES `sales_customer_profiles` WRITE;
/*!40000 ALTER TABLE `sales_customer_profiles` DISABLE KEYS */;
INSERT INTO `sales_customer_profiles` VALUES (1,2,1,500000.00,30,NULL,NULL,'active',0.00,NULL,'2026-10-18 10:43:46','2026-10-18 19:47:54');
/*!40000 ALTER TABLE `sales_customer_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_invoice_requests`
--

DROP TABLE IF EXISTS `sales_invoice_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_invoice_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `sales_order_id` bigint unsigned NOT NULL,
  `contract_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'requested',
  `requested_amount` decimal(14,2) NOT NULL,
  `amount_paid` decimal(14,2) NOT NULL DEFAULT '0.00',
  `invoice_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `requested_by_employee_id` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_invoice_requests_company_id_foreign` (`company_id`),
  KEY `sales_invoice_requests_sales_order_id_foreign` (`sales_order_id`),
  KEY `sales_invoice_requests_contract_id_foreign` (`contract_id`),
  KEY `sales_invoice_requests_requested_by_employee_id_foreign` (`requested_by_employee_id`),
  CONSTRAINT `sales_invoice_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_invoice_requests_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `crm_contracts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_invoice_requests_requested_by_employee_id_foreign` FOREIGN KEY (`requested_by_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_invoice_requests_sales_order_id_foreign` FOREIGN KEY (`sales_order_id`) REFERENCES `crm_sales_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_invoice_requests`
--

LOCK TABLES `sales_invoice_requests` WRITE;
/*!40000 ALTER TABLE `sales_invoice_requests` DISABLE KEYS */;
INSERT INTO `sales_invoice_requests` VALUES (1,2,2,NULL,'paid',47200.00,47200.00,'INV-TEST-001','2026-11-18',4,NULL,'2026-10-18 19:47:10','2026-10-18 19:47:54');
/*!40000 ALTER TABLE `sales_invoice_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_price_list_items`
--

DROP TABLE IF EXISTS `sales_price_list_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_price_list_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `price_list_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `unit_price` decimal(14,2) NOT NULL,
  `min_qty` decimal(14,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_pli_list_item_minqty_unique` (`price_list_id`,`item_id`,`min_qty`),
  KEY `sales_price_list_items_item_id_foreign` (`item_id`),
  CONSTRAINT `sales_price_list_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_price_list_items_price_list_id_foreign` FOREIGN KEY (`price_list_id`) REFERENCES `sales_price_lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_price_list_items`
--

LOCK TABLES `sales_price_list_items` WRITE;
/*!40000 ALTER TABLE `sales_price_list_items` DISABLE KEYS */;
INSERT INTO `sales_price_list_items` VALUES (1,1,1,45.00,100.00,'2026-10-18 10:44:24','2026-10-18 10:44:24');
/*!40000 ALTER TABLE `sales_price_list_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_price_lists`
--

DROP TABLE IF EXISTS `sales_price_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_price_lists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INR',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_price_lists_company_id_foreign` (`company_id`),
  CONSTRAINT `sales_price_lists_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_price_lists`
--

LOCK TABLES `sales_price_lists` WRITE;
/*!40000 ALTER TABLE `sales_price_lists` DISABLE KEYS */;
INSERT INTO `sales_price_lists` VALUES (1,2,'Wholesale','INR',1,NULL,NULL,1,'2026-10-18 10:44:06','2026-10-18 10:44:06');
/*!40000 ALTER TABLE `sales_price_lists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_settings`
--

DROP TABLE IF EXISTS `sales_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `discount_approval_threshold_percent` tinyint unsigned NOT NULL DEFAULT '15',
  `default_payment_terms_days` int unsigned NOT NULL DEFAULT '30',
  `require_credit_check` tinyint(1) NOT NULL DEFAULT '1',
  `block_order_over_credit_limit` tinyint(1) NOT NULL DEFAULT '0',
  `default_price_list_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_settings_company_id_unique` (`company_id`),
  KEY `sales_settings_default_price_list_id_foreign` (`default_price_list_id`),
  CONSTRAINT `sales_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_settings_default_price_list_id_foreign` FOREIGN KEY (`default_price_list_id`) REFERENCES `sales_price_lists` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_settings`
--

LOCK TABLES `sales_settings` WRITE;
/*!40000 ALTER TABLE `sales_settings` DISABLE KEYS */;
INSERT INTO `sales_settings` VALUES (1,2,10,45,1,1,NULL,'2026-10-22 01:51:12','2026-10-22 01:51:25');
/*!40000 ALTER TABLE `sales_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `separation_clearances`
--

DROP TABLE IF EXISTS `separation_clearances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `separation_clearances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `resignation_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_cleared` tinyint(1) NOT NULL DEFAULT '0',
  `cleared_by_user_id` bigint unsigned DEFAULT NULL,
  `cleared_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `separation_clearances_resignation_id_department_unique` (`resignation_id`,`department`),
  KEY `separation_clearances_company_id_foreign` (`company_id`),
  KEY `separation_clearances_employee_id_foreign` (`employee_id`),
  KEY `separation_clearances_cleared_by_user_id_foreign` (`cleared_by_user_id`),
  CONSTRAINT `separation_clearances_cleared_by_user_id_foreign` FOREIGN KEY (`cleared_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `separation_clearances_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `separation_clearances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `separation_clearances_resignation_id_foreign` FOREIGN KEY (`resignation_id`) REFERENCES `resignations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `separation_clearances`
--

LOCK TABLES `separation_clearances` WRITE;
/*!40000 ALTER TABLE `separation_clearances` DISABLE KEYS */;
/*!40000 ALTER TABLE `separation_clearances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('JJO88EjsF7tceYwvPy9iVUOyFXdnkdWqlq8DrctL',1,'127.0.0.1','curl/8.17.0','eyJfdG9rZW4iOiJ2OEVndUxHM0dIOWhrc3FlNFZCQlFlc3JORVZTU0tDZUl5dklZSGtYIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9jcm0iLCJyb3V0ZSI6ImFkbWluLmNybS5kYXNoYm9hcmQifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MX0=',1792653807),('MlCAjim0Kzhy8A9ompuu74S9hyFWQCigeHQ29AyP',1,'127.0.0.1','curl/8.17.0','eyJfdG9rZW4iOiJ0elNDdEE5TWlTMHBaU0F6U1hPMEpBSDJ1YjJ0TDF5cU1ibXRUa3Y0IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9jcm0iLCJyb3V0ZSI6ImFkbWluLmNybS5kYXNoYm9hcmQifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MX0=',1792650723),('Sj52PqJB53G7s2GLwGW5vwzt0BBNRhGe1q9HPEF9',1,'127.0.0.1','curl/8.17.0','eyJfdG9rZW4iOiJMeTdBaHRmVlZjaWJDd2hzTnlxNTZzVWFqRXh4RFVZQXgxcEp3SlFtIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9zYWxlc1wvcXVvdGF0aW9uc1wvY3JlYXRlIiwicm91dGUiOiJhZG1pbi5zYWxlcy5xdW90YXRpb25zLmNyZWF0ZSJ9LCJfZmxhc2giOnsib2xkIjpbInN0YXR1cyJdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjEsInN0YXR1cyI6IlF1b3RhdGlvbiBjcmVhdGVkIC0gYWRkIGxpbmUgaXRlbXMgYmVsb3cuIn0=',1792653392);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_rosters`
--

DROP TABLE IF EXISTS `shift_rosters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_rosters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shift_rosters_employee_id_date_unique` (`employee_id`,`date`),
  KEY `shift_rosters_shift_id_foreign` (`shift_id`),
  KEY `shift_rosters_company_id_date_index` (`company_id`,`date`),
  CONSTRAINT `shift_rosters_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_rosters_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_rosters_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=232 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_rosters`
--

LOCK TABLES `shift_rosters` WRITE;
/*!40000 ALTER TABLE `shift_rosters` DISABLE KEYS */;
INSERT INTO `shift_rosters` VALUES (45,2,17,1,'2026-09-28','2026-09-28 01:13:50','2026-09-28 01:13:50'),(46,2,18,5,'2026-09-28','2026-09-28 01:13:52','2026-09-28 01:15:48'),(47,2,16,1,'2026-09-28','2026-09-28 01:13:55','2026-09-28 01:13:55'),(48,2,19,1,'2026-09-28','2026-09-28 01:13:57','2026-09-28 01:13:57'),(49,2,4,1,'2026-09-28','2026-09-28 01:14:00','2026-09-28 01:14:00'),(50,2,17,1,'2026-09-29','2026-09-28 01:14:02','2026-09-28 01:14:02'),(51,2,18,5,'2026-09-29','2026-09-28 01:14:04','2026-09-28 01:15:50'),(52,2,16,1,'2026-09-29','2026-09-28 01:14:06','2026-09-28 01:14:06'),(53,2,19,1,'2026-09-29','2026-09-28 01:14:08','2026-09-28 01:14:08'),(54,2,4,1,'2026-09-29','2026-09-28 01:14:11','2026-09-28 01:14:11'),(55,2,17,1,'2026-09-30','2026-09-28 01:14:13','2026-09-28 01:14:13'),(56,2,18,5,'2026-09-30','2026-09-28 01:14:15','2026-09-28 01:15:52'),(57,2,16,1,'2026-09-30','2026-09-28 01:14:16','2026-09-28 01:14:16'),(58,2,19,1,'2026-09-30','2026-09-28 01:14:19','2026-09-28 01:14:19'),(59,2,4,1,'2026-09-30','2026-09-28 01:14:21','2026-09-28 01:14:21'),(60,2,17,1,'2026-10-01','2026-09-28 01:14:24','2026-09-28 01:14:24'),(61,2,18,5,'2026-10-01','2026-09-28 01:14:26','2026-09-28 01:15:54'),(62,2,16,1,'2026-10-01','2026-09-28 01:14:28','2026-09-28 01:14:28'),(63,2,19,1,'2026-10-01','2026-09-28 01:14:29','2026-09-28 01:14:29'),(64,2,4,1,'2026-10-01','2026-09-28 01:14:32','2026-09-28 01:14:32'),(65,2,17,5,'2026-10-02','2026-09-28 01:14:34','2026-09-28 01:14:39'),(66,2,18,5,'2026-10-02','2026-09-28 01:14:36','2026-09-28 01:14:36'),(67,2,16,5,'2026-10-02','2026-09-28 01:14:42','2026-09-28 01:14:42'),(68,2,19,5,'2026-10-02','2026-09-28 01:14:44','2026-09-28 01:14:44'),(69,2,4,5,'2026-10-02','2026-09-28 01:14:47','2026-09-28 01:14:47'),(70,2,17,1,'2026-10-04','2026-09-28 01:15:04','2026-09-28 01:15:04'),(71,2,18,1,'2026-10-04','2026-09-28 01:15:06','2026-09-28 01:15:06'),(72,2,16,1,'2026-10-04','2026-09-28 01:15:08','2026-09-28 01:15:08'),(73,2,19,1,'2026-10-04','2026-09-28 01:15:10','2026-09-28 01:15:10'),(74,2,4,1,'2026-10-04','2026-09-28 01:15:12','2026-09-28 01:15:12');
/*!40000 ALTER TABLE `shift_rosters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_swap_requests`
--

DROP TABLE IF EXISTS `shift_swap_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_swap_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `swap_with_employee_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shift_swap_requests_employee_id_foreign` (`employee_id`),
  KEY `shift_swap_requests_swap_with_employee_id_foreign` (`swap_with_employee_id`),
  KEY `shift_swap_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `shift_swap_requests_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `shift_swap_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_swap_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_swap_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shift_swap_requests_swap_with_employee_id_foreign` FOREIGN KEY (`swap_with_employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_swap_requests`
--

LOCK TABLES `shift_swap_requests` WRITE;
/*!40000 ALTER TABLE `shift_swap_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_swap_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shifts`
--

DROP TABLE IF EXISTS `shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `break_minutes` smallint unsigned NOT NULL DEFAULT '0',
  `grace_minutes` smallint unsigned NOT NULL DEFAULT '0',
  `half_day_hours` decimal(4,2) DEFAULT NULL,
  `full_day_hours` decimal(4,2) DEFAULT NULL,
  `is_night_shift` tinyint(1) NOT NULL DEFAULT '0',
  `is_flexible` tinyint(1) NOT NULL DEFAULT '0',
  `flexible_window_minutes` smallint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shifts_company_id_branch_id_code_unique` (`company_id`,`branch_id`,`code`),
  KEY `shifts_branch_id_foreign` (`branch_id`),
  CONSTRAINT `shifts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shifts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shifts`
--

LOCK TABLES `shifts` WRITE;
/*!40000 ALTER TABLE `shifts` DISABLE KEYS */;
INSERT INTO `shifts` VALUES (1,2,1,'General Shift','GEN','09:00:00','18:00:00',60,10,4.00,8.00,0,0,NULL,1,'2026-09-18 20:01:40','2026-09-18 20:01:40'),(2,2,5,'General Shift','GEN','09:00:00','18:00:00',60,10,4.00,8.00,0,0,NULL,1,'2026-09-18 20:01:40','2026-09-18 20:01:40'),(5,2,1,'Morning Shift','MOR','06:00:00','14:00:00',30,10,3.75,7.50,0,0,NULL,1,'2026-09-28 01:00:46','2026-09-28 01:00:46'),(34,2,1,'Night Shift','NGT','22:00:00','06:00:00',45,10,3.75,7.25,1,0,NULL,1,'2026-09-28 01:36:44','2026-09-28 01:36:44');
/*!40000 ALTER TABLE `shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_templates`
--

DROP TABLE IF EXISTS `sms_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sms_templates_company_id_foreign` (`company_id`),
  CONSTRAINT `sms_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_templates`
--

LOCK TABLES `sms_templates` WRITE;
/*!40000 ALTER TABLE `sms_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_declarations`
--

DROP TABLE IF EXISTS `tax_declarations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_declarations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `financial_year` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `regime` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `declared_investments` decimal(12,2) DEFAULT NULL,
  `annual_tax` decimal(12,2) NOT NULL DEFAULT '0.00',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tax_declarations_employee_id_financial_year_unique` (`employee_id`,`financial_year`),
  KEY `tax_declarations_company_id_foreign` (`company_id`),
  CONSTRAINT `tax_declarations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tax_declarations_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_declarations`
--

LOCK TABLES `tax_declarations` WRITE;
/*!40000 ALTER TABLE `tax_declarations` DISABLE KEYS */;
INSERT INTO `tax_declarations` VALUES (13,2,17,'2026-2027','old',NULL,9700.00,NULL,'2026-10-06 08:02:41','2026-10-06 08:02:41'),(14,2,18,'2026-2027','old',NULL,70900.00,NULL,'2026-10-06 08:03:09','2026-10-06 08:03:09'),(15,2,16,'2026-2027','new',NULL,162400.00,NULL,'2026-10-06 08:03:28','2026-10-06 08:03:28'),(16,2,19,'2026-2027','new',NULL,0.00,NULL,'2026-10-06 08:03:53','2026-10-06 08:03:53'),(17,2,4,'2026-2027','new',NULL,0.00,NULL,'2026-10-06 08:04:02','2026-10-06 08:04:02');
/*!40000 ALTER TABLE `tax_declarations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_slabs`
--

DROP TABLE IF EXISTS `tax_slabs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_slabs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `regime` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `financial_year` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_income` decimal(14,2) NOT NULL,
  `max_income` decimal(14,2) DEFAULT NULL,
  `tax_rate_percent` decimal(5,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tax_slabs_company_id_regime_financial_year_index` (`company_id`,`regime`,`financial_year`),
  KEY `tax_slabs_branch_id_foreign` (`branch_id`),
  CONSTRAINT `tax_slabs_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tax_slabs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_slabs`
--

LOCK TABLES `tax_slabs` WRITE;
/*!40000 ALTER TABLE `tax_slabs` DISABLE KEYS */;
INSERT INTO `tax_slabs` VALUES (5,2,1,'old','2026-2027',0.00,250000.00,0.00,'2026-10-06 07:47:09','2026-10-06 07:47:09'),(6,2,1,'old','2026-2027',250000.00,500000.00,5.00,'2026-10-06 07:47:09','2026-10-06 07:47:09'),(7,2,1,'old','2026-2027',500000.00,1000000.00,20.00,'2026-10-06 07:47:09','2026-10-06 07:47:09'),(8,2,1,'old','2026-2027',1000000.00,NULL,30.00,'2026-10-06 07:47:09','2026-10-06 07:47:09'),(9,2,1,'new','2026-2027',0.00,400000.00,0.00,'2026-10-06 07:47:09','2026-10-06 07:47:09'),(10,2,1,'new','2026-2027',400000.00,800000.00,5.00,'2026-10-06 07:47:09','2026-10-06 07:47:09'),(11,2,1,'new','2026-2027',800000.00,1200000.00,10.00,'2026-10-06 07:47:09','2026-10-06 07:47:09'),(12,2,1,'new','2026-2027',1200000.00,1600000.00,15.00,'2026-10-06 07:47:09','2026-10-06 07:47:09'),(13,2,1,'new','2026-2027',1600000.00,2000000.00,20.00,'2026-10-06 07:47:09','2026-10-06 07:47:09'),(14,2,1,'new','2026-2027',2000000.00,2400000.00,25.00,'2026-10-06 07:47:09','2026-10-06 07:47:09'),(15,2,1,'new','2026-2027',2400000.00,NULL,30.00,'2026-10-06 07:47:09','2026-10-06 07:47:09');
/*!40000 ALTER TABLE `tax_slabs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenants`
--

DROP TABLE IF EXISTS `tenants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenants` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  PRIMARY KEY (`id`),
  CONSTRAINT `tenants_chk_1` CHECK (json_valid(`data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenants`
--

LOCK TABLES `tenants` WRITE;
/*!40000 ALTER TABLE `tenants` DISABLE KEYS */;
/*!40000 ALTER TABLE `tenants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trainers`
--

DROP TABLE IF EXISTS `trainers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trainers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialization` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trainers_company_id_foreign` (`company_id`),
  KEY `trainers_employee_id_foreign` (`employee_id`),
  CONSTRAINT `trainers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trainers_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainers`
--

LOCK TABLES `trainers` WRITE;
/*!40000 ALTER TABLE `trainers` DISABLE KEYS */;
/*!40000 ALTER TABLE `trainers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_enrollments`
--

DROP TABLE IF EXISTS `training_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_enrollments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `training_session_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'assigned',
  `assigned_date` date NOT NULL,
  `completed_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `training_enrollments_training_session_id_employee_id_unique` (`training_session_id`,`employee_id`),
  KEY `training_enrollments_company_id_foreign` (`company_id`),
  KEY `training_enrollments_employee_id_foreign` (`employee_id`),
  CONSTRAINT `training_enrollments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_enrollments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_enrollments_training_session_id_foreign` FOREIGN KEY (`training_session_id`) REFERENCES `training_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_enrollments`
--

LOCK TABLES `training_enrollments` WRITE;
/*!40000 ALTER TABLE `training_enrollments` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_sessions`
--

DROP TABLE IF EXISTS `training_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `course_id` bigint unsigned NOT NULL,
  `trainer_id` bigint unsigned DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity` int unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_sessions_course_id_foreign` (`course_id`),
  KEY `training_sessions_trainer_id_foreign` (`trainer_id`),
  KEY `training_sessions_company_id_start_date_index` (`company_id`,`start_date`),
  KEY `training_sessions_branch_id_foreign` (`branch_id`),
  CONSTRAINT `training_sessions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_sessions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_sessions_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_sessions_trainer_id_foreign` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_sessions`
--

LOCK TABLES `training_sessions` WRITE;
/*!40000 ALTER TABLE `training_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transfer_requests`
--

DROP TABLE IF EXISTS `transfer_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transfer_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `to_department_id` bigint unsigned DEFAULT NULL,
  `to_branch_id` bigint unsigned DEFAULT NULL,
  `transfer_date` date NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transfer_requests_employee_id_foreign` (`employee_id`),
  KEY `transfer_requests_to_department_id_foreign` (`to_department_id`),
  KEY `transfer_requests_to_branch_id_foreign` (`to_branch_id`),
  KEY `transfer_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `transfer_requests_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `transfer_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transfer_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transfer_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transfer_requests_to_branch_id_foreign` FOREIGN KEY (`to_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transfer_requests_to_department_id_foreign` FOREIGN KEY (`to_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transfer_requests`
--

LOCK TABLES `transfer_requests` WRITE;
/*!40000 ALTER TABLE `transfer_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `transfer_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `travel_bookings`
--

DROP TABLE IF EXISTS `travel_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `travel_bookings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `travel_request_id` bigint unsigned NOT NULL,
  `booking_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'flight',
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `booking_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `travel_date` date NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `travel_bookings_company_id_foreign` (`company_id`),
  KEY `travel_bookings_travel_request_id_foreign` (`travel_request_id`),
  CONSTRAINT `travel_bookings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `travel_bookings_travel_request_id_foreign` FOREIGN KEY (`travel_request_id`) REFERENCES `travel_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `travel_bookings`
--

LOCK TABLES `travel_bookings` WRITE;
/*!40000 ALTER TABLE `travel_bookings` DISABLE KEYS */;
/*!40000 ALTER TABLE `travel_bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `travel_expenses`
--

DROP TABLE IF EXISTS `travel_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `travel_expenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `travel_request_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `amount` decimal(12,2) NOT NULL,
  `expense_date` date NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `travel_expenses_travel_request_id_foreign` (`travel_request_id`),
  KEY `travel_expenses_employee_id_foreign` (`employee_id`),
  KEY `travel_expenses_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `travel_expenses_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `travel_expenses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `travel_expenses_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `travel_expenses_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `travel_expenses_travel_request_id_foreign` FOREIGN KEY (`travel_request_id`) REFERENCES `travel_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `travel_expenses`
--

LOCK TABLES `travel_expenses` WRITE;
/*!40000 ALTER TABLE `travel_expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `travel_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `travel_requests`
--

DROP TABLE IF EXISTS `travel_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `travel_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `purpose` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `mode_of_travel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'flight',
  `estimated_cost` decimal(12,2) DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `travel_requests_employee_id_foreign` (`employee_id`),
  KEY `travel_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `travel_requests_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `travel_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `travel_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `travel_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `travel_requests`
--

LOCK TABLES `travel_requests` WRITE;
/*!40000 ALTER TABLE `travel_requests` DISABLE KEYS */;
INSERT INTO `travel_requests` VALUES (83,2,18,'Client meeting','Delhi','Mumbai','2026-10-08','2026-10-09','flight',NULL,'pending',NULL,NULL,NULL,'2026-10-07 23:52:33','2026-10-07 23:52:33'),(84,2,4,'Client meeting','Delhi','Mumbai','2026-10-01','2026-10-01','flight',30000.00,'pending',NULL,NULL,NULL,'2026-10-09 23:30:44','2026-10-09 23:30:44');
/*!40000 ALTER TABLE `travel_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `travel_settlements`
--

DROP TABLE IF EXISTS `travel_settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `travel_settlements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `travel_request_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `total_booking_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_expense_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `reimbursement_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `settled_date` date DEFAULT NULL,
  `settled_by_user_id` bigint unsigned DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `travel_settlements_travel_request_id_unique` (`travel_request_id`),
  KEY `travel_settlements_company_id_foreign` (`company_id`),
  KEY `travel_settlements_employee_id_foreign` (`employee_id`),
  KEY `travel_settlements_settled_by_user_id_foreign` (`settled_by_user_id`),
  CONSTRAINT `travel_settlements_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `travel_settlements_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `travel_settlements_settled_by_user_id_foreign` FOREIGN KEY (`settled_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `travel_settlements_travel_request_id_foreign` FOREIGN KEY (`travel_request_id`) REFERENCES `travel_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `travel_settlements`
--

LOCK TABLES `travel_settlements` WRITE;
/*!40000 ALTER TABLE `travel_settlements` DISABLE KEYS */;
/*!40000 ALTER TABLE `travel_settlements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `employee_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `invitation_accepted_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_super_admin` tinyint(1) NOT NULL DEFAULT '0',
  `is_platform_admin` tinyint(1) NOT NULL DEFAULT '0',
  `app_access` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `default_app` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('male','female','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `designation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_employee_code_unique` (`employee_code`),
  KEY `users_name_index` (`name`),
  KEY `users_phone_index` (`phone`),
  KEY `users_department_index` (`department`),
  KEY `users_is_active_index` (`is_active`),
  KEY `users_is_super_admin_index` (`is_super_admin`),
  KEY `users_created_at_index` (`created_at`),
  KEY `users_company_id_foreign` (`company_id`),
  CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `users_chk_1` CHECK (json_valid(`app_access`))
) ENGINE=InnoDB AUTO_INCREMENT=151 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,2,'001','Vivek Kumar Pandey','admin@admin.com','2026-08-02 00:20:10',NULL,'$2y$12$4GfX3fNO2wASpQGGAJ6oq.dCeP6Kssd/w9CvCEAeZzyR4MPp5p2ka',1,0,NULL,'hrm','avatars/01KZ0HZ9B75X87M1QREFTD6Q4K.jpg','8210991424','male','1995-05-29','IT','IT','2026-08-05',1,'1124','Ranchi','Jharkhand','India','834002',NULL,'2026-08-02 00:20:10','2026-10-17 00:28:27'),(21,2,NULL,'Priya Sharma','priya.sharma@onesuite.test',NULL,NULL,'$2y$12$0TQgeDs1Z.FvjzlR8AOeZuCLM9t0Ulg2n/cF8ez8hlCbMYkdgXyX2',0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-18 20:01:40','2026-09-18 20:01:40'),(22,2,NULL,'Arjun Mehta','arjun.mehta@onesuite.test',NULL,NULL,'$2y$12$zFuYMP0XJRRPR8n1wzMs7ud7dSKyABQ3Een9h7zgS5Xb2f7DqAIVe',0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-18 20:01:40','2026-09-18 20:01:40'),(23,2,NULL,'Kavya Reddy','employee@gmail.com',NULL,NULL,'$2y$12$bLxfykVHVpxmunEoYoAUuuFdDSx.bPYeYBA7Sl1.8L1ytvTZKP6mK',0,0,'[\"hrm\"]','hrm',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-18 20:01:41','2026-10-07 02:17:44'),(24,2,NULL,'Rohan Gupta','rohan.gupta@onesuite.test',NULL,NULL,'$2y$12$B0lSpCOpK6jR.DofgwX.we8xqDkb0LA1B2FCV4UjukIrPMvzV3h4u',0,0,'[\"hrm\"]','hrm',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-18 20:01:41','2026-09-18 20:01:41'),(25,2,NULL,'Sneha Iyer','sneha.iyer@onesuite.test',NULL,NULL,'$2y$12$eJQXyKTpNq5gUSbsgLUtjeJ1LsGWGaMh0ZdaTieA119NJ17rnAESK',0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-18 20:01:41','2026-09-18 20:01:41'),(26,NULL,NULL,'Platform Admin','platform-admin@onesuite.test',NULL,NULL,'$2y$12$jBKHQri3wk9atUhZYpA2ouM31WBDfTnRU0MOfQqtvIMr0Z6VSkbOa',0,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-19 10:18:54','2026-09-21 21:39:20'),(27,3,NULL,'Acme Admin','admin@acme.test',NULL,NULL,'$2y$12$TW1W6bgVsyJdBinL179KY.g2jzkyK22FyxbdNQ2vHEtgd8NoZmlYG',1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-19 10:21:59','2026-09-19 10:26:33'),(30,6,NULL,'Jane Doe','jane@client3.test','2026-09-21 21:57:09','2026-09-21 21:57:09','$2y$12$x.5Ud/rXoTr21oI9bCkUT.97N3R4dLQb4PGhxrFtDjrYy24yJ5CZ6',1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-21 21:55:53','2026-09-21 21:57:09'),(31,7,NULL,'Sam Lee','sam@client4.test',NULL,NULL,'$2y$12$OdijEtZMUDprwUIwzG0BmeEYF704uoyFJF8VDl3XKePPQMG5VExg2',1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-21 21:57:43','2026-09-21 21:57:43'),(32,2,NULL,'Riya Kapoor','riya@onesuite-client.test','2026-09-21 22:03:18','2026-09-21 22:03:18','$2y$12$y4YShyh4ExRT5Rct5jwrPuKAEjgLD0X7lEq7fWZr2yd1npuWToQtW',0,0,'[\"hrm\"]','hrm',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-21 22:03:05','2026-09-21 22:03:18');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wfh_requests`
--

DROP TABLE IF EXISTS `wfh_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wfh_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wfh_requests_employee_id_foreign` (`employee_id`),
  KEY `wfh_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `wfh_requests_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `wfh_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wfh_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wfh_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wfh_requests`
--

LOCK TABLES `wfh_requests` WRITE;
/*!40000 ALTER TABLE `wfh_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `wfh_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_templates`
--

DROP TABLE IF EXISTS `whatsapp_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `whatsapp_templates_company_id_foreign` (`company_id`),
  CONSTRAINT `whatsapp_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_templates`
--

LOCK TABLES `whatsapp_templates` WRITE;
/*!40000 ALTER TABLE `whatsapp_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `whatsapp_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_steps`
--

DROP TABLE IF EXISTS `workflow_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_steps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `workflow_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `approver_role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sequence` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_steps_workflow_id_foreign` (`workflow_id`),
  CONSTRAINT `workflow_steps_workflow_id_foreign` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_steps`
--

LOCK TABLES `workflow_steps` WRITE;
/*!40000 ALTER TABLE `workflow_steps` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_steps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflows`
--

DROP TABLE IF EXISTS `workflows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflows_company_id_foreign` (`company_id`),
  CONSTRAINT `workflows_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflows`
--

LOCK TABLES `workflows` WRITE;
/*!40000 ALTER TABLE `workflows` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflows` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `working_hours`
--

DROP TABLE IF EXISTS `working_hours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `working_hours` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `day_of_week` tinyint unsigned NOT NULL,
  `is_working_day` tinyint(1) NOT NULL DEFAULT '1',
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `working_hours_company_id_branch_id_day_of_week_unique` (`company_id`,`branch_id`,`day_of_week`),
  KEY `working_hours_branch_id_foreign` (`branch_id`),
  CONSTRAINT `working_hours_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `working_hours_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `working_hours`
--

LOCK TABLES `working_hours` WRITE;
/*!40000 ALTER TABLE `working_hours` DISABLE KEYS */;
INSERT INTO `working_hours` VALUES (1,2,1,1,1,'10:00:00','19:00:00','2026-08-03 04:27:01','2026-08-03 04:28:48'),(2,2,1,2,1,'09:00:00','18:00:00','2026-08-03 04:27:01','2026-08-03 04:28:48'),(3,2,1,3,1,'09:00:00','18:00:00','2026-08-03 04:27:01','2026-08-03 04:28:48'),(4,2,1,4,1,'09:00:00','18:00:00','2026-08-03 04:27:01','2026-08-03 04:28:48'),(5,2,1,5,1,'09:00:00','18:00:00','2026-08-03 04:27:01','2026-08-03 04:28:48'),(6,2,1,6,0,NULL,NULL,'2026-08-03 04:27:01','2026-08-03 04:28:48'),(7,2,1,0,0,NULL,NULL,'2026-08-03 04:27:01','2026-08-03 04:28:49'),(8,2,5,1,1,'09:00:00','18:00:00','2026-09-17 21:55:13','2026-09-17 21:55:13'),(9,2,5,2,1,'09:00:00','18:00:00','2026-09-17 21:55:13','2026-09-17 21:55:13'),(10,2,5,3,1,'09:00:00','18:00:00','2026-09-17 21:55:13','2026-09-17 21:55:13'),(11,2,5,4,1,'09:00:00','18:00:00','2026-09-17 21:55:13','2026-09-17 21:55:13'),(12,2,5,5,1,'09:00:00','18:00:00','2026-09-17 21:55:13','2026-09-17 21:55:13'),(13,2,5,6,0,'09:00:00','18:00:00','2026-09-17 21:55:13','2026-09-17 21:55:13'),(14,2,5,0,0,'09:00:00','18:00:00','2026-09-17 21:55:13','2026-09-17 21:55:13');
/*!40000 ALTER TABLE `working_hours` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'onesuite'
--

--
-- Dumping routines for database 'onesuite'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-22 17:36:12
