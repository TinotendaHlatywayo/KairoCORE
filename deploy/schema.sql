-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: schoolcore
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `academic_automation_rules`
--

DROP TABLE IF EXISTS `academic_automation_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_automation_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `trigger_event` varchar(255) NOT NULL,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`conditions`)),
  `actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`actions`)),
  `frequency` varchar(50) NOT NULL DEFAULT 'instant',
  `delay_minutes` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_automation_rules_school_id_foreign` (`school_id`),
  CONSTRAINT `academic_automation_rules_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `academic_reports`
--

DROP TABLE IF EXISTS `academic_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `section_id` bigint(20) unsigned DEFAULT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `unhu_competencies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`unhu_competencies`)),
  `overall_score` decimal(4,2) NOT NULL DEFAULT 0.00,
  `strength` text DEFAULT NULL,
  `needs_improvement` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'approved',
  `teacher_id` bigint(20) unsigned DEFAULT NULL,
  `teacher_comment` text DEFAULT NULL,
  `headmaster_comment` text DEFAULT NULL,
  `integrity_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_reports_integrity_hash_unique` (`integrity_hash`),
  KEY `academic_reports_student_id_foreign` (`student_id`),
  KEY `academic_reports_term_id_foreign` (`term_id`),
  KEY `academic_reports_section_id_foreign` (`section_id`),
  KEY `academic_reports_teacher_id_foreign` (`teacher_id`),
  KEY `idx_academic_reports_school_student_term` (`school_id`,`student_id`,`term_id`),
  CONSTRAINT `academic_reports_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_reports_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_reports_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_reports_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_reports_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `academic_validation_rules`
--

DROP TABLE IF EXISTS `academic_validation_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_validation_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `rule_key` varchar(255) NOT NULL,
  `entity_type` varchar(255) NOT NULL,
  `action` varchar(255) NOT NULL,
  `error_message` text NOT NULL,
  `warning_message` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_critical` tinyint(1) NOT NULL DEFAULT 0,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditions`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_validation_rules_rule_key_unique` (`rule_key`),
  KEY `academic_validation_rules_school_id_foreign` (`school_id`),
  CONSTRAINT `academic_validation_rules_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `academic_workflow_history`
--

DROP TABLE IF EXISTS `academic_workflow_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_workflow_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `entity_type` varchar(255) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `action` varchar(255) NOT NULL,
  `workflow_step` varchar(255) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `reason` text DEFAULT NULL,
  `browser` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_workflow_history_school_id_foreign` (`school_id`),
  KEY `academic_workflow_history_user_id_foreign` (`user_id`),
  KEY `academic_workflow_history_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `academic_workflow_history_workflow_step_index` (`workflow_step`),
  CONSTRAINT `academic_workflow_history_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_workflow_history_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `academic_workflow_steps`
--

DROP TABLE IF EXISTS `academic_workflow_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_workflow_steps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `step_key` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `step_order` int(11) NOT NULL DEFAULT 0,
  `depends_on` varchar(255) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` bigint(20) unsigned DEFAULT NULL,
  `skipped_until` timestamp NULL DEFAULT NULL,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_workflow_steps_school_id_step_key_unique` (`school_id`,`step_key`),
  KEY `academic_workflow_steps_completed_by_foreign` (`completed_by`),
  CONSTRAINT `academic_workflow_steps_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_workflow_steps_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `academic_workflow_templates`
--

DROP TABLE IF EXISTS `academic_workflow_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_workflow_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `workflow_definition` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`workflow_definition`)),
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_workflow_templates_code_unique` (`code`),
  KEY `academic_workflow_templates_school_id_foreign` (`school_id`),
  CONSTRAINT `academic_workflow_templates_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `academic_years`
--

DROP TABLE IF EXISTS `academic_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_years` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `workflow_completed_at` timestamp NULL DEFAULT NULL,
  `workflow_status` varchar(50) NOT NULL DEFAULT 'draft',
  `workflow_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`workflow_metadata`)),
  PRIMARY KEY (`id`),
  KEY `academic_years_school_id_foreign` (`school_id`),
  CONSTRAINT `academic_years_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `normal_balance` varchar(255) NOT NULL DEFAULT 'debit',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounts_school_id_code_unique` (`school_id`,`code`),
  CONSTRAINT `accounts_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `adaptive_assessments`
--

DROP TABLE IF EXISTS `adaptive_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `adaptive_assessments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `digital_assessment_id` bigint(20) unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `base_difficulty` int(10) unsigned NOT NULL DEFAULT 50,
  `min_difficulty` int(10) unsigned NOT NULL DEFAULT 0,
  `max_difficulty` int(10) unsigned NOT NULL DEFAULT 100,
  `window_size` int(10) unsigned NOT NULL DEFAULT 3,
  `adjustment_rate` decimal(5,2) NOT NULL DEFAULT 10.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_aa_school_da` (`school_id`,`digital_assessment_id`),
  CONSTRAINT `adaptive_assessments_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `adaptive_rules`
--

DROP TABLE IF EXISTS `adaptive_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `adaptive_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `adaptive_assessment_id` bigint(20) unsigned NOT NULL,
  `rule_type` varchar(50) NOT NULL,
  `threshold_from` int(10) unsigned DEFAULT NULL,
  `threshold_to` int(10) unsigned DEFAULT NULL,
  `condition_op` varchar(10) NOT NULL DEFAULT '>=',
  `adjustment` int(10) unsigned NOT NULL DEFAULT 0,
  `target_question_bank_id` bigint(20) unsigned DEFAULT NULL,
  `target_difficulty` int(10) unsigned DEFAULT NULL,
  `priority` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `adaptive_rules_school_id_foreign` (`school_id`),
  KEY `idx_ar_aa_priority` (`adaptive_assessment_id`,`priority`),
  CONSTRAINT `adaptive_rules_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `application_documents`
--

DROP TABLE IF EXISTS `application_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `application_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `application_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `application_documents_school_id_foreign` (`school_id`),
  KEY `application_documents_application_id_foreign` (`application_id`),
  CONSTRAINT `application_documents_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_documents_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `applications`
--

DROP TABLE IF EXISTS `applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `applications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `application_number` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `date_of_birth` date NOT NULL,
  `parent_name` varchar(255) NOT NULL,
  `parent_email` varchar(255) NOT NULL,
  `parent_phone` varchar(255) NOT NULL,
  `parent_relationship` varchar(255) DEFAULT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `applying_year` varchar(255) DEFAULT NULL,
  `applying_term` varchar(255) DEFAULT NULL,
  `applying_level` varchar(255) DEFAULT NULL,
  `transfer_letter_path` varchar(255) DEFAULT NULL,
  `transfer_letter_verified` tinyint(1) NOT NULL DEFAULT 0,
  `photo_path` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `documents_verified` tinyint(1) NOT NULL DEFAULT 0,
  `interview_date` date DEFAULT NULL,
  `interview_notes` text DEFAULT NULL,
  `interview_status` varchar(255) NOT NULL DEFAULT 'pending',
  `decision_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `applications_school_id_application_number_unique` (`school_id`,`application_number`),
  KEY `applications_course_id_foreign` (`course_id`),
  CONSTRAINT `applications_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `applications_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `assessment_marks`
--

DROP TABLE IF EXISTS `assessment_marks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_marks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `assessment_type_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `marks_obtained` decimal(5,2) DEFAULT NULL,
  `teacher_initials` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_assessment_mark` (`enrollment_id`,`assessment_type_id`,`subject_id`),
  KEY `assessment_marks_school_id_foreign` (`school_id`),
  KEY `assessment_marks_subject_id_foreign` (`subject_id`),
  KEY `idx_assessment_marks_type_subject` (`assessment_type_id`,`subject_id`),
  CONSTRAINT `assessment_marks_assessment_type_id_foreign` FOREIGN KEY (`assessment_type_id`) REFERENCES `assessment_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_marks_enrollment_id_foreign` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_marks_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_marks_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `assessment_marks_ledger`
--

DROP TABLE IF EXISTS `assessment_marks_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_marks_ledger` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `assessment_id` bigint(20) unsigned NOT NULL,
  `assessment_sub_component_id` bigint(20) unsigned DEFAULT NULL,
  `marks_obtained` decimal(5,2) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'present',
  `teacher_comment` text DEFAULT NULL,
  `is_digital` tinyint(1) NOT NULL DEFAULT 0,
  `digital_assessment_attempt_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_mark_registration` (`enrollment_id`,`assessment_id`,`assessment_sub_component_id`),
  KEY `assessment_marks_ledger_school_id_foreign` (`school_id`),
  KEY `assessment_marks_ledger_assessment_id_foreign` (`assessment_id`),
  KEY `assessment_marks_ledger_assessment_sub_component_id_foreign` (`assessment_sub_component_id`),
  KEY `assessment_marks_ledger_digital_assessment_attempt_id_foreign` (`digital_assessment_attempt_id`),
  CONSTRAINT `assessment_marks_ledger_assessment_id_foreign` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_marks_ledger_assessment_sub_component_id_foreign` FOREIGN KEY (`assessment_sub_component_id`) REFERENCES `assessment_sub_components` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_marks_ledger_digital_assessment_attempt_id_foreign` FOREIGN KEY (`digital_assessment_attempt_id`) REFERENCES `digital_assessment_attempts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assessment_marks_ledger_enrollment_id_foreign` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_marks_ledger_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `assessment_plan_components`
--

DROP TABLE IF EXISTS `assessment_plan_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_plan_components` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_plan_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `weight_percentage` decimal(5,2) NOT NULL,
  `evaluation_rule` varchar(255) NOT NULL DEFAULT 'average',
  `rule_value_parameter` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assessment_plan_components_assessment_plan_id_foreign` (`assessment_plan_id`),
  CONSTRAINT `assessment_plan_components_assessment_plan_id_foreign` FOREIGN KEY (`assessment_plan_id`) REFERENCES `assessment_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `assessment_plans`
--

DROP TABLE IF EXISTS `assessment_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `created_by_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_subject_term_plan` (`term_id`,`course_id`,`subject_id`),
  KEY `assessment_plans_school_id_foreign` (`school_id`),
  KEY `assessment_plans_course_id_foreign` (`course_id`),
  KEY `assessment_plans_subject_id_foreign` (`subject_id`),
  KEY `assessment_plans_created_by_id_foreign` (`created_by_id`),
  CONSTRAINT `assessment_plans_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_plans_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_plans_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_plans_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_plans_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `assessment_sub_components`
--

DROP TABLE IF EXISTS `assessment_sub_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_sub_components` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `max_mark` decimal(5,2) NOT NULL,
  `weight_percentage` decimal(5,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assessment_sub_components_assessment_id_foreign` (`assessment_id`),
  CONSTRAINT `assessment_sub_components_assessment_id_foreign` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `assessment_types`
--

DROP TABLE IF EXISTS `assessment_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `max_mark` decimal(5,2) NOT NULL DEFAULT 100.00,
  `weight_percentage` decimal(5,2) NOT NULL DEFAULT 100.00,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `course_id` bigint(20) unsigned DEFAULT NULL,
  `section_id` bigint(20) unsigned DEFAULT NULL,
  `created_by_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'marking',
  PRIMARY KEY (`id`),
  KEY `assessment_types_school_id_foreign` (`school_id`),
  KEY `assessment_types_term_id_foreign` (`term_id`),
  KEY `assessment_types_subject_id_foreign` (`subject_id`),
  KEY `assessment_types_course_id_foreign` (`course_id`),
  KEY `assessment_types_section_id_foreign` (`section_id`),
  KEY `assessment_types_created_by_id_foreign` (`created_by_id`),
  CONSTRAINT `assessment_types_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_types_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_types_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_types_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_types_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_types_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `assessments`
--

DROP TABLE IF EXISTS `assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `assessment_plan_component_id` bigint(20) unsigned NOT NULL,
  `section_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `assessment_date` date NOT NULL,
  `max_mark` decimal(5,2) NOT NULL DEFAULT 100.00,
  `included_in_report` tinyint(1) NOT NULL DEFAULT 1,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `mode` varchar(20) NOT NULL DEFAULT 'traditional',
  `digital_assessment_id` bigint(20) unsigned DEFAULT NULL,
  `created_by_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assessments_school_id_foreign` (`school_id`),
  KEY `assessments_assessment_plan_component_id_foreign` (`assessment_plan_component_id`),
  KEY `assessments_section_id_foreign` (`section_id`),
  KEY `assessments_created_by_id_foreign` (`created_by_id`),
  KEY `assessments_digital_assessment_id_foreign` (`digital_assessment_id`),
  KEY `assessments_mode_index` (`mode`),
  CONSTRAINT `assessments_assessment_plan_component_id_foreign` FOREIGN KEY (`assessment_plan_component_id`) REFERENCES `assessment_plan_components` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessments_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessments_digital_assessment_id_foreign` FOREIGN KEY (`digital_assessment_id`) REFERENCES `digital_assessments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assessments_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessments_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `asset_maintenance_logs`
--

DROP TABLE IF EXISTS `asset_maintenance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_maintenance_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `fixed_asset_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'preventive',
  `schedule_type` varchar(255) NOT NULL DEFAULT 'one_time',
  `recurrence_interval_days` int(11) DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `completed_date` date DEFAULT NULL,
  `cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `performed_by` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_maintenance_logs_school_id_foreign` (`school_id`),
  KEY `asset_maintenance_logs_fixed_asset_id_foreign` (`fixed_asset_id`),
  CONSTRAINT `asset_maintenance_logs_fixed_asset_id_foreign` FOREIGN KEY (`fixed_asset_id`) REFERENCES `fixed_assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_maintenance_logs_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `card_print_history`
--

DROP TABLE IF EXISTS `card_print_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `card_print_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `card_template_id` bigint(20) unsigned NOT NULL,
  `serial_number` varchar(255) NOT NULL,
  `verification_code` varchar(255) NOT NULL,
  `printed_by_id` bigint(20) unsigned DEFAULT NULL,
  `printed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `printer_type` varchar(255) NOT NULL DEFAULT 'pvc',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `card_print_history_serial_number_unique` (`serial_number`),
  KEY `card_print_history_school_id_foreign` (`school_id`),
  KEY `card_print_history_student_id_foreign` (`student_id`),
  KEY `card_print_history_card_template_id_foreign` (`card_template_id`),
  KEY `card_print_history_printed_by_id_foreign` (`printed_by_id`),
  CONSTRAINT `card_print_history_card_template_id_foreign` FOREIGN KEY (`card_template_id`) REFERENCES `card_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `card_print_history_printed_by_id_foreign` FOREIGN KEY (`printed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `card_print_history_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `card_print_history_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `card_template_versions`
--

DROP TABLE IF EXISTS `card_template_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `card_template_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `card_template_id` bigint(20) unsigned NOT NULL,
  `version_number` int(11) NOT NULL DEFAULT 1,
  `layout_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`layout_config`)),
  `created_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `card_template_versions_card_template_id_foreign` (`card_template_id`),
  KEY `card_template_versions_created_by_id_foreign` (`created_by_id`),
  CONSTRAINT `card_template_versions_card_template_id_foreign` FOREIGN KEY (`card_template_id`) REFERENCES `card_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `card_template_versions_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `card_templates`
--

DROP TABLE IF EXISTS `card_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `card_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `orientation` varchar(255) NOT NULL DEFAULT 'portrait',
  `card_type` varchar(255) NOT NULL DEFAULT 'student',
  `barcode_format` varchar(255) NOT NULL DEFAULT 'Code128',
  `background_path` varchar(255) DEFAULT NULL,
  `watermark_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `layout_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`layout_config`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `card_templates_school_id_foreign` (`school_id`),
  CONSTRAINT `card_templates_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `challenge_participants`
--

DROP TABLE IF EXISTS `challenge_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `challenge_participants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `gamification_challenge_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `progress` smallint(5) unsigned NOT NULL DEFAULT 0,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `xp_earned` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cp_challenge_student` (`gamification_challenge_id`,`student_id`),
  KEY `challenge_participants_student_id_foreign` (`student_id`),
  KEY `idx_cp_school_student` (`school_id`,`student_id`),
  CONSTRAINT `challenge_participants_gamification_challenge_id_foreign` FOREIGN KEY (`gamification_challenge_id`) REFERENCES `gamification_challenges` (`id`) ON DELETE CASCADE,
  CONSTRAINT `challenge_participants_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `challenge_participants_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `classrooms`
--

DROP TABLE IF EXISTS `classrooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `classrooms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `capacity` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_school_classroom_name` (`school_id`,`name`),
  CONSTRAINT `classrooms_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_medical_alerts`
--

DROP TABLE IF EXISTS `clinic_medical_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clinic_medical_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `alert_level` varchar(255) NOT NULL DEFAULT 'medium',
  `message` varchar(255) NOT NULL,
  `expires_at` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_medical_alerts_school_id_foreign` (`school_id`),
  KEY `clinic_medical_alerts_student_id_foreign` (`student_id`),
  CONSTRAINT `clinic_medical_alerts_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinic_medical_alerts_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_prescriptions`
--

DROP TABLE IF EXISTS `clinic_prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clinic_prescriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `clinic_visit_id` bigint(20) unsigned NOT NULL,
  `inventory_item_id` bigint(20) unsigned DEFAULT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `dosage` varchar(255) NOT NULL,
  `frequency` varchar(255) NOT NULL,
  `quantity_prescribed` int(11) NOT NULL,
  `quantity_dispensed` int(11) NOT NULL DEFAULT 0,
  `dispensed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_prescriptions_school_id_foreign` (`school_id`),
  KEY `clinic_prescriptions_clinic_visit_id_foreign` (`clinic_visit_id`),
  KEY `clinic_prescriptions_inventory_item_id_foreign` (`inventory_item_id`),
  CONSTRAINT `clinic_prescriptions_clinic_visit_id_foreign` FOREIGN KEY (`clinic_visit_id`) REFERENCES `clinic_visits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinic_prescriptions_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_prescriptions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_visits`
--

DROP TABLE IF EXISTS `clinic_visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clinic_visits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `recorded_by_user_id` bigint(20) unsigned NOT NULL,
  `visit_time` datetime NOT NULL,
  `departure_time` datetime DEFAULT NULL,
  `symptoms` text NOT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment_given` text DEFAULT NULL,
  `temperature_celsius` decimal(4,2) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'checked_in',
  `referral_destination` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_visits_school_id_foreign` (`school_id`),
  KEY `clinic_visits_student_id_foreign` (`student_id`),
  KEY `clinic_visits_recorded_by_user_id_foreign` (`recorded_by_user_id`),
  CONSTRAINT `clinic_visits_recorded_by_user_id_foreign` FOREIGN KEY (`recorded_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinic_visits_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinic_visits_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cms_dynamic_sources`
--

DROP TABLE IF EXISTS `cms_dynamic_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_dynamic_sources` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `handle` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `module` varchar(255) NOT NULL,
  `model_class` varchar(255) NOT NULL,
  `query_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`query_config`)),
  `field_mapping` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`field_mapping`)),
  `template_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`template_config`)),
  `cache_ttl` int(10) unsigned NOT NULL DEFAULT 300,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_dynamic_sources_handle_unique` (`handle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cms_form_submissions`
--

DROP TABLE IF EXISTS `cms_form_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_form_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `cms_page_id` bigint(20) unsigned DEFAULT NULL,
  `form_handle` varchar(255) NOT NULL,
  `form_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`form_data`)),
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `status` varchar(255) NOT NULL DEFAULT 'new',
  `notes` text DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cms_global_components`
