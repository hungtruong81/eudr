
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
DROP TABLE IF EXISTS `eudr_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_companies` (
  `company_id` int unsigned NOT NULL AUTO_INCREMENT,
  `company_code` varchar(30) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `short_name` varchar(100) DEFAULT NULL,
  `tax_code` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'inactive',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `generate_default` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`company_id`),
  UNIQUE KEY `company_id_UNIQUE` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=72361 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_company_group_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_company_group_members` (
  `company_group_member_id` int unsigned NOT NULL AUTO_INCREMENT,
  `company_group_id` int NOT NULL DEFAULT '0',
  `user_id` int NOT NULL DEFAULT '0',
  `assigned_by` int NOT NULL DEFAULT '0',
  `assigned_at` datetime NOT NULL,
  PRIMARY KEY (`company_group_member_id`),
  UNIQUE KEY `company_group_member_id_UNIQUE` (`company_group_member_id`)
) ENGINE=InnoDB AUTO_INCREMENT=78409 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_company_group_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_company_group_permissions` (
  `company_group_permission_id` int unsigned NOT NULL AUTO_INCREMENT,
  `company_group_id` int NOT NULL DEFAULT '0',
  `permission_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`company_group_permission_id`),
  UNIQUE KEY `company_group_permission_id_UNIQUE` (`company_group_permission_id`)
) ENGINE=InnoDB AUTO_INCREMENT=88721 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_company_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_company_groups` (
  `company_group_id` int unsigned NOT NULL AUTO_INCREMENT,
  `company_group_code` varchar(30) NOT NULL,
  `default_name` enum('farmer','purchaser','trader','company','inspector') DEFAULT NULL,
  `company_id` int NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `description` text,
  `status` enum('active','inactive') DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`company_group_id`),
  UNIQUE KEY `company_group_id_UNIQUE` (`company_group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=87948 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_connections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_connections` (
  `connection_id` int unsigned NOT NULL AUTO_INCREMENT,
  `connection_code` varchar(30) NOT NULL,
  `requester_company_id` int NOT NULL DEFAULT '0',
  `requester_user_id` int NOT NULL DEFAULT '0' COMMENT 'Người gửi yêu cầu kết nối',
  `target_company_id` int NOT NULL DEFAULT '0',
  `target_user_id` int NOT NULL DEFAULT '0' COMMENT 'Người nhận yêu cầu kết nối',
  `connection_method` enum('phone','qr_code') NOT NULL DEFAULT 'phone' COMMENT 'Phương thức kết nối: "phone" hoặc "qrcode',
  `status` enum('pending','cancelled','accepted','rejected','blocked') NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái kết nối',
  `requested_at` timestamp NOT NULL,
  `notes` text COMMENT 'Ghi chú từ người gửi yêu cầu',
  `created_at` timestamp NOT NULL COMMENT 'Thời gian tạo kết nối',
  `responded_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `rejection_reason` varchar(255) DEFAULT NULL COMMENT 'Lý do từ chối (nếu có)',
  `is_deleted` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`connection_id`),
  UNIQUE KEY `user_connection_id_UNIQUE` (`connection_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6861 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_custom_field_definitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_custom_field_definitions` (
  `field_id` int unsigned NOT NULL AUTO_INCREMENT,
  `field_code` varchar(30) NOT NULL,
  `field_key` varchar(100) NOT NULL COMMENT 'Slug không dấu, unique trong cùng entity_type + company_id',
  `field_label` varchar(200) NOT NULL COMMENT 'Tên hiển thị',
  `field_description` text COMMENT 'Mô tả ngắn',
  `entity_type` enum('land','plant','harvest','customer','product','sales_order','product_lot_import_none_eudr') NOT NULL,
  `field_type` enum('text','textarea','number','date','datetime','boolean','select') NOT NULL,
  `options` json DEFAULT NULL COMMENT 'Danh sách lựa chọn cho field_type=select, ví dụ ["A","B","C"]',
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `is_searchable` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `company_id` int NOT NULL DEFAULT '0',
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`field_id`),
  UNIQUE KEY `field_id_UNIQUE` (`field_id`)
) ENGINE=InnoDB AUTO_INCREMENT=56463 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_custom_field_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_custom_field_values` (
  `value_id` int unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int NOT NULL,
  `entity_type` enum('land','plant','harvest','customer','product','sales_order','product_lot_import_none_eudr') NOT NULL,
  `entity_id` int NOT NULL COMMENT 'PK của thực thể tương ứng',
  `company_id` int NOT NULL DEFAULT '0',
  `value_text` text COMMENT 'Dùng cho text, textarea, select, boolean',
  `value_number` decimal(20,6) DEFAULT NULL COMMENT 'Dùng cho number',
  `value_date` datetime DEFAULT NULL COMMENT 'Dùng cho date, datetime',
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`value_id`),
  UNIQUE KEY `value_id_UNIQUE` (`value_id`)
) ENGINE=InnoDB AUTO_INCREMENT=45661 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_external_material_lands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_external_material_lands` (
  `external_material_land_id` int unsigned NOT NULL AUTO_INCREMENT,
  `external_material_id` int NOT NULL DEFAULT '0',
  `plot_id` int NOT NULL DEFAULT '0',
  `harvest_weight` decimal(15,2) DEFAULT '0.00' COMMENT 'Sản lượng thu hoạch từ vườn này (kg)',
  `notes` text,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`external_material_land_id`),
  UNIQUE KEY `external_material_land_id_UNIQUE` (`external_material_land_id`)
) ENGINE=InnoDB AUTO_INCREMENT=54237 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_external_material_transports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_external_material_transports` (
  `external_material_transport_id` int unsigned NOT NULL AUTO_INCREMENT,
  `external_material_id` int NOT NULL DEFAULT '0',
  `vehicle_license_plate` varchar(45) DEFAULT NULL,
  `driver_name` varchar(255) DEFAULT NULL,
  `driver_phone` varchar(45) DEFAULT NULL,
  `transport_date` date DEFAULT NULL,
  `pickup_time` time DEFAULT NULL,
  `pickup_location` text,
  `delivery_time` time DEFAULT NULL,
  `notes` text,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`external_material_transport_id`),
  UNIQUE KEY `external_material_transport_id_UNIQUE` (`external_material_transport_id`)
) ENGINE=InnoDB AUTO_INCREMENT=53457 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_external_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_external_materials` (
  `external_material_id` int unsigned NOT NULL AUTO_INCREMENT,
  `external_material_code` varchar(30) NOT NULL,
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'Nhà máy nhận nguyên liệu',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'Công ty sở hữu',
  `supplier_name` varchar(255) NOT NULL COMMENT 'Tên nhà cung cấp bên ngoài',
  `supplier_phone` varchar(45) DEFAULT NULL,
  `supplier_address` text,
  `latex_weight` decimal(15,2) DEFAULT '0.00' COMMENT 'Khối lượng mủ nước (kg)',
  `latex_tsc_grade` decimal(5,2) DEFAULT '0.00' COMMENT 'Hàm lượng TSC mủ nước (%)',
  `scrap_rubber_weight` decimal(15,2) DEFAULT '0.00' COMMENT 'Khối lượng mủ tạp (kg)',
  `scrap_rubber_drc_grade` decimal(5,2) DEFAULT '0.00' COMMENT 'Hàm lượng DRC mủ tạp (%)',
  `cup_lump_weight` decimal(15,2) DEFAULT '0.00' COMMENT 'Khối lượng mủ chén (kg)',
  `total_amount` decimal(15,2) DEFAULT '0.00' COMMENT 'Tổng giá trị (VND)',
  `purchase_date` date NOT NULL,
  `notes` text,
  `status` enum('draft','confirmed','cancelled') DEFAULT 'draft',
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`external_material_id`),
  UNIQUE KEY `external_material_id_UNIQUE` (`external_material_id`)
) ENGINE=InnoDB AUTO_INCREMENT=54336 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_factories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_factories` (
  `factory_id` int unsigned NOT NULL AUTO_INCREMENT,
  `factory_code` varchar(30) NOT NULL,
  `factory_name` varchar(255) NOT NULL COMMENT 'Tên nhà máy',
  `company_id` int NOT NULL DEFAULT '0',
  `address` text COMMENT 'Địa chỉ nhà máy',
  `status` enum('active','inactive','maintenance') DEFAULT 'active' COMMENT 'Trạng thái hoạt động',
  `notes` text COMMENT 'Ghi chú',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`factory_id`),
  UNIQUE KEY `factory_id_UNIQUE` (`factory_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7861 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_general_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_general_counters` (
  `counter_id` int unsigned NOT NULL AUTO_INCREMENT,
  `counter_date` date NOT NULL,
  `counter` int NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`counter_id`),
  UNIQUE KEY `contract_counter_id_UNIQUE` (`counter_id`),
  UNIQUE KEY `counter_date_UNIQUE` (`counter_date`)
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_general_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_general_files` (
  `file_id` int unsigned NOT NULL AUTO_INCREMENT,
  `file_code` varchar(30) NOT NULL,
  `user_id` int DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int DEFAULT '0',
  `folder` varchar(50) DEFAULT NULL,
  `image_size` varchar(50) DEFAULT NULL,
  `text_content` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT '0',
  `detection` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`file_id`,`file_code`),
  UNIQUE KEY `file_id_UNIQUE` (`file_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7225 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_general_mail_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_general_mail_queue` (
  `mail_id` int unsigned NOT NULL AUTO_INCREMENT,
  `message_code` varchar(30) NOT NULL COMMENT 'Mã định danh mail (nếu cần truy vết)',
  `send_name` varchar(255) NOT NULL DEFAULT '' COMMENT 'Tên người gửi',
  `send_from` varchar(255) NOT NULL COMMENT 'Email người gửi',
  `send_to` varchar(255) NOT NULL COMMENT 'Người nhận chính',
  `cc` text COMMENT 'Danh sách CC (JSON)',
  `bcc` text COMMENT 'Danh sách BCC (JSON)',
  `reply_to` varchar(255) DEFAULT '' COMMENT 'Email nhận phản hồi',
  `subject` varchar(255) NOT NULL COMMENT 'Tiêu đề',
  `content` text NOT NULL COMMENT 'Nội dung HTML',
  `content_plain` text NOT NULL COMMENT 'Nội dung text thuần',
  `calendar` text COMMENT 'Lịch',
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `time_sent` datetime DEFAULT NULL COMMENT 'Thời gian thực tế gửi',
  `sent_count` int DEFAULT '0' COMMENT 'Số lần thử gửi',
  `error` text COMMENT 'Lỗi nếu có',
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int DEFAULT '0',
  PRIMARY KEY (`mail_id`),
  UNIQUE KEY `mail_id_UNIQUE` (`mail_id`)
) ENGINE=InnoDB AUTO_INCREMENT=389051 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_general_provinces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_general_provinces` (
  `province_id` int unsigned NOT NULL AUTO_INCREMENT,
  `province_name` varchar(100) NOT NULL,
  `type` enum('province','municipality') NOT NULL,
  `code` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`province_id`),
  UNIQUE KEY `id_UNIQUE` (`province_id`)
) ENGINE=InnoDB AUTO_INCREMENT=65505 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_general_vn2000_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_general_vn2000_zones` (
  `zone_id` int unsigned NOT NULL AUTO_INCREMENT,
  `zone_name` varchar(150) NOT NULL,
  `value` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`zone_id`),
  UNIQUE KEY `id_UNIQUE` (`zone_id`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_harvest_plan_lands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_harvest_plan_lands` (
  `harvest_plan_land_id` int unsigned NOT NULL AUTO_INCREMENT,
  `harvest_plan_id` int NOT NULL,
  `plot_id` int NOT NULL,
  PRIMARY KEY (`harvest_plan_land_id`),
  UNIQUE KEY `harvest_plan_land_id_UNIQUE` (`harvest_plan_land_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9134 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_harvest_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_harvest_plans` (
  `harvest_plan_id` int unsigned NOT NULL AUTO_INCREMENT,
  `harvest_plan_code` varchar(30) NOT NULL,
  `company_id` int NOT NULL DEFAULT '0',
  `farmer_id` int DEFAULT '0',
  `dealer_id` int DEFAULT '0',
  `buyer_company_id` int DEFAULT '0',
  `buyer_user_id` int DEFAULT '0',
  `contract_code` varchar(15) DEFAULT NULL,
  `harvest_start_date` date NOT NULL COMMENT 'Ngày bắt đầu thu hoạch',
  `harvest_end_date` date NOT NULL COMMENT 'Ngày kết thúc thu hoạch',
  `tapping_regime` varchar(50) DEFAULT NULL COMMENT 'Chế độ cạo (D1/D2)',
  `expected_yield` decimal(10,2) DEFAULT '0.00' COMMENT 'Tổng sản lượng dự kiến (Kg)',
  `actual_yield` decimal(10,2) DEFAULT '0.00' COMMENT 'Tổng sản lượng thực tế (Kg)',
  `eudr_status` tinyint(1) DEFAULT '0' COMMENT 'Là EUDR hay là Non-EUDR. Mặc định là 0, 1 là EUDR, 2 là Non-EUDR',
  `notes` varchar(255) DEFAULT NULL COMMENT 'Ghi chú nếu có',
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`harvest_plan_id`,`harvest_plan_code`),
  UNIQUE KEY `harvest_plan_id_UNIQUE` (`harvest_plan_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5230 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_harvest_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_harvest_schedules` (
  `harvest_schedule_id` int unsigned NOT NULL AUTO_INCREMENT,
  `harvest_schedule_code` varchar(30) NOT NULL,
  `harvest_plan_id` int NOT NULL,
  `plot_id` int NOT NULL,
  `pickup_date` date DEFAULT NULL COMMENT 'Ngày thu hoạch',
  `pickup_time` time DEFAULT NULL COMMENT 'Thời gian thu hoạch',
  `expected_yield` decimal(10,2) DEFAULT NULL COMMENT 'Sản lượng dự kiến (Mong đợi)',
  `actual_yield` decimal(10,2) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `company_id` int NOT NULL DEFAULT '0',
  `buyer_company_id` int NOT NULL DEFAULT '0',
  `buyer_user_id` int NOT NULL DEFAULT '0',
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`harvest_schedule_id`,`harvest_schedule_code`),
  UNIQUE KEY `harvest_schedule_id_UNIQUE` (`harvest_schedule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7898 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_land_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_land_shares` (
  `land_share_id` int unsigned NOT NULL AUTO_INCREMENT,
  `plot_id` int NOT NULL DEFAULT '0',
  `owner_id` int NOT NULL DEFAULT '0',
  `shared_with_user_id` int NOT NULL DEFAULT '0',
  `status` enum('active','revoked') DEFAULT 'active',
  `created_at` timestamp NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`land_share_id`),
  UNIQUE KEY `land_share_id_UNIQUE` (`land_share_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9710 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_lands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_lands` (
  `plot_id` int unsigned NOT NULL AUTO_INCREMENT,
  `plot_code` varchar(30) NOT NULL COMMENT 'Mã lô đất',
  `plot_name` varchar(255) NOT NULL COMMENT 'Tên của lô đất',
  `farmer_user_id` int DEFAULT NULL,
  `farmer_name` varchar(150) DEFAULT NULL COMMENT 'Tên của người sở hữu lô đất hoặc người thuê lô đất đó',
  `company_id` int NOT NULL DEFAULT '0',
  `company_name` varchar(255) DEFAULT NULL COMMENT 'Tên của Company sở hữu lô đất hoặc Company trực thuộc của Farmer',
  `ownership` varchar(50) DEFAULT NULL COMMENT 'Quyền sở hữu/ sử dụng đất\n"Owner/ Chủ sở hữu\n Rent/ Thuê"',
  `land_records` varchar(50) DEFAULT NULL COMMENT 'Hồ sơ, giấy tờ đất (ID file hình giấy tờ đã được Upload)\nFile scan của sổ hồng/chứng từ thuê/chứng từ liên quan',
  `land_document_detection` int DEFAULT '0',
  `province_id` int DEFAULT '0',
  `zone_id` int DEFAULT '0',
  `country` varchar(100) DEFAULT NULL COMMENT 'Quốc gia',
  `coordinate_origin_points` text COMMENT 'Tọa độ gốc được OCR từ file giấy tờ đất',
  `coordinates` text COMMENT 'Danh sách tọa độ sau khi được convert từ tọa độ gốc',
  `land_area` decimal(10,2) DEFAULT NULL COMMENT 'Diện tích của lô đất Theo chứng từ đất (Đơn vị hecta)\n',
  `address` varchar(255) DEFAULT NULL COMMENT 'Địa chỉ của lô đất',
  `altitude_above_sea_level` decimal(10,2) DEFAULT '0.00' COMMENT 'Độ cao so với mực nước biển (m).',
  `soil` varchar(100) DEFAULT NULL COMMENT 'Loại đất (Ví dụ: "Cát pha" hoặc "Đất sét")\n"USDA Đất cát / USDA Sand\n\nUSDA Đất thịt / USDA Loam\n\nUSDA Đất sét / USDA Clay\n\nUSDA Đất phù sa / USDA Silt\nUSDA Đất than bùn / USDA Peat\n\nUSDA Đất cát pha / USDA Sandy Loam\n\nUSDA Đất sét pha / USDA Clay Loam"\n',
  `status` varchar(100) DEFAULT NULL COMMENT 'Tình trạng sử dụng đất hiện tại ',
  `maximum_yield` int DEFAULT '0' COMMENT 'Sản lượng tối đa mà lô đất có thể đạt được (kg/năm) Mũ nước',
  `classify` varchar(100) DEFAULT NULL COMMENT 'Phân loại đất \n(ví dụ: K1, K2 với K1 là đất năng suất cao, K2 là đất năng suất trung bình)',
  `area_24` decimal(10,2) DEFAULT NULL COMMENT 'Diện tích đất có thể canh tác trong vòng 24 tháng (ha)',
  `notes` varchar(255) DEFAULT NULL COMMENT 'Ghi chú của lô đất',
  `register_type` varchar(20) DEFAULT 'internal' COMMENT 'Nguồn đăng ký vườn: internal (nội bộ), external (từ nguồn ngoài)',
  `eudr_status` tinyint(1) DEFAULT '0' COMMENT 'Đất là EUDR hay là Non-EUDR. Mặc định là 0, 1 là EUDR, 2 là Non-EUDR',
  `is_approved` tinyint(1) DEFAULT '0' COMMENT 'Đánh dấu đã được admin duyệt - Khi đã được duyệt thì Farmer đó không được chỉnh sửa',
  `approved_by` int DEFAULT '0',
  `approved_at` timestamp NULL DEFAULT NULL,
  `is_vendor` tinyint(1) DEFAULT '0' COMMENT 'Vườn của Vendor',
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`plot_id`,`plot_code`),
  UNIQUE KEY `plot_id_UNIQUE` (`plot_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2238 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_master_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_master_prices` (
  `price_id` int unsigned NOT NULL AUTO_INCREMENT,
  `price_code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `price_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `price_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `domestic_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `international_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `company_id` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`price_id`),
  UNIQUE KEY `price_id_UNIQUE` (`price_id`)
) ENGINE=InnoDB AUTO_INCREMENT=75649 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_notifications` (
  `notification_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0' COMMENT 'Người nhận thông báo',
  `type` varchar(45) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_id` int DEFAULT '0' COMMENT 'ID liên quan (connection_id hoặc ...)',
  `related_code` varchar(30) DEFAULT NULL,
  `related_type` enum('connection','transaction_ticket') DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`notification_id`),
  UNIQUE KEY `notification_id_UNIQUE` (`notification_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6921 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_permissions` (
  `permission_id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `display_name` varchar(150) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `scope` varchar(45) DEFAULT NULL,
  `action` varchar(45) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `category` varchar(45) DEFAULT NULL,
  `is_system` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `permission_id_UNIQUE` (`permission_id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4811 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_plants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_plants` (
  `plant_id` int unsigned NOT NULL AUTO_INCREMENT,
  `plant_code` varchar(30) NOT NULL,
  `company_id` int NOT NULL DEFAULT '0',
  `plot_id` int NOT NULL DEFAULT '0',
  `crop_type` varchar(100) DEFAULT NULL COMMENT 'Loại cây trồng\\n"PB 235\\nRRIV 209\\nRRIV 1\\nRRIV 106\\nRRIV 124\\nPB 255\\nVM 515\\nPB 260\\nRRIC 121\\nRRIV 4 \\n..."\\n',
  `year_of_planting` year DEFAULT '2000' COMMENT 'Năm mà cây được trồng vào đất',
  `plantation_name` varchar(150) DEFAULT NULL COMMENT 'Tên của đợt gieo trồng',
  `expected_harvest` decimal(10,2) DEFAULT NULL COMMENT 'Sản lượng dự kiến thu hoạch được theo số lượng gieo trồng\\n',
  `plant_status` varchar(50) DEFAULT NULL COMMENT 'Trạng thái hiện tại của đợt gieo trồng\n"Nảy mầm / Germination - \nCây con / Seedling - \nCây giống / Sapling - \nCây trưởng thành / Juvenile - \nRa nhựa Tapping - \nLão hóa / Senescence"\n',
  `date_end_of_planting` date DEFAULT NULL COMMENT 'Ngày kết thúc việc gieo trồng',
  `type_of_plantation` varchar(50) DEFAULT NULL COMMENT 'Loại hình gieo trồng\n"Độc canh - \nXen Canh - \nNông Lâm Kết hợp - \nKhác"\n',
  `planting_method` varchar(50) DEFAULT NULL COMMENT 'Phương pháp gieo trồng (Ví dụ: Gieo trồng trực tiếp, cấy ghép)',
  `planting_distance` varchar(50) DEFAULT NULL COMMENT 'Khoảng cách giữa các cây trồng\n',
  `year_of_start_tapping` year DEFAULT NULL COMMENT 'Năm bắt đầu cạo mủ, thời điểm khi cây cao su bắt đầu được khai thác mủ lần đầu tiên. Thông thường, cây cao su có thể bắt đầu cho khai thác mủ sau khoảng 5-7 năm kể từ khi trồng, tùy thuộc vào điều kiện chăm sóc và giống cây.\\n',
  `year_of_upward_tapping` year DEFAULT NULL COMMENT 'Năm cạo ngược, giai đoạn mà việc khai thác mủ cao su chuyển từ phương pháp cạo dọc thân cây (downward tapping) sang phương pháp cạo ngược lên phía trên (upward tapping).\\n',
  `percentage_of_trees_meeting_perimeter_standards` decimal(10,2) DEFAULT NULL COMMENT 'Phần trăm cây cần đạt kích thước chu vi tiêu chuẩn để bắt đầu thu hoạch\\n',
  `denity_of_tapping_tree` int DEFAULT NULL COMMENT 'Mật độ cây có thể khai thác (cây/ha)',
  `tapping_method` varchar(50) DEFAULT NULL COMMENT 'Phương pháp khai thác (Ví dụ: Khai thác nửa xoắn ốc, khai thác toàn xoắn ốc,...)\nCó thể là D2 do giống cây, thông thường thì D3 và D4. thời tiết bất lợi mưa nhiều mới có D5, D6\n',
  `annual_yield` int DEFAULT NULL COMMENT 'Sản lượng thu hoạch hằng năm của đợt gieo trồng\\n',
  `clone_type_of_tree` varchar(150) DEFAULT NULL COMMENT 'Các cây được nhân giống vô tính từ một cây mẹ ban đầu, nhằm duy trì các đặc tính di truyền ưu việt của cây mẹ\\nPhần này thể hiện là Tên loại giống nào đang được trồng trên đất đó\\n',
  `effective_tree_density` int DEFAULT NULL COMMENT 'Mật độ cây trồng thực tế trên mỗi hectare đất',
  `standard_deviation` varchar(50) DEFAULT NULL COMMENT 'Độ lệch chuẩn của năng suất mủ của các cây cao su trong một khu vườn\n',
  `production_24` decimal(10,2) DEFAULT NULL COMMENT 'Sản lượng mủ cao su trong 24 tháng vừa qua\\n',
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`plant_id`,`plant_code`),
  UNIQUE KEY `plant_id_UNIQUE` (`plant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5507 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_plants_crop_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_plants_crop_types` (
  `plant_crop_type_id` int unsigned NOT NULL AUTO_INCREMENT,
  `crop_type_name` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`plant_crop_type_id`),
  UNIQUE KEY `plant_crop_type_id_UNIQUE` (`plant_crop_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6754 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_bales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_bales` (
  `bale_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK bành',
  `pressing_run_id` int unsigned NOT NULL COMMENT 'Tham chiếu bước ép bành',
  `pressing_quality_detail_id` int unsigned DEFAULT NULL COMMENT 'Tham chiếu quality detail bước ép',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `bale_no` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Số thứ tự bành',
  `grade_id` int unsigned NOT NULL COMMENT 'Grade chất lượng của bành',
  `bale_weight_kg` decimal(10,3) DEFAULT NULL COMMENT 'Khối lượng bành (kg)',
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'formed' COMMENT 'formed,qc_pass,qc_fail,packed',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`bale_id`)
) ENGINE=InnoDB AUTO_INCREMENT=85569 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh sách bành đầu ra theo grade';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_channel_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_channel_runs` (
  `channel_run_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK bước mương',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `raw_tank_id` int unsigned NOT NULL COMMENT 'Bồn lấy nguyên liệu',
  `channel_id` int unsigned NOT NULL COMMENT 'Mương xử lý/đánh đông',
  `input_latex_kg` decimal(14,3) NOT NULL DEFAULT '0.000' COMMENT 'Đầu vào: kg mủ từ bồn ra mương',
  `input_quality_note` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Đánh giá chất lượng mủ tại mương',
  `input_ph` decimal(6,3) DEFAULT NULL COMMENT 'pH mủ đầu vào',
  `coagulation_done` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Đã xử lý và đánh đông',
  `output_ready_for_cutting_kg` decimal(14,3) NOT NULL DEFAULT '0.000' COMMENT 'Đầu ra: kg mủ sẵn sàng cắt tờ',
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft' COMMENT 'draft,in_progress,completed,cancelled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`channel_run_id`)
) ENGINE=InnoDB AUTO_INCREMENT=84789 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bước 1: mương xử lý + đánh đông';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_channels` (
  `channel_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK mương',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `channel_code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã mương',
  `channel_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Tên mương',
  `capacity_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Sức chứa tối đa (kg)',
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'available' COMMENT 'available,in_use,cleaning',
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`channel_id`)
) ENGINE=InnoDB AUTO_INCREMENT=84392 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh mục mương xử lý và đánh đông';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_cutting_machines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_cutting_machines` (
  `cutting_machine_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK máy cắt tờ',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `cutting_machine_code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã máy cắt tờ',
  `cutting_machine_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Tên máy cắt tờ',
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'available' COMMENT 'available,in_use,maintenance',
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`cutting_machine_id`)
) ENGINE=InnoDB AUTO_INCREMENT=88774 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh mục máy cắt tờ';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_cutting_run_quality_outputs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_cutting_run_quality_outputs` (
  `cutting_quality_output_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK output theo chất lượng ở bước cắt',
  `cutting_run_id` int unsigned NOT NULL COMMENT 'Bước cắt cha',
  `grade_id` int unsigned DEFAULT '0' COMMENT 'Loại chất lượng (optional, tham chiếu grade nếu có)',
  `quality_type` enum('L1','L2','L3','Mix','NA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'NA' COMMENT 'Tên loại chất lượng',
  `output_sheet_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Đầu ra: số tờ sau cắt theo quality',
  `output_sheet_thickness_min_mm` decimal(6,2) NOT NULL DEFAULT '15.00' COMMENT 'Độ dày min sau cắt (mm)',
  `output_sheet_thickness_max_mm` decimal(6,2) NOT NULL DEFAULT '20.00' COMMENT 'Độ dày max sau cắt (mm)',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`cutting_quality_output_id`)
) ENGINE=InnoDB AUTO_INCREMENT=93458 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Đầu ra bước cắt theo từng loại chất lượng';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_cutting_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_cutting_runs` (
  `cutting_run_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK bước cắt tờ',
  `channel_run_id` int unsigned NOT NULL COMMENT 'Nguồn mủ từ mương nào',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `cutting_machine_id` int unsigned NOT NULL COMMENT 'Máy cắt tờ nào',
  `input_channel_latex_kg` decimal(14,3) NOT NULL DEFAULT '0.000' COMMENT 'Đầu vào: kg mủ từ mương',
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft' COMMENT 'draft,in_progress,completed,cancelled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`cutting_run_id`)
) ENGINE=InnoDB AUTO_INCREMENT=92418 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bước 2: cắt tờ từ mủ mương theo máy cắt';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_drying_run_quality_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_drying_run_quality_details` (
  `drying_quality_detail_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK chi tiết quality bước sấy',
  `drying_run_id` int unsigned NOT NULL COMMENT 'Bước sấy cha',
  `grade_id` int unsigned DEFAULT '0' COMMENT 'Loại chất lượng (optional, tham chiếu grade nếu có)',
  `quality_type` enum('L1','L2','L3','Mix','NA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'NA' COMMENT 'Tên loại chất lượng',
  `input_sheet_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Đầu vào số tờ theo quality',
  `output_sheet_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Đầu ra số tờ sau sấy theo quality',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`drying_quality_detail_id`)
) ENGINE=InnoDB AUTO_INCREMENT=88967 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Input/output bước sấy theo quality';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_drying_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_drying_runs` (
  `drying_run_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK bước sấy',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất',
  `hanging_run_id` int unsigned NOT NULL COMMENT 'Nguồn đầu vào từ bước phơi',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `oven_id` int unsigned DEFAULT NULL COMMENT 'Lò sấy sử dụng',
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft' COMMENT 'draft,in_progress,completed,cancelled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`drying_run_id`)
) ENGINE=InnoDB AUTO_INCREMENT=58360 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bước 5: sấy';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_gong_carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_gong_carts` (
  `gong_cart_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK xe gòong',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `gong_cart_code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã xe gòong',
  `gong_cart_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Tên xe gòong',
  `max_poles` int unsigned NOT NULL DEFAULT '0' COMMENT 'Tổng số sào treo của xe gòong (hệ thống đánh số từ 1..max_poles)',
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'available' COMMENT 'available,in_use,cleaning',
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`gong_cart_id`)
) ENGINE=InnoDB AUTO_INCREMENT=87884 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh mục xe gòong phơi';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_grade_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_grade_prices` (
  `grade_price_id` int unsigned NOT NULL AUTO_INCREMENT,
  `grade_id` int NOT NULL DEFAULT '0',
  `domestic_price` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Giá trong nước',
  `international_price` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Giá quốc tế',
  `effective_from` date NOT NULL COMMENT 'Ngày bắt đầu có hiệu lực',
  `effective_to` date DEFAULT NULL COMMENT 'Ngày hết hiệu lực',
  `note` text,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`grade_price_id`),
  UNIQUE KEY `grade_price_id_UNIQUE` (`grade_price_id`)
) ENGINE=InnoDB AUTO_INCREMENT=54543 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_grades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_grades` (
  `grade_id` int unsigned NOT NULL AUTO_INCREMENT,
  `grade_code` varchar(30) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`grade_id`),
  UNIQUE KEY `grade_id_UNIQUE` (`grade_id`)
) ENGINE=InnoDB AUTO_INCREMENT=54651 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_hanging_quality_pole_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_hanging_quality_pole_assignments` (
  `hanging_pole_assignment_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK gán sào cụ thể cho quality',
  `hanging_run_id` int unsigned NOT NULL COMMENT 'Bước phơi cha',
  `hanging_run_pole_id` int unsigned NOT NULL COMMENT 'Sào cụ thể được gán',
  `hanging_quality_detail_id` int unsigned NOT NULL COMMENT 'Chi tiết quality bước phơi',
  `grade_id` int unsigned DEFAULT '0' COMMENT 'Loại chất lượng (optional, tham chiếu grade nếu có)',
  `quality_type` enum('L1','L2','L3','Mix','NA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'NA' COMMENT 'Tên loại chất lượng',
  `pole_no` int unsigned NOT NULL COMMENT 'Số sào được gán (không bắt buộc liên tiếp)',
  `assigned_sheet_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Số tờ mủ được treo ở sào này',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`hanging_pole_assignment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=86497 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Gán từng sào cụ thể cho quality; hỗ trợ chọn sào không liên tiếp';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_hanging_run_poles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_hanging_run_poles` (
  `hanging_run_pole_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK trạng thái sào trong một bước phơi',
  `hanging_run_id` int unsigned NOT NULL COMMENT 'Bước phơi cha',
  `pole_no` int unsigned NOT NULL COMMENT 'Số thứ tự sào trên xe gòong (1..gong_max_poles_snapshot)',
  `pole_status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'empty' COMMENT 'empty,occupied,blocked,broken',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`hanging_run_pole_id`)
) ENGINE=InnoDB AUTO_INCREMENT=100772 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh sách sào và trạng thái từng sào trong một bước phơi';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_hanging_run_quality_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_hanging_run_quality_details` (
  `hanging_quality_detail_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK chi tiết quality bước phơi',
  `hanging_run_id` int unsigned NOT NULL COMMENT 'Bước phơi cha',
  `grade_id` int unsigned DEFAULT '0' COMMENT 'Loại chất lượng (optional, tham chiếu grade nếu có)',
  `quality_type` enum('L1','L2','L3','Mix','NA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'NA' COMMENT 'Tên loại chất lượng',
  `input_sheet_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Đầu vào số tờ theo quality',
  `output_sheet_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Đầu ra số tờ sau phơi theo quality',
  `allocated_pole_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Tổng số sào thực tế được gán cho quality này',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`hanging_quality_detail_id`)
) ENGINE=InnoDB AUTO_INCREMENT=87368 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Input/output bước phơi theo quality';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_hanging_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_hanging_runs` (
  `hanging_run_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK bước phơi',
  `rolling_run_id` int unsigned NOT NULL COMMENT 'Nguồn đầu vào từ bước cán',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất',
  `gong_cart_id` int unsigned DEFAULT NULL COMMENT 'Xe gòong/sào sử dụng',
  `gong_max_poles_snapshot` int unsigned DEFAULT NULL COMMENT 'Snapshot tổng số sào của xe gòong tại thời điểm chạy bước phơi',
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft' COMMENT 'draft,in_progress,completed,cancelled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`hanging_run_id`)
) ENGINE=InnoDB AUTO_INCREMENT=79361 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bước 4: phơi';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_order_channel_setup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_order_channel_setup` (
  `order_channel_setup_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK thiết lập mương',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất cha',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `channel_id` int unsigned NOT NULL COMMENT 'Mương dự kiến sử dụng',
  `planned_volume_kg` decimal(14,3) NOT NULL DEFAULT '0.000' COMMENT 'Khối lượng dự kiến đổ vào mương (kg)',
  `coagulation_agent_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Loại chất tạo tủa dự kiến (ví dụ: acid formic, acetic acid)',
  `coagulation_agent_volume` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Lượng chất tạo tủa dự kiến',
  `setup_status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active' COMMENT 'active,changed_pending,rejected,cancelled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  PRIMARY KEY (`order_channel_setup_id`)
) ENGINE=InnoDB AUTO_INCREMENT=87761 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Thiết lập mương chứa cho lệnh sản xuất';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_order_cutting_machine_setup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_order_cutting_machine_setup` (
  `order_cutting_machine_setup_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK thiết lập máy cắt',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất cha',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `cutting_machine_id` int unsigned NOT NULL COMMENT 'Máy cắt dự kiến sử dụng',
  `expected_cutting_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng dự kiến đưa vào máy cắt (kg)',
  `expected_sheet_quantity` int NOT NULL DEFAULT '0' COMMENT 'Số lượng tờ dự kiến cho quality này',
  `setup_status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active' COMMENT 'active,changed_pending,rejected,cancelled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  PRIMARY KEY (`order_cutting_machine_setup_id`)
) ENGINE=InnoDB AUTO_INCREMENT=89585 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Thiết lập máy cắt tờ cho lệnh sản xuất';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_order_drying_setup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_order_drying_setup` (
  `order_drying_setup_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK thiết lập máy sấy',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất cha',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `oven_id` int unsigned NOT NULL COMMENT 'Lò sấy dự kiến sử dụng',
  `expected_drying_hours` int unsigned DEFAULT NULL COMMENT 'Thời gian sấy dự kiến (giờ)',
  `expected_final_moisture_percent` decimal(5,2) DEFAULT NULL COMMENT 'Độ ẩm cuối dự kiến (%)',
  `setup_status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active' COMMENT 'active,changed_pending,rejected,cancelled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  PRIMARY KEY (`order_drying_setup_id`)
) ENGINE=InnoDB AUTO_INCREMENT=88982 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Thiết lập máy sấy cho lệnh sản xuất';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_order_hanging_setup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_order_hanging_setup` (
  `order_hanging_setup_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK thiết lập xe gòong phơi',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất cha',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `gong_cart_id` int unsigned NOT NULL COMMENT 'Xe gòong dự kiến sử dụng',
  `expected_hanging_hours` int unsigned DEFAULT NULL COMMENT 'Thời gian phơi dự kiến (giờ)',
  `setup_status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active' COMMENT 'active,changed_pending,rejected,cancelled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  PRIMARY KEY (`order_hanging_setup_id`)
) ENGINE=InnoDB AUTO_INCREMENT=124428 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Thiết lập xe gòong phơi cho lệnh sản xuất';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_order_hanging_setup_poles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_order_hanging_setup_poles` (
  `order_hanging_setup_pole_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK cấu hình sào cần phơi',
  `order_hanging_setup_id` int unsigned NOT NULL COMMENT 'Thiết lập xe gòong phơi cha',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất cha',
  `pole_no` int unsigned NOT NULL COMMENT 'Số sào cần phơi (1..max_poles của xe gòong)',
  `pole_status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active' COMMENT 'active,cancelled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`order_hanging_setup_pole_id`)
) ENGINE=InnoDB AUTO_INCREMENT=139177 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh sách sào cần phơi theo cấu hình xe gòong của lệnh sản xuất';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_order_hanging_setup_quality_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_order_hanging_setup_quality_details` (
  `order_hanging_setup_quality_detail_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK chi tiết quality của cấu hình phơi',
  `order_hanging_setup_id` int unsigned NOT NULL COMMENT 'Thiết lập xe gòong phơi cha',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất cha',
  `quality_type` enum('L1','L2','L3','Mix','NA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'NA' COMMENT 'Loại chất lượng',
  `input_sheet_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Số tờ phơi theo quality',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`order_hanging_setup_quality_detail_id`)
) ENGINE=InnoDB AUTO_INCREMENT=112994 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Chi tiết quality trong cấu hình phơi của lệnh sản xuất';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_order_hanging_setup_quality_pole_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_order_hanging_setup_quality_pole_assignments` (
  `order_hanging_setup_pole_assignment_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK phân bổ sào theo quality trong setup',
  `order_hanging_setup_id` int unsigned NOT NULL COMMENT 'Thiết lập xe gòong phơi cha',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất cha',
  `order_hanging_setup_quality_detail_id` int unsigned NOT NULL COMMENT 'Chi tiết quality của setup',
  `order_hanging_setup_pole_id` int unsigned NOT NULL COMMENT 'Sào thuộc setup',
  `quality_type` enum('L1','L2','L3','Mix','NA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'NA' COMMENT 'Loại chất lượng',
  `pole_no` int unsigned NOT NULL COMMENT 'Số sào được phân bổ',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`order_hanging_setup_pole_assignment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=106177 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Phân bổ từng sào theo quality trong cấu hình setup phơi';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_order_pallet_setup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_order_pallet_setup` (
  `order_pallet_setup_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK thiết lập đóng pallet',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất cha',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `warehouse_id` int NOT NULL DEFAULT '0',
  `planned_pallet_quantity` int unsigned NOT NULL DEFAULT '0' COMMENT 'Số lượng pallet dự kiến cần đóng',
  `setup_status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active' COMMENT 'active,changed_pending,rejected,cancelled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  PRIMARY KEY (`order_pallet_setup_id`)
) ENGINE=InnoDB AUTO_INCREMENT=238958 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Thiết lập đóng pallet cho lệnh sản xuất (bước 9)';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_order_pressing_setup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_order_pressing_setup` (
  `order_pressing_setup_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK thiết lập ép bành theo quality',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất cha',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `product_type_id` int NOT NULL DEFAULT '0',
  `grade_id` int unsigned NOT NULL COMMENT 'Loại chất lượng (L1/L2/...)',
  `planned_sheet_quantity` int unsigned NOT NULL DEFAULT '0' COMMENT 'Số lượng bành dự kiến cần sản xuất',
  `setup_status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active' COMMENT 'active,changed_pending,rejected,cancelled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  PRIMARY KEY (`order_pressing_setup_id`)
) ENGINE=InnoDB AUTO_INCREMENT=88803 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Thiết lập ép bành theo từng chất lượng (bước 8)';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_order_raw_tank_setup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_order_raw_tank_setup` (
  `order_raw_tank_setup_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK thiết lập bồn nguyên liệu',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất cha',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `raw_tank_id` int unsigned NOT NULL COMMENT 'Bồn nguyên liệu thô cần lấy',
  `planned_volume_kg` decimal(14,3) NOT NULL DEFAULT '0.000' COMMENT 'Khối lượng dự kiến lấy từ bồn (kg)',
  `actual_volume_kg` decimal(14,3) DEFAULT NULL COMMENT 'Khối lượng thực tế lấy được',
  `setup_status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active' COMMENT 'active,changed_pending,rejected,cancelled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  PRIMARY KEY (`order_raw_tank_setup_id`)
) ENGINE=InnoDB AUTO_INCREMENT=98403 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Thiết lập bồn nguyên liệu thô cho lệnh sản xuất';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_order_roller_setup_by_quality`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_order_roller_setup_by_quality` (
  `order_roller_setup_quality_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK thiết lập máy cán theo quality',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất cha',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `grade_id` int unsigned NOT NULL COMMENT 'Loại chất lượng (L1/L2/...)',
  `quality_type` enum('L1','L2','L3','Mix','NA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'NA' COMMENT 'Tên loại chất lượng',
  `expected_sheet_quantity` int NOT NULL DEFAULT '0' COMMENT 'Số lượng tờ dự kiến cho quality này',
  `roller_id` int unsigned NOT NULL COMMENT 'Máy cán dự kiến cho quality này',
  `expected_output_thickness_min_mm` decimal(6,2) DEFAULT NULL COMMENT 'Độ dày min dự kiến sau cán (mm)',
  `expected_output_thickness_max_mm` decimal(6,2) DEFAULT NULL COMMENT 'Độ dày max dự kiến sau cán (mm)',
  `setup_status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active' COMMENT 'active,changed_pending,rejected,cancelled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  PRIMARY KEY (`order_roller_setup_quality_id`)
) ENGINE=InnoDB AUTO_INCREMENT=102372 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Thiết lập máy cán theo từng chất lượng';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_order_settling_tank_setup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_order_settling_tank_setup` (
  `order_settling_tank_setup_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK thiết lập bồn lắng đọng',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất cha',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `settling_tank_id` int unsigned NOT NULL COMMENT 'Bồn lắng đọng dự kiến sử dụng',
  `settling_duration_hours` int unsigned DEFAULT NULL COMMENT 'Thời gian lắng đọng dự kiến (giờ)',
  `setup_status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active' COMMENT 'active,changed_pending,rejected,cancelled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  PRIMARY KEY (`order_settling_tank_setup_id`)
) ENGINE=InnoDB AUTO_INCREMENT=342908 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Thiết lập bồn lắng đọng cho lệnh sản xuất';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_order_setup_change_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_order_setup_change_requests` (
  `change_request_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK yêu cầu thay đổi setup',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất cha',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `change_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Loại thay đổi: raw_tank, settling_tank, channel, cutting_machine, roller, hanging, drying, pressing, pallet',
  `change_description` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mô tả thay đổi yêu cầu',
  `old_value` longtext COLLATE utf8mb4_general_ci COMMENT 'Giá trị cũ dạng JSON array',
  `new_value` longtext COLLATE utf8mb4_general_ci COMMENT 'Giá trị mới yêu cầu dạng JSON array',
  `reason` text COLLATE utf8mb4_general_ci COMMENT 'Lý do thay đổi',
  `requested_by` int NOT NULL DEFAULT '0' COMMENT 'ID công nhân yêu cầu',
  `approval_status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending' COMMENT 'pending,approved,rejected',
  `approved_by` int DEFAULT '0' COMMENT 'ID quản lý duyệt',
  `approval_notes` text COLLATE utf8mb4_general_ci COMMENT 'Ghi chú duyệt',
  `approved_at` datetime DEFAULT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`change_request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=88699 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Quản lý yêu cầu thay đổi cấu hình công đoạn';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_orders` (
  `production_order_id` int unsigned NOT NULL AUTO_INCREMENT,
  `production_order_name` varchar(150) NOT NULL,
  `production_order_code` varchar(30) NOT NULL,
  `company_id` int NOT NULL DEFAULT '0',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `contract_id` int NOT NULL DEFAULT '0' COMMENT 'ID Hợp đồng (Nếu có)',
  `contract_code` varchar(15) DEFAULT NULL,
  `product_type_category` enum('scrap_rubber','concentrated_latex') DEFAULT NULL COMMENT 'Danh mục loại sản phẩm mủ tạp: scrap_rubber, Kem: concentrated_latex',
  `product_type_id` int NOT NULL DEFAULT '0' COMMENT 'ID loại sản phẩm',
  `required_quantity` int NOT NULL DEFAULT '0' COMMENT 'Số lượng yêu cầu',
  `production_date` date DEFAULT NULL COMMENT 'Ngày sản xuất',
  `status` enum('draft','pending_approval','approved','in_production','completed','cancelled') DEFAULT 'approved' COMMENT 'Nháp, đang soạn thảo - Chờ phê duyệt - Đã phê duyệt - Đang sản xuất - Hoàn thành - Hủy bỏ',
  `notes` text COMMENT 'Ghi chú lệnh sản xuất',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`production_order_id`),
  UNIQUE KEY `production_order_id_UNIQUE` (`production_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23516 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_ovens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_ovens` (
  `oven_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK lò sấy',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `oven_code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã lò sấy',
  `oven_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Tên lò sấy',
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'available' COMMENT 'available,in_use,maintenance',
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`oven_id`)
) ENGINE=InnoDB AUTO_INCREMENT=89767 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh mục lò sấy';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_pallet_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_pallet_items` (
  `pallet_item_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK dòng chi tiết pallet',
  `pallet_id` int unsigned NOT NULL COMMENT 'Pallet chứa bành',
  `bale_id` int unsigned NOT NULL COMMENT 'Bành được đưa vào pallet',
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`pallet_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=161542 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Liên kết bành vào pallet';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_pallet_items_old`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_pallet_items_old` (
  `pallet_item_id` int unsigned NOT NULL AUTO_INCREMENT,
  `pallet_id` int NOT NULL DEFAULT '0',
  `rubber_block_id` int NOT NULL DEFAULT '0',
  `added_at` datetime NOT NULL,
  PRIMARY KEY (`pallet_item_id`),
  UNIQUE KEY `pallet_item_id_UNIQUE` (`pallet_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=76675 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_pallet_location_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_pallet_location_history` (
  `pallet_location_history_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK lich su di chuyen pallet',
  `pallet_id` int unsigned NOT NULL COMMENT 'Pallet duoc di chuyen',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lenh san xuat cua pallet',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID cong ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nha may',
  `warehouse_id` int unsigned DEFAULT NULL COMMENT 'Kho dich den/hien tai',
  `warehouse_zone_id` int unsigned DEFAULT NULL COMMENT 'Khu vuc dich den/hien tai',
  `warehouse_location_id` int unsigned DEFAULT NULL COMMENT 'Vi tri dich den/hien tai',
  `movement_type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'inbound' COMMENT 'inbound,move,outbound,adjustment',
  `reference_type` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'pallet_run,shipping,manual,stocktake',
  `reference_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'So tham chieu',
  `moved_at` datetime NOT NULL COMMENT 'Thoi diem di chuyen',
  `moved_by` int NOT NULL DEFAULT '0' COMMENT 'Nguoi thuc hien',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`pallet_location_history_id`)
) ENGINE=InnoDB AUTO_INCREMENT=111140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Nhat ky nhap/chuyen/xuat pallet trong kho';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_pallet_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_pallet_runs` (
  `pallet_run_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK bước đóng pallet',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `pallet_run_code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã đợt đóng pallet',
  `input_bale_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Đầu vào: số bành',
  `output_pallet_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Đầu ra: số kiện pallet',
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft' COMMENT 'draft,in_progress,completed,cancelled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`pallet_run_id`)
) ENGINE=InnoDB AUTO_INCREMENT=101239 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bước 7: gom bành thành kiện pallet';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_pallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_pallets` (
  `pallet_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK pallet',
  `pallet_code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã pallet',
  `pallet_run_id` int unsigned NOT NULL COMMENT 'Đợt đóng pallet',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `warehouse_id` int unsigned DEFAULT NULL COMMENT 'Kho chứa pallet',
  `pallet_no` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã pallet trong đợt',
  `bale_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Số bành trên pallet',
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'open' COMMENT 'open,closed,stored,shipped',
  `packed_at` datetime DEFAULT NULL COMMENT 'Thời điểm đóng pallet',
  `shipped_at` datetime DEFAULT NULL COMMENT 'Thời điểm xuất kho',
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`pallet_id`)
) ENGINE=InnoDB AUTO_INCREMENT=172238 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Thông tin từng kiện pallet đầu ra';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_pallets_old`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_pallets_old` (
  `pallet_id` int unsigned NOT NULL AUTO_INCREMENT,
  `pallet_code` varchar(30) NOT NULL,
  `warehouse_id` int NOT NULL DEFAULT '0',
  `status` enum('empty','packed','shipped','cancelled') NOT NULL DEFAULT 'empty',
  `total_bales` int NOT NULL DEFAULT '0',
  `total_weight` decimal(15,2) NOT NULL DEFAULT '0.00',
  `packed_at` datetime DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `company_id` int NOT NULL DEFAULT '0',
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  PRIMARY KEY (`pallet_id`),
  UNIQUE KEY `pallet_id_UNIQUE` (`pallet_id`)
) ENGINE=InnoDB AUTO_INCREMENT=64390 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_pressing_run_quality_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_pressing_run_quality_details` (
  `pressing_quality_detail_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK chi tiết quality bước ép',
  `pressing_run_id` int unsigned NOT NULL COMMENT 'Bước ép cha',
  `grade_id` int unsigned NOT NULL COMMENT 'Loại chất lượng',
  `product_type_id` int NOT NULL DEFAULT '0',
  `input_dried_sheet_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Đầu vào số tờ sau sấy theo quality',
  `qualified_sheet_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Số tờ đạt điều kiện ép theo quality',
  `rejected_sheet_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Số tờ loại theo quality',
  `output_bale_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Đầu ra số bành theo quality',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`pressing_quality_detail_id`)
) ENGINE=InnoDB AUTO_INCREMENT=90999 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Input/output bước ép theo quality';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_pressing_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_pressing_runs` (
  `pressing_run_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK bước ép bành',
  `drying_run_id` int unsigned NOT NULL COMMENT 'Nguồn đầu vào từ bước sấy',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft' COMMENT 'draft,in_progress,completed,cancelled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`pressing_run_id`)
) ENGINE=InnoDB AUTO_INCREMENT=86984 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bước 6: ép bành';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_product_lot_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_product_lot_attachments` (
  `attachment_id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_lot_id` int NOT NULL DEFAULT '0' COMMENT 'FK → eudr_production_product_lots.product_lot_id',
  `file_id` int NOT NULL DEFAULT '0' COMMENT 'FK → eudr_general_files.file_id',
  `attachment_type` enum('contract','signature') DEFAULT NULL COMMENT 'Loại file: contract = file hợp đồng, signature = chữ ký điện tử',
  `label` varchar(255) DEFAULT NULL COMMENT 'Nhãn mô tả (tuỳ chọn)',
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`attachment_id`),
  UNIQUE KEY `attachment_id_UNIQUE` (`attachment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=54386 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_product_lot_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_product_lot_items` (
  `product_lot_item_id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_lot_id` int NOT NULL DEFAULT '0',
  `rubber_block_id` int NOT NULL DEFAULT '0',
  `weight_snapshot` decimal(15,2) NOT NULL,
  `grade_snapshot` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`product_lot_item_id`),
  UNIQUE KEY `product_lot_item_id_UNIQUE` (`product_lot_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=222553 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_product_lot_lands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_product_lot_lands` (
  `product_lot_land_id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_lot_id` int NOT NULL DEFAULT '0' COMMENT 'FK → eudr_production_product_lots',
  `plot_id` int NOT NULL DEFAULT '0' COMMENT 'FK → eudr_lands.plot_id',
  `harvest_weight` decimal(15,2) DEFAULT '0.00' COMMENT 'Sản lượng thu hoạch (kg)',
  `notes` text,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`product_lot_land_id`),
  UNIQUE KEY `product_lot_land_id_UNIQUE` (`product_lot_land_id`)
) ENGINE=InnoDB AUTO_INCREMENT=54337 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_product_lot_non_eudr_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_product_lot_non_eudr_items` (
  `item_id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_lot_id` int NOT NULL DEFAULT '0' COMMENT 'FK → eudr_production_product_lots.product_lot_id',
  `item_name` varchar(255) DEFAULT NULL COMMENT 'Tên mặt hàng / product name',
  `quantity` int NOT NULL DEFAULT '0' COMMENT 'Số lượng',
  `unit` varchar(50) DEFAULT NULL COMMENT 'Đơn vị (kg, tấn, thùng, bao…)',
  `weight` decimal(15,2) DEFAULT '0.00' COMMENT 'Khối lượng (kg)',
  `sort_order` int DEFAULT '0' COMMENT 'Thứ tự hiển thị',
  `notes` text,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `item_id_UNIQUE` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=54350 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_product_lot_transports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_product_lot_transports` (
  `product_lot_transport_id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_lot_id` int NOT NULL DEFAULT '0' COMMENT 'FK → eudr_production_product_lots',
  `vehicle_license_plate` varchar(50) DEFAULT NULL,
  `driver_name` varchar(255) DEFAULT NULL,
  `driver_phone` varchar(45) DEFAULT NULL,
  `transport_date` date DEFAULT NULL,
  `pickup_time` time DEFAULT NULL,
  `pickup_location` text,
  `delivery_time` time DEFAULT NULL,
  `delivery_location` text,
  `notes` text,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`product_lot_transport_id`),
  UNIQUE KEY `product_lot_transport_id_UNIQUE` (`product_lot_transport_id`)
) ENGINE=InnoDB AUTO_INCREMENT=53424 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_product_lots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_product_lots` (
  `product_lot_id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_lot_code` varchar(30) NOT NULL,
  `lot_type` varchar(20) NOT NULL DEFAULT 'internal' COMMENT 'Loại lot: internal (sản xuất nội bộ), external (import từ bên ngoài)',
  `eudr_type` varchar(20) NOT NULL DEFAULT 'eudr' COMMENT 'Loại EUDR: eudr (có vườn), non_eudr (không có vườn)',
  `supplier_company_name` varchar(255) DEFAULT '' COMMENT 'Tên công ty cung cấp (external lot)',
  `supplier_factory_name` varchar(255) DEFAULT '' COMMENT 'Tên nhà máy nguồn (external lot)',
  `supplier_phone` varchar(50) DEFAULT '' COMMENT 'SĐT nhà cung cấp',
  `supplier_address` text COMMENT 'Địa chỉ nhà cung cấp',
  `original_product_lot_code` varchar(50) DEFAULT '' COMMENT 'Mã lot gốc từ hệ thống bên ngoài',
  `external_contract_code` varchar(255) DEFAULT NULL COMMENT 'Mã hợp đồng từ bên ngoài (non_eudr)',
  `purchase_date` date DEFAULT NULL COMMENT 'Ngày mua/nhận lot',
  `purchase_amount` decimal(15,2) DEFAULT '0.00' COMMENT 'Giá trị mua (VND)',
  `notes` text,
  `grade_id` int NOT NULL DEFAULT '0' COMMENT 'ID cấp chất lượng sản phẩm -> eudr_production_grades',
  `grade` varchar(50) NOT NULL,
  `factory_id` int NOT NULL DEFAULT '0',
  `owner_company_id` int NOT NULL DEFAULT '0' COMMENT 'Công ty sở hữu lot hiện tại',
  `owner_id` int NOT NULL DEFAULT '0',
  `production_date_from` date DEFAULT NULL,
  `production_date_to` date DEFAULT NULL,
  `total_blocks` int NOT NULL DEFAULT '0',
  `total_weight` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('draft','confirmed','shipped','cancelled') DEFAULT 'draft',
  `confirmed_at` datetime DEFAULT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_by` int DEFAULT '0',
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`product_lot_id`),
  UNIQUE KEY `product_lot_id_UNIQUE` (`product_lot_id`)
) ENGINE=InnoDB AUTO_INCREMENT=242367 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_product_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_product_types` (
  `product_type_id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_type_code` varchar(30) NOT NULL,
  `product_type_name` varchar(150) NOT NULL,
  `product_type_category` enum('scrap_rubber','concentrated_latex') DEFAULT NULL COMMENT 'Mủ tạp và kem',
  `company_id` int NOT NULL DEFAULT '0',
  `product_weight` decimal(15,2) DEFAULT '0.00',
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`product_type_id`),
  UNIQUE KEY `product_type_id_UNIQUE` (`product_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=32606 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_rollers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_rollers` (
  `roller_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK máy cán',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `roller_code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã máy cán',
  `roller_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Tên máy cán',
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'available' COMMENT 'available,in_use,maintenance',
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`roller_id`)
) ENGINE=InnoDB AUTO_INCREMENT=91351 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh mục máy cán tờ';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_rolling_run_quality_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_rolling_run_quality_details` (
  `rolling_quality_detail_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK chi tiết quality bước cán',
  `rolling_run_id` int unsigned NOT NULL COMMENT 'Bước cán cha',
  `grade_id` int unsigned DEFAULT '0' COMMENT 'Loại chất lượng (optional, tham chiếu grade nếu có)',
  `quality_type` enum('L1','L2','L3','Mix','NA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'NA' COMMENT 'Tên loại chất lượng',
  `input_sheet_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Đầu vào số tờ theo quality',
  `output_sheet_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Đầu ra số tờ theo quality',
  `output_sheet_thickness_min_mm` decimal(6,2) NOT NULL DEFAULT '2.50' COMMENT 'Độ dày min sau cán (mm)',
  `output_sheet_thickness_max_mm` decimal(6,2) NOT NULL DEFAULT '3.50' COMMENT 'Độ dày max sau cán (mm)',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`rolling_quality_detail_id`)
) ENGINE=InnoDB AUTO_INCREMENT=99773 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Input/output bước cán theo từng quality';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_rolling_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_rolling_runs` (
  `rolling_run_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK bước cán tờ',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất',
  `cutting_run_id` int unsigned NOT NULL COMMENT 'Nguồn đầu vào từ bước cắt',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `roller_id` int unsigned DEFAULT NULL COMMENT 'Máy cán sử dụng',
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft' COMMENT 'draft,in_progress,completed,cancelled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`rolling_run_id`)
) ENGINE=InnoDB AUTO_INCREMENT=99355 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bước 3: cán tờ';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_rubber_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_rubber_blocks` (
  `rubber_block_id` int unsigned NOT NULL AUTO_INCREMENT,
  `rubber_block_code` varchar(30) NOT NULL,
  `production_order_id` int NOT NULL DEFAULT '0',
  `product_type_id` int NOT NULL DEFAULT '0',
  `weight` decimal(15,2) NOT NULL,
  `grade` varchar(50) DEFAULT NULL COMMENT 'SVR 3L, SVR10, ...',
  `production_date` date NOT NULL,
  `status` enum('available','packed','allocated','shipped','defective') DEFAULT 'available' COMMENT 'Dùng để biết bành còn dùng được không, tránh bị dùng 2 lần khi tạo lot',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`rubber_block_id`),
  UNIQUE KEY `rubber_block_id_UNIQUE` (`rubber_block_id`)
) ENGINE=InnoDB AUTO_INCREMENT=167440 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_settling_tank_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_settling_tank_runs` (
  `settling_tank_run_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK bước lắng đọng',
  `production_order_id` int unsigned NOT NULL COMMENT 'Lệnh sản xuất',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `raw_tank_id` int unsigned NOT NULL COMMENT 'Bồn nguyên liệu đầu vào',
  `settling_tank_id` int unsigned NOT NULL COMMENT 'Bồn lắng sử dụng',
  `input_latex_kg` decimal(14,3) NOT NULL DEFAULT '0.000' COMMENT 'Khối lượng mủ đầu vào bồn lắng',
  `output_latex_kg` decimal(14,3) NOT NULL DEFAULT '0.000' COMMENT 'Khối lượng mủ sau khi lắng xong',
  `loss_weight_kg` decimal(14,3) NOT NULL DEFAULT '0.000' COMMENT 'Khối lượng hao hụt trong quá trình lắng',
  `input_ph` decimal(6,3) DEFAULT NULL COMMENT 'pH đầu vào bồn lắng',
  `output_ph` decimal(6,3) DEFAULT NULL COMMENT 'pH sau lắng',
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `settling_duration_hours` int NOT NULL DEFAULT '0' COMMENT 'Thời gian lắng đọng mủ (Giờ)',
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'completed' COMMENT 'draft,in_progress,completed,cancelled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`settling_tank_run_id`)
) ENGINE=InnoDB AUTO_INCREMENT=116368 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bước lắng đọng trước khi đổ vào mương';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_settling_tanks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_settling_tanks` (
  `settling_tank_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK bồn lắng đọng',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `settling_tank_code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã bồn lắng đọng',
  `settling_tank_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Tên bồn lắng đọng',
  `capacity_kg` decimal(14,3) NOT NULL DEFAULT '0.000' COMMENT 'Sức chứa tối đa (kg)',
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'available' COMMENT 'available,in_use,cleaning,blocked',
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`settling_tank_id`)
) ENGINE=InnoDB AUTO_INCREMENT=77374 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh mục bồn lắng đọng tạm';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_workflow_template_stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_workflow_template_stages` (
  `template_stage_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK stage trong template',
  `workflow_template_id` int unsigned NOT NULL COMMENT 'Template cha',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy',
  `stage_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã công đoạn (ví dụ: STAGE_01_RAW_MATERIAL_WAREHOUSE)',
  `stage_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Tên công đoạn (ví dụ: Kho Nguyên liệu Thô)',
  `stage_order` int unsigned NOT NULL DEFAULT '1' COMMENT 'Thứ tự công đoạn trong quy trình (1-N)',
  `stage_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Loại công đoạn: warehouse, tank, channel, cutting, rolling, hanging, drying, pressing, pallet',
  `equipment_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Loại thiết bị yêu cầu: raw_tank, settling_tank, channel, cutting_machine, roller, gong_cart, oven, NULL',
  `is_required` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Bắt buộc, 0=Tùy chọn (ví dụ: bồn lắng là optional)',
  `required_duration_hours` int unsigned DEFAULT NULL COMMENT 'Thời gian bắt buộc tại stage này (giờ). Ví dụ: mương 24h, phơi 48h, sấy 72h',
  `min_duration_hours` int unsigned DEFAULT NULL COMMENT 'Thời gian tối thiểu cho phép (giờ)',
  `max_duration_hours` int unsigned DEFAULT NULL COMMENT 'Thời gian tối đa cho phép (giờ)',
  `qr_code_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Stage có QR code để công nhân scan',
  `description` text COLLATE utf8mb4_general_ci COMMENT 'Mô tả chi tiết stage',
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`template_stage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Workflow Template Stages - Định nghĩa các công đoạn trong quy trình chuẩn';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_production_workflow_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_production_workflow_templates` (
  `workflow_template_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK workflow template',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID công ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nhà máy sở hữu template',
  `workflow_template_code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã template (ví dụ: RSS_STANDARD_V1)',
  `workflow_template_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Tên quy trình chuẩn',
  `description` text COLLATE utf8mb4_general_ci COMMENT 'Mô tả chi tiết quy trình',
  `version` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1.0' COMMENT 'Phiên bản template',
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft' COMMENT 'draft,active,archived',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=Quy trình mặc định của factory',
  `created_by` int NOT NULL DEFAULT '0' COMMENT 'Admin tạo template',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`workflow_template_id`)
) ENGINE=InnoDB AUTO_INCREMENT=372633 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Workflow Template Master - Quy trình sản xuất chuẩn (thiết kế 1 lần, tái sử dụng nhiều lần)';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_purchasing_factory_receipt_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_purchasing_factory_receipt_items` (
  `factory_receipt_item_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK dòng chi tiết nhập nhà máy',
  `factory_receipt_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_factory_receipts',
  `purchase_transport_sub_tank_id` int DEFAULT NULL COMMENT 'Dòng chuyến xe nguồn; bắt buộc với phiếu nhập mới',
  `purchase_order_item_id` int DEFAULT NULL COMMENT 'Dòng phiếu thu mua liên quan',
  `sub_tank_id` int DEFAULT NULL COMMENT 'Nguồn từ bình con',
  `vehicle_tank_id` int DEFAULT NULL COMMENT 'Nguồn từ bình trên xe nếu có',
  `raw_material_tank_id` int NOT NULL DEFAULT '0' COMMENT 'Đích: bồn chứa nguyên liệu thô nhà máy',
  `rubber_type` enum('latex','cup_lump','scrap_rubber','mixed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'latex' COMMENT 'Loại mủ nhập',
  `received_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng cân được khi đổ vào nhà máy',
  `accepted_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng được chấp nhận',
  `rejected_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng bị loại bỏ',
  `ph_value` decimal(4,2) DEFAULT NULL COMMENT 'pH xác nhận',
  `nh3_percent` decimal(6,3) DEFAULT NULL COMMENT 'NH3 xác nhận',
  `impurity_percent` decimal(6,3) DEFAULT NULL COMMENT 'Tạp chất xác nhận',
  `tsc_percent` decimal(5,2) DEFAULT NULL COMMENT 'TSC xác nhận',
  `volume_before_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Thực tồn bồn trước nhập',
  `volume_after_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Thực tồn bồn sau nhập',
  `notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`factory_receipt_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=472638 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Chi tiết nhập bồn nguyên liệu thô từ phiếu thu mua';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_purchasing_factory_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_purchasing_factory_receipts` (
  `factory_receipt_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK phiếu nhập nhà máy từ thu mua',
  `factory_receipt_code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã phiếu nhập nhà máy',
  `purchase_order_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_orders',
  `purchase_transport_id` int DEFAULT NULL COMMENT 'Chuyến xe liên quan',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'Công ty nhập',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'Nhà máy nhập',
  `receipt_date` datetime NOT NULL COMMENT 'Thời điểm nhập nhà máy',
  `status` enum('draft','posted','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft' COMMENT 'Trạng thái phiếu nhập',
  `posted_at` datetime DEFAULT NULL COMMENT 'Thời điểm ghi nhận vào kho nhà máy',
  `posted_by` int DEFAULT '0' COMMENT 'Người ghi nhận',
  `notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`factory_receipt_id`)
) ENGINE=InnoDB AUTO_INCREMENT=579944 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Header phiếu nhập nhà máy phát sinh từ nghiệp vụ Thu Mua';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_purchasing_order_buyer_land_maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_purchasing_order_buyer_land_maps` (
  `purchase_order_buyer_land_map_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK map vườn-bình buyer',
  `purchase_order_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_orders',
  `purchase_order_item_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_order_items',
  `purchase_order_buyer_sub_tank_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_order_buyer_sub_tanks',
  `purchase_order_land_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_order_lands',
  `planned_receive_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng dự kiến nhận từ đúng vườn vào đúng bình buyer',
  `actual_receive_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng thực tế đã nhận từ đúng vườn vào đúng bình buyer',
  `received_at` datetime DEFAULT NULL COMMENT 'Thời điểm hoàn tất tiếp nhận cặp vườn-bình',
  `confirmed_by` int NOT NULL DEFAULT '0' COMMENT 'User buyer xác nhận khối lượng thực nhận',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int NOT NULL DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`purchase_order_buyer_land_map_id`),
  UNIQUE KEY `uq_purch_order_buyer_land_map_active` (`purchase_order_buyer_sub_tank_id`,`purchase_order_land_id`,`deleted_by`),
  KEY `idx_purch_order_buyer_land_map_order` (`purchase_order_id`,`deleted_by`),
  KEY `idx_purch_order_buyer_land_map_item` (`purchase_order_item_id`,`deleted_by`),
  KEY `idx_purch_order_buyer_land_map_buyer` (`purchase_order_buyer_sub_tank_id`,`deleted_by`),
  KEY `idx_purch_order_buyer_land_map_land` (`purchase_order_land_id`,`deleted_by`),
  CONSTRAINT `chk_purch_order_buyer_land_map_weights` CHECK (((`planned_receive_weight_kg` > 0) and (`actual_receive_weight_kg` >= 0) and (`actual_receive_weight_kg` <= `planned_receive_weight_kg`)))
) ENGINE=InnoDB AUTO_INCREMENT=461153 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Đối ứng N-N và khối lượng tiếp nhận giữa từng vườn Nông Hộ và từng bình buyer trong cùng phiếu';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_purchasing_order_buyer_seller_sub_tank_maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_purchasing_order_buyer_seller_sub_tank_maps` (
  `purchase_order_buyer_seller_sub_tank_map_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK map buyer-seller sub tank',
  `purchase_order_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_orders',
  `purchase_order_buyer_sub_tank_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_order_buyer_sub_tanks',
  `purchase_order_seller_sub_tank_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_order_seller_sub_tanks',
  `planned_transfer_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng buyer dự kiến nhận từ đúng bình seller này vào đúng bình buyer này',
  `actual_transfer_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng thực tế đã chuyển giữa cặp bình',
  `transferred_at` datetime DEFAULT NULL COMMENT 'Thời điểm hoàn tất sang nhận giữa cặp bình',
  `confirmed_by` int DEFAULT '0' COMMENT 'User buyer xác nhận khối lượng thực nhận của cặp bình',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`purchase_order_buyer_seller_sub_tank_map_id`)
) ENGINE=InnoDB AUTO_INCREMENT=579949 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Đối ứng N-N và khối lượng chuyển thực tế giữa từng cặp bình buyer-seller trong cùng phiếu';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_purchasing_order_buyer_sub_tanks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_purchasing_order_buyer_sub_tanks` (
  `purchase_order_buyer_sub_tank_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK bình con bên mua trên phiếu',
  `purchase_order_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_orders',
  `sub_tank_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_sub_tanks; bảng này thể hiện vai trò buyer của bình trong phiếu',
  `buyer_company_id` int NOT NULL DEFAULT '0' COMMENT 'Snapshot công ty sở hữu bình bên mua; phải khớp purchase_order.buyer_company_id',
  `assigned_by` int NOT NULL DEFAULT '0' COMMENT 'User phía buyer chọn bình nhận mủ',
  `purchase_order_item_id` int DEFAULT NULL COMMENT 'Dòng hàng liên quan',
  `planned_receive_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng dự kiến nhận vào bình buyer',
  `actual_receive_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng thực tế buyer nhận',
  `received_at` datetime DEFAULT NULL COMMENT 'Thời điểm buyer tiếp nhận vào bình',
  `status` enum('assigned','receiving','received','transferred','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'assigned' COMMENT 'Trạng thái bình bên mua trên phiếu',
  `notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`purchase_order_buyer_sub_tank_id`)
) ENGINE=InnoDB AUTO_INCREMENT=476672 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh sách bình con bên mua gán vào phiếu để nhận mủ từ một hoặc nhiều bình seller';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_purchasing_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_purchasing_order_items` (
  `purchase_order_item_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK dòng chi tiết phiếu thu mua',
  `purchase_order_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_orders',
  `rubber_type` enum('latex','cup_lump','scrap_rubber','mixed','other') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'latex' COMMENT 'Loại mủ',
  `quality_basis` enum('kg','tsc','drc','fixed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'kg' COMMENT 'Cơ sở tính đơn giá',
  `quality_value` decimal(10,3) DEFAULT NULL COMMENT 'Giá trị TSC/DRC nếu áp dụng',
  `quantity` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Số lượng tổng quát',
  `weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng quy đổi kg',
  `unit_price` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT 'Đơn giá/m3 hoặc VND/kg',
  `line_amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT 'Thành tiền dòng hàng',
  `notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Ghi chú chi tiết',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`purchase_order_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=334305 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Chi tiết loại mủ, chất lượng, khối lượng, đơn giá, thành tiền của phiếu thu mua';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_purchasing_order_lands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_purchasing_order_lands` (
  `purchase_order_land_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK vườn trên phiếu thu mua',
  `purchase_order_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_orders',
  `purchase_order_item_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_order_items',
  `plot_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_lands.plot_id',
  `seller_source_type` enum('system_user','vendor') COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nguồn seller tại thời điểm mua',
  `farmer_user_id` int DEFAULT NULL COMMENT 'Nông hộ sở hữu/bán vườn nếu seller là system_user',
  `vendor_id` int DEFAULT NULL COMMENT 'Vendor sở hữu/ủy quyền vườn nếu seller là vendor',
  `land_code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Snapshot mã vườn',
  `land_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Snapshot tên vườn',
  `farmer_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Snapshot chủ vườn',
  `land_area` decimal(10,2) DEFAULT NULL COMMENT 'Snapshot diện tích ha',
  `harvest_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Sản lượng từ vườn trên dòng mua',
  `purchased_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng thực mua từ vườn',
  `notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`purchase_order_land_id`)
) ENGINE=InnoDB AUTO_INCREMENT=437305 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Nguồn vườn và snapshot truy xuất trên phiếu thu mua';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_purchasing_order_seller_sub_tanks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_purchasing_order_seller_sub_tanks` (
  `purchase_order_seller_sub_tank_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK bình con bên bán trên phiếu',
  `purchase_order_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_orders',
  `sub_tank_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_sub_tanks; bảng này thể hiện vai trò seller của bình trong phiếu',
  `seller_company_id` int NOT NULL DEFAULT '0' COMMENT 'Snapshot công ty sở hữu bình bên bán; phải khớp purchase_order.seller_company_id',
  `declared_by` int NOT NULL DEFAULT '0' COMMENT 'User phía seller chọn bình và khai báo khối lượng',
  `purchase_order_item_id` int DEFAULT NULL COMMENT 'Dòng hàng liên quan nếu cần tách theo mặt hàng',
  `filled_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng seller khai báo trong bình',
  `estimated_tsc_percent` decimal(5,2) DEFAULT NULL COMMENT 'TSC ước tính seller khai báo',
  `estimated_drc_percent` decimal(5,2) DEFAULT NULL COMMENT 'DRC ước tính seller khai báo',
  `sealed_at` datetime DEFAULT NULL COMMENT 'Thời điểm seller niêm phong bình',
  `sealed_by` int DEFAULT '0' COMMENT 'Người seller niêm phong',
  `status` enum('declared','seller_confirmed','loaded','received','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'declared' COMMENT 'Trạng thái bình bên bán trên phiếu',
  `notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`purchase_order_seller_sub_tank_id`),
  UNIQUE KEY `uq_purch_order_seller_sub_tank_active` (`purchase_order_id`,`sub_tank_id`,`deleted_by`),
  KEY `idx_purch_order_seller_sub_tank_company` (`seller_company_id`,`deleted_by`),
  KEY `idx_purch_order_seller_sub_tank_item` (`purchase_order_item_id`,`deleted_by`),
  KEY `idx_purch_order_seller_sub_tank_status` (`status`,`deleted_by`),
  CONSTRAINT `chk_purch_order_seller_weight` CHECK ((`filled_weight_kg` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=374315 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh sách bình con seller khai báo trên phiếu trước khi xác nhận và buyer nhận mủ; có thể là nông hộ, thu mua khác, công ty/trader hoặc vendor ngoài hệ thống';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_purchasing_order_status_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_purchasing_order_status_logs` (
  `purchase_order_status_log_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK lịch sử trạng thái phiếu',
  `purchase_order_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_orders',
  `from_status` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Trạng thái trước khi chuyển',
  `to_status` varchar(40) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Trạng thái sau khi chuyển',
  `actor_user_id` int NOT NULL DEFAULT '0' COMMENT 'Người thực hiện thao tác',
  `actor_role` enum('buyer','seller','factory','system') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'buyer' COMMENT 'Vai trò thực hiện',
  `action_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Loại thao tác: send, confirm, re-confirm, cancel, close',
  `notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Ghi chú thao tác',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`purchase_order_status_log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=373138 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Nhật ký thay đổi trạng thái và các lần xác nhận phiếu thu mua giữa bên mua và bên bán';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_purchasing_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_purchasing_orders` (
  `purchase_order_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK phiếu thu mua',
  `purchase_order_code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã phiếu thu mua',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'Công ty bên mua',
  `buyer_user_id` int NOT NULL DEFAULT '0' COMMENT 'Người thu mua tạo phiếu',
  `buyer_company_id` int NOT NULL DEFAULT '0' COMMENT 'Công ty bên mua snapshot/logic',
  `buyer_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Snapshot tên bên mua',
  `buyer_phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Snapshot SĐT bên mua',
  `buyer_address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Snapshot địa chỉ bên mua',
  `seller_source_type` enum('system_user','vendor') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'system_user' COMMENT 'Bên bán trong hệ thống hay vendor ngoài hệ thống',
  `seller_user_id` int DEFAULT NULL COMMENT 'User bên bán nếu có',
  `seller_vendor_id` int DEFAULT NULL COMMENT 'Vendor bên bán nếu ngoài hệ thống',
  `seller_company_id` int DEFAULT '0' COMMENT 'Công ty bên bán nếu seller là company/trader',
  `seller_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Snapshot tên bên bán',
  `seller_phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Snapshot SĐT bên bán',
  `seller_address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Snapshot địa chỉ bên bán',
  `seller_account_type` enum('farmer','purchaser','trader','company','vendor') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'farmer' COMMENT 'Loại tài khoản bên bán tại thời điểm tạo phiếu',
  `connection_id` int DEFAULT NULL COMMENT 'Liên kết eudr_connections nếu giao dịch trong hệ thống',
  `legacy_transaction_ticket_id` int DEFAULT NULL COMMENT 'Liên kết transaction ticket nếu cần đồng bộ ngược',
  `purchase_date` datetime NOT NULL COMMENT 'Thời điểm tạo hoặc xác nhận mua',
  `expected_delivery_at` datetime DEFAULT NULL COMMENT 'Dự kiến giao về nhà máy/kho',
  `currency` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'VND' COMMENT 'Loại tiền',
  `total_quantity` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Tổng số lượng/quy đổi tổng',
  `total_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Tổng khối lượng mủ quy đổi kg',
  `total_estimated_amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT 'Tổng giá trị ước tính',
  `status` enum('draft','sent_to_seller','seller_confirmed','buyer_reconfirmed','transport_planned','in_transit','arrived_factory','quality_checked','received_closed','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft' COMMENT 'Trạng thái nghiệp vụ của phiếu',
  `seller_confirmed_at` datetime DEFAULT NULL COMMENT 'Thời điểm bên bán xác nhận',
  `buyer_reconfirmed_at` datetime DEFAULT NULL COMMENT 'Thời điểm bên mua xác nhận lại sau khi seller cập nhật',
  `closed_at` datetime DEFAULT NULL COMMENT 'Thời điểm kết thúc nghiệp vụ',
  `cancelled_at` datetime DEFAULT NULL COMMENT 'Thời điểm hủy phiếu',
  `cancel_reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Lý do hủy phiếu',
  `notes` text COLLATE utf8mb4_general_ci COMMENT 'Ghi chú header',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`purchase_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=376349 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Header phiếu thu mua giữa bên mua và bên bán; có thể là nông hộ, thu mua khác, công ty/trader hoặc vendor ngoài hệ thống';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_purchasing_quality_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_purchasing_quality_checks` (
  `quality_check_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK phiếu/lần kiểm tra chất lượng',
  `purchase_order_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_orders',
  `purchase_transport_id` int DEFAULT NULL COMMENT 'Chuyến xe liên quan',
  `sub_tank_id` int DEFAULT NULL COMMENT 'Bình con được lấy mẫu',
  `vehicle_tank_id` int DEFAULT NULL COMMENT 'Bình trên xe nếu lấy mẫu trực tiếp trên xe',
  `inspected_at` datetime NOT NULL COMMENT 'Thời điểm kiểm tra',
  `inspector_user_id` int NOT NULL DEFAULT '0' COMMENT 'Người kiểm tra',
  `sample_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Mã mẫu',
  `latex_color` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Màu sắc mủ',
  `ph_value` decimal(4,2) DEFAULT NULL COMMENT 'Độ pH',
  `nh3_percent` decimal(6,3) DEFAULT NULL COMMENT 'Hàm lượng NH3',
  `impurity_percent` decimal(6,3) DEFAULT NULL COMMENT 'Tạp chất',
  `tsc_percent` decimal(5,2) DEFAULT NULL COMMENT 'TSC',
  `drc_percent` decimal(5,2) DEFAULT NULL COMMENT 'DRC',
  `temperature_c` decimal(5,2) DEFAULT NULL COMMENT 'Nhiệt độ mẫu',
  `result` enum('pass','fail','conditional') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pass' COMMENT 'Kết quả kiểm tra',
  `disposition` enum('accept','reblend','reject') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'accept' COMMENT 'Hướng xử lý sau kiểm tra',
  `fail_reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Lý do fail nếu có',
  `notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`quality_check_id`)
) ENGINE=InnoDB AUTO_INCREMENT=476237 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Kết quả kiểm tra chất lượng mủ trong quy trình thu mua';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_purchasing_sub_tank_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_purchasing_sub_tank_history` (
  `sub_tank_history_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK lịch sử bình con',
  `sub_tank_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_sub_tanks',
  `purchase_order_id` int DEFAULT NULL COMMENT 'Phiếu thu mua liên quan nếu có',
  `entity_type` enum('intake','transfer_to_factory_tank','factory_receipt','transport','manual_adjustment','cancellation') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'intake' COMMENT 'Nguồn phát sinh giao dịch bình con',
  `entity_id` int NOT NULL DEFAULT '0' COMMENT 'ID bản ghi nguồn phát sinh giao dịch bình con',
  `action_type` enum('input','output','transfer','adjustment') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'input' COMMENT 'Loại biến động khối lượng',
  `rubber_type` enum('latex','cup_lump','scrap_rubber','mixed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'latex',
  `qty_in_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng nhập vào bình',
  `qty_out_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng xuất khỏi bình',
  `weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Giá trị biến động tổng quát để truy vấn nhanh',
  `ph_value` decimal(4,2) DEFAULT NULL COMMENT 'pH mẫu mủ',
  `nh3_percent` decimal(6,3) DEFAULT NULL COMMENT 'Hàm lượng NH3',
  `impurity_percent` decimal(6,3) DEFAULT NULL COMMENT 'Tạp chất',
  `tsc_percent` decimal(5,2) DEFAULT NULL COMMENT 'TSC nếu có',
  `temperature_c` decimal(5,2) DEFAULT NULL COMMENT 'Nhiệt độ mẫu',
  `volume_before_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng trước giao dịch',
  `volume_after_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng sau giao dịch',
  `event_time` datetime NOT NULL COMMENT 'Thời điểm phát sinh',
  `operator_user_id` int NOT NULL DEFAULT '0' COMMENT 'Người thao tác (thu mua, vận chuyển, nhà máy)',
  `notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`sub_tank_history_id`),
  KEY `idx_purch_sub_history_tank_time` (`sub_tank_id`,`event_time`),
  KEY `idx_purch_sub_history_order` (`purchase_order_id`),
  KEY `idx_purch_sub_history_entity` (`entity_type`,`entity_id`)
) ENGINE=InnoDB AUTO_INCREMENT=576256 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lịch sử biến động khối lượng của từng bình con';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_purchasing_sub_tank_intake_land_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_purchasing_sub_tank_intake_land_allocations` (
  `sub_tank_intake_land_allocation_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK phân bổ thực nhận',
  `sub_tank_intake_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_sub_tank_intakes',
  `purchase_order_id` int NOT NULL DEFAULT '0' COMMENT 'Snapshot phiếu thu mua',
  `purchase_order_item_id` int NOT NULL DEFAULT '0' COMMENT 'Snapshot dòng hàng',
  `purchase_order_land_id` int NOT NULL DEFAULT '0' COMMENT 'Snapshot vườn nguồn',
  `purchase_order_buyer_land_map_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_order_buyer_land_maps',
  `received_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng thực nhận từ mapping vườn trong intake',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int NOT NULL DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`sub_tank_intake_land_allocation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=364136 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Phân bổ N-N khối lượng thực nhận từ một intake về các mapping vườn-bình buyer';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_purchasing_sub_tank_intake_mapping_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_purchasing_sub_tank_intake_mapping_allocations` (
  `sub_tank_intake_mapping_allocation_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK phân bổ thực nhận theo cặp bình',
  `sub_tank_intake_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_sub_tank_intakes',
  `purchase_order_id` int NOT NULL DEFAULT '0' COMMENT 'Snapshot phiếu thu mua',
  `purchase_order_buyer_seller_sub_tank_map_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> mapping bình seller-buyer',
  `purchase_order_buyer_sub_tank_id` int NOT NULL DEFAULT '0' COMMENT 'Snapshot bình buyer trên phiếu',
  `purchase_order_seller_sub_tank_id` int NOT NULL DEFAULT '0' COMMENT 'Snapshot bình seller trên phiếu',
  `received_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng thực nhận từ mapping trong intake',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`sub_tank_intake_mapping_allocation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=464437 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Phân bổ thực nhận của intake theo từng cặp bình seller-buyer';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_purchasing_sub_tank_intakes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_purchasing_sub_tank_intakes` (
  `sub_tank_intake_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK nhật ký tiếp nhận mủ vào bình con',
  `sub_tank_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_sub_tanks',
  `purchase_order_id` int DEFAULT NULL COMMENT 'FK logic -> eudr_purchasing_orders; NULL nếu tiếp nhận ngoài phiếu',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'Công ty tiếp nhận mủ',
  `purchaser_user_id` int NOT NULL DEFAULT '0' COMMENT 'Người thu mua tiếp nhận',
  `seller_source_type` enum('system_user','vendor') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'system_user' COMMENT 'Nguồn bên bán',
  `farmer_user_id` int NOT NULL DEFAULT '0' COMMENT 'Nông hộ bán mủ nếu là user hệ thống',
  `vendor_id` int DEFAULT NULL COMMENT 'Vendor bán mủ nếu là bên ngoài hệ thống',
  `transaction_ticket_id` int DEFAULT NULL COMMENT 'Liên kết transaction ticket nếu có',
  `transaction_ticket_code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Snapshot mã phiếu giao dịch',
  `intake_no` int NOT NULL DEFAULT '1' COMMENT 'Số thứ tự lần nhận trong cùng 1 bình con',
  `rubber_type` enum('latex','cup_lump','scrap_rubber','mixed') COLLATE utf8mb4_general_ci DEFAULT 'latex' COMMENT 'Loại mủ được nhận',
  `received_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng mủ nhận vào bình',
  `latex_color` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Màu sắc mủ',
  `ph_value` decimal(4,2) DEFAULT NULL COMMENT 'Độ pH',
  `nh3_percent` decimal(6,3) DEFAULT NULL COMMENT 'Hàm lượng NH3',
  `impurity_percent` decimal(6,3) DEFAULT NULL COMMENT 'Tạp chất',
  `tsc_percent` decimal(5,2) DEFAULT NULL COMMENT 'TSC nếu có',
  `temperature_c` decimal(5,2) DEFAULT NULL COMMENT 'Nhiệt độ mẫu',
  `received_at` datetime NOT NULL COMMENT 'Thời gian tiếp nhận mủ vào bình con',
  `harvested_at` datetime DEFAULT NULL COMMENT 'Thời điểm thu hoạch nếu có',
  `volume_before_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng trước khi nhận',
  `volume_after_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng sau khi nhận',
  `received_by` int NOT NULL DEFAULT '0' COMMENT 'User thực hiện tiếp nhận',
  `notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Ghi chú',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`sub_tank_intake_id`)
) ENGINE=InnoDB AUTO_INCREMENT=372242 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Nhật ký tiếp nhận mủ vào bình con';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_purchasing_sub_tanks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_purchasing_sub_tanks` (
  `sub_tank_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK bình con',
  `sub_tank_code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã bình con',
  `sub_tank_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Tên bình con',
  `qr_code` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Mã QR định danh bình con',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'Công ty quản lý bình con',
  `manager_user_id` int NOT NULL DEFAULT '0' COMMENT 'Người phụ trách bình con (buyer/seller)',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'Nhà máy đích/default factory',
  `rubber_type` enum('latex','cup_lump','scrap_rubber','mixed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'latex' COMMENT 'Loại mủ chua chính của bình con',
  `capacity_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Sức chứa tối đa của bình con',
  `current_volume_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng hiện tại trong bình',
  `location` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Vị trí hiện tại của bình',
  `status` enum('idle','in_use','sealed','transporting','cleaning','damaged','inactive','maintenance','full') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'idle' COMMENT 'Trạng thái bình con',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`sub_tank_id`)
) ENGINE=InnoDB AUTO_INCREMENT=172255 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh mục bình con dùng trong nghiệp vụ Thu Mua; vai trò buyer/seller được phân biệt ở bảng nghiệp vụ liên kết';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_purchasing_transport_sub_tanks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_purchasing_transport_sub_tanks` (
  `purchase_transport_sub_tank_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK bình con trên chuyến xe',
  `purchase_transport_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_transports',
  `sub_tank_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_sub_tanks',
  `seller_sub_tank_ref_id` int DEFAULT NULL COMMENT 'Ref -> eudr_purchasing_order_seller_sub_tanks',
  `buyer_sub_tank_ref_id` int DEFAULT NULL COMMENT 'Ref -> eudr_purchasing_order_buyer_sub_tanks',
  `vehicle_tank_id` int DEFAULT NULL COMMENT 'FK logic -> eudr_vehicle_tanks nếu sang chiết vào bình trên xe',
  `loaded_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng khi lên xe',
  `loaded_at` datetime DEFAULT NULL COMMENT 'Thời điểm lên xe',
  `unloaded_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng khi đổ xuống',
  `unloaded_at` datetime DEFAULT NULL COMMENT 'Thời điểm đổ xuống',
  `loss_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Hao hụt vận chuyển',
  `notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`purchase_transport_sub_tank_id`)
) ENGINE=InnoDB AUTO_INCREMENT=172250 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh sách bình con tham gia trên từng chuyến xe thu mua';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_purchasing_transports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_purchasing_transports` (
  `purchase_transport_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK chuyến vận chuyển thu mua',
  `purchase_transport_code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã chuyến vận chuyển',
  `purchase_order_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_purchasing_orders',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'Công ty bên mua',
  `source_location` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Điểm lấy hàng',
  `destination_factory_id` int NOT NULL DEFAULT '0' COMMENT 'Nhà máy đích',
  `vehicle_id` int DEFAULT NULL COMMENT 'FK logic -> eudr_transportation_vehicle',
  `vehicle_license_plate` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Snapshot biển số xe',
  `driver_user_id` int DEFAULT NULL COMMENT 'Tài xế trong hệ thống',
  `driver_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Snapshot tên tài xế',
  `driver_phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Snapshot SĐT tài xế',
  `departed_at` datetime DEFAULT NULL COMMENT 'Thời điểm rời điểm lấy',
  `arrived_at` datetime DEFAULT NULL COMMENT 'Thời điểm đến nhà máy',
  `status` enum('planned','loading','in_transit','arrived','cancelled','closed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'planned' COMMENT 'Trạng thái chuyến xe',
  `seal_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Số niêm phong chuyến xe',
  `notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`purchase_transport_id`)
) ENGINE=InnoDB AUTO_INCREMENT=676262 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Header chuyến vận chuyển cho phiếu thu mua';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_role_permissions` (
  `role_permission_id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  PRIMARY KEY (`role_permission_id`),
  UNIQUE KEY `id_UNIQUE` (`role_permission_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_roles` (
  `role_id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_id_UNIQUE` (`role_id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=87427 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_sales_contract_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_sales_contract_files` (
  `contract_file_id` int unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int NOT NULL DEFAULT '0',
  `company_id` int NOT NULL DEFAULT '0',
  `file_id` int NOT NULL DEFAULT '0',
  `label` varchar(255) DEFAULT NULL COMMENT 'Nhãn mô tả',
  `uploaded_at` datetime NOT NULL,
  `uploaded_by` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`contract_file_id`),
  UNIQUE KEY `contract_file_id_UNIQUE` (`contract_file_id`)
) ENGINE=InnoDB AUTO_INCREMENT=72892 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_sales_contract_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_sales_contract_items` (
  `contract_item_id` int unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int NOT NULL DEFAULT '0',
  `company_id` int NOT NULL DEFAULT '0',
  `product_id` int NOT NULL DEFAULT '0' COMMENT 'SKU thành phẩm',
  `uom` varchar(30) NOT NULL COMMENT 'Đơn vị tính',
  `qty_committed` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Sản lượng cam kết',
  `price` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Đơn giá',
  `currency` varchar(10) NOT NULL DEFAULT 'VND',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`contract_item_id`),
  UNIQUE KEY `contract_item_id_UNIQUE` (`contract_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=57574 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_sales_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_sales_contracts` (
  `contract_id` int unsigned NOT NULL AUTO_INCREMENT,
  `contract_code` varchar(30) NOT NULL,
  `company_id` int NOT NULL DEFAULT '0',
  `customer_id` int NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL COMMENT 'Tên/tóm tắt hợp đồng',
  `start_date` date NOT NULL COMMENT 'Ngày hiệu lực',
  `end_date` date DEFAULT NULL COMMENT 'Ngày hết hạn',
  `payment_terms` varchar(255) DEFAULT NULL COMMENT 'Điều khoản thanh toán (NET30,…)',
  `delivery_terms` varchar(255) DEFAULT NULL COMMENT 'Điều khoản giao (FOB/CIF/địa điểm)',
  `currency` varchar(10) NOT NULL DEFAULT 'VND' COMMENT 'Tiền tệ',
  `status` enum('draft','active','expired','terminated','cancelled') NOT NULL DEFAULT 'draft',
  `signed_at` datetime DEFAULT NULL COMMENT 'Thời điểm ký/duyệt',
  `signed_by` int DEFAULT '0' COMMENT 'User duyệt/ ký',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`contract_id`),
  UNIQUE KEY `contract_id_UNIQUE` (`contract_id`)
) ENGINE=InnoDB AUTO_INCREMENT=67127 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_sales_customer_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_sales_customer_contacts` (
  `customer_contact_id` int unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL DEFAULT '0',
  `company_id` int NOT NULL DEFAULT '0',
  `customer_name` varchar(255) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position` varchar(150) DEFAULT NULL COMMENT 'Chức vụ',
  `notes` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT '0' COMMENT '1: liên hệ chính',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`customer_contact_id`),
  UNIQUE KEY `customer_contact_id_UNIQUE` (`customer_contact_id`)
) ENGINE=InnoDB AUTO_INCREMENT=52877 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_sales_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_sales_customers` (
  `customer_id` int unsigned NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(30) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `customer_company_name` varchar(255) DEFAULT NULL,
  `business_license_file_ids` varchar(50) DEFAULT NULL,
  `company_id` int NOT NULL DEFAULT '0',
  `tax_code` varchar(50) DEFAULT NULL COMMENT 'Mã số thuế',
  `billing_address` varchar(255) DEFAULT NULL COMMENT 'Địa chỉ xuất hóa đơn',
  `shipping_address` varchar(255) DEFAULT NULL COMMENT 'Địa chỉ giao hàng',
  `customer_type` varchar(50) DEFAULT NULL COMMENT 'Phân loại (enterprise/retail/…)',
  `status` enum('active','inactive') DEFAULT 'active',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `customer_id_UNIQUE` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=42604 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_sales_issue_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_sales_issue_allocations` (
  `issue_allocation_id` int unsigned NOT NULL AUTO_INCREMENT,
  `issue_item_id` int NOT NULL DEFAULT '0' COMMENT 'Tham chiếu dòng phiếu xuất',
  `sale_order_item_id` int NOT NULL DEFAULT '0' COMMENT 'Tham chiếu dòng đơn (để truy nhanh)',
  `product_tank_id` int NOT NULL DEFAULT '0' COMMENT 'Bồn thành phẩm (nếu áp dụng)',
  `raw_material_tank_id` int DEFAULT '0' COMMENT 'Bồn nguyên liệu (nếu áp dụng)',
  `transaction_ticket_id` int DEFAULT NULL COMMENT 'Phiếu giao dịch (nếu áp dụng)',
  `lot_id` int DEFAULT '0' COMMENT 'Lô/batch (nếu có)',
  `qty_issued` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Số lượng xuất từ bồn/lô',
  `weight_issued` decimal(15,2) DEFAULT '0.00' COMMENT 'Khối lượng xuất (nếu cần)',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`issue_allocation_id`),
  UNIQUE KEY `issue_allocation_id_UNIQUE` (`issue_allocation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=67364 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_sales_issue_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_sales_issue_items` (
  `issue_item_id` int unsigned NOT NULL AUTO_INCREMENT,
  `issue_id` int NOT NULL DEFAULT '0' COMMENT 'Tham chiếu phiếu xuất',
  `sale_order_item_id` int NOT NULL DEFAULT '0' COMMENT 'Tham chiếu dòng đơn hàng',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'Thuộc công ty',
  `source_type` varchar(30) NOT NULL DEFAULT 'finished_product' COMMENT 'Loại nguồn: finished_product, raw_material, product_lot',
  `product_id` int NOT NULL DEFAULT '0' COMMENT 'ID loại thành phẩm',
  `product_lot_id` int DEFAULT NULL COMMENT 'ID lô thành phẩm (product lot)',
  `uom` varchar(50) NOT NULL COMMENT 'Đơn vị tính',
  `qty_issued` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Số lượng xuất',
  `price` decimal(15,2) DEFAULT '0.00' COMMENT 'Giá tại thời điểm xuất (nếu cần)',
  `currency` varchar(10) DEFAULT NULL COMMENT 'Tiền tệ (nếu lưu giá)',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`issue_item_id`),
  UNIQUE KEY `issue_item_id_UNIQUE` (`issue_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=63463 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_sales_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_sales_issues` (
  `issue_id` int unsigned NOT NULL AUTO_INCREMENT,
  `issue_code` varchar(30) NOT NULL,
  `sale_order_id` int NOT NULL DEFAULT '0' COMMENT 'Tham chiếu đơn hàng',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'Thuộc công ty',
  `warehouse_id` int DEFAULT '0' COMMENT 'Kho xuất',
  `issue_date` datetime NOT NULL COMMENT 'Ngày/giờ xuất',
  `status` enum('draft','issued','cancelled') NOT NULL DEFAULT 'draft' COMMENT 'Trạng thái phiếu',
  `document_ref` varchar(100) DEFAULT NULL COMMENT 'Số chứng từ/đơn giao ngoài',
  `shipper` varchar(150) DEFAULT NULL COMMENT 'Hãng vận chuyển/tài xế',
  `vehicle_no` varchar(50) DEFAULT NULL COMMENT 'Biển số xe',
  `receiver` varchar(150) DEFAULT NULL COMMENT 'Người/đơn vị nhận',
  `reason_code` varchar(50) DEFAULT NULL COMMENT 'Mã lý do xuất (nếu dùng)',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` int DEFAULT '0',
  PRIMARY KEY (`issue_id`),
  UNIQUE KEY `issue_id_UNIQUE` (`issue_id`)
) ENGINE=InnoDB AUTO_INCREMENT=73916 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_sales_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_sales_order_items` (
  `sale_order_item_id` int unsigned NOT NULL AUTO_INCREMENT,
  `sale_order_id` int NOT NULL DEFAULT '0',
  `company_id` int NOT NULL DEFAULT '0',
  `source_type` varchar(30) NOT NULL DEFAULT 'finished_product' COMMENT 'Loại nguồn: finished_product, raw_material, product_lot',
  `transaction_ticket_id` int DEFAULT NULL COMMENT 'ID phiếu giao dịch (nếu có)',
  `raw_material_tank_id` int DEFAULT NULL COMMENT 'ID bồn nguyên liệu (nếu có)',
  `product_tank_id` int NOT NULL DEFAULT '0' COMMENT 'ID bồn chứa thành phẩm',
  `product_type_id` int NOT NULL DEFAULT '0' COMMENT 'ID loại sản phẩm',
  `product_lot_id` int NOT NULL DEFAULT '0' COMMENT 'ID lô thành phẩm (product lot)',
  `rubber_type` varchar(50) DEFAULT NULL COMMENT 'Loại cao su',
  `quality_grade` decimal(9,4) DEFAULT NULL COMMENT 'Hạng chất lượng',
  `uom` varchar(30) NOT NULL COMMENT 'Đơn vị tính',
  `qty_ordered` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT ' Số lượng đặt',
  `qty_allocated` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Đã phân bổ tồn (nếu có)',
  `qty_shipped` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Đã giao (nếu có)',
  `price` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Đơn giá',
  `discount_rate` decimal(9,4) DEFAULT NULL COMMENT '% giảm giá (nếu có)',
  `surcharge` decimal(15,2) DEFAULT NULL COMMENT 'Phụ phí (nếu có)',
  `currency` varchar(10) NOT NULL DEFAULT 'VND' COMMENT 'Tiền tệ',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`sale_order_item_id`),
  UNIQUE KEY `sale_order_item_id_UNIQUE` (`sale_order_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=76177 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_sales_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_sales_orders` (
  `sale_order_id` int unsigned NOT NULL AUTO_INCREMENT,
  `sale_order_code` varchar(30) NOT NULL,
  `company_id` int NOT NULL DEFAULT '0',
  `customer_id` int NOT NULL DEFAULT '0',
  `buyer_company_id` int NOT NULL DEFAULT '0' COMMENT 'Công ty bên mua (Trader-to-Trader)',
  `buyer_user_id` int NOT NULL DEFAULT '0' COMMENT 'Người mua (user_id bên mua)',
  `contract_id` int DEFAULT '0' COMMENT 'Tham chiếu hợp đồng (nếu có)',
  `quotation_id` int DEFAULT '0' COMMENT 'Tham chiếu báo giá (nếu có)',
  `order_date` date NOT NULL COMMENT 'Ngày đặt hàng',
  `delivery_date` date DEFAULT NULL COMMENT 'Ngày giao hàng',
  `order_source_type` varchar(30) NOT NULL DEFAULT 'warehouse' COMMENT 'Nguồn đơn hàng: warehouse, transaction_ticket, product_lot',
  `currency` varchar(10) NOT NULL DEFAULT 'VND' COMMENT 'Tiền tệ',
  `payment_terms` varchar(255) DEFAULT NULL COMMENT 'Điều khoản thanh toán',
  `delivery_address` varchar(255) DEFAULT NULL COMMENT 'Địa chỉ giao',
  `notes` varchar(255) DEFAULT NULL,
  `status` enum('draft','pending','approved','allocated','shipping','closed','cancelled') NOT NULL DEFAULT 'draft',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Tổng tiền (tính từ dòng)',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`sale_order_id`),
  UNIQUE KEY `sale_order_id_UNIQUE` (`sale_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=58268 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_settings` (
  `setting_id` int unsigned NOT NULL AUTO_INCREMENT,
  `setting_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '0',
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '0',
  `time` int unsigned DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `company_id` int DEFAULT NULL,
  PRIMARY KEY (`setting_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_sms_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_sms_logs` (
  `sms_id` int unsigned NOT NULL AUTO_INCREMENT,
  `sms_code` varchar(30) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `otp_code` varchar(6) DEFAULT NULL,
  `message` text NOT NULL,
  `request_payload` text,
  `response_payload` text,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `provider` varchar(50) DEFAULT NULL COMMENT 'Nhà cung cấp dịch vụ: FPT, VGs, ...',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`sms_id`),
  UNIQUE KEY `sms_id_UNIQUE` (`sms_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_sms_otp_rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_sms_otp_rate_limits` (
  `otp_rate_limit_id` int unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) NOT NULL,
  `purpose` enum('register','login','reset_password') NOT NULL COMMENT 'Loại OTP (Đăng ký, đăng nhập, ...)',
  `request_count` int NOT NULL DEFAULT '1' COMMENT 'Tổng số lần gửi OTP trong 1 khoảng thời gian (ví dụ 5 phút).',
  `last_request_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Lần gửi OTP cuối cùng',
  `locked_until` datetime DEFAULT NULL COMMENT 'Nếu spam quá giới hạn → khóa số này cho đến thời điểm này',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`otp_rate_limit_id`),
  UNIQUE KEY `otp_rate_limit_id_UNIQUE` (`otp_rate_limit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4449 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_sms_otp_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_sms_otp_requests` (
  `otp_request_id` int unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `purpose` enum('register','login','reset_password') NOT NULL COMMENT 'Loại OTP (Đăng ký, đăng nhập, ...)',
  `attempt_count` int NOT NULL DEFAULT '0' COMMENT 'Đếm số lần nhập OTP sai. Dùng để khóa nếu nhập sai quá số lần quy định',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Trạng thái đã xác thực OTP hay chưa',
  `expires_at` datetime NOT NULL COMMENT 'Thời gian hết hạn',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `verified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`otp_request_id`),
  UNIQUE KEY `otp_requests_id_UNIQUE` (`otp_request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5565 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_tanks_finished_goods_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_tanks_finished_goods_receipts` (
  `finished_goods_receipt_id` int unsigned NOT NULL AUTO_INCREMENT,
  `finished_goods_receipt_code` varchar(30) NOT NULL,
  `finished_goods_receipt_name` varchar(255) NOT NULL,
  `company_id` int NOT NULL DEFAULT '0',
  `production_order_id` int NOT NULL DEFAULT '0',
  `factory_id` int DEFAULT '0',
  `product_type_category` enum('scrap_rubber','concentrated_latex') DEFAULT NULL,
  `product_type_id` int NOT NULL DEFAULT '0',
  `product_tank_id` int NOT NULL DEFAULT '0',
  `actual_quantity` int NOT NULL DEFAULT '0' COMMENT 'Số lượng đơn vị thực tế sản xuất được',
  `actual_weight` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng thực tế (KG)',
  `tank_volume_before` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tank_volume_after` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('draft','verified','completed') DEFAULT 'completed',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`finished_goods_receipt_id`),
  UNIQUE KEY `finished_goods_receipt_id_UNIQUE` (`finished_goods_receipt_id`)
) ENGINE=InnoDB AUTO_INCREMENT=41698 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_tanks_finished_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_tanks_finished_product` (
  `product_tank_id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_tank_code` varchar(30) NOT NULL,
  `product_tank_name` varchar(255) NOT NULL,
  `company_id` int NOT NULL DEFAULT '0',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'Nhà máy sở hữu',
  `product_type` varchar(100) DEFAULT NULL COMMENT 'Loại thành phẩm: SVR 3L, SVR 5, SVR 10, SVR 20, etc.',
  `capacity` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Dung tích tối đa (kg)',
  `current_volume` decimal(15,2) DEFAULT '0.00' COMMENT 'Khối lượng hiện tại (kg)',
  `location` varchar(255) DEFAULT NULL COMMENT 'Vị trí trong nhà máy',
  `status` enum('active','inactive','maintenance','full') DEFAULT 'active' COMMENT 'Trạng thái: hoạt động, không hoạt động, bảo trì, đầy',
  `notes` text COMMENT 'Ghi chú',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`product_tank_id`),
  UNIQUE KEY `product_tank_id_UNIQUE` (`product_tank_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_tanks_finished_product_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_tanks_finished_product_history` (
  `product_tank_history_id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_tank_id` int NOT NULL DEFAULT '0',
  `entity_type` enum('production_order','material_release','finished_goods_receipt') NOT NULL DEFAULT 'finished_goods_receipt',
  `entity_id` int NOT NULL DEFAULT '0',
  `action_type` enum('input','output','transfer','adjustment') NOT NULL DEFAULT 'input' COMMENT 'Loại: nhập, xuất, chuyển, điều chỉnh',
  `product_type_category` enum('scrap_rubber','concentrated_latex') DEFAULT NULL,
  `product_type_id` int DEFAULT '0',
  `quantity` int NOT NULL DEFAULT '0',
  `weight` decimal(15,2) NOT NULL DEFAULT '0.00',
  `volume_before` decimal(15,2) NOT NULL DEFAULT '0.00',
  `volume_after` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`product_tank_history_id`),
  UNIQUE KEY `product_tank_history_id_UNIQUE` (`product_tank_history_id`)
) ENGINE=InnoDB AUTO_INCREMENT=38606 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_tanks_raw_material`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_tanks_raw_material` (
  `raw_material_tank_id` int unsigned NOT NULL AUTO_INCREMENT,
  `raw_material_tank_code` varchar(30) NOT NULL,
  `raw_material_tank_name` varchar(255) NOT NULL,
  `company_id` int NOT NULL DEFAULT '0',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'Nhà máy sở hữu',
  `tank_type` enum('latex','cup_lump','scrap_rubber','mixed') DEFAULT 'latex' COMMENT 'Loại nguyên liệu: mủ nước, mủ đông chén, mủ phế liệu, hỗn hợp',
  `capacity` decimal(15,2) DEFAULT '0.00' COMMENT 'Dung tích tối đa (kg)',
  `current_volume` decimal(15,2) DEFAULT '0.00' COMMENT 'Khối lượng hiện tại (kg)',
  `current_tsc` decimal(5,2) DEFAULT '0.00' COMMENT 'TSC trung bình hiện tại (%)',
  `location` varchar(255) DEFAULT NULL COMMENT 'Vị trí trong nhà máy',
  `status` enum('active','inactive','maintenance','full') DEFAULT 'active' COMMENT 'Trạng thái: hoạt động, không hoạt động, bảo trì, đầy',
  `notes` text COMMENT 'Ghi chú',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`raw_material_tank_id`),
  UNIQUE KEY `raw_material_tank_id_UNIQUE` (`raw_material_tank_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8022 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_tanks_raw_material_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_tanks_raw_material_history` (
  `raw_material_tank_history_id` int unsigned NOT NULL AUTO_INCREMENT,
  `raw_material_tank_id` int NOT NULL DEFAULT '0' COMMENT 'ID bồn chứa',
  `entity_type` enum('transportation_route','raw_material_release','production_channel_run','purchasing_factory_receipt') DEFAULT NULL,
  `entity_id` int NOT NULL DEFAULT '0',
  `action_type` enum('input','output','transfer','adjustment') NOT NULL DEFAULT 'input' COMMENT 'Loại: nhập, xuất, chuyển, điều chỉnh',
  `rubber_type` enum('latex','cup_lump','scrap_rubber','mixed') NOT NULL DEFAULT 'latex' COMMENT 'Loại mủ',
  `weight` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng (kg)',
  `tsc` decimal(5,2) DEFAULT '0.00' COMMENT 'TSC (%)',
  `volume_before` decimal(15,2) DEFAULT '0.00' COMMENT 'Khối lượng trước (kg)',
  `volume_after` decimal(15,2) DEFAULT '0.00' COMMENT 'Khối lượng sau (kg)',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`raw_material_tank_history_id`),
  UNIQUE KEY `tank_raw_material_history_UNIQUE` (`raw_material_tank_history_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6956 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_tanks_raw_material_release_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_tanks_raw_material_release_items` (
  `material_release_item_id` int unsigned NOT NULL AUTO_INCREMENT,
  `material_release_id` int NOT NULL DEFAULT '0',
  `raw_tank_id` int NOT NULL DEFAULT '0',
  `rubber_type` enum('latex','cup_lump','scrap_rubber') DEFAULT NULL,
  `weight_requested` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'KG cần lấy',
  `weight_released` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'KG thực tế lấy',
  `tank_volume_before` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Trạng thái bồn trước khi xuất',
  `tank_volume_after` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Trạng thái bồn sau xuất',
  `released_at` date NOT NULL,
  `created_at` datetime NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`material_release_item_id`),
  UNIQUE KEY `material_release_item_id_UNIQUE` (`material_release_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=58692 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_tanks_raw_material_releases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_tanks_raw_material_releases` (
  `material_release_id` int unsigned NOT NULL AUTO_INCREMENT,
  `material_release_code` varchar(30) NOT NULL,
  `material_release_name` varchar(255) NOT NULL,
  `company_id` int NOT NULL DEFAULT '0',
  `production_order_id` int NOT NULL DEFAULT '0',
  `total_requested_weight` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Tổng khối lượng xuất kho',
  `status` enum('draft','pending','approved','in_progress','completed','cancelled') DEFAULT 'in_progress' COMMENT 'Nháp, Chờ duyệt, Đã duyệt, Đang xuất kho, Hoàn thành xuất kho, Hủy',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`material_release_id`),
  UNIQUE KEY `material_release_id_UNIQUE` (`material_release_id`)
) ENGINE=InnoDB AUTO_INCREMENT=51261 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_transaction_ticket_land_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_transaction_ticket_land_links` (
  `ticket_land_link_id` int unsigned NOT NULL AUTO_INCREMENT,
  `transaction_ticket_id` int NOT NULL DEFAULT '0',
  `plot_id` int NOT NULL DEFAULT '0',
  `allocated_latex_weight` decimal(10,2) DEFAULT '0.00' COMMENT 'Phân bổ khối lượng mủ nước từ vườn này',
  `allocated_scrap_weight` decimal(10,2) DEFAULT '0.00',
  `estimated_harvest_date` date DEFAULT NULL COMMENT 'Ngày thu hoạch dự kiến',
  `actual_harvest_date` date DEFAULT NULL COMMENT 'Ngày thu hoạch thực tế',
  `notes` text COMMENT 'Ghi chú riêng cho vườn',
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`ticket_land_link_id`),
  UNIQUE KEY `ticket_land_link_id_UNIQUE` (`ticket_land_link_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9344 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_transaction_ticket_sale_purchase_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_transaction_ticket_sale_purchase_links` (
  `sale_purchase_link_id` int unsigned NOT NULL AUTO_INCREMENT,
  `sale_ticket_id` int NOT NULL DEFAULT '0' COMMENT 'ID phiếu bán',
  `purchase_ticket_id` int NOT NULL DEFAULT '0' COMMENT 'ID phiếu mua được sử dụng',
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`sale_purchase_link_id`),
  UNIQUE KEY `sale_purchase_link_id_UNIQUE` (`sale_purchase_link_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7638 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_transaction_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_transaction_tickets` (
  `transaction_ticket_id` int unsigned NOT NULL AUTO_INCREMENT,
  `transaction_ticket_code` varchar(30) NOT NULL,
  `transaction_ticket_type` enum('purchase','sale') NOT NULL COMMENT 'Loại phiếu: mua/bán',
  `contract_code` varchar(15) DEFAULT NULL,
  `connection_id` int NOT NULL DEFAULT '0' COMMENT 'ID Liên kết kết nối giữa 2 tài khoản',
  `buyer_company_id` int NOT NULL DEFAULT '0',
  `buyer_user_id` int NOT NULL DEFAULT '0' COMMENT 'Thông tin người mua - User ID  người mua',
  `buyer_name` varchar(255) NOT NULL COMMENT 'Thông tin người mua - Tên người mua',
  `buyer_phone` varchar(15) NOT NULL COMMENT 'Thông tin người mua - Số điện thoại người mua',
  `buyer_account_type` enum('farmer','purchaser','trader','company') NOT NULL COMMENT 'Thông tin người mua - Loại tài khoản của người mua',
  `buyer_address` text COMMENT 'Thông tin người mua - Địa chỉ người mua',
  `seller_company_id` int NOT NULL DEFAULT '0',
  `seller_user_id` int NOT NULL DEFAULT '0' COMMENT 'Thông tin người bán - User ID  người bán',
  `seller_name` varchar(255) NOT NULL COMMENT 'Thông tin người bán - Tên người bán',
  `seller_phone` varchar(15) NOT NULL COMMENT 'Thông tin người bán - Số điện thoại người bán',
  `seller_account_type` enum('farmer','purchaser','trader','company') NOT NULL COMMENT 'Thông tin người bán - Loại tài khoản người bán',
  `seller_address` text COMMENT 'Thông tin người bán - Địa chỉ người bán',
  `latex_weight` decimal(10,2) DEFAULT '0.00' COMMENT 'Khối lượng mủ nước (Kg)',
  `latex_tsc_grade` decimal(5,2) DEFAULT '0.00' COMMENT 'Độ TSC (%)',
  `latex_price_per_tsc` bigint DEFAULT '0' COMMENT 'Đơn giá đồng/độ TSC/kg (đ)',
  `latex_total_amount` bigint DEFAULT '0' COMMENT 'Thành tiền mủ nước (đ)',
  `latex_notes` text COMMENT 'Ghi chú mủ nước',
  `scrap_rubber_weight` decimal(10,2) DEFAULT '0.00' COMMENT 'Khối lượng mủ tạp (Kg)',
  `scrap_rubber_drc_grade` decimal(5,2) DEFAULT '0.00' COMMENT 'Độ DRC (%)',
  `scrap_rubber_price_per_drc` bigint DEFAULT '0' COMMENT 'Đơn giá theo độ DRC (đ)',
  `scrap_rubber_total_amount` bigint DEFAULT '0' COMMENT 'Thành tiền mủ tạp (đ)',
  `scrap_rubber_notes` text COMMENT 'Ghi chú mủ tạp',
  `payment_terms` varchar(255) DEFAULT NULL COMMENT 'Điều kiện thanh toán',
  `delivery_terms` varchar(255) DEFAULT NULL COMMENT 'Điều kiện giao hàng',
  `status` enum('draft','pending','sent','accepted','rejected','completed','cancelled') DEFAULT 'draft' COMMENT 'Trạng thái của phiếu',
  `sent_at` timestamp NULL DEFAULT NULL COMMENT 'Thời gian gửi tới đối tác giao dịch',
  `responded_at` timestamp NULL DEFAULT NULL COMMENT 'Thời gian đối tác phản hồi',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'Thời gian hoàn thành',
  `rejection_reason` varchar(255) DEFAULT NULL COMMENT 'Lý do từ chối',
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int NOT NULL DEFAULT '0' COMMENT 'Người tạo phiếu',
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`transaction_ticket_id`),
  UNIQUE KEY `transaction_ticket_id_UNIQUE` (`transaction_ticket_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8853 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_transportation_route_transaction_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_transportation_route_transaction_tickets` (
  `route_purchase_ticket_link` int unsigned NOT NULL AUTO_INCREMENT,
  `transportation_route_id` int NOT NULL DEFAULT '0',
  `transaction_ticket_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`route_purchase_ticket_link`),
  UNIQUE KEY `route_purchase_ticket_link_UNIQUE` (`route_purchase_ticket_link`)
) ENGINE=InnoDB AUTO_INCREMENT=57351 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_transportation_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_transportation_routes` (
  `transportation_route_id` int unsigned NOT NULL AUTO_INCREMENT,
  `transportation_route_code` varchar(30) NOT NULL,
  `company_id` int NOT NULL DEFAULT '0',
  `vehicle_id` int NOT NULL DEFAULT '0',
  `driver_id` int DEFAULT '0',
  `driver_name` varchar(255) NOT NULL COMMENT 'Tên tài xế',
  `transport_date` date NOT NULL COMMENT 'Ngày vận chuyển',
  `pickup_time` time DEFAULT NULL COMMENT 'Giờ lấy mủ',
  `source_type` enum('purchase_ticket','factory') NOT NULL DEFAULT 'purchase_ticket' COMMENT 'Nguồn: phiếu mua, nhà máy, kho',
  `source_transaction_ticket_id` int DEFAULT '0' COMMENT 'ID phiếu mua công ty (nguồn mủ)',
  `source_factory_id` int DEFAULT '0' COMMENT 'ID nhà máy nguồn (nếu chuyển giữa nhà máy)',
  `destination_factory_id` int NOT NULL DEFAULT '0' COMMENT 'Nhà máy nhận mủ',
  `destination_raw_material_tank_id` int DEFAULT '0' COMMENT 'Bồn chứa đích (nếu có)',
  `status` enum('pending','in_transit','delivered','arrived','unloaded','cancelled') DEFAULT 'pending' COMMENT 'Trạng thái: chờ, đang vận chuyển, đã giao, đổ nguyên liệu vào bồn, hủy',
  `departure_time` datetime DEFAULT NULL COMMENT 'Thời gian xuất phát',
  `arrival_time` datetime DEFAULT NULL COMMENT 'Thời gian đến',
  `delivery_confirmed_at` datetime DEFAULT NULL COMMENT 'Thời gian xác nhận giao hàng',
  `delivery_confirmed_by` int DEFAULT '0',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`transportation_route_id`),
  UNIQUE KEY `transportation_route_id_UNIQUE` (`transportation_route_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9013 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_transportation_vehicle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_transportation_vehicle` (
  `vehicle_id` int unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_code` varchar(30) NOT NULL,
  `vehicle_name` varchar(150) NOT NULL,
  `company_id` int NOT NULL DEFAULT '0',
  `brand` varchar(50) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `manufacture_year` int DEFAULT NULL,
  `license_plate` varchar(50) DEFAULT NULL,
  `address` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  `created_by` int DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`vehicle_id`),
  UNIQUE KEY `vehicle_id_UNIQUE` (`vehicle_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23999 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_transportation_vehicle_brand`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_transportation_vehicle_brand` (
  `vehicle_brand_id` int unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_brand_name` varchar(150) NOT NULL,
  PRIMARY KEY (`vehicle_brand_id`),
  UNIQUE KEY `vehicle_brand_id_UNIQUE` (`vehicle_brand_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31466 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_user_permissions` (
  `user_permission_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `permission_id` int NOT NULL,
  PRIMARY KEY (`user_permission_id`),
  UNIQUE KEY `id_UNIQUE` (`user_permission_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4406 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_user_roles` (
  `user_role_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  PRIMARY KEY (`user_role_id`),
  UNIQUE KEY `id_UNIQUE` (`user_role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_users` (
  `user_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_code` varchar(30) NOT NULL,
  `company_id` int NOT NULL DEFAULT '0',
  `parent_user_id` int DEFAULT '0' COMMENT 'Dành cho user loại worker(Tá điền), liên kết đến users_id của farmer quản lý',
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(45) NOT NULL,
  `salt` varchar(10) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `register_type` varchar(20) DEFAULT 'farmer' COMMENT 'Kiểu người dùng đăng ký (Vì Người dùng có thể đăng ký làm Farmer/ Nông hộ hoặc làm Chành/Collector)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT '0',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `is_approved` tinyint(1) DEFAULT '0' COMMENT 'Đánh dấu đã được admin duyệt hay chưa để cho phép đăng nhập',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Đánh dấu tài khoản có đang hoạt động hay bị vô hiệu hóa bởi chủ công ty',
  PRIMARY KEY (`user_id`,`user_code`),
  UNIQUE KEY `user_id_UNIQUE` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=43332 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_vehicle_tanks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_vehicle_tanks` (
  `vehicle_tank_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK bình chứa cố định/gắn trên xe',
  `vehicle_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_transportation_vehicle',
  `vehicle_tank_code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã bình trên xe',
  `vehicle_tank_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Tên khoang/bình trên xe',
  `tank_type` enum('latex','cup_lump','scrap_rubber','mixed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'latex' COMMENT 'Loại mủ có thể chứa',
  `capacity_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Sức chứa tối đa của bình trên xe',
  `current_weight_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng hiện tại',
  `compartment_no` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Số khoang nếu xe có nhiều khoang',
  `status` enum('idle','loading','in_transit','unloading','cleaning','maintenance','inactive') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'idle' COMMENT 'Trạng thái bình trên xe',
  `notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`vehicle_tank_id`)
) ENGINE=InnoDB AUTO_INCREMENT=475309 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh mục bình/khoang chứa mủ gắn trên xe vận chuyển; có thể mapping 1-1 hoặc 1-n với bình con bên bán';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_vendor_lands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_vendor_lands` (
  `vendor_land_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK quan he vendor-vuon',
  `vendor_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_vendors',
  `plot_id` int NOT NULL DEFAULT '0' COMMENT 'FK logic -> eudr_lands.plot_id',
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active' COMMENT 'Vendor được phép bán sản phẩm từ vườn',
  `verified_at` datetime DEFAULT NULL COMMENT 'Thời điểm xác minh quan hệ vendor-vườn',
  `verified_by` int DEFAULT '0',
  `notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`vendor_land_id`)
) ENGINE=InnoDB AUTO_INCREMENT=232227 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh mục vườn được vendor khai báo/ủy quyền bán sản phẩm; vendor có thể là công ty/trader hoặc nông hộ';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_vendors` (
  `vendor_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK vendor',
  `vendor_code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mã vendor duy nhất',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'Công ty quản lý vendor',
  `vendor_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Tên vendor/tên bên bán',
  `vendor_type` enum('individual','company') COLLATE utf8mb4_general_ci DEFAULT 'company' COMMENT 'Loại vendor: cá nhân hoặc công ty',
  `identity_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Số định danh cá nhân; bắt buộc và duy nhất với vendor cá nhân',
  `contact_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Người liên hệ',
  `contact_phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Số điện thoại người liên hệ',
  `tax_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Mã số thuế nếu có',
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Địa chỉ vendor',
  `province_id` int DEFAULT '0' COMMENT 'Tỉnh/TP',
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active' COMMENT 'Trạng thái sử dụng vendor',
  `notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Ghi chú bổ sung',
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  PRIMARY KEY (`vendor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=147741 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh mục vendor/bên bán ngoài hệ thống; có thể là công ty/trader hoặc nông hộ';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_warehouse_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_warehouse_locations` (
  `warehouse_location_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK vi tri luu tru',
  `warehouse_id` int unsigned NOT NULL COMMENT 'Kho cha',
  `warehouse_zone_id` int unsigned NOT NULL COMMENT 'Khu vuc cha',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID cong ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nha may',
  `location_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Ma vi tri (A-01-02-03)',
  `row_no` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Day',
  `bay_no` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Ngan',
  `level_no` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Tang',
  `position_no` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Vi tri',
  `max_pallet_capacity` int unsigned NOT NULL DEFAULT '1' COMMENT 'Suc chua pallet toi da tai vi tri',
  `occupied_pallet_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'So pallet dang chiem cho',
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'empty' COMMENT 'empty,occupied,blocked,inactive',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`warehouse_location_id`)
) ENGINE=InnoDB AUTO_INCREMENT=145640 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Vi tri luu tru chi tiet trong kho';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_warehouse_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_warehouse_zones` (
  `warehouse_zone_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK khu vuc kho',
  `warehouse_id` int unsigned NOT NULL COMMENT 'Kho cha',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID cong ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nha may',
  `zone_code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Ma khu vuc',
  `zone_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Ten khu vuc',
  `zone_type` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'storage' COMMENT 'receiving,storage,staging,shipping,quarantine',
  `capacity_pallet` int unsigned NOT NULL DEFAULT '0' COMMENT 'Suc chua pallet cua khu vuc',
  `current_pallet_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'So pallet hien tai cua khu vuc',
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active' COMMENT 'active,blocked,inactive',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`warehouse_zone_id`)
) ENGINE=InnoDB AUTO_INCREMENT=111341 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Khu vuc ben trong kho';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eudr_warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eudr_warehouses` (
  `warehouse_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK kho',
  `company_id` int NOT NULL DEFAULT '0' COMMENT 'ID cong ty',
  `factory_id` int NOT NULL DEFAULT '0' COMMENT 'ID nha may',
  `warehouse_code` varchar(30) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Ma kho',
  `warehouse_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Ten kho',
  `warehouse_type` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'finished_goods' COMMENT 'raw_material,intermediate,finished_goods,production_pallet,transit',
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Dia chi kho',
  `manager_user_id` int NOT NULL DEFAULT '0' COMMENT 'Nguoi quan ly kho',
  `capacity_pallet` int unsigned NOT NULL DEFAULT '0' COMMENT 'Suc chua toi da theo pallet',
  `max_weight_kg` decimal(14,3) NOT NULL DEFAULT '0.000' COMMENT 'Tai trong toi da (kg)',
  `current_pallet_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'So pallet hien tai (co the tinh toan batch)',
  `current_weight_kg` decimal(14,3) NOT NULL DEFAULT '0.000' COMMENT 'Tong trong luong hien tai (kg)',
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active' COMMENT 'active,maintenance,blocked,inactive',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_by` int DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`warehouse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=125342 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Danh muc kho';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