--

DROP TABLE IF EXISTS `cms_global_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_global_components` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `cms_website_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `handle` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditions`)),
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_global_components_handle_unique` (`handle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cms_media`
--

DROP TABLE IF EXISTS `cms_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `extension` varchar(255) NOT NULL,
  `file_size` bigint(20) unsigned NOT NULL,
  `disk` varchar(255) NOT NULL DEFAULT 'public',
  `path` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `width` int(10) unsigned DEFAULT NULL,
  `height` int(10) unsigned DEFAULT NULL,
  `dominant_color` varchar(255) DEFAULT NULL,
  `exif` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`exif`)),
  `folder` varchar(255) NOT NULL DEFAULT 'uploads',
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `alt_text` varchar(255) DEFAULT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `credit` varchar(255) DEFAULT NULL,
  `usage_count` int(10) unsigned NOT NULL DEFAULT 0,
  `used_in` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`used_in`)),
  `variants` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variants`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_media_uuid_unique` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cms_navigation_menus`
--

DROP TABLE IF EXISTS `cms_navigation_menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_navigation_menus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `cms_website_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `handle` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL DEFAULT 'header',
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`items`)),
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_navigation_menus_handle_unique` (`handle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cms_page_templates`
--

DROP TABLE IF EXISTS `cms_page_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_page_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `handle` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'general',
  `thumbnail` varchar(255) DEFAULT NULL,
  `blocks` longtext NOT NULL,
  `page_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`page_settings`)),
  `seo_defaults` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seo_defaults`)),
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_school_template` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_page_templates_handle_unique` (`handle`),
  KEY `cms_page_templates_school_id_index` (`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cms_page_versions`
--

DROP TABLE IF EXISTS `cms_page_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_page_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `cms_page_id` bigint(20) unsigned NOT NULL,
  `cms_website_id` bigint(20) unsigned NOT NULL,
  `version_number` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `blocks` longtext NOT NULL,
  `page_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`page_settings`)),
  `seo_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seo_data`)),
  `change_summary` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_by_type` varchar(255) DEFAULT NULL,
  `is_autosave` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cms_pages`
--

DROP TABLE IF EXISTS `cms_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `cms_website_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `is_homepage` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `blocks` longtext DEFAULT NULL,
  `draft_blocks` longtext DEFAULT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `parent_slug` varchar(255) DEFAULT NULL,
  `depth` int(10) unsigned NOT NULL DEFAULT 0,
  `is_protected` tinyint(1) NOT NULL DEFAULT 0,
  `password` varchar(255) DEFAULT NULL,
  `hide_from_nav` tinyint(1) NOT NULL DEFAULT 0,
  `hide_from_sitemap` tinyint(1) NOT NULL DEFAULT 0,
  `page_template` varchar(255) NOT NULL DEFAULT 'default',
  `page_layout` varchar(64) DEFAULT NULL,
  `page_theme` varchar(64) DEFAULT NULL,
  `seo_og_title` varchar(255) DEFAULT NULL,
  `seo_og_description` text DEFAULT NULL,
  `seo_og_image` varchar(255) DEFAULT NULL,
  `seo_twitter_card` varchar(255) DEFAULT NULL,
  `seo_structured_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seo_structured_data`)),
  `canonical_url` varchar(255) DEFAULT NULL,
  `noindex` tinyint(1) NOT NULL DEFAULT 0,
  `nofollow` tinyint(1) NOT NULL DEFAULT 0,
  `og_type` varchar(255) NOT NULL DEFAULT 'website',
  `og_locale` varchar(255) NOT NULL DEFAULT 'en_US',
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `published_by` bigint(20) unsigned DEFAULT NULL,
  `custom_css` text DEFAULT NULL,
  `custom_js` text DEFAULT NULL,
  `page_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`page_settings`)),
  `scripts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`scripts`)),
  PRIMARY KEY (`id`),
  KEY `cms_pages_cms_website_id_foreign` (`cms_website_id`),
  KEY `cms_pages_school_id_index` (`school_id`),
  CONSTRAINT `cms_pages_cms_website_id_foreign` FOREIGN KEY (`cms_website_id`) REFERENCES `cms_websites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cms_pages_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cms_reusable_blocks`
--

DROP TABLE IF EXISTS `cms_reusable_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_reusable_blocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'custom',
  `content` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cms_reusable_blocks_school_id_foreign` (`school_id`),
  CONSTRAINT `cms_reusable_blocks_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cms_site_templates`
--

DROP TABLE IF EXISTS `cms_site_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_site_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `cms_website_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cms_site_templates_school_id_index` (`school_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cms_user_preferences`
--

DROP TABLE IF EXISTS `cms_user_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_user_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `editor_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`editor_settings`)),
  `preview_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preview_settings`)),
  `block_favorites` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`block_favorites`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cms_websites`
--

DROP TABLE IF EXISTS `cms_websites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_websites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `is_template_site` tinyint(1) NOT NULL DEFAULT 0,
  `active_site_template_id` bigint(20) unsigned DEFAULT NULL,
  `active_template` varchar(255) NOT NULL DEFAULT 'modern_international',
  `font_primary` varchar(255) NOT NULL DEFAULT 'Inter',
  `font_secondary` varchar(255) NOT NULL DEFAULT 'Outfit',
  `font_heading` varchar(255) DEFAULT NULL,
  `color_primary` varchar(255) NOT NULL DEFAULT '#1e40af',
  `color_secondary` varchar(255) NOT NULL DEFAULT '#0ea5e9',
  `color_accent` varchar(255) NOT NULL DEFAULT '#f59e0b',
  `color_background` varchar(255) NOT NULL DEFAULT '#ffffff',
  `color_text` varchar(255) NOT NULL DEFAULT '#1f2937',
  `color_card_bg` varchar(255) NOT NULL DEFAULT '#f9fafb',
  `navigation_menu` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`navigation_menu`)),
  `footer_menu` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`footer_menu`)),
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_links`)),
  `custom_css` text DEFAULT NULL,
  `custom_js` text DEFAULT NULL,
  `logo_light_path` varchar(255) DEFAULT NULL,
  `logo_dark_path` varchar(255) DEFAULT NULL,
  `design_radius` varchar(255) NOT NULL DEFAULT 'md',
  `design_shadow` varchar(255) NOT NULL DEFAULT 'md',
  `design_container` varchar(255) NOT NULL DEFAULT 'wide',
  `design_button_style` varchar(255) NOT NULL DEFAULT 'pill',
  `favicon_path` varchar(255) DEFAULT NULL,
  `seo_title_suffix` varchar(255) DEFAULT NULL,
  `seo_global_description` text DEFAULT NULL,
  `seo_og_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `color_border` varchar(255) NOT NULL DEFAULT '#e5e7eb',
  `color_error` varchar(255) NOT NULL DEFAULT '#ef4444',
  `color_success` varchar(255) NOT NULL DEFAULT '#10b981',
  `color_warning` varchar(255) NOT NULL DEFAULT '#f59e0b',
  `design_spacing_scale` varchar(255) NOT NULL DEFAULT 'md',
  `seo_default_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seo_default_meta`)),
  `apple_touch_icon_path` varchar(255) DEFAULT NULL,
  `custom_head` text DEFAULT NULL,
  `announcement_banner` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`announcement_banner`)),
  `theme_overrides` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`theme_overrides`)),
  `font_load_strategy` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`font_load_strategy`)),
  `enable_animations` tinyint(1) NOT NULL DEFAULT 1,
  `enable_lazy_load` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `cms_websites_school_id_index` (`school_id`),
  CONSTRAINT `cms_websites_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `communication_announcements`
--

DROP TABLE IF EXISTS `communication_announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communication_announcements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `published_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `visibility` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`visibility`)),
  `priority` varchar(50) NOT NULL DEFAULT 'normal',
  `display_style` varchar(50) NOT NULL DEFAULT 'card',
  `requires_acknowledgement` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `communication_announcements_school_id_status_priority_index` (`school_id`,`status`,`priority`),
  CONSTRAINT `communication_announcements_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `communication_chat_messages`
--

DROP TABLE IF EXISTS `communication_chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communication_chat_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `thread_id` bigint(20) unsigned NOT NULL,
  `sender_id` bigint(20) unsigned NOT NULL,
  `message` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `reactions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reactions`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `communication_chat_messages_thread_id_foreign` (`thread_id`),
  KEY `communication_chat_messages_sender_id_foreign` (`sender_id`),
  KEY `communication_chat_messages_school_id_thread_id_created_at_index` (`school_id`,`thread_id`,`created_at`),
  CONSTRAINT `communication_chat_messages_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `communication_chat_messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `communication_chat_messages_thread_id_foreign` FOREIGN KEY (`thread_id`) REFERENCES `communication_chat_threads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `communication_chat_participants`
--

DROP TABLE IF EXISTS `communication_chat_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communication_chat_participants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `thread_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `last_read_at` timestamp NULL DEFAULT NULL,
  `is_muted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_thread_participant` (`school_id`,`thread_id`,`user_id`),
  KEY `communication_chat_participants_thread_id_foreign` (`thread_id`),
  KEY `communication_chat_participants_user_id_foreign` (`user_id`),
  CONSTRAINT `communication_chat_participants_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `communication_chat_participants_thread_id_foreign` FOREIGN KEY (`thread_id`) REFERENCES `communication_chat_threads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `communication_chat_participants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `communication_chat_threads`
--

DROP TABLE IF EXISTS `communication_chat_threads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communication_chat_threads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'one_to_one',
  `name` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `communication_chat_threads_school_id_foreign` (`school_id`),
  CONSTRAINT `communication_chat_threads_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `communication_events`
--

DROP TABLE IF EXISTS `communication_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communication_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `created_by_id` bigint(20) unsigned DEFAULT NULL,
  `organizer_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `all_day` tinyint(1) NOT NULL DEFAULT 0,
  `location` varchar(255) DEFAULT NULL,
  `reminder_minutes` int(10) unsigned DEFAULT NULL,
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `recurrence` varchar(20) NOT NULL DEFAULT 'none',
  `color` varchar(50) NOT NULL DEFAULT '#1e3a8a',
  `target_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_roles`)),
  `target_sections` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_sections`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `communication_events_school_id_start_time_end_time_index` (`school_id`,`start_time`,`end_time`),
  KEY `communication_events_created_by_id_foreign` (`created_by_id`),
  KEY `communication_events_organizer_id_foreign` (`organizer_id`),
  CONSTRAINT `communication_events_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `communication_events_organizer_id_foreign` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `communication_events_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `communication_helpdesk_replies`
--

DROP TABLE IF EXISTS `communication_helpdesk_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communication_helpdesk_replies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `message` text NOT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `communication_helpdesk_replies_school_id_foreign` (`school_id`),
  KEY `communication_helpdesk_replies_ticket_id_foreign` (`ticket_id`),
  KEY `communication_helpdesk_replies_user_id_foreign` (`user_id`),
  CONSTRAINT `communication_helpdesk_replies_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `communication_helpdesk_replies_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `communication_helpdesk_tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `communication_helpdesk_replies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `communication_helpdesk_tickets`
--

DROP TABLE IF EXISTS `communication_helpdesk_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communication_helpdesk_tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `ticket_number` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `category` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `priority` varchar(50) NOT NULL DEFAULT 'medium',
  `status` varchar(50) NOT NULL DEFAULT 'open',
  `assigned_to_id` bigint(20) unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ticket_number` (`school_id`,`ticket_number`),
  KEY `communication_helpdesk_tickets_user_id_foreign` (`user_id`),
  KEY `communication_helpdesk_tickets_assigned_to_id_foreign` (`assigned_to_id`),
  KEY `communication_helpdesk_tickets_school_id_status_priority_index` (`school_id`,`status`,`priority`),
  CONSTRAINT `communication_helpdesk_tickets_assigned_to_id_foreign` FOREIGN KEY (`assigned_to_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `communication_helpdesk_tickets_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `communication_helpdesk_tickets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `communication_poll_options`
--

DROP TABLE IF EXISTS `communication_poll_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communication_poll_options` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `poll_id` bigint(20) unsigned NOT NULL,
  `option_value` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `communication_poll_options_school_id_foreign` (`school_id`),
  KEY `communication_poll_options_poll_id_foreign` (`poll_id`),
  CONSTRAINT `communication_poll_options_poll_id_foreign` FOREIGN KEY (`poll_id`) REFERENCES `communication_polls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `communication_poll_options_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `communication_poll_votes`
--

DROP TABLE IF EXISTS `communication_poll_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communication_poll_votes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `poll_id` bigint(20) unsigned NOT NULL,
  `option_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `written_response` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_poll_vote` (`school_id`,`poll_id`,`user_id`),
  KEY `communication_poll_votes_poll_id_foreign` (`poll_id`),
  KEY `communication_poll_votes_option_id_foreign` (`option_id`),
  KEY `communication_poll_votes_user_id_foreign` (`user_id`),
  CONSTRAINT `communication_poll_votes_option_id_foreign` FOREIGN KEY (`option_id`) REFERENCES `communication_poll_options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `communication_poll_votes_poll_id_foreign` FOREIGN KEY (`poll_id`) REFERENCES `communication_polls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `communication_poll_votes_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `communication_poll_votes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `communication_polls`
--

DROP TABLE IF EXISTS `communication_polls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communication_polls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `question` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'poll',
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `target_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_roles`)),
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `communication_polls_school_id_foreign` (`school_id`),
  CONSTRAINT `communication_polls_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `communication_resources`
--

DROP TABLE IF EXISTS `communication_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communication_resources` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `visibility` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`visibility`)),
  `version` varchar(255) NOT NULL DEFAULT '1.0',
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `download_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `communication_resources_school_id_foreign` (`school_id`),
  CONSTRAINT `communication_resources_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `course_subject`
--

DROP TABLE IF EXISTS `course_subject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_subject` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `section_id` bigint(20) unsigned DEFAULT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'main',
  `periods_per_week` int(10) unsigned NOT NULL DEFAULT 4,
  `room_preference` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_course_subject` (`school_id`,`course_id`,`subject_id`),
  KEY `course_subject_course_id_foreign` (`course_id`),
  KEY `course_subject_subject_id_foreign` (`subject_id`),
  KEY `course_subject_teacher_id_foreign` (`teacher_id`),
  KEY `course_subject_section_id_foreign` (`section_id`),
  CONSTRAINT `course_subject_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_subject_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_subject_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `course_subject_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_subject_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `teacher_id` bigint(20) unsigned DEFAULT NULL,
  `level` varchar(255) NOT NULL DEFAULT 'primary',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `workflow_completed_at` timestamp NULL DEFAULT NULL,
  `workflow_status` varchar(50) NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_school_course_name` (`school_id`,`name`),
  KEY `courses_teacher_id_foreign` (`teacher_id`),
  CONSTRAINT `courses_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `courses_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `custom_roles`
--

DROP TABLE IF EXISTS `custom_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custom_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`permissions`)),
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_custom_role_name` (`school_id`,`name`),
  CONSTRAINT `custom_roles_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `department_user`
--

DROP TABLE IF EXISTS `department_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_department_user_pair` (`user_id`,`department_id`),
  KEY `department_user_school_id_foreign` (`school_id`),
  KEY `department_user_department_id_foreign` (`department_id`),
  CONSTRAINT `department_user_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `department_user_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `department_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'academic',
  `head_user_id` bigint(20) unsigned DEFAULT NULL,
  `budget_code` varchar(100) DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dept_code` (`school_id`,`code`),
  CONSTRAINT `departments_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `depreciation_schedules`
--

DROP TABLE IF EXISTS `depreciation_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `depreciation_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `fixed_asset_id` bigint(20) unsigned NOT NULL,
  `fiscal_year` int(11) NOT NULL,
  `depreciation_amount` decimal(15,2) NOT NULL,
  `book_value_start` decimal(15,2) NOT NULL,
  `book_value_end` decimal(15,2) NOT NULL,
  `is_posted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_depr_asset_year` (`school_id`,`fixed_asset_id`,`fiscal_year`),
  KEY `depreciation_schedules_fixed_asset_id_foreign` (`fixed_asset_id`),
  CONSTRAINT `depreciation_schedules_fixed_asset_id_foreign` FOREIGN KEY (`fixed_asset_id`) REFERENCES `fixed_assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `depreciation_schedules_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `digital_assessment_attempts`
--

DROP TABLE IF EXISTS `digital_assessment_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `digital_assessment_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `digital_assessment_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned DEFAULT NULL,
  `attempt_number` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `submitted_at` timestamp NULL DEFAULT NULL,
  `duration_seconds` int(10) unsigned DEFAULT NULL,
  `score` decimal(7,2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `auto_score` decimal(7,2) DEFAULT NULL,
  `manual_score` decimal(7,2) DEFAULT NULL,
  `final_score` decimal(7,2) DEFAULT NULL,
  `marks_obtained` decimal(7,2) DEFAULT NULL,
  `max_possible_marks` decimal(7,2) DEFAULT NULL,
  `status` varchar(25) NOT NULL DEFAULT 'in_progress',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `suspicious_activity_log` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`suspicious_activity_log`)),
  `feedback_viewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_daat_assess_stud_att` (`digital_assessment_id`,`student_id`,`attempt_number`),
  KEY `digital_assessment_attempts_enrollment_id_foreign` (`enrollment_id`),
  KEY `idx_daat_school_da_stat` (`school_id`,`digital_assessment_id`,`status`),
  KEY `idx_daat_school_stud_stat` (`school_id`,`student_id`,`status`),
  KEY `idx_daat_student_status` (`student_id`,`status`),
  CONSTRAINT `digital_assessment_attempts_digital_assessment_id_foreign` FOREIGN KEY (`digital_assessment_id`) REFERENCES `digital_assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `digital_assessment_attempts_enrollment_id_foreign` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `digital_assessment_attempts_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `digital_assessment_attempts_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `digital_assessment_auto_saves`
--

DROP TABLE IF EXISTS `digital_assessment_auto_saves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `digital_assessment_auto_saves` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `digital_assessment_attempt_id` bigint(20) unsigned NOT NULL,
  `question_bank_id` bigint(20) unsigned NOT NULL,
  `response_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_data`)),
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_daas_attempt_qb` (`digital_assessment_attempt_id`,`question_bank_id`),
  KEY `fk_daas_qb` (`question_bank_id`),
  CONSTRAINT `fk_daas_attempt` FOREIGN KEY (`digital_assessment_attempt_id`) REFERENCES `digital_assessment_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_daas_qb` FOREIGN KEY (`question_bank_id`) REFERENCES `question_bank` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `digital_assessment_questions`
--

DROP TABLE IF EXISTS `digital_assessment_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `digital_assessment_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `digital_assessment_id` bigint(20) unsigned NOT NULL,
  `question_bank_id` bigint(20) unsigned NOT NULL,
  `question_order` smallint(5) unsigned NOT NULL,
  `marks_override` decimal(5,2) DEFAULT NULL,
  `pool_name` varchar(255) DEFAULT NULL,
  `pool_weight` smallint(5) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_daq_assess_qb` (`digital_assessment_id`,`question_bank_id`),
  KEY `digital_assessment_questions_question_bank_id_foreign` (`question_bank_id`),
  KEY `idx_daq_assess_order` (`digital_assessment_id`,`question_order`),
  CONSTRAINT `digital_assessment_questions_digital_assessment_id_foreign` FOREIGN KEY (`digital_assessment_id`) REFERENCES `digital_assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `digital_assessment_questions_question_bank_id_foreign` FOREIGN KEY (`question_bank_id`) REFERENCES `question_bank` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `digital_assessment_responses`
--

DROP TABLE IF EXISTS `digital_assessment_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `digital_assessment_responses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `digital_assessment_attempt_id` bigint(20) unsigned NOT NULL,
  `question_bank_id` bigint(20) unsigned NOT NULL,
  `learner_answer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`learner_answer`)),
  `file_path` varchar(255) DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `file_mime` varchar(255) DEFAULT NULL,
  `correct_answer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`correct_answer`)),
  `is_correct` tinyint(1) DEFAULT NULL,
  `marks_awarded` decimal(5,2) DEFAULT NULL,
  `marks_possible` decimal(5,2) DEFAULT NULL,
  `time_spent_seconds` smallint(5) unsigned DEFAULT NULL,
  `answered_at` timestamp NULL DEFAULT NULL,
  `confidence_level` tinyint(3) unsigned DEFAULT NULL,
  `question_difficulty_at_time` varchar(20) DEFAULT NULL,
  `question_selection_reason` text DEFAULT NULL,
  `teacher_feedback` text DEFAULT NULL,
  `feedback_viewed_at` timestamp NULL DEFAULT NULL,
  `marked_by_id` bigint(20) unsigned DEFAULT NULL,
  `marked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_dar_attempt_qb` (`digital_assessment_attempt_id`,`question_bank_id`),
  KEY `digital_assessment_responses_marked_by_id_foreign` (`marked_by_id`),
  KEY `idx_dar_qb_iscorrect` (`question_bank_id`,`is_correct`),
  CONSTRAINT `digital_assessment_responses_marked_by_id_foreign` FOREIGN KEY (`marked_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_dar_attempt` FOREIGN KEY (`digital_assessment_attempt_id`) REFERENCES `digital_assessment_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dar_qb` FOREIGN KEY (`question_bank_id`) REFERENCES `question_bank` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `digital_assessments`
--

DROP TABLE IF EXISTS `digital_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `digital_assessments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `section_id` bigint(20) unsigned DEFAULT NULL,
  `academic_year_id` bigint(20) unsigned DEFAULT NULL,
  `term_id` bigint(20) unsigned DEFAULT NULL,
  `created_by_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `instructions` longtext DEFAULT NULL,
  `assessment_mode` varchar(20) NOT NULL DEFAULT 'standard',
  `assessment_category` varchar(20) NOT NULL DEFAULT 'formative',
  `contributes_to_grade` tinyint(1) NOT NULL DEFAULT 0,
  `assessment_type_id` bigint(20) unsigned DEFAULT NULL,
  `difficulty` varchar(20) DEFAULT NULL,
  `duration_minutes` smallint(5) unsigned DEFAULT NULL,
  `total_marks` decimal(7,2) NOT NULL DEFAULT 0.00,
  `pass_mark` decimal(5,2) NOT NULL DEFAULT 0.00,
  `max_attempts` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `attempts_allowed` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `randomize_questions` tinyint(1) NOT NULL DEFAULT 0,
  `randomize_options` tinyint(1) NOT NULL DEFAULT 0,
  `show_feedback` tinyint(1) NOT NULL DEFAULT 1,
  `feedback_mode` varchar(25) NOT NULL DEFAULT 'after_submission',
  `shuffle_question_pool` tinyint(1) NOT NULL DEFAULT 0,
  `allow_backward_navigation` tinyint(1) NOT NULL DEFAULT 1,
  `allow_question_skipping` tinyint(1) NOT NULL DEFAULT 1,
  `calculator_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `password_protection` tinyint(1) NOT NULL DEFAULT 0,
  `anti_cheating_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `question_pool_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`question_pool_config`)),
  `availability_start_at` timestamp NULL DEFAULT NULL,
  `availability_end_at` timestamp NULL DEFAULT NULL,
  `deadline_at` timestamp NULL DEFAULT NULL,
  `late_submission_allowed` tinyint(1) NOT NULL DEFAULT 0,
  `auto_submit` tinyint(1) NOT NULL DEFAULT 1,
  `adaptive_mode` tinyint(1) NOT NULL DEFAULT 0,
  `adaptive_base_difficulty` int(10) unsigned NOT NULL DEFAULT 50,
  `adaptive_window_size` int(10) unsigned NOT NULL DEFAULT 3,
  `adaptive_adjustment_rate` decimal(5,2) NOT NULL DEFAULT 10.00,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `digital_assessments_subject_id_foreign` (`subject_id`),
  KEY `digital_assessments_section_id_foreign` (`section_id`),
  KEY `digital_assessments_academic_year_id_foreign` (`academic_year_id`),
  KEY `digital_assessments_term_id_foreign` (`term_id`),
  KEY `digital_assessments_created_by_id_foreign` (`created_by_id`),
  KEY `digital_assessments_assessment_type_id_foreign` (`assessment_type_id`),
  KEY `idx_da_school_subj_status` (`school_id`,`subject_id`,`status`),
  KEY `idx_da_school_sect_status` (`school_id`,`section_id`,`status`),
  KEY `idx_da_school_creator` (`school_id`,`created_by_id`),
  KEY `idx_da_status_avail` (`status`,`availability_start_at`,`availability_end_at`),
  CONSTRAINT `digital_assessments_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `digital_assessments_assessment_type_id_foreign` FOREIGN KEY (`assessment_type_id`) REFERENCES `assessment_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `digital_assessments_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `digital_assessments_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `digital_assessments_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `digital_assessments_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `digital_assessments_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `disciplinary_cases`
--

DROP TABLE IF EXISTS `disciplinary_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disciplinary_cases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `offense` text NOT NULL,
  `incident_date` date NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'under_investigation',
  `severity` varchar(50) NOT NULL DEFAULT 'verbal_warning',
  `resolution_notes` text DEFAULT NULL,
  `action_taken_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disciplinary_cases_school_id_foreign` (`school_id`),
  KEY `disciplinary_cases_employee_id_foreign` (`employee_id`),
  KEY `disciplinary_cases_action_taken_by_id_foreign` (`action_taken_by_id`),
  CONSTRAINT `disciplinary_cases_action_taken_by_id_foreign` FOREIGN KEY (`action_taken_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `disciplinary_cases_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `disciplinary_cases_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `domains`
--

DROP TABLE IF EXISTS `domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `domains` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `domain` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `domains_domain_unique` (`domain`),
  KEY `domains_school_id_foreign` (`school_id`),
  CONSTRAINT `domains_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `email_configurations`
--

DROP TABLE IF EXISTS `email_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_configurations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `category` varchar(30) NOT NULL,
  `mailer` varchar(30) NOT NULL DEFAULT 'platform',
  `from_name` varchar(255) DEFAULT NULL,
  `from_email` varchar(255) DEFAULT NULL,
  `reply_to_name` varchar(255) DEFAULT NULL,
  `reply_to_email` varchar(255) DEFAULT NULL,
  `host` varchar(255) DEFAULT NULL,
  `port` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` text DEFAULT NULL,
  `encryption` varchar(10) DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_configurations_school_id_category_unique` (`school_id`,`category`),
  CONSTRAINT `email_configurations_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `employee_assets`
--

DROP TABLE IF EXISTS `employee_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_assets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `serial_number` varchar(255) NOT NULL,
  `issued_date` date NOT NULL,
  `returned_date` date DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'issued',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_assets_school_id_foreign` (`school_id`),
  KEY `employee_assets_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_assets_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_assets_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `employee_contracts`
--

DROP TABLE IF EXISTS `employee_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_contracts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `contract_number` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `probation_period_days` int(11) NOT NULL DEFAULT 90,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `document_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_contract_num` (`school_id`,`contract_number`),
  KEY `employee_contracts_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_contracts_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_contracts_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `employee_number` varchar(255) NOT NULL,
  `national_id` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `gender` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `marital_status` varchar(50) NOT NULL DEFAULT 'single',
  `phone_number` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `physical_address` text NOT NULL,
  `emergency_contact_name` varchar(255) NOT NULL,
  `emergency_contact_phone` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'Teacher',
  `employment_type` varchar(255) NOT NULL DEFAULT 'Permanent',
  `contract_end_date` date DEFAULT NULL,
  `date_joined` date NOT NULL,
  `current_grade_id` bigint(20) unsigned DEFAULT NULL,
  `spouse_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`spouse_details`)),
  `dependents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dependents`)),
  `next_of_kin` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`next_of_kin`)),
  `medical_conditions` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `emergency_medical_notes` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `suspension_reason` text DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `photo_rejected_reason` text DEFAULT NULL,
  `photo_rejected_by` bigint(20) unsigned DEFAULT NULL,
  `photo_rejected_at` timestamp NULL DEFAULT NULL,
  `document_contract` varchar(255) DEFAULT NULL,
  `document_academic` varchar(255) DEFAULT NULL,
  `document_professional` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_num` (`school_id`,`employee_number`),
  UNIQUE KEY `uq_emp_nat_id` (`school_id`,`national_id`),
  KEY `employees_user_id_foreign` (`user_id`),
  KEY `employees_current_grade_id_foreign` (`current_grade_id`),
  KEY `employees_school_id_status_index` (`school_id`,`status`),
  CONSTRAINT `employees_current_grade_id_foreign` FOREIGN KEY (`current_grade_id`) REFERENCES `salary_grades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enrollments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `academic_year_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `section_id` bigint(20) unsigned NOT NULL,
  `roll_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `enrollments_school_id_student_id_academic_year_id_unique` (`school_id`,`student_id`,`academic_year_id`),
  KEY `enrollments_student_id_foreign` (`student_id`),
  KEY `enrollments_academic_year_id_foreign` (`academic_year_id`),
  KEY `enrollments_course_id_foreign` (`course_id`),
  KEY `enrollments_section_id_foreign` (`section_id`),
  KEY `idx_enrollments_school_section` (`school_id`,`section_id`),
  CONSTRAINT `enrollments_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollments_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollments_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `enterprise_report_schedules`
--

DROP TABLE IF EXISTS `enterprise_report_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enterprise_report_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `enterprise_report_template_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `frequency` varchar(255) NOT NULL,
  `distribution_method` varchar(20) NOT NULL DEFAULT 'email',
  `output_format` varchar(10) NOT NULL DEFAULT 'pdf',
  `generate_on_demand` tinyint(1) NOT NULL DEFAULT 0,
  `recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`recipients`)),
  `filter_overrides` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filter_overrides`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_run_at` timestamp NULL DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enterprise_report_schedules_school_id_foreign` (`school_id`),
  KEY `fk_ent_sched_temp_id` (`enterprise_report_template_id`),
  CONSTRAINT `enterprise_report_schedules_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ent_sched_temp_id` FOREIGN KEY (`enterprise_report_template_id`) REFERENCES `enterprise_report_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `enterprise_report_templates`
--

DROP TABLE IF EXISTS `enterprise_report_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enterprise_report_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `module` varchar(255) NOT NULL,
  `report_type` varchar(255) NOT NULL,
  `report_category` varchar(80) DEFAULT NULL,
  `orientation` varchar(255) NOT NULL DEFAULT 'portrait',
  `selected_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`selected_fields`)),
  `layout_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`layout_settings`)),
  `sharing_scope` varchar(20) NOT NULL DEFAULT 'private',
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `datasets` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datasets`)),
  `joins` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`joins`)),
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `grouping` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`grouping`)),
  `calculations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`calculations`)),
  `sorting` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sorting`)),
  `visualizations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`visualizations`)),
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_favorite` tinyint(1) NOT NULL DEFAULT 0,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `config_version` int(10) unsigned NOT NULL DEFAULT 1,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `last_run_at` timestamp NULL DEFAULT NULL,
  `last_edited_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ent_rep_temp_school_name` (`school_id`,`name`),
  KEY `enterprise_report_templates_created_by_id_foreign` (`created_by_id`),
  KEY `idx_ert_sharing_scope` (`sharing_scope`),
  KEY `idx_ert_report_category` (`report_category`),
  CONSTRAINT `enterprise_report_templates_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enterprise_report_templates_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `expense_categories`
--

DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expense_categories_school_id_foreign` (`school_id`),
  KEY `expense_categories_account_id_foreign` (`account_id`),
  CONSTRAINT `expense_categories_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expense_categories_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `expense_types`
--

DROP TABLE IF EXISTS `expense_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `expense_category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expense_types_school_id_foreign` (`school_id`),
  KEY `expense_types_expense_category_id_foreign` (`expense_category_id`),
  KEY `expense_types_account_id_foreign` (`account_id`),
  CONSTRAINT `expense_types_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expense_types_expense_category_id_foreign` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expense_types_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `expense_type_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `expense_date` date NOT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'approved',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_school_id_foreign` (`school_id`),
  KEY `expenses_expense_type_id_foreign` (`expense_type_id`),
  KEY `expenses_supplier_id_foreign` (`supplier_id`),
  KEY `expenses_account_id_foreign` (`account_id`),
  KEY `expenses_user_id_foreign` (`user_id`),
  CONSTRAINT `expenses_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_expense_type_id_foreign` FOREIGN KEY (`expense_type_id`) REFERENCES `expense_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expenses_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expenses_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `exports`
--

DROP TABLE IF EXISTS `exports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `completed_at` timestamp NULL DEFAULT NULL,
  `file_disk` varchar(255) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `exporter` varchar(255) NOT NULL,
  `processed_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `total_rows` int(10) unsigned NOT NULL,
  `successful_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exports_user_id_foreign` (`user_id`),
  CONSTRAINT `exports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_import_rows`
--

DROP TABLE IF EXISTS `failed_import_rows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_import_rows` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `import_id` bigint(20) unsigned NOT NULL,
  `validation_error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `failed_import_rows_import_id_foreign` (`import_id`),
  CONSTRAINT `failed_import_rows_import_id_foreign` FOREIGN KEY (`import_id`) REFERENCES `imports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fee_categories`
--

DROP TABLE IF EXISTS `fee_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fee_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fee_categories_school_id_foreign` (`school_id`),
  CONSTRAINT `fee_categories_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fee_structures`
--

DROP TABLE IF EXISTS `fee_structures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fee_structures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `fee_category_id` bigint(20) unsigned NOT NULL,
  `scope_type` varchar(255) NOT NULL DEFAULT 'single',
  `course_id` bigint(20) unsigned DEFAULT NULL,
  `academic_year_id` bigint(20) unsigned NOT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `currency` varchar(255) NOT NULL DEFAULT 'USD',
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fee_structures_school_id_foreign` (`school_id`),
  KEY `fee_structures_fee_category_id_foreign` (`fee_category_id`),
  KEY `fee_structures_course_id_foreign` (`course_id`),
  KEY `fee_structures_academic_year_id_foreign` (`academic_year_id`),
  KEY `fee_structures_term_id_foreign` (`term_id`),
  CONSTRAINT `fee_structures_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_structures_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_structures_fee_category_id_foreign` FOREIGN KEY (`fee_category_id`) REFERENCES `fee_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_structures_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_structures_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fee_waivers`
--

DROP TABLE IF EXISTS `fee_waivers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fee_waivers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'percentage',
  `value` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fee_waivers_school_id_foreign` (`school_id`),
  CONSTRAINT `fee_waivers_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `finance_auditing_trails`
--

DROP TABLE IF EXISTS `finance_auditing_trails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `finance_auditing_trails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  `payload_before` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload_before`)),
  `payload_after` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload_after`)),
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `finance_auditing_trails_school_id_foreign` (`school_id`),
  KEY `finance_auditing_trails_user_id_foreign` (`user_id`),
  CONSTRAINT `finance_auditing_trails_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `finance_auditing_trails_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `finance_document_templates`
--

DROP TABLE IF EXISTS `finance_document_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `finance_document_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `design_theme` varchar(255) NOT NULL DEFAULT 'classic_line',
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `layout_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`layout_config`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `finance_document_templates_school_id_index` (`school_id`),
  KEY `finance_document_templates_document_type_index` (`document_type`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fixed_assets`
--

DROP TABLE IF EXISTS `fixed_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fixed_assets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `asset_number` varchar(255) NOT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `acquisition_date` date NOT NULL,
  `purchase_cost` decimal(15,2) NOT NULL,
  `salvage_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `useful_life_years` int(10) unsigned NOT NULL,
  `depreciation_method` varchar(255) NOT NULL DEFAULT 'straight_line',
  `current_value` decimal(15,2) NOT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `funding_source` varchar(255) NOT NULL DEFAULT 'school_funds',
  `insurance_policy_number` varchar(255) DEFAULT NULL,
  `assigned_location_id` bigint(20) unsigned DEFAULT NULL,
  `custodian_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fixed_asset_num` (`school_id`,`asset_number`),
  KEY `fixed_assets_inventory_item_id_foreign` (`inventory_item_id`),
  KEY `fixed_assets_assigned_location_id_foreign` (`assigned_location_id`),
  KEY `fixed_assets_custodian_id_foreign` (`custodian_id`),
  CONSTRAINT `fixed_assets_assigned_location_id_foreign` FOREIGN KEY (`assigned_location_id`) REFERENCES `inventory_locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fixed_assets_custodian_id_foreign` FOREIGN KEY (`custodian_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fixed_assets_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fixed_assets_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gamification_achievements`
--

DROP TABLE IF EXISTS `gamification_achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gamification_achievements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) NOT NULL DEFAULT 'heroicon-o-trophy',
  `criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`criteria`)),
  `xp_reward` smallint(5) unsigned NOT NULL DEFAULT 0,
  `achievement_type` varchar(30) NOT NULL DEFAULT 'performance',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ga_school_active` (`school_id`,`is_active`),
  CONSTRAINT `gamification_achievements_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gamification_badges`
--

DROP TABLE IF EXISTS `gamification_badges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gamification_badges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) NOT NULL DEFAULT 'heroicon-o-star',
  `criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`criteria`)),
  `xp_reward` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `visibility` varchar(20) NOT NULL DEFAULT 'public',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gb_school_active` (`school_id`,`is_active`),
  CONSTRAINT `gamification_badges_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gamification_challenges`
--

DROP TABLE IF EXISTS `gamification_challenges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gamification_challenges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `created_by_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `challenge_type` varchar(20) NOT NULL DEFAULT 'individual',
  `target_subject_id` bigint(20) unsigned DEFAULT NULL,
  `target_topic` varchar(255) DEFAULT NULL,
  `target_count` smallint(5) unsigned NOT NULL DEFAULT 5,
  `reward_xp` smallint(5) unsigned NOT NULL DEFAULT 100,
  `reward_badge_id` bigint(20) unsigned DEFAULT NULL,
  `start_at` timestamp NULL DEFAULT NULL,
  `end_at` timestamp NULL DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gamification_challenges_created_by_id_foreign` (`created_by_id`),
  KEY `gamification_challenges_target_subject_id_foreign` (`target_subject_id`),
  KEY `gamification_challenges_reward_badge_id_foreign` (`reward_badge_id`),
  KEY `idx_gch_school_status_dates` (`school_id`,`status`,`start_at`,`end_at`),
  CONSTRAINT `gamification_challenges_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gamification_challenges_reward_badge_id_foreign` FOREIGN KEY (`reward_badge_id`) REFERENCES `gamification_badges` (`id`) ON DELETE SET NULL,
  CONSTRAINT `gamification_challenges_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gamification_challenges_target_subject_id_foreign` FOREIGN KEY (`target_subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gamification_settings`
--

DROP TABLE IF EXISTS `gamification_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gamification_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `xp_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `badges_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `achievements_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `streaks_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `challenges_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `leaderboards_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `xp_per_assessment_complete` smallint(5) unsigned NOT NULL DEFAULT 10,
  `xp_per_improvement` smallint(5) unsigned NOT NULL DEFAULT 15,
  `xp_per_streak_day` smallint(5) unsigned NOT NULL DEFAULT 5,
  `xp_per_topic_mastery` smallint(5) unsigned NOT NULL DEFAULT 25,
  `xp_per_challenge_complete` smallint(5) unsigned NOT NULL DEFAULT 20,
  `streak_qualifying_activities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`streak_qualifying_activities`)),
  `level_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`level_config`)),
  `leaderboard_scope` varchar(20) NOT NULL DEFAULT 'class',
  `leaderboard_anonymize` tinyint(1) NOT NULL DEFAULT 0,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_gs_school` (`school_id`),
  CONSTRAINT `gamification_settings_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `generated_reports`
--

DROP TABLE IF EXISTS `generated_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `generated_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `enterprise_report_template_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `format` varchar(255) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `generated_by_id` bigint(20) unsigned DEFAULT NULL,
  `record_count` int(10) unsigned NOT NULL DEFAULT 0,
  `execution_ms` int(10) unsigned DEFAULT NULL,
  `data_checksum` varchar(64) DEFAULT NULL,
  `data_validated` tinyint(1) NOT NULL DEFAULT 0,
  `validated_at` timestamp NULL DEFAULT NULL,
  `summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`summary`)),
  `filters_used` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters_used`)),
  `is_downloaded` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `generated_reports_school_id_foreign` (`school_id`),
  KEY `fk_gen_rep_temp_id` (`enterprise_report_template_id`),
  KEY `generated_reports_generated_by_id_foreign` (`generated_by_id`),
  CONSTRAINT `fk_gen_rep_temp_id` FOREIGN KEY (`enterprise_report_template_id`) REFERENCES `enterprise_report_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `generated_reports_generated_by_id_foreign` FOREIGN KEY (`generated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `generated_reports_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `goods_received_items`
--

DROP TABLE IF EXISTS `goods_received_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goods_received_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `goods_received_note_id` bigint(20) unsigned NOT NULL,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `quantity_accepted` int(11) NOT NULL,
  `quantity_rejected` int(11) NOT NULL DEFAULT 0,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `batch_number` varchar(255) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_received_items_goods_received_note_id_foreign` (`goods_received_note_id`),
  KEY `goods_received_items_inventory_item_id_foreign` (`inventory_item_id`),
  CONSTRAINT `goods_received_items_goods_received_note_id_foreign` FOREIGN KEY (`goods_received_note_id`) REFERENCES `goods_received_notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `goods_received_items_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `goods_received_notes`
--

DROP TABLE IF EXISTS `goods_received_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goods_received_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `procurement_order_id` bigint(20) unsigned NOT NULL,
  `grn_number` varchar(255) NOT NULL,
  `received_date` date NOT NULL,
  `received_by_id` bigint(20) unsigned DEFAULT NULL,
  `delivery_challan_number` varchar(255) DEFAULT NULL,
  `supplier_invoice_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_grn_num` (`school_id`,`grn_number`),
  KEY `goods_received_notes_procurement_order_id_foreign` (`procurement_order_id`),
  KEY `goods_received_notes_received_by_id_foreign` (`received_by_id`),
  CONSTRAINT `goods_received_notes_procurement_order_id_foreign` FOREIGN KEY (`procurement_order_id`) REFERENCES `procurement_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `goods_received_notes_received_by_id_foreign` FOREIGN KEY (`received_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `goods_received_notes_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `grading_points`
--

DROP TABLE IF EXISTS `grading_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grading_points` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `grading_scale_id` bigint(20) unsigned NOT NULL,
  `symbol` varchar(255) NOT NULL,
  `min_score` decimal(5,2) NOT NULL,
  `max_score` decimal(5,2) NOT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grading_points_grading_scale_id_foreign` (`grading_scale_id`),
  CONSTRAINT `grading_points_grading_scale_id_foreign` FOREIGN KEY (`grading_scale_id`) REFERENCES `grading_scales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `grading_scales`
--

DROP TABLE IF EXISTS `grading_scales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grading_scales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grading_scales_school_id_foreign` (`school_id`),
  CONSTRAINT `grading_scales_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `grading_systems`
--

DROP TABLE IF EXISTS `grading_systems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grading_systems` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `guardians`
--

DROP TABLE IF EXISTS `guardians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guardians` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) NOT NULL,
  `relationship` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `guardians_school_id_foreign` (`school_id`),
  CONSTRAINT `guardians_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `homework_submissions`
--

DROP TABLE IF EXISTS `homework_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `homework_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `homework_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `grade_obtained` decimal(5,2) DEFAULT NULL,
  `teacher_feedback` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_homework_submission` (`school_id`,`homework_id`,`student_id`),
  KEY `homework_submissions_homework_id_foreign` (`homework_id`),
  KEY `homework_submissions_student_id_foreign` (`student_id`),
  CONSTRAINT `homework_submissions_homework_id_foreign` FOREIGN KEY (`homework_id`) REFERENCES `homeworks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `homework_submissions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `homework_submissions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `homeworks`
--

DROP TABLE IF EXISTS `homeworks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `homeworks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `youtube_url` varchar(255) DEFAULT NULL,
  `due_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `section_id` bigint(20) unsigned DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `homeworks_section_id_foreign` (`section_id`),
  KEY `homeworks_subject_id_foreign` (`subject_id`),
  KEY `idx_homeworks_school_section` (`school_id`,`section_id`),
  CONSTRAINT `homeworks_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `homeworks_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `homeworks_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hostel_allocations`
--

DROP TABLE IF EXISTS `hostel_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hostel_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `bed_id` bigint(20) unsigned NOT NULL,
  `room_id` bigint(20) unsigned DEFAULT NULL,
  `academic_year_id` bigint(20) unsigned NOT NULL,
  `status` enum('active','completed','cancelled','waiting_list') NOT NULL DEFAULT 'active',
  `allocated_at` date NOT NULL,
  `expected_checkout_at` date DEFAULT NULL,
  `checked_out_at` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hostel_alloc_student` (`school_id`,`student_id`,`academic_year_id`,`status`),
  KEY `hostel_allocations_student_id_foreign` (`student_id`),
  KEY `hostel_allocations_bed_id_foreign` (`bed_id`),
  KEY `hostel_allocations_academic_year_id_foreign` (`academic_year_id`),
  KEY `hostel_allocations_room_id_foreign` (`room_id`),
  CONSTRAINT `hostel_allocations_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_allocations_bed_id_foreign` FOREIGN KEY (`bed_id`) REFERENCES `hostel_beds` (`id`),
  CONSTRAINT `hostel_allocations_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `hostel_rooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hostel_allocations_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_allocations_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hostel_attendance_students`
--

DROP TABLE IF EXISTS `hostel_attendance_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hostel_attendance_students` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `hostel_attendance_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `status` enum('present','absent','late','leave') NOT NULL DEFAULT 'present',
  `remarks` text DEFAULT NULL,
  `notified_parents_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hostel_att_student` (`school_id`,`hostel_attendance_id`,`student_id`),
  KEY `hostel_attendance_students_hostel_attendance_id_foreign` (`hostel_attendance_id`),
  KEY `hostel_attendance_students_student_id_foreign` (`student_id`),
  CONSTRAINT `hostel_attendance_students_hostel_attendance_id_foreign` FOREIGN KEY (`hostel_attendance_id`) REFERENCES `hostel_attendances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_attendance_students_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_attendance_students_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hostel_attendances`
--

DROP TABLE IF EXISTS `hostel_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hostel_attendances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `hostel_id` bigint(20) unsigned NOT NULL,
  `recorded_by_user_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hostel_att_day_type` (`school_id`,`hostel_id`,`date`,`type`),
  KEY `hostel_attendances_hostel_id_foreign` (`hostel_id`),
  KEY `hostel_attendances_recorded_by_user_id_foreign` (`recorded_by_user_id`),
  CONSTRAINT `hostel_attendances_hostel_id_foreign` FOREIGN KEY (`hostel_id`) REFERENCES `hostels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_attendances_recorded_by_user_id_foreign` FOREIGN KEY (`recorded_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_attendances_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hostel_beds`
--

DROP TABLE IF EXISTS `hostel_beds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hostel_beds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `room_id` bigint(20) unsigned NOT NULL,
  `bed_number` varchar(255) NOT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `condition` varchar(255) NOT NULL DEFAULT 'good',
  `status` varchar(255) NOT NULL DEFAULT 'vacant',
  `cleaning_status` varchar(255) NOT NULL DEFAULT 'clean',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hostel_bed_num` (`school_id`,`room_id`,`bed_number`),
  KEY `hostel_beds_room_id_foreign` (`room_id`),
  CONSTRAINT `hostel_beds_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `hostel_rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_beds_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hostel_buildings`
--

DROP TABLE IF EXISTS `hostel_buildings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hostel_buildings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `hostel_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hostel_bld_name` (`school_id`,`hostel_id`,`name`),
  KEY `hostel_buildings_hostel_id_foreign` (`hostel_id`),
  CONSTRAINT `hostel_buildings_hostel_id_foreign` FOREIGN KEY (`hostel_id`) REFERENCES `hostels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_buildings_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hostel_floors`
--

DROP TABLE IF EXISTS `hostel_floors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hostel_floors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `building_id` bigint(20) unsigned NOT NULL,
  `floor_number` int(11) NOT NULL,
  `floor_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hostel_flr_num` (`school_id`,`building_id`,`floor_number`),
  KEY `hostel_floors_building_id_foreign` (`building_id`),
  CONSTRAINT `hostel_floors_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `hostel_buildings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_floors_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hostel_inspections`
--

DROP TABLE IF EXISTS `hostel_inspections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hostel_inspections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `room_id` bigint(20) unsigned NOT NULL,
  `inspector_user_id` bigint(20) unsigned NOT NULL,
  `inspection_date` date NOT NULL,
  `cleanliness_score` int(11) NOT NULL,
  `inventory_status_score` int(11) NOT NULL,
  `orderliness_score` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `passes_inspection` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hostel_inspections_school_id_foreign` (`school_id`),
  KEY `hostel_inspections_room_id_foreign` (`room_id`),
  KEY `hostel_inspections_inspector_user_id_foreign` (`inspector_user_id`),
  CONSTRAINT `hostel_inspections_inspector_user_id_foreign` FOREIGN KEY (`inspector_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_inspections_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `hostel_rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_inspections_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hostel_out_passes`
--

DROP TABLE IF EXISTS `hostel_out_passes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hostel_out_passes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `hostel_id` bigint(20) unsigned NOT NULL,
  `requester_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `reason` text NOT NULL,
  `expected_departure` datetime NOT NULL,
  `expected_return` datetime NOT NULL,
  `actual_departure` datetime DEFAULT NULL,
  `actual_return` datetime DEFAULT NULL,
  `parent_otp` varchar(6) DEFAULT NULL,
  `parent_approved_at` datetime DEFAULT NULL,
  `warden_approver_id` bigint(20) unsigned DEFAULT NULL,
  `warden_approved_at` datetime DEFAULT NULL,
  `gate_scanned_at` datetime DEFAULT NULL,
  `gate_scanner_id` bigint(20) unsigned DEFAULT NULL,
  `qr_code` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hostel_out_passes_qr_code_unique` (`qr_code`),
  KEY `hostel_out_passes_school_id_foreign` (`school_id`),
  KEY `hostel_out_passes_student_id_foreign` (`student_id`),
  KEY `hostel_out_passes_hostel_id_foreign` (`hostel_id`),
  KEY `hostel_out_passes_requester_id_foreign` (`requester_id`),
  KEY `hostel_out_passes_warden_approver_id_foreign` (`warden_approver_id`),
  KEY `hostel_out_passes_gate_scanner_id_foreign` (`gate_scanner_id`),
  CONSTRAINT `hostel_out_passes_gate_scanner_id_foreign` FOREIGN KEY (`gate_scanner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hostel_out_passes_hostel_id_foreign` FOREIGN KEY (`hostel_id`) REFERENCES `hostels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_out_passes_requester_id_foreign` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_out_passes_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_out_passes_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_out_passes_warden_approver_id_foreign` FOREIGN KEY (`warden_approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hostel_rooms`
--

DROP TABLE IF EXISTS `hostel_rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hostel_rooms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `hostel_id` bigint(20) unsigned DEFAULT NULL,
  `wing_id` bigint(20) unsigned DEFAULT NULL,
  `floor_id` bigint(20) unsigned DEFAULT NULL,
  `room_number` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `room_type` varchar(255) NOT NULL DEFAULT 'dormitory',
  `condition` varchar(255) NOT NULL DEFAULT 'good',
  `status` varchar(255) NOT NULL DEFAULT 'available',
  `capacity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hostel_rm_num` (`school_id`,`floor_id`,`room_number`),
  KEY `hostel_rooms_wing_id_foreign` (`wing_id`),
  KEY `hostel_rooms_floor_id_foreign` (`floor_id`),
  KEY `hostel_rooms_hostel_id_foreign` (`hostel_id`),
  CONSTRAINT `hostel_rooms_floor_id_foreign` FOREIGN KEY (`floor_id`) REFERENCES `hostel_floors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_rooms_hostel_id_foreign` FOREIGN KEY (`hostel_id`) REFERENCES `hostels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_rooms_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_rooms_wing_id_foreign` FOREIGN KEY (`wing_id`) REFERENCES `hostel_wings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hostel_staff`
--

DROP TABLE IF EXISTS `hostel_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hostel_staff` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `hostel_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` enum('master','matron','assistant_warden') NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hostel_staff_role` (`school_id`,`hostel_id`,`user_id`,`role`),
  KEY `hostel_staff_hostel_id_foreign` (`hostel_id`),
  KEY `hostel_staff_user_id_foreign` (`user_id`),
  CONSTRAINT `hostel_staff_hostel_id_foreign` FOREIGN KEY (`hostel_id`) REFERENCES `hostels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_staff_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_staff_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hostel_visitors`
--

DROP TABLE IF EXISTS `hostel_visitors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hostel_visitors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `visitor_name` varchar(255) NOT NULL,
  `national_id` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) NOT NULL,
  `relationship` varchar(255) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `arrival_time` datetime NOT NULL,
  `departure_time` datetime DEFAULT NULL,
  `vehicle_registration` varchar(255) DEFAULT NULL,
  `items_brought` text DEFAULT NULL,
  `items_taken` text DEFAULT NULL,
  `badge_number` varchar(255) DEFAULT NULL,
  `is_blacklisted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hostel_visitors_school_id_foreign` (`school_id`),
  KEY `hostel_visitors_student_id_foreign` (`student_id`),
  CONSTRAINT `hostel_visitors_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_visitors_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hostel_wings`
--

DROP TABLE IF EXISTS `hostel_wings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hostel_wings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `floor_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hostel_wng_name` (`school_id`,`floor_id`,`name`),
  KEY `hostel_wings_floor_id_foreign` (`floor_id`),
  CONSTRAINT `hostel_wings_floor_id_foreign` FOREIGN KEY (`floor_id`) REFERENCES `hostel_floors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_wings_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hostels`
--

DROP TABLE IF EXISTS `hostels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hostels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('boys','girls','mixed') NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hostels_school_id_name_unique` (`school_id`,`name`),
  CONSTRAINT `hostels_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hr_audit_logs`
--

DROP TABLE IF EXISTS `hr_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `action` varchar(255) NOT NULL,
  `auditable_type` varchar(255) NOT NULL,
  `auditable_id` bigint(20) unsigned NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `before_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`before_payload`)),
  `after_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`after_payload`)),
  `transition_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hr_audit_logs_user_id_foreign` (`user_id`),
  KEY `uq_audit_search` (`school_id`,`auditable_type`,`auditable_id`),
  CONSTRAINT `hr_audit_logs_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hr_audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imports`
--

DROP TABLE IF EXISTS `imports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `completed_at` timestamp NULL DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `importer` varchar(255) NOT NULL,
  `processed_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `total_rows` int(10) unsigned NOT NULL,
  `successful_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `imports_user_id_foreign` (`user_id`),
  CONSTRAINT `imports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_batches`
--

DROP TABLE IF EXISTS `inventory_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `batch_number` varchar(255) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `initial_quantity` int(11) NOT NULL,
  `current_quantity` int(11) NOT NULL,
  `unit_cost` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_batch_num` (`school_id`,`inventory_item_id`,`batch_number`),
  KEY `inventory_batches_inventory_item_id_foreign` (`inventory_item_id`),
  CONSTRAINT `inventory_batches_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_batches_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_categories`
--

DROP TABLE IF EXISTS `inventory_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_cat_name` (`school_id`,`parent_id`,`name`),
  KEY `inventory_categories_parent_id_foreign` (`parent_id`),
  CONSTRAINT `inventory_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `inventory_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_categories_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_issuances`
--

DROP TABLE IF EXISTS `inventory_issuances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_issuances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `inventory_location_id` bigint(20) unsigned DEFAULT NULL,
  `inventory_batch_id` bigint(20) unsigned DEFAULT NULL,
  `issued_to_type` varchar(255) NOT NULL,
  `issued_to_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `is_returnable` tinyint(1) NOT NULL DEFAULT 0,
  `expected_return_date` date DEFAULT NULL,
  `actual_return_date` date DEFAULT NULL,
  `condition_on_issue` varchar(255) NOT NULL DEFAULT 'good',
  `condition_on_return` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'issued',
  `remarks` varchar(255) DEFAULT NULL,
  `issued_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_issuances_school_id_foreign` (`school_id`),
  KEY `inventory_issuances_inventory_item_id_foreign` (`inventory_item_id`),
  KEY `inventory_issuances_inventory_batch_id_foreign` (`inventory_batch_id`),
  KEY `inventory_issuances_issued_by_id_foreign` (`issued_by_id`),
  KEY `inventory_issuances_inventory_location_id_foreign` (`inventory_location_id`),
  CONSTRAINT `inventory_issuances_inventory_batch_id_foreign` FOREIGN KEY (`inventory_batch_id`) REFERENCES `inventory_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_issuances_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_issuances_inventory_location_id_foreign` FOREIGN KEY (`inventory_location_id`) REFERENCES `inventory_locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_issuances_issued_by_id_foreign` FOREIGN KEY (`issued_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_issuances_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_items`
--

DROP TABLE IF EXISTS `inventory_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `sku` varchar(255) NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `item_type` varchar(255) NOT NULL,
  `unit_of_measure` varchar(255) NOT NULL DEFAULT 'pieces',
  `reorder_level` int(10) unsigned NOT NULL DEFAULT 10,
  `current_quantity` int(11) NOT NULL DEFAULT 0,
  `average_unit_cost` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `is_saleable` tinyint(1) NOT NULL DEFAULT 0,
  `sale_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_item_sku` (`school_id`,`sku`),
  KEY `inventory_items_category_id_foreign` (`category_id`),
  CONSTRAINT `inventory_items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_items_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_locations`
--

DROP TABLE IF EXISTS `inventory_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'general',
  `responsible_officer_id` bigint(20) unsigned DEFAULT NULL,
  `temperature_sensitive` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_loc_code` (`school_id`,`code`),
  KEY `inventory_locations_parent_id_foreign` (`parent_id`),
  KEY `inventory_locations_responsible_officer_id_foreign` (`responsible_officer_id`),
  CONSTRAINT `inventory_locations_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `inventory_locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_locations_responsible_officer_id_foreign` FOREIGN KEY (`responsible_officer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_locations_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_maintenance_logs`
--

DROP TABLE IF EXISTS `inventory_maintenance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_maintenance_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `performed_by` varchar(255) NOT NULL,
  `maintenance_date` date NOT NULL,
  `next_due_date` date DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text NOT NULL,
  `status` enum('scheduled','in_progress','completed','failed') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_maintenance_logs_school_id_foreign` (`school_id`),
  KEY `inventory_maintenance_logs_inventory_item_id_foreign` (`inventory_item_id`),
  CONSTRAINT `inventory_maintenance_logs_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_maintenance_logs_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_procurements`
--

DROP TABLE IF EXISTS `inventory_procurements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_procurements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `requested_by_id` bigint(20) unsigned NOT NULL,
  `approved_by_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `type` enum('request','order','receipt','invoice') NOT NULL,
  `status` enum('draft','pending_approval','approved','ordered','received','cancelled') NOT NULL DEFAULT 'draft',
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `items_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items_payload`)),
  `reference_number` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventory_procurements_ref` (`school_id`,`reference_number`),
  KEY `inventory_procurements_requested_by_id_foreign` (`requested_by_id`),
  KEY `inventory_procurements_approved_by_id_foreign` (`approved_by_id`),
  KEY `inventory_procurements_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `inventory_procurements_approved_by_id_foreign` FOREIGN KEY (`approved_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_procurements_requested_by_id_foreign` FOREIGN KEY (`requested_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_procurements_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_procurements_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `inventory_suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_settings`
--

DROP TABLE IF EXISTS `inventory_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `active_modules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`active_modules`)),
  `valuation_method` varchar(255) NOT NULL DEFAULT 'moving_average',
  `low_stock_notification_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`low_stock_notification_roles`)),
  `auto_bill_issued_uniforms` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_settings_school` (`school_id`),
  CONSTRAINT `inventory_settings_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_stock_adjustment_items`
--

DROP TABLE IF EXISTS `inventory_stock_adjustment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_stock_adjustment_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_adjustment_id` bigint(20) unsigned NOT NULL,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `system_quantity` int(11) NOT NULL,
  `physical_quantity` int(11) NOT NULL,
  `variance` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_stock_adjustment_items_stock_adjustment_id_foreign` (`stock_adjustment_id`),
  KEY `inventory_stock_adjustment_items_inventory_item_id_foreign` (`inventory_item_id`),
  CONSTRAINT `inventory_stock_adjustment_items_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_stock_adjustment_items_stock_adjustment_id_foreign` FOREIGN KEY (`stock_adjustment_id`) REFERENCES `inventory_stock_adjustments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_stock_adjustments`
--

DROP TABLE IF EXISTS `inventory_stock_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_stock_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `inventory_location_id` bigint(20) unsigned NOT NULL,
  `adjustment_number` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `conducted_date` date NOT NULL,
  `conducted_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_adj_num` (`school_id`,`adjustment_number`),
  KEY `inventory_stock_adjustments_inventory_location_id_foreign` (`inventory_location_id`),
  KEY `inventory_stock_adjustments_conducted_by_id_foreign` (`conducted_by_id`),
  CONSTRAINT `inventory_stock_adjustments_conducted_by_id_foreign` FOREIGN KEY (`conducted_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_stock_adjustments_inventory_location_id_foreign` FOREIGN KEY (`inventory_location_id`) REFERENCES `inventory_locations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_stock_adjustments_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_stock_movements`
--

DROP TABLE IF EXISTS `inventory_stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `inventory_location_id` bigint(20) unsigned NOT NULL,
  `inventory_batch_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `performed_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_stock_movements_school_id_foreign` (`school_id`),
  KEY `inventory_stock_movements_inventory_item_id_foreign` (`inventory_item_id`),
  KEY `inventory_stock_movements_inventory_location_id_foreign` (`inventory_location_id`),
  KEY `inventory_stock_movements_inventory_batch_id_foreign` (`inventory_batch_id`),
  KEY `inventory_stock_movements_performed_by_id_foreign` (`performed_by_id`),
  CONSTRAINT `inventory_stock_movements_inventory_batch_id_foreign` FOREIGN KEY (`inventory_batch_id`) REFERENCES `inventory_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_stock_movements_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_stock_movements_inventory_location_id_foreign` FOREIGN KEY (`inventory_location_id`) REFERENCES `inventory_locations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_stock_movements_performed_by_id_foreign` FOREIGN KEY (`performed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_stock_movements_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_suppliers`
--

DROP TABLE IF EXISTS `inventory_suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `physical_address` text DEFAULT NULL,
  `tax_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_sup_name` (`school_id`,`name`),
  CONSTRAINT `inventory_suppliers_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_transactions`
--

DROP TABLE IF EXISTS `inventory_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `transaction_type` enum('purchase','issue','return','adjustment','transfer','write_off','audit') NOT NULL,
  `quantity` int(11) NOT NULL,
  `balance_after` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_transactions_school_id_foreign` (`school_id`),
  KEY `inventory_transactions_inventory_item_id_foreign` (`inventory_item_id`),
  KEY `inventory_transactions_user_id_foreign` (`user_id`),
  CONSTRAINT `inventory_transactions_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_transactions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `fee_structure_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_foreign` (`invoice_id`),
  KEY `invoice_items_fee_structure_id_foreign` (`fee_structure_id`),
  CONSTRAINT `invoice_items_fee_structure_id_foreign` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `academic_year_id` bigint(20) unsigned DEFAULT NULL,
  `term_id` bigint(20) unsigned DEFAULT NULL,
  `fee_waiver_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_number` varchar(255) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `subtotal_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `waiver_details` varchar(255) DEFAULT NULL,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `balance_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'unpaid',
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `due_date` date DEFAULT NULL,
  `integrity_hash` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  KEY `invoices_student_id_foreign` (`student_id`),
  KEY `invoices_academic_year_id_foreign` (`academic_year_id`),
  KEY `invoices_term_id_foreign` (`term_id`),
  KEY `invoices_fee_waiver_id_foreign` (`fee_waiver_id`),
  KEY `invoices_school_id_student_id_status_index` (`school_id`,`student_id`,`status`),
  CONSTRAINT `invoices_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_fee_waiver_id_foreign` FOREIGN KEY (`fee_waiver_id`) REFERENCES `fee_waivers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `journal_entries`
--

DROP TABLE IF EXISTS `journal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journal_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `entry_date` date NOT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `narration` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'posted',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `journal_entries_school_id_foreign` (`school_id`),
  KEY `journal_entries_user_id_foreign` (`user_id`),
  CONSTRAINT `journal_entries_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `journal_entries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `journal_line_items`
--

DROP TABLE IF EXISTS `journal_line_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journal_line_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `journal_entry_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `memo` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `journal_line_items_school_id_foreign` (`school_id`),
  KEY `journal_line_items_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `journal_line_items_account_id_foreign` (`account_id`),
  CONSTRAINT `journal_line_items_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE NO ACTION,
  CONSTRAINT `journal_line_items_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `journal_line_items_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `knowledge_asset_author`
--

DROP TABLE IF EXISTS `knowledge_asset_author`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `knowledge_asset_author` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `knowledge_asset_id` bigint(20) unsigned NOT NULL,
  `library_author_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kno_as_author_pivot` (`knowledge_asset_id`,`library_author_id`),
  KEY `knowledge_asset_author_library_author_id_foreign` (`library_author_id`),
  CONSTRAINT `knowledge_asset_author_knowledge_asset_id_foreign` FOREIGN KEY (`knowledge_asset_id`) REFERENCES `knowledge_assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `knowledge_asset_author_library_author_id_foreign` FOREIGN KEY (`library_author_id`) REFERENCES `library_authors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `knowledge_asset_copies`
--

DROP TABLE IF EXISTS `knowledge_asset_copies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `knowledge_asset_copies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `knowledge_asset_id` bigint(20) unsigned NOT NULL,
  `barcode` varchar(255) NOT NULL,
  `qr_code` varchar(255) NOT NULL,
  `shelf` varchar(255) DEFAULT NULL,
  `rack` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `condition` varchar(255) NOT NULL DEFAULT 'excellent',
  `status` varchar(255) NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kno_cop_barcode` (`school_id`,`barcode`),
  KEY `knowledge_asset_copies_knowledge_asset_id_foreign` (`knowledge_asset_id`),
  CONSTRAINT `knowledge_asset_copies_knowledge_asset_id_foreign` FOREIGN KEY (`knowledge_asset_id`) REFERENCES `knowledge_assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `knowledge_asset_copies_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `knowledge_assets`
--

DROP TABLE IF EXISTS `knowledge_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `knowledge_assets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `uploaded_by_id` bigint(20) unsigned NOT NULL,
  `approved_by_id` bigint(20) unsigned DEFAULT NULL,
  `library_category_id` bigint(20) unsigned NOT NULL,
  `knowledge_format_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `subtype` varchar(255) DEFAULT NULL,
  `abstract_description` text DEFAULT NULL,
  `visibility` varchar(255) NOT NULL DEFAULT 'library_only',
  `isbn` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_year` varchar(4) DEFAULT NULL,
  `language` varchar(100) NOT NULL DEFAULT 'English',
  `media_type` varchar(255) NOT NULL DEFAULT 'physical',
  `file_path` varchar(255) DEFAULT NULL,
  `external_url` varchar(255) DEFAULT NULL,
  `cover_image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `knowledge_assets_uploaded_by_id_foreign` (`uploaded_by_id`),
  KEY `knowledge_assets_approved_by_id_foreign` (`approved_by_id`),
  KEY `knowledge_assets_library_category_id_foreign` (`library_category_id`),
  KEY `knowledge_assets_knowledge_format_id_foreign` (`knowledge_format_id`),
  KEY `idx_kno_as_lookup` (`school_id`,`media_type`),
  CONSTRAINT `knowledge_assets_approved_by_id_foreign` FOREIGN KEY (`approved_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `knowledge_assets_knowledge_format_id_foreign` FOREIGN KEY (`knowledge_format_id`) REFERENCES `knowledge_formats` (`id`) ON DELETE SET NULL,
  CONSTRAINT `knowledge_assets_library_category_id_foreign` FOREIGN KEY (`library_category_id`) REFERENCES `library_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `knowledge_assets_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `knowledge_assets_uploaded_by_id_foreign` FOREIGN KEY (`uploaded_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `knowledge_formats`
--

DROP TABLE IF EXISTS `knowledge_formats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `knowledge_formats` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `media_type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kno_fmt_school_name` (`school_id`,`name`,`media_type`),
  CONSTRAINT `knowledge_formats_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `leaderboard_snapshots`
--

DROP TABLE IF EXISTS `leaderboard_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leaderboard_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `snapshot_date` date NOT NULL,
  `snapshot_type` varchar(20) NOT NULL DEFAULT 'weekly',
  `scope_type` varchar(20) NOT NULL,
  `scope_id` bigint(20) unsigned DEFAULT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `score` int(11) NOT NULL DEFAULT 0,
  `rank_position` smallint(5) unsigned NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `leaderboard_snapshots_student_id_foreign` (`student_id`),
  KEY `idx_lbs_school_date_scope` (`school_id`,`snapshot_date`,`scope_type`,`scope_id`),
  KEY `idx_lbs_school_date_stud` (`school_id`,`snapshot_date`,`student_id`),
  CONSTRAINT `leaderboard_snapshots_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leaderboard_snapshots_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `learner_achievements`
--

DROP TABLE IF EXISTS `learner_achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `learner_achievements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `gamification_achievement_id` bigint(20) unsigned NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_lach_student_ach` (`student_id`,`gamification_achievement_id`),
  KEY `learner_achievements_gamification_achievement_id_foreign` (`gamification_achievement_id`),
  KEY `idx_lach_school_student` (`school_id`,`student_id`),
  CONSTRAINT `learner_achievements_gamification_achievement_id_foreign` FOREIGN KEY (`gamification_achievement_id`) REFERENCES `gamification_achievements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learner_achievements_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learner_achievements_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `learner_badges`
--

DROP TABLE IF EXISTS `learner_badges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `learner_badges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `gamification_badge_id` bigint(20) unsigned NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_lbg_student_badge` (`student_id`,`gamification_badge_id`),
  KEY `learner_badges_gamification_badge_id_foreign` (`gamification_badge_id`),
  KEY `idx_lbg_school_student` (`school_id`,`student_id`),
  CONSTRAINT `learner_badges_gamification_badge_id_foreign` FOREIGN KEY (`gamification_badge_id`) REFERENCES `gamification_badges` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learner_badges_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learner_badges_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `learner_mastery`
--

DROP TABLE IF EXISTS `learner_mastery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `learner_mastery` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `topic` varchar(255) NOT NULL,
  `subtopic` varchar(255) DEFAULT NULL,
  `mastery_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `mastery_label` varchar(20) NOT NULL DEFAULT 'beginning',
  `total_assessments` int(10) unsigned NOT NULL DEFAULT 0,
  `correct_responses` int(10) unsigned NOT NULL DEFAULT 0,
  `total_responses` int(10) unsigned NOT NULL DEFAULT 0,
  `last_assessed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_lm_enroll_subj_topic` (`enrollment_id`,`subject_id`,`topic`,`subtopic`),
  KEY `learner_mastery_subject_id_foreign` (`subject_id`),
  KEY `idx_lm_school_stud_subj` (`school_id`,`student_id`,`subject_id`),
  KEY `idx_lm_student_label` (`student_id`,`mastery_label`),
  CONSTRAINT `learner_mastery_enrollment_id_foreign` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learner_mastery_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learner_mastery_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learner_mastery_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `learner_streaks`
--

DROP TABLE IF EXISTS `learner_streaks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `learner_streaks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `current_streak` smallint(5) unsigned NOT NULL DEFAULT 0,
  `longest_streak` smallint(5) unsigned NOT NULL DEFAULT 0,
  `last_activity_date` date DEFAULT NULL,
  `streak_start_date` date DEFAULT NULL,
  `total_active_days` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_lstreak_school_student` (`school_id`,`student_id`),
  KEY `learner_streaks_student_id_foreign` (`student_id`),
  KEY `idx_lstreak_school_streak` (`school_id`,`current_streak`),
  CONSTRAINT `learner_streaks_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learner_streaks_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `learner_xp`
--

DROP TABLE IF EXISTS `learner_xp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `learner_xp` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `total_xp` int(10) unsigned NOT NULL DEFAULT 0,
  `current_level` smallint(5) unsigned NOT NULL DEFAULT 1,
  `current_level_name` varchar(50) NOT NULL DEFAULT 'Explorer',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_lxp_school_student` (`school_id`,`student_id`),
  KEY `learner_xp_student_id_foreign` (`student_id`),
  KEY `idx_lxp_school_xp` (`school_id`,`total_xp`),
  CONSTRAINT `learner_xp_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learner_xp_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `leave_type_id` bigint(20) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `supporting_document_path` varchar(255) DEFAULT NULL,
  `hr_remarks` text DEFAULT NULL,
  `approved_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_requests_employee_id_foreign` (`employee_id`),
  KEY `leave_requests_leave_type_id_foreign` (`leave_type_id`),
  KEY `leave_requests_approved_by_id_foreign` (`approved_by_id`),
  KEY `leave_requests_school_id_employee_id_status_index` (`school_id`,`employee_id`,`status`),
  CONSTRAINT `leave_requests_approved_by_id_foreign` FOREIGN KEY (`approved_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(100) NOT NULL,
  `days_per_year` int(11) NOT NULL DEFAULT 21,
  `carry_forward` tinyint(1) NOT NULL DEFAULT 0,
  `max_accumulation` int(11) NOT NULL DEFAULT 30,
  `probation_restricted_days` int(11) NOT NULL DEFAULT 0,
  `gender_restriction` varchar(50) DEFAULT NULL,
  `service_length_months_required` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_leave_type_code` (`school_id`,`code`),
  CONSTRAINT `leave_types_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `library_authors`
--

DROP TABLE IF EXISTS `library_authors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_authors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_library_authors_school_name` (`school_id`,`name`),
  CONSTRAINT `library_authors_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `library_book_author`
--

DROP TABLE IF EXISTS `library_book_author`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_book_author` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `library_book_id` bigint(20) unsigned NOT NULL,
  `library_author_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_book_author_pivot` (`library_book_id`,`library_author_id`),
  KEY `library_book_author_library_author_id_foreign` (`library_author_id`),
  CONSTRAINT `library_book_author_library_author_id_foreign` FOREIGN KEY (`library_author_id`) REFERENCES `library_authors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `library_book_author_library_book_id_foreign` FOREIGN KEY (`library_book_id`) REFERENCES `library_books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `library_book_copies`
--

DROP TABLE IF EXISTS `library_book_copies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_book_copies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `library_book_id` bigint(20) unsigned NOT NULL,
  `barcode` varchar(255) NOT NULL,
  `qr_code` varchar(255) NOT NULL,
  `shelf` varchar(255) DEFAULT NULL,
  `rack` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `condition` enum('excellent','good','fair','poor','damaged') NOT NULL DEFAULT 'excellent',
  `status` enum('available','issued','reserved','maintenance','lost','written_off') NOT NULL DEFAULT 'available',
  `purchase_cost` decimal(10,2) DEFAULT NULL,
  `replacement_cost` decimal(10,2) DEFAULT NULL,
  `acquired_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_library_book_copies_barcode` (`school_id`,`barcode`),
  UNIQUE KEY `uq_library_book_copies_qr` (`school_id`,`qr_code`),
  KEY `library_book_copies_library_book_id_foreign` (`library_book_id`),
  CONSTRAINT `library_book_copies_library_book_id_foreign` FOREIGN KEY (`library_book_id`) REFERENCES `library_books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `library_book_copies_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `library_books`
--

DROP TABLE IF EXISTS `library_books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_books` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `library_category_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_year` varchar(4) DEFAULT NULL,
  `isbn` varchar(255) DEFAULT NULL,
  `language` varchar(100) NOT NULL DEFAULT 'English',
  `subject` varchar(255) DEFAULT NULL,
  `grade_level` varchar(255) DEFAULT NULL,
  `cover_image_path` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `abstract_description` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `library_format_id` bigint(20) unsigned DEFAULT NULL,
  `media_type` varchar(255) NOT NULL DEFAULT 'physical',
  `external_url` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `library_books_library_category_id_foreign` (`library_category_id`),
  KEY `idx_library_books_isbn` (`school_id`,`isbn`),
  KEY `idx_library_books_title` (`school_id`,`title`),
  KEY `library_books_library_format_id_foreign` (`library_format_id`),
  CONSTRAINT `library_books_library_category_id_foreign` FOREIGN KEY (`library_category_id`) REFERENCES `library_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `library_books_library_format_id_foreign` FOREIGN KEY (`library_format_id`) REFERENCES `library_formats` (`id`) ON DELETE SET NULL,
  CONSTRAINT `library_books_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `library_categories`
--

DROP TABLE IF EXISTS `library_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_library_categories_hierarchy` (`school_id`,`name`,`parent_id`),
  KEY `library_categories_parent_id_foreign` (`parent_id`),
  CONSTRAINT `library_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `library_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `library_categories_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `library_formats`
--

DROP TABLE IF EXISTS `library_formats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_formats` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `media_type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lib_fmt_school_name` (`school_id`,`name`,`media_type`),
  CONSTRAINT `library_formats_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `library_issues`
--

DROP TABLE IF EXISTS `library_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_issues` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `library_book_copy_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `issued_by_id` bigint(20) unsigned NOT NULL,
  `issued_at` date NOT NULL,
  `due_at` date NOT NULL,
  `returned_at` date DEFAULT NULL,
  `status` enum('issued','returned','overdue','lost','damaged') NOT NULL DEFAULT 'issued',
  `fine_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fine_status` enum('unpaid','paid','waived') NOT NULL DEFAULT 'unpaid',
  `renewals_count` int(11) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `library_issues_library_book_copy_id_foreign` (`library_book_copy_id`),
  KEY `library_issues_student_id_foreign` (`student_id`),
  KEY `library_issues_user_id_foreign` (`user_id`),
  KEY `library_issues_issued_by_id_foreign` (`issued_by_id`),
  KEY `idx_library_issues_status` (`school_id`,`status`),
  CONSTRAINT `library_issues_issued_by_id_foreign` FOREIGN KEY (`issued_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `library_issues_library_book_copy_id_foreign` FOREIGN KEY (`library_book_copy_id`) REFERENCES `library_book_copies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `library_issues_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `library_issues_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `library_issues_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mark_correction_requests`
--

DROP TABLE IF EXISTS `mark_correction_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mark_correction_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `assessment_marks_ledger_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `old_mark` decimal(5,2) DEFAULT NULL,
  `new_mark` decimal(5,2) NOT NULL,
  `old_status` varchar(255) DEFAULT NULL,
  `new_status` varchar(255) DEFAULT NULL,
  `reason_for_change` text NOT NULL,
  `approval_status` varchar(255) NOT NULL DEFAULT 'pending',
  `approved_by_id` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mark_correction_requests_school_id_foreign` (`school_id`),
  KEY `mark_correction_requests_assessment_marks_ledger_id_foreign` (`assessment_marks_ledger_id`),
  KEY `mark_correction_requests_teacher_id_foreign` (`teacher_id`),
  KEY `mark_correction_requests_approved_by_id_foreign` (`approved_by_id`),
  CONSTRAINT `mark_correction_requests_approved_by_id_foreign` FOREIGN KEY (`approved_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mark_correction_requests_assessment_marks_ledger_id_foreign` FOREIGN KEY (`assessment_marks_ledger_id`) REFERENCES `assessment_marks_ledger` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mark_correction_requests_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mark_correction_requests_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mark_records`
--

DROP TABLE IF EXISTS `mark_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mark_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `subject_paper_id` bigint(20) unsigned NOT NULL,
  `bot_mark` decimal(5,2) DEFAULT NULL,
  `mot_mark` decimal(5,2) DEFAULT NULL,
  `eot_mark` decimal(5,2) DEFAULT NULL,
  `c1_mark` decimal(5,2) DEFAULT NULL,
  `c2_mark` decimal(5,2) DEFAULT NULL,
  `c3_mark` decimal(5,2) DEFAULT NULL,
  `teacher_initials` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_paper_mark` (`enrollment_id`,`subject_paper_id`),
  KEY `mark_records_school_id_foreign` (`school_id`),
  KEY `mark_records_subject_id_foreign` (`subject_id`),
  KEY `mark_records_subject_paper_id_foreign` (`subject_paper_id`),
  CONSTRAINT `mark_records_enrollment_id_foreign` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mark_records_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mark_records_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mark_records_subject_paper_id_foreign` FOREIGN KEY (`subject_paper_id`) REFERENCES `subject_papers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `cleared_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`),
  KEY `idx_notifications_notifiable_read` (`notifiable_type`,`notifiable_id`,`read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payment_plans`
--

DROP TABLE IF EXISTS `payment_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `proposed_installments_count` decimal(3,0) NOT NULL,
  `installment_amount` decimal(15,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `parent_notes` text DEFAULT NULL,
  `admin_feedback` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_plans_school_id_foreign` (`school_id`),
  KEY `payment_plans_student_id_foreign` (`student_id`),
  KEY `payment_plans_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `payment_plans_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_plans_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_plans_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `receipt_number` varchar(255) DEFAULT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `payment_method` varchar(50) NOT NULL DEFAULT 'cash',
  `is_reversed` tinyint(1) NOT NULL DEFAULT 0,
  `reversal_reason` varchar(255) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reversed_by_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_invoice_id_foreign` (`invoice_id`),
  KEY `payments_school_id_invoice_id_index` (`school_id`,`invoice_id`),
  KEY `payments_reversed_by_id_foreign` (`reversed_by_id`),
  CONSTRAINT `payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_reversed_by_id_foreign` FOREIGN KEY (`reversed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payroll_periods`
--

DROP TABLE IF EXISTS `payroll_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_periods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payroll_period_dates` (`school_id`,`start_date`,`end_date`),
  CONSTRAINT `payroll_periods_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payroll_runs`
--

DROP TABLE IF EXISTS `payroll_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `payroll_period_id` bigint(20) unsigned NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'calculated',
  `calculated_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `gross_total` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `deductions_total` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `net_total` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_runs_school_id_foreign` (`school_id`),
  KEY `payroll_runs_payroll_period_id_foreign` (`payroll_period_id`),
  CONSTRAINT `payroll_runs_payroll_period_id_foreign` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_runs_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payslip_items`
--

DROP TABLE IF EXISTS `payslip_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payslip_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `payslip_id` bigint(20) unsigned NOT NULL,
  `code` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `amount` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `is_taxable` tinyint(1) NOT NULL DEFAULT 1,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payslip_items_school_id_foreign` (`school_id`),
  KEY `payslip_items_payslip_id_foreign` (`payslip_id`),
  CONSTRAINT `payslip_items_payslip_id_foreign` FOREIGN KEY (`payslip_id`) REFERENCES `payslips` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payslip_items_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payslips`
--

DROP TABLE IF EXISTS `payslips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payslips` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `payroll_run_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `base_salary` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `gross_pay` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `total_deductions` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `net_pay` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `status` varchar(50) NOT NULL DEFAULT 'calculated',
  `payment_method` varchar(50) NOT NULL DEFAULT 'Bank Transfer',
  `payment_date` date DEFAULT NULL,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `integrity_hash` varchar(255) DEFAULT NULL,
  `qr_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payslip_unique_run_emp` (`school_id`,`payroll_run_id`,`employee_id`),
  KEY `payslips_payroll_run_id_foreign` (`payroll_run_id`),
  KEY `payslips_employee_id_foreign` (`employee_id`),
  CONSTRAINT `payslips_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payslips_payroll_run_id_foreign` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payslips_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `platform_announcements`
--

DROP TABLE IF EXISTS `platform_announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_announcements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'info',
  `target_plans` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_plans`)),
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `platform_audit_logs`
--

DROP TABLE IF EXISTS `platform_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `platform_audit_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `platform_audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `platform_backups`
--

DROP TABLE IF EXISTS `platform_backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_backups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `size_bytes` bigint(20) NOT NULL DEFAULT 0,
  `checksum` varchar(255) DEFAULT NULL,
  `disk` varchar(255) NOT NULL DEFAULT 'local',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'completed',
  `error_log` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `platform_message_recipients`
--

DROP TABLE IF EXISTS `platform_message_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_message_recipients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint(20) unsigned NOT NULL,
  `school_id` bigint(20) unsigned NOT NULL,
  `status` enum('sent','delivered','read') NOT NULL DEFAULT 'sent',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_message_recipients_message_id_school_id_unique` (`message_id`,`school_id`),
  KEY `platform_message_recipients_school_id_status_index` (`school_id`,`status`),
  CONSTRAINT `platform_message_recipients_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `platform_messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `platform_message_recipients_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `platform_messages`
--

DROP TABLE IF EXISTS `platform_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `thread_id` varchar(255) NOT NULL,
  `sender_type` enum('platform','school') NOT NULL DEFAULT 'school',
  `sender_user_id` bigint(20) unsigned DEFAULT NULL,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `recipient_type` enum('platform','school') NOT NULL DEFAULT 'school',
  `recipient_scope` enum('all','selected','single') NOT NULL DEFAULT 'single',
  `target_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_meta`)),
  `subject` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `priority` enum('info','normal','important') NOT NULL DEFAULT 'normal',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_messages_uuid_unique` (`uuid`),
  KEY `platform_messages_sender_user_id_foreign` (`sender_user_id`),
  KEY `platform_messages_school_id_foreign` (`school_id`),
  KEY `platform_messages_thread_id_index` (`thread_id`),
  CONSTRAINT `platform_messages_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `platform_messages_sender_user_id_foreign` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `platform_restore_logs`
--

DROP TABLE IF EXISTS `platform_restore_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_restore_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `backup_id` bigint(20) unsigned NOT NULL,
  `performed_by_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `error_details` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `platform_restore_logs_backup_id_foreign` (`backup_id`),
  KEY `platform_restore_logs_performed_by_id_foreign` (`performed_by_id`),
  CONSTRAINT `platform_restore_logs_backup_id_foreign` FOREIGN KEY (`backup_id`) REFERENCES `platform_backups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `platform_restore_logs_performed_by_id_foreign` FOREIGN KEY (`performed_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `platform_settings`
--

DROP TABLE IF EXISTS `platform_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(100) NOT NULL,
  `key` varchar(150) NOT NULL,
  `value` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_settings_group_key_unique` (`group`,`key`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `platform_templates`
--

DROP TABLE IF EXISTS `platform_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `preview_image` varchar(255) DEFAULT NULL,
  `configuration_blueprint` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`configuration_blueprint`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `procurement_order_items`
--

DROP TABLE IF EXISTS `procurement_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `procurement_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `procurement_order_id` bigint(20) unsigned NOT NULL,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `quantity_ordered` int(11) NOT NULL,
  `quantity_received` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `procurement_order_items_procurement_order_id_foreign` (`procurement_order_id`),
  KEY `procurement_order_items_inventory_item_id_foreign` (`inventory_item_id`),
  CONSTRAINT `procurement_order_items_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `procurement_order_items_procurement_order_id_foreign` FOREIGN KEY (`procurement_order_id`) REFERENCES `procurement_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `procurement_orders`
--

DROP TABLE IF EXISTS `procurement_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `procurement_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `procurement_request_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `order_number` varchar(255) NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_proc_ord_num` (`school_id`,`order_number`),
  KEY `procurement_orders_procurement_request_id_foreign` (`procurement_request_id`),
  KEY `procurement_orders_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `procurement_orders_procurement_request_id_foreign` FOREIGN KEY (`procurement_request_id`) REFERENCES `procurement_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procurement_orders_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `procurement_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `inventory_suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `procurement_request_items`
--

DROP TABLE IF EXISTS `procurement_request_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `procurement_request_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `procurement_request_id` bigint(20) unsigned NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `inventory_item_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `estimated_unit_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `specifications` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `procurement_request_items_procurement_request_id_foreign` (`procurement_request_id`),
  KEY `procurement_request_items_inventory_item_id_foreign` (`inventory_item_id`),
  CONSTRAINT `procurement_request_items_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procurement_request_items_procurement_request_id_foreign` FOREIGN KEY (`procurement_request_id`) REFERENCES `procurement_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `procurement_requests`
--

DROP TABLE IF EXISTS `procurement_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `procurement_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `request_number` varchar(255) NOT NULL,
  `requester_id` bigint(20) unsigned NOT NULL,
  `department_id` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `urgency` varchar(255) NOT NULL DEFAULT 'medium',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_proc_req_num` (`school_id`,`request_number`),
  KEY `procurement_requests_requester_id_foreign` (`requester_id`),
  CONSTRAINT `procurement_requests_requester_id_foreign` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `procurement_requests_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchase_requests`
--

DROP TABLE IF EXISTS `purchase_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `estimated_amount` decimal(15,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `requested_by_id` bigint(20) unsigned DEFAULT NULL,
  `approved_by_id` bigint(20) unsigned DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_requests_school_id_foreign` (`school_id`),
  KEY `purchase_requests_supplier_id_foreign` (`supplier_id`),
  KEY `purchase_requests_requested_by_id_foreign` (`requested_by_id`),
  KEY `purchase_requests_approved_by_id_foreign` (`approved_by_id`),
  CONSTRAINT `purchase_requests_approved_by_id_foreign` FOREIGN KEY (`approved_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_requests_requested_by_id_foreign` FOREIGN KEY (`requested_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_requests_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_requests_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `question_analytics`
--

DROP TABLE IF EXISTS `question_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `question_analytics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `question_bank_id` bigint(20) unsigned NOT NULL,
  `total_attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `correct_count` int(10) unsigned NOT NULL DEFAULT 0,
  `incorrect_count` int(10) unsigned NOT NULL DEFAULT 0,
  `skipped_count` int(10) unsigned NOT NULL DEFAULT 0,
  `percentage_correct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `average_response_time_seconds` int(10) unsigned NOT NULL DEFAULT 0,
  `average_confidence` decimal(3,2) NOT NULL DEFAULT 0.00,
  `last_calculated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_qa_school_qb` (`school_id`,`question_bank_id`),
  KEY `question_analytics_question_bank_id_foreign` (`question_bank_id`),
  CONSTRAINT `question_analytics_question_bank_id_foreign` FOREIGN KEY (`question_bank_id`) REFERENCES `question_bank` (`id`) ON DELETE CASCADE,
  CONSTRAINT `question_analytics_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `question_bank`
--

DROP TABLE IF EXISTS `question_bank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `question_bank` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `created_by_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `question_type` varchar(30) NOT NULL,
  `question_text` text NOT NULL,
  `question_html` longtext DEFAULT NULL,
  `explanation` text DEFAULT NULL,
  `explanation_html` longtext DEFAULT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `correct_answer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`correct_answer`)),
  `manual_marking` tinyint(1) NOT NULL DEFAULT 0,
  `matching_pairs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`matching_pairs`)),
  `ordering_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ordering_items`)),
  `fill_blank_answer` varchar(255) DEFAULT NULL,
  `short_answer` text DEFAULT NULL,
  `numeric_answer` decimal(12,4) DEFAULT NULL,
  `marks` decimal(5,2) NOT NULL DEFAULT 1.00,
  `difficulty` varchar(20) NOT NULL DEFAULT 'intermediate',
  `topic` varchar(255) DEFAULT NULL,
  `subtopic` varchar(255) DEFAULT NULL,
  `learning_objective` varchar(255) DEFAULT NULL,
  `competency` varchar(255) DEFAULT NULL,
  `curriculum_reference` varchar(255) DEFAULT NULL,
  `grade_level` varchar(255) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `usage_count` int(10) unsigned NOT NULL DEFAULT 0,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `question_bank_subject_id_foreign` (`subject_id`),
  KEY `question_bank_created_by_id_foreign` (`created_by_id`),
  KEY `idx_qb_school_subj_status` (`school_id`,`subject_id`,`status`),
  KEY `idx_qb_school_topic` (`school_id`,`topic`),
  KEY `idx_qb_school_diff` (`school_id`,`difficulty`),
  KEY `idx_qb_school_qtype` (`school_id`,`question_type`),
  CONSTRAINT `question_bank_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `question_bank_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `question_bank_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `report_dashboard_widgets`
--

DROP TABLE IF EXISTS `report_dashboard_widgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_dashboard_widgets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `report_dashboard_id` bigint(20) unsigned NOT NULL,
  `enterprise_report_template_id` bigint(20) unsigned DEFAULT NULL,
  `widget_type` varchar(30) NOT NULL DEFAULT 'chart',
  `title` varchar(255) DEFAULT NULL,
  `viz_type` varchar(30) DEFAULT NULL,
  `dataset_key` varchar(80) DEFAULT NULL,
  `field_key` varchar(120) DEFAULT NULL,
  `aggregate` varchar(20) DEFAULT NULL,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `position` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{"x":0,"y":0,"w":4,"h":4}' CHECK (json_valid(`position`)),
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_dashboard_widgets_school_id_foreign` (`school_id`),
  KEY `fk_widget_dashboard_id` (`report_dashboard_id`),
  KEY `fk_widget_report_id` (`enterprise_report_template_id`),
  CONSTRAINT `fk_widget_dashboard_id` FOREIGN KEY (`report_dashboard_id`) REFERENCES `report_dashboards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_widget_report_id` FOREIGN KEY (`enterprise_report_template_id`) REFERENCES `enterprise_report_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `report_dashboard_widgets_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `report_dashboards`
--

DROP TABLE IF EXISTS `report_dashboards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_dashboards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `layout_grid` varchar(20) NOT NULL DEFAULT '12',
  `created_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_report_dashboards_school_name` (`school_id`,`name`),
  KEY `report_dashboards_created_by_id_foreign` (`created_by_id`),
  CONSTRAINT `report_dashboards_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `report_dashboards_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `report_template_versions`
--

DROP TABLE IF EXISTS `report_template_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_template_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `enterprise_report_template_id` bigint(20) unsigned NOT NULL,
  `version` int(10) unsigned NOT NULL,
  `snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`snapshot`)),
  `created_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rpt_ver_template_version` (`enterprise_report_template_id`,`version`),
  KEY `report_template_versions_school_id_foreign` (`school_id`),
  CONSTRAINT `fk_rpt_ver_template_id` FOREIGN KEY (`enterprise_report_template_id`) REFERENCES `enterprise_report_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `report_template_versions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `report_templates`
--

DROP TABLE IF EXISTS `report_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `design_theme` varchar(255) NOT NULL DEFAULT 'classic_line',
  `target_level` varchar(255) NOT NULL DEFAULT 'primary',
  `scope_type` varchar(255) NOT NULL DEFAULT 'level',
  `course_id` bigint(20) unsigned DEFAULT NULL,
  `section_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `layout_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`layout_config`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_templates_school_id_foreign` (`school_id`),
  KEY `report_templates_course_id_foreign` (`course_id`),
  KEY `report_templates_section_id_foreign` (`section_id`),
  CONSTRAINT `report_templates_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `report_templates_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `report_templates_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `revenue_categories`
--

DROP TABLE IF EXISTS `revenue_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `revenue_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `revenue_categories_school_id_foreign` (`school_id`),
  KEY `revenue_categories_account_id_foreign` (`account_id`),
  CONSTRAINT `revenue_categories_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `revenue_categories_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `revenue_streams`
--

DROP TABLE IF EXISTS `revenue_streams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `revenue_streams` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `revenue_category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `default_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `revenue_streams_school_id_foreign` (`school_id`),
  KEY `revenue_streams_revenue_category_id_foreign` (`revenue_category_id`),
  KEY `revenue_streams_account_id_foreign` (`account_id`),
  CONSTRAINT `revenue_streams_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `revenue_streams_revenue_category_id_foreign` FOREIGN KEY (`revenue_category_id`) REFERENCES `revenue_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `revenue_streams_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_school_id_name_guard_name_unique` (`school_id`,`name`,`guard_name`),
  CONSTRAINT `roles_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `saas_audit_logs`
--

DROP TABLE IF EXISTS `saas_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `performed_by_id` bigint(20) unsigned DEFAULT NULL,
  `event_type` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `payload_before` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload_before`)),
  `payload_after` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload_after`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `saas_audit_logs_school_id_foreign` (`school_id`),
  KEY `saas_audit_logs_performed_by_id_foreign` (`performed_by_id`),
  KEY `saas_audit_logs_event_type_index` (`event_type`),
  CONSTRAINT `saas_audit_logs_performed_by_id_foreign` FOREIGN KEY (`performed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `saas_audit_logs_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `saas_billing_addresses`
--

DROP TABLE IF EXISTS `saas_billing_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_billing_addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `vat_number` varchar(255) DEFAULT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `address_line_1` varchar(255) DEFAULT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state_province` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL DEFAULT 'ZW',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `saas_billing_addresses_school_id_foreign` (`school_id`),
  CONSTRAINT `saas_billing_addresses_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `saas_billing_settings`
--

DROP TABLE IF EXISTS `saas_billing_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_billing_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(255) NOT NULL,
  `bank_account_name` varchar(255) NOT NULL,
  `bank_account_number` varchar(255) NOT NULL,
  `bank_branch_code` varchar(255) DEFAULT NULL,
  `bank_swift_code` varchar(255) DEFAULT NULL,
  `paynow_integration_id` varchar(255) DEFAULT NULL,
  `paynow_integration_key` varchar(255) DEFAULT NULL,
  `support_email` varchar(255) NOT NULL DEFAULT 'billing@schoolcore.test',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `saas_invoice_items`
--

DROP TABLE IF EXISTS `saas_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `saas_invoice_id` bigint(20) unsigned NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `saas_invoice_items_saas_invoice_id_foreign` (`saas_invoice_id`),
  CONSTRAINT `saas_invoice_items_saas_invoice_id_foreign` FOREIGN KEY (`saas_invoice_id`) REFERENCES `saas_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `saas_invoices`
--

DROP TABLE IF EXISTS `saas_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `school_id` bigint(20) unsigned NOT NULL,
  `saas_subscription_id` bigint(20) unsigned NOT NULL,
  `invoice_number` varchar(255) NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `status` varchar(255) NOT NULL DEFAULT 'unpaid',
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `payment_instructions` text DEFAULT NULL,
  `integrity_hash` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saas_invoices_uuid_unique` (`uuid`),
  UNIQUE KEY `saas_invoices_invoice_number_unique` (`invoice_number`),
  KEY `saas_invoices_school_id_foreign` (`school_id`),
  KEY `saas_invoices_saas_subscription_id_foreign` (`saas_subscription_id`),
  CONSTRAINT `saas_invoices_saas_subscription_id_foreign` FOREIGN KEY (`saas_subscription_id`) REFERENCES `saas_subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saas_invoices_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `saas_manual_submissions`
--

DROP TABLE IF EXISTS `saas_manual_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_manual_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `saas_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `reference_number` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `payment_date` date DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `receipt_file_path` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `rejection_reason` varchar(255) DEFAULT NULL,
  `reviewed_by_id` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `saas_manual_submissions_saas_invoice_id_foreign` (`saas_invoice_id`),
  KEY `saas_manual_submissions_reviewed_by_id_foreign` (`reviewed_by_id`),
  KEY `saas_manual_submissions_school_id_status_index` (`school_id`,`status`),
  CONSTRAINT `saas_manual_submissions_reviewed_by_id_foreign` FOREIGN KEY (`reviewed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `saas_manual_submissions_saas_invoice_id_foreign` FOREIGN KEY (`saas_invoice_id`) REFERENCES `saas_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `saas_manual_submissions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `saas_plan_features`
--

DROP TABLE IF EXISTS `saas_plan_features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_plan_features` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `saas_plan_id` bigint(20) unsigned NOT NULL,
  `feature_key` varchar(255) NOT NULL,
  `feature_value` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `saas_plan_features_saas_plan_id_foreign` (`saas_plan_id`),
  CONSTRAINT `saas_plan_features_saas_plan_id_foreign` FOREIGN KEY (`saas_plan_id`) REFERENCES `saas_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `saas_plans`
--

DROP TABLE IF EXISTS `saas_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price_monthly` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `trial_days` int(11) NOT NULL DEFAULT 14,
  `grace_days` int(11) NOT NULL DEFAULT 7,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saas_plans_uuid_unique` (`uuid`),
  UNIQUE KEY `saas_plans_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `saas_receipts`
--

DROP TABLE IF EXISTS `saas_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `school_id` bigint(20) unsigned NOT NULL,
  `saas_invoice_id` bigint(20) unsigned NOT NULL,
  `saas_transaction_id` bigint(20) unsigned NOT NULL,
  `receipt_number` varchar(255) NOT NULL,
  `amount_paid` decimal(12,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verification_token` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saas_receipts_uuid_unique` (`uuid`),
  UNIQUE KEY `saas_receipts_receipt_number_unique` (`receipt_number`),
  UNIQUE KEY `saas_receipts_verification_token_unique` (`verification_token`),
  KEY `saas_receipts_school_id_foreign` (`school_id`),
  KEY `saas_receipts_saas_invoice_id_foreign` (`saas_invoice_id`),
  KEY `saas_receipts_saas_transaction_id_foreign` (`saas_transaction_id`),
  CONSTRAINT `saas_receipts_saas_invoice_id_foreign` FOREIGN KEY (`saas_invoice_id`) REFERENCES `saas_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saas_receipts_saas_transaction_id_foreign` FOREIGN KEY (`saas_transaction_id`) REFERENCES `saas_transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saas_receipts_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `saas_subscription_histories`
--

DROP TABLE IF EXISTS `saas_subscription_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_subscription_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `saas_subscription_id` bigint(20) unsigned NOT NULL,
  `action_type` varchar(255) NOT NULL,
  `old_plan_id` bigint(20) unsigned DEFAULT NULL,
  `new_plan_id` bigint(20) unsigned NOT NULL,
  `change_notes` text DEFAULT NULL,
  `performed_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `saas_subscription_histories_school_id_foreign` (`school_id`),
  KEY `saas_subscription_histories_saas_subscription_id_foreign` (`saas_subscription_id`),
  KEY `saas_subscription_histories_old_plan_id_foreign` (`old_plan_id`),
  KEY `saas_subscription_histories_new_plan_id_foreign` (`new_plan_id`),
  KEY `saas_subscription_histories_performed_by_id_foreign` (`performed_by_id`),
  CONSTRAINT `saas_subscription_histories_new_plan_id_foreign` FOREIGN KEY (`new_plan_id`) REFERENCES `saas_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saas_subscription_histories_old_plan_id_foreign` FOREIGN KEY (`old_plan_id`) REFERENCES `saas_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `saas_subscription_histories_performed_by_id_foreign` FOREIGN KEY (`performed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `saas_subscription_histories_saas_subscription_id_foreign` FOREIGN KEY (`saas_subscription_id`) REFERENCES `saas_subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saas_subscription_histories_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `saas_subscriptions`
--

DROP TABLE IF EXISTS `saas_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `school_id` bigint(20) unsigned NOT NULL,
  `saas_plan_id` bigint(20) unsigned NOT NULL,
  `billing_period` varchar(255) NOT NULL DEFAULT 'monthly',
  `status` varchar(255) NOT NULL DEFAULT 'trialing',
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `grace_ends_at` timestamp NULL DEFAULT NULL,
  `custom_price_monthly` decimal(12,2) DEFAULT NULL,
  `credit_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `next_payment_date` date DEFAULT NULL,
  `last_payment_date` date DEFAULT NULL,
  `auto_deactivate_after_days` int(11) NOT NULL DEFAULT 5,
  `dunning_days_before` int(11) NOT NULL DEFAULT 2,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_school_saas_sub` (`school_id`),
  UNIQUE KEY `saas_subscriptions_uuid_unique` (`uuid`),
  KEY `saas_subscriptions_saas_plan_id_foreign` (`saas_plan_id`),
  KEY `saas_subscriptions_next_payment_date_index` (`next_payment_date`),
  CONSTRAINT `saas_subscriptions_saas_plan_id_foreign` FOREIGN KEY (`saas_plan_id`) REFERENCES `saas_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saas_subscriptions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `saas_transactions`
--

DROP TABLE IF EXISTS `saas_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `school_id` bigint(20) unsigned NOT NULL,
  `saas_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `payment_gateway_key` varchar(255) NOT NULL,
  `transaction_reference` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `processed_at` timestamp NULL DEFAULT NULL,
  `gateway_raw_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_raw_response`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saas_transactions_uuid_unique` (`uuid`),
  UNIQUE KEY `saas_transactions_transaction_reference_unique` (`transaction_reference`),
  KEY `saas_transactions_school_id_foreign` (`school_id`),
  KEY `saas_transactions_saas_invoice_id_foreign` (`saas_invoice_id`),
  KEY `saas_transactions_payment_gateway_key_index` (`payment_gateway_key`),
  CONSTRAINT `saas_transactions_saas_invoice_id_foreign` FOREIGN KEY (`saas_invoice_id`) REFERENCES `saas_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `saas_transactions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `salary_grade_history`
--

DROP TABLE IF EXISTS `salary_grade_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_grade_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `previous_grade_id` bigint(20) unsigned DEFAULT NULL,
  `new_grade_id` bigint(20) unsigned NOT NULL,
  `base_salary` decimal(15,4) NOT NULL,
  `effective_date` date NOT NULL,
  `reason` varchar(255) NOT NULL,
  `approved_by_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `salary_grade_history_school_id_foreign` (`school_id`),
  KEY `salary_grade_history_employee_id_foreign` (`employee_id`),
  KEY `salary_grade_history_previous_grade_id_foreign` (`previous_grade_id`),
  KEY `salary_grade_history_new_grade_id_foreign` (`new_grade_id`),
  KEY `salary_grade_history_approved_by_id_foreign` (`approved_by_id`),
  CONSTRAINT `salary_grade_history_approved_by_id_foreign` FOREIGN KEY (`approved_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `salary_grade_history_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `salary_grade_history_new_grade_id_foreign` FOREIGN KEY (`new_grade_id`) REFERENCES `salary_grades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `salary_grade_history_previous_grade_id_foreign` FOREIGN KEY (`previous_grade_id`) REFERENCES `salary_grades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `salary_grade_history_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `salary_grades`
--

DROP TABLE IF EXISTS `salary_grades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_grades` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `base_salary` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `hourly_rate` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `housing_allowance` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `transport_allowance` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `duty_allowance` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `overtime_eligible` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_salary_grade_name` (`school_id`,`name`),
  CONSTRAINT `salary_grades_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `school_bank_accounts`
--

DROP TABLE IF EXISTS `school_bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_bank_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `branch_code` varchar(255) DEFAULT NULL,
  `swift_code` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_bank_accounts_school_id_foreign` (`school_id`),
  CONSTRAINT `school_bank_accounts_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `school_rollovers`
--

DROP TABLE IF EXISTS `school_rollovers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_rollovers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `from_academic_year_id` bigint(20) unsigned NOT NULL,
  `to_academic_year_id` bigint(20) unsigned NOT NULL,
  `strategy` varchar(255) NOT NULL,
  `students_processed_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_rollovers_school_id_foreign` (`school_id`),
  KEY `school_rollovers_from_academic_year_id_foreign` (`from_academic_year_id`),
  KEY `school_rollovers_to_academic_year_id_foreign` (`to_academic_year_id`),
  CONSTRAINT `school_rollovers_from_academic_year_id_foreign` FOREIGN KEY (`from_academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `school_rollovers_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `school_rollovers_to_academic_year_id_foreign` FOREIGN KEY (`to_academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `school_subscriptions`
--

DROP TABLE IF EXISTS `school_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `saas_plan_id` bigint(20) unsigned NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `storage_limit_gb` bigint(20) unsigned NOT NULL DEFAULT 10,
  `max_users` int(10) unsigned NOT NULL DEFAULT 15,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_subscriptions_school_id_foreign` (`school_id`),
  KEY `school_subscriptions_saas_plan_id_foreign` (`saas_plan_id`),
  CONSTRAINT `school_subscriptions_saas_plan_id_foreign` FOREIGN KEY (`saas_plan_id`) REFERENCES `saas_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `school_subscriptions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `schools`
--

DROP TABLE IF EXISTS `schools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schools` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `country` varchar(255) DEFAULT NULL,
  `motto` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `physical_address` text DEFAULT NULL,
  `language` varchar(255) NOT NULL DEFAULT 'english',
  `institution_type` varchar(255) NOT NULL DEFAULT 'secondary',
  `other_institution_type` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `stamp_path` varchar(255) DEFAULT NULL,
  `subdomain` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `region` varchar(255) NOT NULL DEFAULT 'zimbabwe',
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `has_dummy_data` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `locale` varchar(10) NOT NULL DEFAULT 'en',
  PRIMARY KEY (`id`),
  UNIQUE KEY `schools_subdomain_unique` (`subdomain`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `screening_rules`
--

DROP TABLE IF EXISTS `screening_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `screening_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `source_course_id` bigint(20) unsigned NOT NULL,
  `target_course_id` bigint(20) unsigned NOT NULL,
  `target_section_id` bigint(20) unsigned NOT NULL,
  `rule_type` varchar(255) NOT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `min_percentage` decimal(5,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `screening_rules_school_id_foreign` (`school_id`),
  KEY `screening_rules_source_course_id_foreign` (`source_course_id`),
  KEY `screening_rules_target_course_id_foreign` (`target_course_id`),
  KEY `screening_rules_target_section_id_foreign` (`target_section_id`),
  KEY `screening_rules_subject_id_foreign` (`subject_id`),
  CONSTRAINT `screening_rules_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `screening_rules_source_course_id_foreign` FOREIGN KEY (`source_course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `screening_rules_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `screening_rules_target_course_id_foreign` FOREIGN KEY (`target_course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `screening_rules_target_section_id_foreign` FOREIGN KEY (`target_section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sections`
--

DROP TABLE IF EXISTS `sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 40,
  `workflow_status` varchar(50) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `workflow_completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_school_section_name` (`school_id`,`course_id`,`name`),
  KEY `sections_course_id_foreign` (`course_id`),
  CONSTRAINT `sections_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sections_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `staff_attendances`
--

DROP TABLE IF EXISTS `staff_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_attendances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','half_day','excused') NOT NULL DEFAULT 'present',
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `method` varchar(255) NOT NULL DEFAULT 'manual',
  `marked_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_staff_attendance_day` (`school_id`,`user_id`,`date`),
  KEY `staff_attendances_user_id_foreign` (`user_id`),
  KEY `staff_attendances_marked_by_id_foreign` (`marked_by_id`),
  CONSTRAINT `staff_attendances_marked_by_id_foreign` FOREIGN KEY (`marked_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_attendances_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_attendances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `staff_loans`
--

DROP TABLE IF EXISTS `staff_loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_loans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `loan_type` varchar(100) NOT NULL,
  `principal_amount` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `balance_remaining` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `monthly_deduction` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `approved_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_loans_school_id_foreign` (`school_id`),
  KEY `staff_loans_employee_id_foreign` (`employee_id`),
  KEY `staff_loans_approved_by_id_foreign` (`approved_by_id`),
  CONSTRAINT `staff_loans_approved_by_id_foreign` FOREIGN KEY (`approved_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_loans_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_loans_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_attendances`
--

DROP TABLE IF EXISTS `student_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_attendances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `timetable_lesson_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL DEFAULT 'present',
  `remarks` varchar(255) DEFAULT NULL,
  `marked_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_attendance_lesson` (`school_id`,`student_id`,`timetable_lesson_id`,`date`),
  KEY `student_attendances_student_id_foreign` (`student_id`),
  KEY `student_attendances_timetable_lesson_id_foreign` (`timetable_lesson_id`),
  KEY `student_attendances_marked_by_id_foreign` (`marked_by_id`),
  CONSTRAINT `student_attendances_marked_by_id_foreign` FOREIGN KEY (`marked_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_attendances_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_attendances_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_attendances_timetable_lesson_id_foreign` FOREIGN KEY (`timetable_lesson_id`) REFERENCES `timetable_lessons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=601 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_card_status_logs`
--

DROP TABLE IF EXISTS `student_card_status_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_card_status_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `action` varchar(255) NOT NULL,
  `reason` text DEFAULT NULL,
  `processed_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_card_status_logs_school_id_foreign` (`school_id`),
  KEY `student_card_status_logs_student_id_foreign` (`student_id`),
  KEY `student_card_status_logs_processed_by_id_foreign` (`processed_by_id`),
  CONSTRAINT `student_card_status_logs_processed_by_id_foreign` FOREIGN KEY (`processed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_card_status_logs_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_card_status_logs_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_competencies`
--

DROP TABLE IF EXISTS `student_competencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_competencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `skill_area` varchar(255) NOT NULL,
  `score` decimal(4,2) NOT NULL DEFAULT 0.00,
  `remark` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_competencies_school_id_foreign` (`school_id`),
  KEY `student_competencies_enrollment_id_foreign` (`enrollment_id`),
  CONSTRAINT `student_competencies_enrollment_id_foreign` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_competencies_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_documents`
--

DROP TABLE IF EXISTS `student_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_documents_student_id_foreign` (`student_id`),
  KEY `student_documents_school_id_student_id_index` (`school_id`,`student_id`),
  CONSTRAINT `student_documents_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_documents_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_fee_waiver`
--

DROP TABLE IF EXISTS `student_fee_waiver`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_fee_waiver` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `fee_waiver_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_fee_waiver_student_id_foreign` (`student_id`),
  KEY `student_fee_waiver_fee_waiver_id_foreign` (`fee_waiver_id`),
  CONSTRAINT `student_fee_waiver_fee_waiver_id_foreign` FOREIGN KEY (`fee_waiver_id`) REFERENCES `fee_waivers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_fee_waiver_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_guardian`
--

DROP TABLE IF EXISTS `student_guardian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_guardian` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `guardian_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `student_guardian_student_id_foreign` (`student_id`),
  KEY `student_guardian_guardian_id_foreign` (`guardian_id`),
  CONSTRAINT `student_guardian_guardian_id_foreign` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_guardian_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_hostel_profiles`
--

DROP TABLE IF EXISTS `student_hostel_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_hostel_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `medical_conditions` text DEFAULT NULL,
  `dietary_restrictions` text DEFAULT NULL,
  `emergency_contacts` text DEFAULT NULL,
  `laundry_number` varchar(255) DEFAULT NULL,
  `qr_pass_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_hostel_profiles_school_id_student_id_unique` (`school_id`,`student_id`),
  KEY `student_hostel_profiles_student_id_foreign` (`student_id`),
  CONSTRAINT `student_hostel_profiles_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_hostel_profiles_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_medical_records`
--

DROP TABLE IF EXISTS `student_medical_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_medical_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `chronic_conditions` text DEFAULT NULL,
  `immunization_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`immunization_history`)),
  `regular_medications` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_medical_records_school_id_student_id_unique` (`school_id`,`student_id`),
  KEY `student_medical_records_student_id_foreign` (`student_id`),
  CONSTRAINT `student_medical_records_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_medical_records_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_payment_submissions`
--

DROP TABLE IF EXISTS `student_payment_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_payment_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `gateway` varchar(20) NOT NULL DEFAULT 'manual',
  `reference_number` varchar(255) DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `payment_date` date DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `source_bank_name` varchar(255) DEFAULT NULL,
  `source_account_number` varchar(255) DEFAULT NULL,
  `destination_bank_account_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `proof_file_path` varchar(255) DEFAULT NULL,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `rejection_reason` varchar(255) DEFAULT NULL,
  `reviewed_by_id` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_payment_submissions_invoice_id_foreign` (`invoice_id`),
  KEY `student_payment_submissions_reviewed_by_id_foreign` (`reviewed_by_id`),
  KEY `student_payment_submissions_school_id_status_index` (`school_id`,`status`),
  KEY `student_payment_submissions_student_id_invoice_id_index` (`student_id`,`invoice_id`),
  CONSTRAINT `student_payment_submissions_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_payment_submissions_reviewed_by_id_foreign` FOREIGN KEY (`reviewed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_payment_submissions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_payment_submissions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) DEFAULT NULL,
  `school_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `application_id` bigint(20) unsigned DEFAULT NULL,
  `student_id_number` varchar(255) DEFAULT NULL,
  `admission_number` varchar(255) NOT NULL,
  `national_id` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `date_of_birth` date NOT NULL,
  `admission_date` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `card_expiry_date` date DEFAULT NULL,
  `card_status` varchar(255) NOT NULL DEFAULT 'pending_issuance',
  `house` varchar(255) DEFAULT NULL,
  `boarding_status` varchar(255) NOT NULL DEFAULT 'day_scholar',
  `blood_group` varchar(255) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `photo_rejected_reason` text DEFAULT NULL,
  `photo_rejected_by` bigint(20) unsigned DEFAULT NULL,
  `photo_rejected_at` timestamp NULL DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `medical_notes` text DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(255) DEFAULT NULL,
  `parent_email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_school_id_admission_number_unique` (`school_id`,`admission_number`),
  UNIQUE KEY `uq_school_student_id_number` (`school_id`,`student_id_number`),
  KEY `students_user_id_foreign` (`user_id`),
  KEY `students_application_id_foreign` (`application_id`),
  CONSTRAINT `students_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `subject_papers`
--

DROP TABLE IF EXISTS `subject_papers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subject_papers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject_papers_school_id_foreign` (`school_id`),
  KEY `subject_papers_subject_id_foreign` (`subject_id`),
  CONSTRAINT `subject_papers_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subject_papers_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'theory',
  `credit_weight` decimal(4,2) NOT NULL DEFAULT 1.00,
  `is_elective` tinyint(1) NOT NULL DEFAULT 0,
  `workflow_status` varchar(50) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `workflow_completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_school_subject_code` (`school_id`,`code`),
  CONSTRAINT `subjects_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tax_number` varchar(255) DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `suppliers_school_id_foreign` (`school_id`),
  CONSTRAINT `suppliers_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_audit_logs`
--

DROP TABLE IF EXISTS `system_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `module` varchar(100) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `outcome` varchar(50) NOT NULL DEFAULT 'success',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `system_audit_logs_school_id_foreign` (`school_id`),
  KEY `system_audit_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `system_audit_logs_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `system_audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1464 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `group` varchar(100) NOT NULL,
  `key` varchar(150) NOT NULL,
  `value` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sys_setting_key` (`school_id`,`group`,`key`),
  CONSTRAINT `system_settings_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=282 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tenant_healths`
--

DROP TABLE IF EXISTS `tenant_healths`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tenant_healths` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `uptime_percentage` decimal(5,2) NOT NULL DEFAULT 100.00,
  `response_time_ms` int(10) unsigned NOT NULL DEFAULT 150,
  `storage_used_bytes` bigint(20) unsigned NOT NULL DEFAULT 0,
  `active_users_count` int(10) unsigned NOT NULL DEFAULT 0,
  `health_logs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`health_logs`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_healths_school_id_unique` (`school_id`),
  CONSTRAINT `tenant_healths_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `terminology_overrides`
--

DROP TABLE IF EXISTS `terminology_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terminology_overrides` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `terminology_overrides_school_id_key_unique` (`school_id`,`key`),
  CONSTRAINT `terminology_overrides_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `terms`
--

DROP TABLE IF EXISTS `terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `academic_year_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `workflow_completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `terms_school_id_foreign` (`school_id`),
  KEY `terms_academic_year_id_foreign` (`academic_year_id`),
  CONSTRAINT `terms_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `terms_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `time_slots`
--

DROP TABLE IF EXISTS `time_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `time_slots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_break` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `template_id` bigint(20) unsigned DEFAULT NULL,
  `color` varchar(255) NOT NULL DEFAULT '#f1f5f9',
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `time_slots_school_id_foreign` (`school_id`),
  KEY `time_slots_template_id_foreign` (`template_id`),
  CONSTRAINT `time_slots_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `time_slots_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `timetable_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `timetable_lessons`
--

DROP TABLE IF EXISTS `timetable_lessons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timetable_lessons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `template_id` bigint(20) unsigned DEFAULT NULL,
  `academic_year_id` bigint(20) unsigned NOT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `section_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `classroom_id` bigint(20) unsigned NOT NULL,
  `time_slot_id` bigint(20) unsigned NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL DEFAULT 'monday',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `custom_label` varchar(255) DEFAULT NULL,
  `color` varchar(255) NOT NULL DEFAULT '#15803d',
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_timetable_stream_time` (`school_id`,`academic_year_id`,`term_id`,`time_slot_id`,`day_of_week`,`section_id`),
  UNIQUE KEY `uq_timetable_teacher_time` (`school_id`,`academic_year_id`,`term_id`,`time_slot_id`,`day_of_week`,`teacher_id`),
  UNIQUE KEY `uq_timetable_classroom_time` (`school_id`,`academic_year_id`,`term_id`,`time_slot_id`,`day_of_week`,`classroom_id`),
  KEY `timetable_lessons_academic_year_id_foreign` (`academic_year_id`),
  KEY `timetable_lessons_term_id_foreign` (`term_id`),
  KEY `timetable_lessons_course_id_foreign` (`course_id`),
  KEY `timetable_lessons_section_id_foreign` (`section_id`),
  KEY `timetable_lessons_subject_id_foreign` (`subject_id`),
  KEY `timetable_lessons_teacher_id_foreign` (`teacher_id`),
  KEY `timetable_lessons_classroom_id_foreign` (`classroom_id`),
  KEY `timetable_lessons_time_slot_id_foreign` (`time_slot_id`),
  KEY `timetable_lessons_template_id_foreign` (`template_id`),
  CONSTRAINT `timetable_lessons_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_lessons_classroom_id_foreign` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_lessons_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_lessons_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_lessons_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_lessons_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_lessons_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_lessons_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `timetable_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_lessons_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_lessons_time_slot_id_foreign` FOREIGN KEY (`time_slot_id`) REFERENCES `time_slots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `timetable_templates`
--

DROP TABLE IF EXISTS `timetable_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timetable_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_school_template_name` (`school_id`,`name`),
  CONSTRAINT `timetable_templates_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_tasks`
--

DROP TABLE IF EXISTS `user_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `created_by_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_to_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `due_time` varchar(10) DEFAULT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'medium',
  `important` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `cleared_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `reminder_at` datetime DEFAULT NULL,
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `recurrence` varchar(20) NOT NULL DEFAULT 'none',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_tasks_created_by_id_foreign` (`created_by_id`),
  KEY `user_tasks_assigned_to_id_foreign` (`assigned_to_id`),
  KEY `user_tasks_school_id_assigned_to_id_status_index` (`school_id`,`assigned_to_id`,`status`),
  KEY `user_tasks_school_id_created_by_id_status_index` (`school_id`,`created_by_id`,`status`),
  KEY `user_tasks_due_status_idx` (`school_id`,`due_date`,`status`),
  KEY `user_tasks_assignee_due_idx` (`school_id`,`assigned_to_id`,`due_date`),
  KEY `user_tasks_important_idx` (`school_id`,`important`,`status`),
  CONSTRAINT `user_tasks_assigned_to_id_foreign` FOREIGN KEY (`assigned_to_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_tasks_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_tasks_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `custom_role_id` bigint(20) unsigned DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `account_status` varchar(20) NOT NULL DEFAULT 'active',
  `requested_role` varchar(50) DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `activated_at` timestamp NULL DEFAULT NULL,
  `rejected_reason` text DEFAULT NULL,
  `do_not_disturb` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `phone` varchar(60) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `activation_token` varchar(100) DEFAULT NULL,
  `activation_token_expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `locale` varchar(10) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_school_id_email_unique` (`school_id`,`email`),
  UNIQUE KEY `users_activation_token_unique` (`activation_token`),
  UNIQUE KEY `users_google_id_unique` (`google_id`),
  KEY `users_custom_role_id_foreign` (`custom_role_id`),
  KEY `users_approved_by_foreign` (`approved_by`),
  CONSTRAINT `users_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_custom_role_id_foreign` FOREIGN KEY (`custom_role_id`) REFERENCES `custom_roles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `xp_transactions`
--

DROP TABLE IF EXISTS `xp_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `xp_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `learner_xp_id` bigint(20) unsigned NOT NULL,
  `amount` int(11) NOT NULL,
  `type` varchar(30) NOT NULL,
  `description` text DEFAULT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `xp_transactions_learner_xp_id_foreign` (`learner_xp_id`),
  KEY `idx_xpt_school_stud_time` (`school_id`,`student_id`,`created_at`),
  KEY `idx_xpt_student_type` (`student_id`,`type`),
  CONSTRAINT `xp_transactions_learner_xp_id_foreign` FOREIGN KEY (`learner_xp_id`) REFERENCES `learner_xp` (`id`) ON DELETE CASCADE,
  CONSTRAINT `xp_transactions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `xp_transactions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'schoolcore'
--
