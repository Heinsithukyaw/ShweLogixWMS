-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 18, 2025 at 04:32 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_types`
--

CREATE TABLE `activity_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activity_type_code` varchar(255) NOT NULL,
  `activity_type_name` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `default_duration` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `last_modified_by` varchar(255) DEFAULT NULL,
  `ai_insight_flag` varchar(200) DEFAULT NULL COMMENT '0 - no / 1 - yes',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_types`
--

INSERT INTO `activity_types` (`id`, `activity_type_code`, `activity_type_name`, `category`, `default_duration`, `description`, `created_by`, `last_modified_by`, `ai_insight_flag`, `status`, `created_at`, `updated_at`) VALUES
(1, 'RECV', 'Receiving', 'Inbound', NULL, 'Receiving goods from suppliers', NULL, NULL, 'Yes', 1, '2025-05-20 06:48:08', '2025-05-20 06:48:08'),
(2, 'PICK', 'Picking', 'Outbound', NULL, 'Retrieving items from storage for orders', NULL, NULL, 'Yes', 1, '2025-05-20 06:48:45', '2025-05-20 06:48:45');

-- --------------------------------------------------------

--
-- Table structure for table `advanced_shipping_notices`
--

CREATE TABLE `advanced_shipping_notices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `asn_code` varchar(255) NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `expected_arrival` date DEFAULT NULL,
  `carrier_id` bigint(20) UNSIGNED NOT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `total_items` int(11) DEFAULT 0,
  `total_pallet` int(11) DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 - pending / 1 - verified / 2 - received',
  `notes` text DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `advanced_shipping_notices`
--

INSERT INTO `advanced_shipping_notices` (`id`, `asn_code`, `supplier_id`, `purchase_order_id`, `expected_arrival`, `carrier_id`, `tracking_number`, `total_items`, `total_pallet`, `status`, `notes`, `received_date`, `created_at`, `updated_at`) VALUES
(22, 'ASN00003', 2, NULL, '2025-02-02', 1, 'TRK45789305', 200, 5, 0, NULL, '2025-02-05', '2025-05-30 02:58:15', '2025-06-02 04:44:58'),
(23, 'ASN00002', 2, NULL, '2025-02-02', 1, 'TRK45789250', 1000, 8, 2, NULL, NULL, '2025-05-30 02:59:59', '2025-06-02 04:44:09'),
(24, 'ASN00001', 2, NULL, '2019-02-04', 1, NULL, NULL, NULL, 1, 'Complete shipment', '2020-02-05', '2025-05-30 03:27:44', '2025-05-30 06:41:37');

-- --------------------------------------------------------

--
-- Table structure for table `advanced_shipping_notice_details`
--

CREATE TABLE `advanced_shipping_notice_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `asn_detail_code` varchar(255) NOT NULL,
  `asn_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `item_description` text DEFAULT NULL,
  `expected_qty` int(11) DEFAULT 0,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `lot_number` varchar(255) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `received_qty` int(11) DEFAULT NULL,
  `variance` varchar(255) DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 - pending / 1 - missing / 2 - partial / 3 - received',
  `location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pallet_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `advanced_shipping_notice_details`
--

INSERT INTO `advanced_shipping_notice_details` (`id`, `asn_detail_code`, `asn_id`, `item_id`, `item_description`, `expected_qty`, `uom_id`, `lot_number`, `expiration_date`, `received_qty`, `variance`, `status`, `location_id`, `pallet_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'ASNDETAIL001', 24, 7, NULL, 98, 1, NULL, NULL, NULL, NULL, 0, 1, 1, NULL, '2025-06-17 04:08:58', '2025-06-17 04:14:34');

-- --------------------------------------------------------

--
-- Table structure for table `areas`
--

CREATE TABLE `areas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `area_code` varchar(255) NOT NULL,
  `area_name` varchar(255) NOT NULL,
  `area_type` varchar(255) NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `responsible_person` text DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `location_description` varchar(255) DEFAULT NULL,
  `capacity` varchar(255) DEFAULT NULL,
  `dimensions` varchar(255) DEFAULT NULL,
  `environmental_conditions` varchar(255) DEFAULT NULL,
  `equipment` varchar(255) DEFAULT NULL,
  `custom_attributes` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active / 2 - Under Maintenance / 3 - Planned / 4 - Decommissioned',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `areas`
--

INSERT INTO `areas` (`id`, `area_code`, `area_name`, `area_type`, `warehouse_id`, `responsible_person`, `phone_number`, `email`, `location_description`, `capacity`, `dimensions`, `environmental_conditions`, `equipment`, `custom_attributes`, `status`, `created_at`, `updated_at`) VALUES
(1, 'A001', 'Receiving Dock', 'Receiving', 1, 'John Doe', NULL, 'john@shwelogix.com', 'Dock 1, West Side', '50 pallets', '20m x 10m x 5m', 'Forklift, Pallet Jack', NULL, NULL, 1, '2025-05-10 23:03:53', '2025-05-12 07:22:16'),
(2, 'A002', 'Bulk Storage', 'Storage', 1, 'Jane Smith', NULL, 'jane@shwelogix.com', 'Aisle 1-5', '1000 pallets', '100m x 50m x 10m', 'Ambient', NULL, NULL, 3, '2025-05-11 01:19:19', '2025-05-12 07:23:48'),
(3, 'A004', 'Area V', 'Receiving', 1, 'U Yan Lay', '97878', 'yan@gmail.com', 'somewhere', '100', '30', 'Ambient', NULL, NULL, 0, '2025-05-13 06:35:37', '2025-05-13 06:37:49'),
(4, 'A005', 'Shipping Area D', 'Shipping', 1, 'John Dee', '23456789', 'john@gmail.com', NULL, '10000', NULL, NULL, NULL, NULL, 1, '2025-06-17 07:15:12', '2025-06-17 07:15:12');

-- --------------------------------------------------------

--
-- Table structure for table `base_uoms`
--

CREATE TABLE `base_uoms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `short_code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `base_uoms`
--

INSERT INTO `base_uoms` (`id`, `short_code`, `name`, `created_at`, `updated_at`) VALUES
(1, 'PC', 'Piece', '2025-04-24 14:17:34', '2025-04-24 14:17:34'),
(2, 'BX', 'Box', '2025-04-24 14:17:34', '2025-04-24 14:17:34'),
(3, 'CRT', 'Carton', '2025-04-24 14:17:34', '2025-04-24 14:17:34'),
(4, 'PLT', 'Pallet', '2025-04-24 14:17:34', '2025-04-24 14:17:34'),
(5, 'KG', 'Kilogram', '2025-04-24 14:17:34', '2025-04-24 14:17:34'),
(6, 'GM', 'Gram', '2025-04-24 14:17:34', '2025-04-24 14:17:34'),
(7, 'LTR', 'Liter', '2025-04-24 14:17:34', '2025-04-24 14:17:34'),
(8, 'ML', 'Militer', '2025-04-24 14:17:34', '2025-04-24 14:17:34');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `brand_code` varchar(255) NOT NULL,
  `brand_name` varchar(255) NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `subcategory_id` bigint(20) UNSIGNED NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `brand_code`, `brand_name`, `category_id`, `subcategory_id`, `status`, `description`, `created_at`, `updated_at`) VALUES
(6, 'Brand X', 'Alpha', 2, 4, 1, NULL, '2025-05-07 06:16:28', '2025-05-12 06:51:17'),
(7, 'Brand Y', 'Toshiba', 2, 4, 1, NULL, '2025-05-12 06:27:41', '2025-05-12 06:51:30'),
(8, 'Brand Z', 'Xiaomi', 2, 3, 1, NULL, '2025-05-12 06:34:21', '2025-05-12 06:51:43');

-- --------------------------------------------------------

--
-- Table structure for table `business_contacts`
--

CREATE TABLE `business_contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `contact_code` varchar(255) NOT NULL,
  `contact_name` varchar(255) NOT NULL,
  `business_party_id` bigint(20) UNSIGNED NOT NULL,
  `designation` varchar(255) NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `preferred_contact_method` varchar(255) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `business_contacts`
--

INSERT INTO `business_contacts` (`id`, `contact_code`, `contact_name`, `business_party_id`, `designation`, `department`, `phone_number`, `email`, `address`, `country`, `preferred_contact_method`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(2, 'CP001', 'John Doe', 2, 'Sales Manager', 'Sales', '9701', 'john@gmail.com', NULL, 'USA', 'Email', 1, NULL, '2025-05-12 07:15:33', '2025-05-12 07:15:33'),
(3, 'CP002', 'Joe Smith', 3, 'Procurement Head', 'Purchasing', '8867', 'simith@gmail.com', NULL, 'UK', 'Phone', 1, NULL, '2025-05-12 07:16:35', '2025-05-12 07:16:35');

-- --------------------------------------------------------

--
-- Table structure for table `business_parties`
--

CREATE TABLE `business_parties` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `party_code` varchar(255) NOT NULL,
  `party_name` varchar(255) NOT NULL,
  `party_type` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `tax_vat` varchar(255) DEFAULT NULL,
  `business_registration_no` varchar(255) DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `credit_limit` int(11) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `custom_attributes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `business_parties`
--

INSERT INTO `business_parties` (`id`, `party_code`, `party_name`, `party_type`, `contact_person`, `phone_number`, `email`, `address`, `country`, `tax_vat`, `business_registration_no`, `payment_terms`, `credit_limit`, `status`, `custom_attributes`, `created_at`, `updated_at`) VALUES
(2, 'SUP001', 'ABC Suppliers Ltd.', 'Supplier', 'John Doe', '669887676765', 'johndoe@gmail.com', 'New York', 'USA', 'US12345678', 'BR123456', 'Net 30', 50000, 1, NULL, '2025-05-06 07:13:36', '2025-05-12 07:09:03'),
(3, 'CUST001', 'XYZ Retailers Inc.', 'Customer', 'Jane Smith', '2431', 'smithj@gmail.com', 'New York', 'USA', 'UK98765432', 'BR987654', 'Net 30', 7000, 1, NULL, '2025-05-06 07:17:59', '2025-05-12 07:10:18');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_code` varchar(255) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hierarchy_level` int(11) DEFAULT NULL,
  `applicable_industry` varchar(255) NOT NULL,
  `storage_condition` varchar(255) NOT NULL,
  `handling_instructions` varchar(255) NOT NULL,
  `tax_category` varchar(255) NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_code`, `category_name`, `parent_id`, `hierarchy_level`, `applicable_industry`, `storage_condition`, `handling_instructions`, `tax_category`, `uom_id`, `status`, `description`, `created_at`, `updated_at`) VALUES
(2, 'ELEC', 'Electronics', NULL, 1, 'Retail, Manufacturing', 'Dry Area', 'Fragile', 'GST 18%', 1, 1, NULL, '2025-04-24 14:52:24', '2025-05-12 06:17:01'),
(3, 'MOB', 'Mobile Phones', 2, 2, 'Retail', 'Dry Area', 'Fragile', 'GST 18%', 1, 1, NULL, '2025-04-24 15:18:50', '2025-05-12 06:18:30'),
(4, 'Home', 'Home Appliances', 2, 2, 'Retail', 'Dry Area', 'Heavy', 'GST 12%', 1, 1, NULL, '2025-04-25 00:18:46', '2025-05-12 06:20:20'),
(5, 'FURN', 'Furniture', NULL, 1, 'Retail , Interior', 'Dry Area', 'Heavy, Fragile', 'GST 12%', 1, 1, NULL, '2025-04-25 03:15:28', '2025-05-12 06:22:54'),
(6, 'BEV', 'Beverages', NULL, 1, 'Retail, Food & Beverage', 'Cold Storage', 'Heavy with care', 'VAT Exempt', 8, 1, NULL, '2025-05-12 06:24:46', '2025-05-12 06:24:46');

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `city_code` varchar(255) NOT NULL,
  `city_name` varchar(255) NOT NULL,
  `country_id` bigint(20) UNSIGNED NOT NULL,
  `state_id` bigint(20) UNSIGNED NOT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `last_modified_by` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `city_code`, `city_name`, `country_id`, `state_id`, `postal_code`, `latitude`, `longitude`, `created_by`, `last_modified_by`, `status`, `created_at`, `updated_at`) VALUES
(1, 'MM-YGN', 'Yangon', 1, 1, '11181', '16.8409', '96.1735', NULL, NULL, 1, '2025-05-19 08:55:25', '2025-05-19 08:55:25');

-- --------------------------------------------------------

--
-- Table structure for table `cost_types`
--

CREATE TABLE `cost_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cost_code` varchar(255) NOT NULL,
  `cost_name` varchar(255) NOT NULL,
  `cost_type` varchar(255) NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `subcategory_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `modified_by` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cost_types`
--

INSERT INTO `cost_types` (`id`, `cost_code`, `cost_name`, `cost_type`, `category_id`, `subcategory_id`, `created_by`, `modified_by`, `status`, `created_at`, `updated_at`) VALUES
(1, 'CCV', 'CCC', 'Fixed', 1, 2, NULL, NULL, 0, '2025-05-19 02:45:34', '2025-05-19 02:48:56');

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `country_code` varchar(255) NOT NULL,
  `country_name` varchar(255) NOT NULL,
  `country_code_3` varchar(255) NOT NULL,
  `numeric_code` int(11) NOT NULL,
  `currency_id` bigint(20) UNSIGNED NOT NULL,
  `phone_code` varchar(255) NOT NULL,
  `capital` varchar(255) NOT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `modified_by` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `country_code`, `country_name`, `country_code_3`, `numeric_code`, `currency_id`, `phone_code`, `capital`, `created_by`, `modified_by`, `status`, `created_at`, `updated_at`) VALUES
(1, 'MM', 'Myanmar', 'MMR', 104, 1, '95', 'Naypitaw', NULL, NULL, 1, '2025-05-19 06:03:36', '2025-05-19 06:03:36'),
(2, 'US', 'United States', 'USA', 840, 2, '1', 'New York', NULL, NULL, 1, '2025-05-19 06:09:30', '2025-05-19 06:09:30');

-- --------------------------------------------------------

--
-- Table structure for table `cross_docking_tasks`
--

CREATE TABLE `cross_docking_tasks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cross_docking_task_code` varchar(255) NOT NULL,
  `asn_id` bigint(20) UNSIGNED NOT NULL,
  `asn_detail_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `item_description` text DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `source_location_id` bigint(20) UNSIGNED NOT NULL,
  `destination_location_id` bigint(20) UNSIGNED NOT NULL,
  `outbound_shipment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_to_id` bigint(20) UNSIGNED DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT 0 COMMENT '0 - low / 1 - medium / 2 - high',
  `status` int(11) NOT NULL DEFAULT 0 COMMENT '0 - pending / 1 - in progress / 2 - completed / 3 -delayed',
  `created_date` date DEFAULT NULL,
  `start_time` date DEFAULT NULL,
  `complete_time` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `currencies`
--

CREATE TABLE `currencies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `currency_code` varchar(255) NOT NULL,
  `currency_name` varchar(255) NOT NULL,
  `symbol` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `exchange_rate` varchar(255) DEFAULT NULL,
  `base_currency` varchar(255) DEFAULT NULL,
  `decimal_places` varchar(255) DEFAULT NULL,
  `created_by` date DEFAULT NULL,
  `modified_by` date DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `currencies`
--

INSERT INTO `currencies` (`id`, `currency_code`, `currency_name`, `symbol`, `country`, `exchange_rate`, `base_currency`, `decimal_places`, `created_by`, `modified_by`, `status`, `created_at`, `updated_at`) VALUES
(1, 'MMK', 'Myanmar Kyat', 'Kyat', 'Myanmar', '1000', 'USD', NULL, NULL, NULL, 1, '2025-05-19 03:16:21', '2025-05-19 06:05:57'),
(2, 'USD', 'United States Dollar', '$', 'USA', '1', 'USD', NULL, NULL, NULL, 1, '2025-05-19 06:06:38', '2025-05-19 06:06:38'),
(3, 'EUR', 'Euro', '€', 'UK', '0.94', 'USD', NULL, NULL, NULL, 1, '2025-05-19 06:07:23', '2025-05-19 06:07:23'),
(4, 'GBP', 'British Pound Sterling', '£', 'UK', '0.8', 'USD', NULL, NULL, NULL, 1, '2025-05-19 06:07:53', '2025-05-19 06:07:53');

-- --------------------------------------------------------

--
-- Table structure for table `dock_equipment`
--

CREATE TABLE `dock_equipment` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `dock_code` varchar(255) NOT NULL,
  `dock_name` varchar(255) NOT NULL,
  `dock_type` varchar(255) NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `area_id` bigint(20) UNSIGNED NOT NULL,
  `dock_number` varchar(255) NOT NULL,
  `capacity` varchar(255) NOT NULL,
  `capacity_unit` varchar(255) NOT NULL,
  `dimensions` varchar(255) DEFAULT NULL,
  `equipment_features` text DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `operating_hours` varchar(255) DEFAULT NULL,
  `assigned_staff` varchar(200) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `custom_attributes` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 2 COMMENT '0 - under maintenance / 1 - out of service / 2 - operational / 3 - scheduled maintenance / 4 - reserved',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dock_equipment`
--

INSERT INTO `dock_equipment` (`id`, `dock_code`, `dock_name`, `dock_type`, `warehouse_id`, `area_id`, `dock_number`, `capacity`, `capacity_unit`, `dimensions`, `equipment_features`, `last_maintenance_date`, `next_maintenance_date`, `operating_hours`, `assigned_staff`, `remarks`, `custom_attributes`, `status`, `created_at`, `updated_at`) VALUES
(2, 'DOCK001', 'Receiving Dock 1', 'Receiving', 1, 1, '01', '30000', 'kg', NULL, '[\"Dock Leveler\",\"Dock Seal\"]', NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-05-17 01:40:09', '2025-05-19 06:37:01'),
(3, 'DOCK002', 'Shipping Dock A', 'Shipping', 1, 2, '02', '25000', 'kg', '10m x 4.5m', '[\"Dock Leveler\"]', NULL, NULL, '6:00 - 18:00', NULL, NULL, NULL, 2, '2025-05-17 01:52:32', '2025-05-19 06:38:00');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_code` varchar(255) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(255) NOT NULL,
  `dob` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `nationality` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `employment_type` varchar(255) DEFAULT NULL,
  `shift` varchar(255) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `salary` varchar(255) DEFAULT NULL,
  `currency` varchar(255) DEFAULT NULL,
  `is_supervisor` int(11) DEFAULT 0 COMMENT '0 - no / 1 - yes',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - in active / 1 - active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_code`, `employee_name`, `email`, `phone_number`, `dob`, `gender`, `nationality`, `address`, `department_id`, `job_title`, `employment_type`, `shift`, `hire_date`, `salary`, `currency`, `is_supervisor`, `status`, `created_at`, `updated_at`) VALUES
(1, 'EMP001', 'John Doe', 'ee@gmail', '3456', '2003-03-03', 'Male', NULL, NULL, NULL, 'Forklift Operator', 'Part Time', 'Day Shift', NULL, NULL, NULL, 1, 1, '2025-05-17 05:58:27', '2025-05-31 06:32:52');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_categories`
--

CREATE TABLE `financial_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_code` varchar(255) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `financial_categories`
--

INSERT INTO `financial_categories` (`id`, `category_code`, `category_name`, `parent_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'FC001', 'Fcone', NULL, 0, '2025-05-18 23:44:35', '2025-05-18 23:45:49'),
(2, 'FC002', 'FCtwo', 1, 1, '2025-05-18 23:48:23', '2025-05-18 23:48:23');

-- --------------------------------------------------------

--
-- Table structure for table `good_received_notes`
--

CREATE TABLE `good_received_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `grn_code` varchar(255) NOT NULL,
  `inbound_shipment_id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `received_date` date DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `total_items` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 - pending / 1 - rejected / 2 - approved',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `good_received_notes`
--

INSERT INTO `good_received_notes` (`id`, `grn_code`, `inbound_shipment_id`, `purchase_order_id`, `supplier_id`, `received_date`, `created_by`, `approved_by`, `total_items`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(16, 'GRNDETAIL0001', 3, NULL, 2, NULL, NULL, NULL, NULL, NULL, 0, '2025-06-17 07:53:51', '2025-06-17 07:53:51');

-- --------------------------------------------------------

--
-- Table structure for table `good_received_note_items`
--

CREATE TABLE `good_received_note_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `grn_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `expected_qty` int(11) DEFAULT NULL,
  `received_qty` int(11) DEFAULT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `location_id` bigint(20) UNSIGNED NOT NULL,
  `notes` text DEFAULT NULL,
  `condition_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 - damaged / 1 - expired / 2 - good',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `good_received_note_items`
--

INSERT INTO `good_received_note_items` (`id`, `grn_id`, `product_id`, `expected_qty`, `received_qty`, `uom_id`, `location_id`, `notes`, `condition_status`, `created_at`, `updated_at`) VALUES
(22, 16, 7, 12, 2, 1, 3, NULL, 1, '2025-06-17 08:00:31', '2025-06-17 08:00:31');

-- --------------------------------------------------------

--
-- Table structure for table `inbound_shipments`
--

CREATE TABLE `inbound_shipments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shipment_code` varchar(255) NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `carrier_id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `staging_location_id` int(11) DEFAULT NULL,
  `expected_arrival` date DEFAULT NULL,
  `actual_arrival` date DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 - expected / 1 - In Transit / 2 - arrival / 3 - unloaded / 4 - received\r\n',
  `version_control` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 - Lite / 1 - Pro / 2 - Legend',
  `trailer_number` varchar(255) DEFAULT NULL,
  `seal_number` varchar(255) DEFAULT NULL,
  `total_pallet` int(11) DEFAULT 0,
  `total_weight` varchar(255) DEFAULT '0',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inbound_shipments`
--

INSERT INTO `inbound_shipments` (`id`, `shipment_code`, `supplier_id`, `carrier_id`, `purchase_order_id`, `staging_location_id`, `expected_arrival`, `actual_arrival`, `status`, `version_control`, `trailer_number`, `seal_number`, `total_pallet`, `total_weight`, `notes`, `created_at`, `updated_at`) VALUES
(3, 'SHP003', 2, 1, NULL, 2, NULL, NULL, 1, 0, NULL, NULL, 0, NULL, NULL, '2025-06-17 05:13:42', '2025-06-17 05:15:44');

-- --------------------------------------------------------

--
-- Table structure for table `inbound_shipment_details`
--

CREATE TABLE `inbound_shipment_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `inbound_detail_code` varchar(255) NOT NULL,
  `inbound_shipment_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_number` varchar(255) DEFAULT NULL,
  `expected_qty` int(11) DEFAULT NULL,
  `received_qty` int(11) DEFAULT NULL,
  `damaged_qty` int(11) DEFAULT NULL,
  `lot_number` varchar(255) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `location_id` bigint(20) UNSIGNED NOT NULL,
  `received_by` varchar(255) DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 - exception / 1 - expected / 2 - received',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inbound_shipment_details`
--

INSERT INTO `inbound_shipment_details` (`id`, `inbound_detail_code`, `inbound_shipment_id`, `product_id`, `purchase_order_number`, `expected_qty`, `received_qty`, `damaged_qty`, `lot_number`, `expiration_date`, `location_id`, `received_by`, `received_date`, `status`, `created_at`, `updated_at`) VALUES
(3, 'DET0001', 3, 7, NULL, 100, 60, 30, NULL, NULL, 4, NULL, NULL, 0, '2025-06-17 07:03:06', '2025-06-17 07:03:06');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `location_code` varchar(255) NOT NULL,
  `location_name` varchar(255) NOT NULL,
  `location_type` varchar(255) NOT NULL,
  `zone_id` bigint(20) UNSIGNED NOT NULL,
  `aisle` text DEFAULT NULL,
  `row` varchar(255) DEFAULT NULL,
  `level` varchar(255) DEFAULT NULL,
  `bin` varchar(255) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `capacity_unit` varchar(255) DEFAULT NULL,
  `restrictions` varchar(255) DEFAULT NULL,
  `bar_code` varchar(255) DEFAULT NULL,
  `utilization` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - available / 1 - occupied / 2 - reserved / 3 - under maintenance',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `location_code`, `location_name`, `location_type`, `zone_id`, `aisle`, `row`, `level`, `bin`, `capacity`, `capacity_unit`, `restrictions`, `bar_code`, `utilization`, `description`, `status`, `created_at`, `updated_at`) VALUES
(2, 'LOC001', 'Bin A1-R1-L1-B1', 'Bin', 1, 'A1', 'R1', 'L1', 'B1', 100, 'items', 'None', '1234567890', 45, 'Location description', 1, '2025-05-12 07:26:12', '2025-06-18 07:59:22'),
(3, 'LOC002', 'Rack A2-R2-L2', 'Rack', 2, 'A2', 'R2', 'L2', 'B2', 500, 'items', 'Hazmat', '556789', 29, NULL, 2, '2025-05-12 07:27:55', '2025-06-18 07:57:01'),
(4, 'LOC003', 'LOC-A12-B03', 'Bin', 3, '12', '3', '2', '03', 1000, 'kg', 'Hazmat', '234567890-', NULL, NULL, 1, '2025-06-17 06:50:25', '2025-06-17 06:50:25'),
(5, 'LOC0007', 'SHIP-B1-01', 'Bin', 4, 'B1', '3', '2', 'A3', 900, 'm²', 'None', 'wertyui12345', NULL, NULL, 1, '2025-06-17 07:24:07', '2025-06-17 07:24:07'),
(6, 'LOC1', 'Test New Location', 'Bin', 1, 'A-1', '1', '1', 'B-1', 200, 'kg', 'Hazmat', '1234567dfghj', 100, NULL, 1, '2025-06-18 02:19:42', '2025-06-18 07:57:25');

-- --------------------------------------------------------

--
-- Table structure for table `material_handling_eqs`
--

CREATE TABLE `material_handling_eqs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mhe_code` varchar(255) NOT NULL,
  `mhe_name` varchar(255) NOT NULL,
  `mhe_type` varchar(255) NOT NULL,
  `manufacturer` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `serial_number` varchar(255) NOT NULL,
  `purchase_date` date NOT NULL,
  `warranty_expire_date` date NOT NULL,
  `capacity` varchar(255) NOT NULL,
  `capacity_unit` varchar(255) NOT NULL,
  `current_location_detail` varchar(255) DEFAULT NULL,
  `home_location` varchar(255) DEFAULT NULL,
  `shift_availability` varchar(255) DEFAULT NULL,
  `operator_assigned` varchar(255) DEFAULT NULL,
  `maintenance_schedule_type` varchar(255) DEFAULT NULL,
  `maintenance_frequency` varchar(255) DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `last_service_type` varchar(255) DEFAULT NULL,
  `last_maintenance_due_date` date DEFAULT NULL,
  `safety_inspection_due_date` date DEFAULT NULL,
  `safety_certification_expire_date` date DEFAULT NULL,
  `safety_features` varchar(255) DEFAULT NULL,
  `uptime_percentage_monthly` varchar(255) DEFAULT NULL,
  `maintenance_cost` varchar(255) DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `currency_unit` varchar(255) DEFAULT NULL,
  `energy_consumption_per_hour` varchar(255) DEFAULT NULL,
  `depreciation_start_date` date DEFAULT NULL,
  `depreciation_method` varchar(255) DEFAULT NULL,
  `estimated_useful_life_year` varchar(255) DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_contact_id` bigint(20) DEFAULT NULL,
  `expected_replacement_date` date DEFAULT NULL,
  `disposal_date` varchar(255) DEFAULT NULL,
  `replacement_mhe_id` bigint(20) DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `custom_attributes` text DEFAULT NULL,
  `usage_status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1 - available / 2 - maintenance / 3 - in use',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1 - operational / 2 - under maintenance',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `material_handling_eqs`
--

INSERT INTO `material_handling_eqs` (`id`, `mhe_code`, `mhe_name`, `mhe_type`, `manufacturer`, `model`, `serial_number`, `purchase_date`, `warranty_expire_date`, `capacity`, `capacity_unit`, `current_location_detail`, `home_location`, `shift_availability`, `operator_assigned`, `maintenance_schedule_type`, `maintenance_frequency`, `last_maintenance_date`, `last_service_type`, `last_maintenance_due_date`, `safety_inspection_due_date`, `safety_certification_expire_date`, `safety_features`, `uptime_percentage_monthly`, `maintenance_cost`, `currency`, `currency_unit`, `energy_consumption_per_hour`, `depreciation_start_date`, `depreciation_method`, `estimated_useful_life_year`, `supplier_id`, `supplier_contact_id`, `expected_replacement_date`, `disposal_date`, `replacement_mhe_id`, `remark`, `custom_attributes`, `usage_status`, `status`, `created_at`, `updated_at`) VALUES
(4, 'MHE001', 'Electric Forklift 1', 'Tugger Train', 'Toyota', '8FGCU25', '123456', '2002-11-11', '0028-02-22', '2000', 'kg', 'Zone A - Aisle 1 - Bay 2', 'Parking Spot 1', 'All Shifts', NULL, 'Time-based', 'Monthly', '2000-02-21', 'Hydraulic System Overhaul', '2008-03-31', NULL, NULL, '[\"Guard Retails\",\"Emergency Lowering\"]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, '2025-05-31 15:04:07', '2025-05-31 08:34:07'),
(5, 'MHE002', 'Manual Pallet Jack A', 'Manual Pallet Jack', 'Crown', 'PTH50', '789012', '2008-11-12', '2029-11-03', '2500', 'kg', 'Zone B - Receiving Dock', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[\"Emergency Lowering\",\"Ergonomic Handle\"]', NULL, '29999', 'USD', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-05-31 15:09:15', '2025-05-31 08:39:15');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(3, '2016_06_01_000001_create_oauth_auth_codes_table', 1),
(4, '2016_06_01_000002_create_oauth_access_tokens_table', 1),
(5, '2016_06_01_000003_create_oauth_refresh_tokens_table', 1),
(6, '2016_06_01_000004_create_oauth_clients_table', 1),
(7, '2016_06_01_000005_create_oauth_personal_access_clients_table', 1),
(8, '2019_08_19_000000_create_failed_jobs_table', 1),
(9, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(10, '2025_04_23_202851_create_base_uoms_table', 1),
(11, '2025_04_23_202852_create_unit_of_measures_table', 1),
(12, '2025_04_23_202924_create_categories_table', 1),
(13, '2025_04_25_044051_create_brands_table', 2),
(14, '2025_04_25_082333_create_products_table', 3),
(18, '2025_04_28_114946_create_product_inventories_table', 4),
(19, '2025_05_05_150730_create_product_dimensions_table', 5),
(20, '2025_05_05_170025_create_product_commercials_table', 6),
(21, '2025_05_05_192440_create_product_others_table', 7),
(22, '2025_05_06_065912_create_business_parties_table', 8),
(23, '2025_05_06_182735_create_business_contacts_table', 9),
(25, '2025_05_10_111334_create_warehouses_table', 10),
(26, '2025_05_10_143040_create_areas_table', 11),
(28, '2025_05_11_064420_create_zones_table', 12),
(29, '2025_05_11_064635_create_locations_table', 13),
(30, '2025_05_14_030821_create_material_handling_eqs_table', 14),
(31, '2025_05_14_045622_create_storage_equipment_table', 14),
(32, '2025_05_14_071238_create_pallet_equipment_table', 14),
(33, '2025_05_14_104056_create_dock_equipment_table', 14),
(34, '2025_05_17_101633_create_employees_table', 15),
(35, '2025_05_17_155912_create_order_types_table', 16),
(36, '2025_05_17_162214_create_shipping_carriers_table', 16),
(37, '2025_05_17_164346_create_financial_categories_table', 16),
(38, '2025_05_17_170051_create_cost_types_table', 16),
(39, '2025_05_18_115815_create_currencies_table', 16),
(40, '2025_05_18_144258_create_taxes_table', 16),
(41, '2025_05_18_145831_create_payment_terms_table', 16),
(42, '2025_05_18_160817_create_countries_table', 16),
(43, '2025_05_19_134846_create_states_table', 17),
(44, '2025_05_19_140045_create_cities_table', 17),
(45, '2025_05_20_075236_create_statuses_table', 18),
(46, '2025_05_20_080844_create_activity_types_table', 18),
(49, '2025_05_27_101539_create_advanced_shipping_notices_table', 19),
(51, '2025_05_27_173048_create_inbound_shipments_table', 19),
(52, '2025_05_27_175823_create_inbound_shipment_details_table', 19),
(53, '2025_05_28_011543_create_receiving_appointments_table', 20),
(54, '2025_05_28_054908_create_unloading_sessions_table', 21),
(56, '2025_05_28_062648_create_quality_inspections_table', 22),
(57, '2025_06_01_081021_create_good_received_notes_table', 23),
(58, '2025_06_01_163838_create_good_received_note_items_table', 24),
(63, '2025_06_03_083848_create_receiving_exceptions_table', 25),
(64, '2025_06_03_093547_create_put_away_tasks_table', 25),
(65, '2025_06_04_095225_create_cross_docking_tasks_table', 26),
(66, '2025_06_04_132019_create_receiving_labor_trackings_table', 27),
(67, '2025_06_05_163057_create_receiving_docks_table', 27),
(68, '2025_06_05_164841_create_staging_locations_table', 27),
(69, '2025_06_05_170323_create_receiving_equipment_table', 27),
(71, '2025_05_27_173046_create_advanced_shipping_notice_details_table', 28);

-- --------------------------------------------------------

--
-- Table structure for table `oauth_access_tokens`
--

CREATE TABLE `oauth_access_tokens` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oauth_access_tokens`
--

INSERT INTO `oauth_access_tokens` (`id`, `user_id`, `client_id`, `name`, `scopes`, `revoked`, `created_at`, `updated_at`, `expires_at`) VALUES
('01113ba7fd7cab8c81548097dc763a593cdc33ff3dc79816c64b475ad4f559b9ae0d618bddb68821', 1, 1, 'laravel10', '[]', 0, '2025-05-10 04:36:35', '2025-05-10 04:36:36', '2026-05-10 11:06:35'),
('034646588ca0e84e57a8a5e26c282c815d1e236c8c50efb9a709a521cf8f0c3ce4b2caab493b12d4', 1, 1, 'laravel10', '[]', 0, '2025-06-02 04:48:12', '2025-06-02 04:48:12', '2026-06-02 11:18:12'),
('20e3a34692e27e2f4aa90f6a0c354db1dd86664f2b87636149e1e87f44e3c751bb3655c69ca5aa7f', 1, 1, 'laravel10', '[]', 0, '2025-05-30 02:13:14', '2025-05-30 02:13:14', '2026-05-30 08:43:14'),
('21659514655c33023375a1425d9755bd7a9fb0f3737e7c28db9685e754bf47cffe53720ea931edab', 1, 1, 'laravel10', '[]', 0, '2025-05-12 06:10:16', '2025-05-12 06:10:17', '2026-05-12 12:40:16'),
('26e043a68fe3efed7b2573b2f2931e7a0947486d092baba46dad87c624001421dbcca4d90a4bf6fc', 1, 1, 'laravel10', '[]', 0, '2025-05-30 02:10:22', '2025-05-30 02:10:22', '2026-05-30 08:40:22'),
('311a7c46568b60f583b908afaadbabe593f4fea5a1a0c5027a239572d5b2a624d8d35cfebf680c80', 1, 1, 'laravel10', '[]', 0, '2025-06-01 22:20:06', '2025-06-01 22:20:06', '2026-06-02 04:50:06'),
('36dc3d246e843f5417261f590c788eff632088189aada7f6edccd9b6405748fdce75907d548cfe2a', 2, 1, 'Google Login Token', '[]', 0, '2025-06-02 03:36:57', '2025-06-02 03:36:57', '2026-06-02 10:06:57'),
('48ead80e13eba8e8a8cddb1a1f31ee26aa7f80197a98539fbc48e0483d5dbdd2211f17e186bf530b', 1, 1, 'laravel10', '[]', 0, '2025-04-24 14:21:05', '2025-04-24 14:21:05', '2026-04-24 20:51:05'),
('59c31833014b6fddc3477f67547eae5420d9b48023896255abcf73bc44e73808345033e02f7a21ff', 1, 1, 'laravel10', '[]', 0, '2025-06-18 07:53:44', '2025-06-18 07:53:44', '2026-06-18 14:23:44'),
('6323c151b4c05f6ec93ed5e974dd500117771c15efcabaa47892fabad978836f0bc45add7a46bc5b', 1, 1, 'laravel10', '[]', 0, '2025-05-30 01:58:29', '2025-05-30 01:58:29', '2026-05-30 08:28:29'),
('652a047e8932dc1044c0471e20af890631c6a1d746c2bf2695873bf4c63849e46fc2abdde547f0c6', 1, 1, 'laravel10', '[]', 0, '2025-05-07 12:09:11', '2025-05-07 12:09:11', '2026-05-07 18:39:11'),
('6f7a4f2762622ca550cf2c2ee3d771cdb7b5f245b0809cda1845eff3ae276de4a52d8597571941c5', 1, 1, 'laravel10', '[]', 0, '2025-05-16 22:53:34', '2025-05-16 22:53:34', '2026-05-17 05:23:34'),
('7911b55a85bbb97602273571a59fd21cee068553d720e8810363de2ac607bc1f22bbc9df5ea21911', 1, 1, 'laravel10', '[]', 0, '2025-05-30 02:05:20', '2025-05-30 02:05:20', '2026-05-30 08:35:20'),
('8c2c8fc315cb2aed9e1263927b3a3c4bee567520096130b7a49f54ec1347c854e94f4e345cec534b', 1, 1, 'laravel10', '[]', 0, '2025-04-24 15:15:34', '2025-04-24 15:15:34', '2026-04-24 21:45:34'),
('93184712187fe741379ae14aa4c943c1eab65dde35bad5837239490a94c1d534eb7620b1f73e856a', 2, 1, 'Google Login Token', '[]', 0, '2025-05-07 12:05:35', '2025-05-07 12:05:35', '2026-05-07 18:35:35'),
('9458037b28654f017a7b2876bb952762e8e5cfe6816b6e3c15a0034b75c8d8db50e9f8414c2a453d', 2, 1, 'Google Login Token', '[]', 0, '2025-05-07 12:07:29', '2025-05-07 12:07:29', '2026-05-07 18:37:29'),
('96966a9312559c581263dfb797fbabadbe09a794e26a8dd813acd9a305db3d9a4b111861d3c81a26', 1, 1, 'laravel10', '[]', 0, '2025-04-25 04:04:26', '2025-04-25 04:04:26', '2026-04-25 10:34:26'),
('9c91f95743ad4d9db2a2205ad97b70bd13d9e8fa06719ec5f91a96ac46979638ffa9ca4e07b0ab4a', 1, 1, 'laravel10', '[]', 0, '2025-05-30 02:07:27', '2025-05-30 02:07:27', '2026-05-30 08:37:27'),
('a6f702d5fdb9779fbfb8e54b75951475132329b9c746604a7cd00c9f76719269d9bef53247c8b24d', 3, 1, 'laravel10', '[]', 0, '2025-05-21 01:10:02', '2025-05-21 01:10:02', '2026-05-21 07:40:02'),
('a7f67a7b3b1eef9cbb94c4279051f421fdae67fab0f79bb4d81a17fc1ee7f69d002c1351f2dce297', 1, 1, 'laravel10', '[]', 0, '2025-06-01 22:16:47', '2025-06-01 22:16:47', '2026-06-02 04:46:47'),
('a916b3a7c8583768f623a199e837b71b4321c58fbd4887021e4603d08d4cacb61c957b11d68182c6', 1, 1, 'laravel10', '[]', 0, '2025-05-31 13:41:54', '2025-05-31 13:41:55', '2026-05-31 20:11:54'),
('aa99c8d395bd7620a4a8c212fb3b2b8e86ccbb736fcc2e10db229f0182415c726ba5763f6d894a5e', 1, 1, 'laravel10', '[]', 0, '2025-04-26 01:28:18', '2025-04-26 01:28:18', '2026-04-26 07:58:18'),
('b3af29ea6c281a520a8d69051ef3d1bcea51d2490b939bef8f065eb131ec70aaedcfae5eba3bd437', 2, 1, 'Google Login Token', '[]', 0, '2025-05-07 07:24:01', '2025-05-07 07:24:01', '2026-05-07 13:54:01'),
('b43ed90df20722359ece508b83c50d067e3970b9e61a5c1c4dfef2782c9a6e6bc7aea5bb4267162a', 1, 1, 'laravel10', '[]', 0, '2025-06-16 03:24:28', '2025-06-16 03:24:28', '2026-06-16 09:54:28'),
('b4664367fc437d98c5ca289ed7859fbcbd52c05abb603e056d5e25e201813a27297ee6bc6ab3691f', 1, 1, 'laravel10', '[]', 0, '2025-05-13 06:25:44', '2025-05-13 06:25:44', '2026-05-13 12:55:44'),
('b6f33cec5fc1d6ab641d450dd23418debc44fd2bceddcee63be546ab073ba9c3c69664eb7f1b8e37', 1, 1, 'laravel10', '[]', 0, '2025-06-18 07:54:36', '2025-06-18 07:54:36', '2026-06-18 14:24:36'),
('b961b9795c887cc7357154f4bab8cc02312acf11cec47b078c4dc6454379356c418b48185789ef93', 1, 1, 'laravel10', '[]', 0, '2025-04-24 23:22:19', '2025-04-24 23:22:19', '2026-04-25 05:52:19'),
('c3ffd60f82511634e9b394ba70cb816e204fd74a8ebedd2dc403e7a3880238fe5fdf9aec5f42aac8', 1, 1, 'laravel10', '[]', 0, '2025-05-12 06:10:13', '2025-05-12 06:10:13', '2026-05-12 12:40:13'),
('c81fe67280c3a8182a83fc09d9e807987f0ba31ec60771e925a3af16a9a8991d0939f6f6e6fce615', 1, 1, 'laravel10', '[]', 0, '2025-06-08 22:30:40', '2025-06-08 22:30:41', '2026-06-09 05:00:40'),
('da53e3df38ad72ed2729560058d07301e519ac0dda0abc3a80e795a7e44cbf474c497d3af1abaad4', 1, 1, 'laravel10', '[]', 0, '2025-06-01 00:20:20', '2025-06-01 00:20:20', '2026-06-01 06:50:20'),
('e1cd0669c5f9488571f522cbcd2f07d3b7722b027151912df877e76187ba705d4297d94394bd38d9', 1, 1, 'laravel10', '[]', 0, '2025-06-18 07:55:57', '2025-06-18 07:55:57', '2026-06-18 14:25:57'),
('f774aafd2f7ab3eb35908b98d5b6256eb8fb26ec846a42bae4628636f96451b028bbc4647ffe686d', 1, 1, 'laravel10', '[]', 0, '2025-04-25 04:02:57', '2025-04-25 04:02:57', '2026-04-25 10:32:57'),
('ffcfcc37c18866625ce6112b9cdca142bf499044a10e030a56efb21d2db39557e144cc261277a80e', 1, 1, 'laravel10', '[]', 0, '2025-05-30 02:06:14', '2025-05-30 02:06:14', '2026-05-30 08:36:14');

-- --------------------------------------------------------

--
-- Table structure for table `oauth_auth_codes`
--

CREATE TABLE `oauth_auth_codes` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_clients`
--

CREATE TABLE `oauth_clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `secret` varchar(100) DEFAULT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `redirect` text NOT NULL,
  `personal_access_client` tinyint(1) NOT NULL,
  `password_client` tinyint(1) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oauth_clients`
--

INSERT INTO `oauth_clients` (`id`, `user_id`, `name`, `secret`, `provider`, `redirect`, `personal_access_client`, `password_client`, `revoked`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Laravel Personal Access Client', 'oQw7kdx8YNXjeUGsIaZEeZcz7tAGBgNvMzQOMT9f', NULL, 'http://localhost', 1, 0, 0, '2025-04-24 14:17:38', '2025-04-24 14:17:38'),
(2, NULL, 'Laravel Password Grant Client', 'YRoBWYNwcG69nK6sGsx5G23pBpTGd9YNyvuoAf8m', 'users', 'http://localhost', 0, 1, 0, '2025-04-24 14:17:38', '2025-04-24 14:17:38');

-- --------------------------------------------------------

--
-- Table structure for table `oauth_personal_access_clients`
--

CREATE TABLE `oauth_personal_access_clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oauth_personal_access_clients`
--

INSERT INTO `oauth_personal_access_clients` (`id`, `client_id`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-04-24 14:17:38', '2025-04-24 14:17:38');

-- --------------------------------------------------------

--
-- Table structure for table `oauth_refresh_tokens`
--

CREATE TABLE `oauth_refresh_tokens` (
  `id` varchar(100) NOT NULL,
  `access_token_id` varchar(100) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_types`
--

CREATE TABLE `order_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_type_code` varchar(255) NOT NULL,
  `order_type_name` varchar(255) NOT NULL,
  `direction` varchar(255) NOT NULL,
  `priority_level` varchar(255) NOT NULL,
  `default_workflow` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_types`
--

INSERT INTO `order_types` (`id`, `order_type_code`, `order_type_name`, `direction`, `priority_level`, `default_workflow`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'o1212121', 'ggg', 'Inbound', 'Low', 'ffff', NULL, 0, '2025-05-18 22:08:55', '2025-05-18 22:10:50'),
(3, 'o1', 'ggg', 'Inbound', 'Low', 'ffff', NULL, 1, '2025-05-18 22:09:28', '2025-05-18 22:09:28');

-- --------------------------------------------------------

--
-- Table structure for table `pallet_equipment`
--

CREATE TABLE `pallet_equipment` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pallet_code` varchar(255) NOT NULL,
  `pallet_name` varchar(255) NOT NULL,
  `pallet_type` varchar(255) NOT NULL,
  `material` varchar(255) NOT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `length` varchar(255) DEFAULT NULL,
  `width` varchar(255) DEFAULT NULL,
  `height` varchar(255) DEFAULT NULL,
  `weight_capacity` varchar(255) DEFAULT NULL,
  `empty_weight` varchar(255) DEFAULT NULL,
  `condition` tinyint(4) DEFAULT NULL COMMENT ' 0 - good / 1 - excellent / 2 - fair / 3 - poor / 4 - damaged ',
  `current_location` varchar(255) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `last_inspection_date` date DEFAULT NULL,
  `next_inspection_date` date DEFAULT NULL,
  `pooled_pallet` tinyint(4) DEFAULT NULL COMMENT '0 - no / 1 - yes',
  `pool_provider` varchar(255) DEFAULT NULL,
  `cost_per_unit` varchar(255) DEFAULT NULL,
  `expected_lifespan_year` int(11) DEFAULT NULL,
  `rfid_tag` varchar(255) DEFAULT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `currently_assigned` varchar(255) DEFAULT NULL,
  `assigned_shipment` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - available / 1 - in use / 2 - reserved / 3 - under repair / 4 - Quarantined / 5 - disposed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pallet_equipment`
--

INSERT INTO `pallet_equipment` (`id`, `pallet_code`, `pallet_name`, `pallet_type`, `material`, `manufacturer`, `length`, `width`, `height`, `weight_capacity`, `empty_weight`, `condition`, `current_location`, `purchase_date`, `last_inspection_date`, `next_inspection_date`, `pooled_pallet`, `pool_provider`, `cost_per_unit`, `expected_lifespan_year`, `rfid_tag`, `barcode`, `currently_assigned`, `assigned_shipment`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'PLT123456789', 'Standard Wood Pallet', 'Standard 4-way', 'Wood', 'Pallet Co.', '1.2', '1', '0.5', '1500', '25', 1, 'Zone A-1', NULL, NULL, '0003-03-31', 0, NULL, NULL, NULL, NULL, '0003-03-31', NULL, NULL, 2, NULL, '2025-05-16 21:24:54', '2025-05-19 06:29:52'),
(2, 'PLT987654321', 'Heavy Duty Plastic Pallet', 'Heavy Duty 4-way', 'Plastic', 'Dura Pallet Inc.', '1.2', '1', '0.6', '2000', '22', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2025-05-16 21:30:59', '2025-05-19 06:32:10'),
(3, 'PLT456789123', 'EUR Pallet', 'EUR / EPAL', 'Wood', 'Euro Pallet Systems', '1.9', '1', '-1', '1500', '25', 2, NULL, NULL, NULL, NULL, 1, 'ddd', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2025-05-16 21:36:42', '2025-05-19 06:33:27');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_terms`
--

CREATE TABLE `payment_terms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_term_code` varchar(255) NOT NULL,
  `payment_term_name` varchar(255) NOT NULL,
  `payment_type` varchar(255) NOT NULL,
  `payment_due_day` varchar(255) DEFAULT NULL,
  `discount_percent` varchar(255) DEFAULT NULL,
  `discount_day` int(11) DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `modified_by` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_terms`
--

INSERT INTO `payment_terms` (`id`, `payment_term_code`, `payment_term_name`, `payment_type`, `payment_due_day`, `discount_percent`, `discount_day`, `created_by`, `modified_by`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'dfdfdfdfd', 'rr', 'Credit', NULL, NULL, 33, NULL, NULL, NULL, 1, '2025-05-19 05:22:01', '2025-05-19 05:24:01');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_code` varchar(255) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `subcategory_id` bigint(20) UNSIGNED NOT NULL,
  `brand_id` bigint(20) UNSIGNED NOT NULL,
  `part_no` varchar(255) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_code`, `product_name`, `category_id`, `subcategory_id`, `brand_id`, `part_no`, `status`, `description`, `created_at`, `updated_at`) VALUES
(7, 'SKU-001', 'Mobile Phone X1', 2, 3, 8, 'MPX1-001', 1, NULL, '2025-05-07 12:10:12', '2025-05-12 06:37:22'),
(8, 'SKU-002', 'Smart Kettle', 2, 4, 6, 'SK-202', 1, NULL, '2025-05-12 06:39:24', '2025-05-12 06:39:24'),
(9, 'SKU-003', 'Mystery in the Manor', 2, 4, 7, 'F-MM-01', 1, NULL, '2025-05-12 06:41:44', '2025-05-12 06:41:44'),
(10, 'SKU-004', 'Laptop', 2, 4, 6, 'Part 002', 1, NULL, '2025-05-12 06:50:30', '2025-05-12 06:50:30');

-- --------------------------------------------------------

--
-- Table structure for table `product_commercials`
--

CREATE TABLE `product_commercials` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `customer_code` varchar(255) NOT NULL,
  `bar_code` varchar(255) NOT NULL,
  `cost_price` varchar(255) NOT NULL,
  `standard_price` varchar(255) NOT NULL,
  `currency` varchar(255) NOT NULL,
  `discount` varchar(255) DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `country_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_commercials`
--

INSERT INTO `product_commercials` (`id`, `product_id`, `customer_code`, `bar_code`, `cost_price`, `standard_price`, `currency`, `discount`, `supplier_id`, `manufacturer`, `country_code`, `created_at`, `updated_at`) VALUES
(2, 8, 'Cus-002', '1.23E+12', '300', '100', 'EUR', '0.08', 2, 'Manufacturer Y', 'CN', '2025-05-12 00:28:00', '2025-05-12 07:04:15'),
(3, 7, 'CUS-001', '1.23E+11', '400', '500', 'USD', '10', 2, 'Manufacturer X', 'US', '2025-05-12 02:26:40', '2025-05-12 07:03:30');

-- --------------------------------------------------------

--
-- Table structure for table `product_dimensions`
--

CREATE TABLE `product_dimensions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `dimension_use` varchar(255) NOT NULL,
  `length` varchar(255) DEFAULT NULL,
  `width` varchar(255) DEFAULT NULL,
  `height` varchar(255) DEFAULT NULL,
  `weight` varchar(255) DEFAULT NULL,
  `volume` varchar(255) DEFAULT NULL,
  `storage_volume` varchar(255) DEFAULT NULL,
  `space_area` varchar(255) DEFAULT NULL,
  `units_per_box` varchar(255) DEFAULT NULL,
  `boxes_per_pallet` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_dimensions`
--

INSERT INTO `product_dimensions` (`id`, `product_id`, `dimension_use`, `length`, `width`, `height`, `weight`, `volume`, `storage_volume`, `space_area`, `units_per_box`, `boxes_per_pallet`, `created_at`, `updated_at`) VALUES
(8, 7, 'Dimension', '33', '33', '33', '33', '33', '33', '33', '33', '33', '2025-05-07 12:10:35', '2025-05-07 12:10:35'),
(10, 8, 'Dimension', '30', '20', '10', '1.2', '6000', '3000', '600', '10', '20', '2025-05-12 07:01:50', '2025-05-12 07:01:50');

-- --------------------------------------------------------

--
-- Table structure for table `product_inventories`
--

CREATE TABLE `product_inventories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `warehouse_code` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `batch_no` varchar(255) NOT NULL,
  `lot_no` varchar(255) NOT NULL,
  `packing_qty` int(11) DEFAULT NULL,
  `whole_qty` int(11) DEFAULT NULL,
  `loose_qty` int(11) DEFAULT NULL,
  `reorder_level` int(11) DEFAULT NULL,
  `stock_rotation_policy` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_inventories`
--

INSERT INTO `product_inventories` (`id`, `product_id`, `uom_id`, `warehouse_code`, `location`, `batch_no`, `lot_no`, `packing_qty`, `whole_qty`, `loose_qty`, `reorder_level`, `stock_rotation_policy`, `created_at`, `updated_at`) VALUES
(2, 7, 1, 'WH-ELECTRO-01', 'Aisle 1-Shelf 3', 'B20250301', 'L2025A100', 10, 10, 1, 10, 'FIFO', '2025-05-12 06:56:19', '2025-05-12 06:56:19'),
(3, 8, 2, 'WH-HOME-01', 'L2025B50', 'B20250220', 'L2025B50', 22, 2, 2, 6, 'FIFO', '2025-05-12 06:57:58', '2025-05-12 06:57:58'),
(4, 9, 3, 'WH-BOOK-01', 'Aisle 3-Shelf 5', 'B20241115', 'L2024C250', 33, 5, 55, 5, 'LIFO', '2025-05-12 06:59:26', '2025-05-12 06:59:26'),
(5, 10, 1, 'WH-000-1', 'L2025D100', 'B20250228', 'L2025D100', 11, 111, 11, 3, 'FEFO', '2025-05-12 07:00:41', '2025-05-12 07:00:41');

-- --------------------------------------------------------

--
-- Table structure for table `product_others`
--

CREATE TABLE `product_others` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `manufacture_date` varchar(255) NOT NULL,
  `expire_date` varchar(255) NOT NULL,
  `abc_category_value` varchar(255) NOT NULL,
  `abc_category_activity` varchar(255) NOT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `custom_attributes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_others`
--

INSERT INTO `product_others` (`id`, `product_id`, `manufacture_date`, `expire_date`, `abc_category_value`, `abc_category_activity`, `remark`, `custom_attributes`, `created_at`, `updated_at`) VALUES
(3, 7, '2025-05-06', '2028-05-08', 'A', 'High Activity', NULL, NULL, '2025-05-12 07:04:50', '2025-05-12 07:04:50'),
(4, 8, '2025-04-06', '2028-05-08', 'B', 'Medium Activity', NULL, NULL, '2025-05-12 07:05:18', '2025-05-12 07:05:18');

-- --------------------------------------------------------

--
-- Table structure for table `put_away_tasks`
--

CREATE TABLE `put_away_tasks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `put_away_task_code` varchar(255) NOT NULL,
  `inbound_shipment_detail_id` bigint(20) UNSIGNED NOT NULL,
  `assigned_to_id` bigint(20) UNSIGNED NOT NULL,
  `created_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `start_time` date DEFAULT NULL,
  `complete_time` date DEFAULT NULL,
  `source_location_id` bigint(20) UNSIGNED NOT NULL,
  `destination_location_id` bigint(20) UNSIGNED NOT NULL,
  `qty` int(11) DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT 0 COMMENT '0 - low / 1 - medium / 2 - high',
  `status` int(11) NOT NULL DEFAULT 0 COMMENT '0 - pending / 1 - in progress / 2 - completed',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `put_away_tasks`
--

INSERT INTO `put_away_tasks` (`id`, `put_away_task_code`, `inbound_shipment_detail_id`, `assigned_to_id`, `created_date`, `due_date`, `start_time`, `complete_time`, `source_location_id`, `destination_location_id`, `qty`, `priority`, `status`, `created_at`, `updated_at`) VALUES
(2, 'PA00001', 3, 1, NULL, NULL, NULL, NULL, 2, 3, 100, 0, 0, '2025-06-17 07:09:11', '2025-06-17 07:09:11');

-- --------------------------------------------------------

--
-- Table structure for table `quality_inspections`
--

CREATE TABLE `quality_inspections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `quality_inspection_code` varchar(255) NOT NULL,
  `inbound_shipment_detail_id` bigint(20) UNSIGNED NOT NULL,
  `inspector_name` varchar(255) NOT NULL,
  `inspection_date` date DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 - pending / 1 - failed / 2 - passed',
  `rejection_reason` text DEFAULT NULL,
  `sample_size` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `corrective_action` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receiving_appointments`
--

CREATE TABLE `receiving_appointments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `appointment_code` varchar(255) NOT NULL,
  `inbound_shipment_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `dock_id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `start_time` date DEFAULT NULL,
  `end_time` date DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 - scheduled / 1 - confirmed / 2 - in progress / 3 - completed / 4 - cancelled\r\n\r\n',
  `carrier_name` varchar(255) DEFAULT NULL,
  `driver_name` varchar(255) DEFAULT NULL,
  `driver_phone_number` varchar(255) DEFAULT NULL,
  `trailer_number` varchar(255) DEFAULT NULL,
  `estimated_pallet` int(11) DEFAULT NULL,
  `check_in_time` date DEFAULT NULL,
  `check_out_time` date DEFAULT NULL,
  `version_control` tinyint(4) DEFAULT 0 COMMENT '0 - Lite / 1 - Pro / 2 - Legend',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receiving_docks`
--

CREATE TABLE `receiving_docks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `dock_code` varchar(255) NOT NULL,
  `dock_number` varchar(255) NOT NULL,
  `dock_type` varchar(255) NOT NULL,
  `zone_id` bigint(20) UNSIGNED NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 2 COMMENT '0 - out of service / 1 - in used / 2 - available',
  `features` varchar(255) DEFAULT NULL,
  `additional_features` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `receiving_docks`
--

INSERT INTO `receiving_docks` (`id`, `dock_code`, `dock_number`, `dock_type`, `zone_id`, `status`, `features`, `additional_features`, `created_at`, `updated_at`) VALUES
(2, 'DCK002', 'D-01', 'Truck', 1, 0, '[\"Standard Dock\",\"Level Adjuster\"]', NULL, '2025-06-17 07:42:11', '2025-06-17 07:42:11');

-- --------------------------------------------------------

--
-- Table structure for table `receiving_equipment`
--

CREATE TABLE `receiving_equipment` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `receiving_equipment_code` varchar(255) NOT NULL,
  `receiving_equipment_name` varchar(255) NOT NULL,
  `receiving_equipment_type` varchar(255) NOT NULL,
  `assigned_to_id` bigint(20) UNSIGNED DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `days_since_maintenance` int(11) DEFAULT NULL,
  `version_control` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 - lite / 1 - pro / 2 -legend',
  `status` tinyint(4) NOT NULL DEFAULT 2 COMMENT '0 - in use / 1 - maintenance / 2 - available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `receiving_equipment`
--

INSERT INTO `receiving_equipment` (`id`, `receiving_equipment_code`, `receiving_equipment_name`, `receiving_equipment_type`, `assigned_to_id`, `last_maintenance_date`, `notes`, `days_since_maintenance`, `version_control`, `status`, `created_at`, `updated_at`) VALUES
(1, 'EQP001', 'FL-01', 'Forklift', 1, NULL, NULL, NULL, 0, 0, '2025-06-08 22:40:54', '2025-06-08 22:40:54');

-- --------------------------------------------------------

--
-- Table structure for table `receiving_exceptions`
--

CREATE TABLE `receiving_exceptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exception_code` varchar(255) NOT NULL,
  `asn_id` bigint(20) UNSIGNED NOT NULL,
  `asn_detail_id` bigint(20) UNSIGNED NOT NULL,
  `exception_type` varchar(255) NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `item_description` text DEFAULT NULL,
  `severity` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 - Low / 1 - medium / 2 - high / 3 - critical',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 - pending info / 1 - in progress / 2 - open / 3 - resolved',
  `reported_by_id` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_to_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reported_date` date DEFAULT NULL,
  `resolved_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receiving_labor_trackings`
--

CREATE TABLE `receiving_labor_trackings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `labor_entry_code` varchar(255) NOT NULL,
  `emp_id` bigint(20) UNSIGNED NOT NULL,
  `inbound_shipment_id` bigint(20) UNSIGNED NOT NULL,
  `task_type` varchar(255) NOT NULL,
  `start_time` date DEFAULT NULL,
  `end_time` date DEFAULT NULL,
  `duration_min` int(11) DEFAULT NULL,
  `items_processed` int(11) DEFAULT NULL,
  `pallets_processed` int(11) DEFAULT NULL,
  `items_min` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `version_control` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 - lite / 1 - pro / 2 - legend',
  `status` tinyint(4) DEFAULT 0 COMMENT '0 - active / 1 - in active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipping_carriers`
--

CREATE TABLE `shipping_carriers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `carrier_code` varchar(255) NOT NULL,
  `carrier_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `phone_number` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `contract_details` varchar(255) DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `service_type` varchar(255) DEFAULT NULL,
  `tracking_url` varchar(255) DEFAULT NULL,
  `performance_rating` varchar(255) DEFAULT NULL,
  `capabilities` varchar(255) DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `last_modified_by` varchar(225) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shipping_carriers`
--

INSERT INTO `shipping_carriers` (`id`, `carrier_code`, `carrier_name`, `contact_person`, `phone_number`, `email`, `address`, `country`, `contract_details`, `payment_terms`, `service_type`, `tracking_url`, `performance_rating`, `capabilities`, `created_by`, `last_modified_by`, `status`, `created_at`, `updated_at`) VALUES
(1, 'CA001', 'Carrier A Logistics', 'John Doe', '123-456-7890', 'john@carrierA.com', '123 Logistics St, City, USA', 'USA', 'Contract valid until 2025', 'Net 15', 'Ground', 'http://track.carrierA.com', 'Good', 'Hazmat certified', NULL, NULL, 1, '2025-05-18 22:47:55', '2025-05-19 06:42:41'),
(2, 'CB002', 'Carrier B Express', 'Jane Smith', '987-654-3210', 'jane@carrierB.com', '456 Express Ave, City, Canada', 'Canada', 'Annual renewal', 'Cash On Delivery', 'Air', 'http://track.carrierB.com', 'Good', 'Refrigerated shipments', NULL, NULL, 1, '2025-05-18 22:48:42', '2025-05-19 06:44:20');

-- --------------------------------------------------------

--
-- Table structure for table `staging_locations`
--

CREATE TABLE `staging_locations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `staging_location_code` varchar(255) NOT NULL,
  `staging_location_name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `area_id` int(11) NOT NULL,
  `zone_id` int(11) NOT NULL,
  `capacity` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `current_usage` int(11) DEFAULT NULL,
  `last_updated` date DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 2 COMMENT '0 - in active / 1 - maintenance / 2 - active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staging_locations`
--

INSERT INTO `staging_locations` (`id`, `staging_location_code`, `staging_location_name`, `type`, `warehouse_id`, `area_id`, `zone_id`, `capacity`, `description`, `current_usage`, `last_updated`, `status`, `created_at`, `updated_at`) VALUES
(2, 'STAG-0011', 'Staging A', 'General', 1, 2, 3, 100, NULL, 50, NULL, 2, '2025-06-17 02:19:04', '2025-06-17 07:50:48'),
(3, 'RECV-A1', 'Receiving Staging', 'General', 1, 1, 1, 1000, NULL, 500, NULL, 2, '2025-06-17 07:51:22', '2025-06-17 07:51:22');

-- --------------------------------------------------------

--
-- Table structure for table `states`
--

CREATE TABLE `states` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `state_code` varchar(255) NOT NULL,
  `state_name` varchar(255) NOT NULL,
  `state_type` varchar(255) NOT NULL,
  `capital` varchar(255) NOT NULL,
  `country_id` bigint(20) UNSIGNED NOT NULL,
  `postal_code_prefix` int(11) DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `last_modified_by` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `states`
--

INSERT INTO `states` (`id`, `state_code`, `state_name`, `state_type`, `capital`, `country_id`, `postal_code_prefix`, `created_by`, `last_modified_by`, `status`, `created_at`, `updated_at`) VALUES
(1, 'MM-YGN', 'Yangon Region', 'Region', 'Yangon', 1, 11, NULL, NULL, 1, '2025-05-19 08:19:05', '2025-05-19 08:19:05'),
(2, 'MM-MDY', 'Mandalay Region', 'Region', 'Mandalay', 1, 5, NULL, NULL, 1, '2025-05-19 08:20:45', '2025-05-19 08:20:45');

-- --------------------------------------------------------

--
-- Table structure for table `statuses`
--

CREATE TABLE `statuses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `status_code` varchar(255) NOT NULL,
  `status_name` varchar(255) NOT NULL,
  `entity_type` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `last_modified_by` varchar(255) DEFAULT NULL,
  `analytics_flag` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `statuses`
--

INSERT INTO `statuses` (`id`, `status_code`, `status_name`, `entity_type`, `category`, `description`, `created_by`, `last_modified_by`, `analytics_flag`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ORD-PND', 'Order Pending', 'Order', 'In Progress', 'Order received but not yet processed', NULL, NULL, 'Yes', 1, '2025-05-20 05:10:18', '2025-05-20 05:10:18'),
(2, 'ORD-CNF', 'Order Confirmed', 'Order', 'In Progress', 'Order confirmed and ready for picking', NULL, NULL, 'Yes', 1, '2025-05-20 05:11:04', '2025-05-20 05:11:04');

-- --------------------------------------------------------

--
-- Table structure for table `storage_equipment`
--

CREATE TABLE `storage_equipment` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `storage_equipment_code` varchar(255) NOT NULL,
  `storage_equipment_name` varchar(255) NOT NULL,
  `storage_equipment_type` varchar(255) NOT NULL,
  `manufacturer` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `serial_number` varchar(255) NOT NULL,
  `purchase_date` date NOT NULL,
  `warranty_expire_date` date NOT NULL,
  `zone_id` bigint(20) UNSIGNED DEFAULT NULL,
  `aisle` varchar(255) DEFAULT NULL,
  `bay` varchar(255) DEFAULT NULL,
  `level` varchar(255) DEFAULT NULL,
  `installation_date` date DEFAULT NULL,
  `last_inspection_date` date DEFAULT NULL,
  `next_inspection_due_date` date DEFAULT NULL,
  `inspection_frequency` varchar(255) DEFAULT NULL,
  `max_weight_capacity` varchar(255) DEFAULT NULL,
  `max_volume_capacity` varchar(255) DEFAULT NULL,
  `length` varchar(255) DEFAULT NULL,
  `width` varchar(255) DEFAULT NULL,
  `height` varchar(255) DEFAULT NULL,
  `material` varchar(255) DEFAULT NULL,
  `shelves_tiers_number` varchar(255) DEFAULT NULL,
  `adjustability` varchar(255) DEFAULT '1' COMMENT '1 - fixed / 2 - fixed lanes / 3 - adjustable beans / 4 - adjustable shelves / 5 - cart & carrier System',
  `safety_features` varchar(255) DEFAULT NULL,
  `load_type` varchar(255) DEFAULT NULL,
  `accessibility` varchar(255) DEFAULT NULL,
  `uptime_percentage_monthly` varchar(255) DEFAULT NULL,
  `maintenance_cost` varchar(255) DEFAULT NULL,
  `currency_unit` varchar(255) DEFAULT NULL,
  `depreciation_start_date` date DEFAULT NULL,
  `depreciation_method` varchar(255) DEFAULT NULL,
  `estimated_useful_life_year` varchar(255) DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `expected_replacement_date` date DEFAULT NULL,
  `disposal_date` varchar(255) DEFAULT NULL,
  `replacement_mhe_code` varchar(255) DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `custom_attributes` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1 - operational / 2 - under maintenance',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `storage_equipment`
--

INSERT INTO `storage_equipment` (`id`, `storage_equipment_code`, `storage_equipment_name`, `storage_equipment_type`, `manufacturer`, `model`, `serial_number`, `purchase_date`, `warranty_expire_date`, `zone_id`, `aisle`, `bay`, `level`, `installation_date`, `last_inspection_date`, `next_inspection_due_date`, `inspection_frequency`, `max_weight_capacity`, `max_volume_capacity`, `length`, `width`, `height`, `material`, `shelves_tiers_number`, `adjustability`, `safety_features`, `load_type`, `accessibility`, `uptime_percentage_monthly`, `maintenance_cost`, `currency_unit`, `depreciation_start_date`, `depreciation_method`, `estimated_useful_life_year`, `supplier_id`, `expected_replacement_date`, `disposal_date`, `replacement_mhe_code`, `remark`, `custom_attributes`, `status`, `created_at`, `updated_at`) VALUES
(1, 'SE001', 'erer', 'Pallet Racking', 'gg', 'dfdfd', '3433434', '0001-11-11', '0001-11-11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[]', 'Cases', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-05-16 10:00:46', '2025-05-16 10:03:20'),
(2, 'STEQ001', 'Pallet Rack System A', 'Pallet Racking', 'Rack Manufacturers Inc.', 'Series 1000', '67890', '2009-02-22', '2003-02-22', NULL, 'Aisle 1', 'Bay1-10', 'level1-5', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-05-19 06:22:46', '2025-05-19 06:22:46'),
(3, 'STEQ002', 'Shelving Unit B-1', 'Wire Shelving', 'Quantum Storage', 'QWB74-3618', 'WS-B1-SN002', '2009-11-12', '2008-02-22', NULL, 'Aisle 5', 'Bay2-6', 'level1-6', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-05-19 06:26:11', '2025-05-19 06:26:11');

-- --------------------------------------------------------

--
-- Table structure for table `taxes`
--

CREATE TABLE `taxes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tax_code` varchar(255) NOT NULL,
  `tax_description` varchar(255) NOT NULL,
  `tax_type` varchar(255) NOT NULL,
  `tax_rate` varchar(255) NOT NULL,
  `effective_date` date NOT NULL,
  `tax_calculation_method` varchar(255) NOT NULL,
  `tax_authority` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `taxes`
--

INSERT INTO `taxes` (`id`, `tax_code`, `tax_description`, `tax_type`, `tax_rate`, `effective_date`, `tax_calculation_method`, `tax_authority`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 'tcfdfdf', 'ttcc', 'Withholding', '20', '0003-03-31', 'Percentage', 'fdfdfd', NULL, 1, '2025-05-19 04:48:30', '2025-05-19 04:51:23');

-- --------------------------------------------------------

--
-- Table structure for table `unit_of_measures`
--

CREATE TABLE `unit_of_measures` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uom_code` varchar(255) NOT NULL,
  `uom_name` varchar(255) NOT NULL,
  `base_uom_id` bigint(20) UNSIGNED NOT NULL,
  `conversion_factor` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `unit_of_measures`
--

INSERT INTO `unit_of_measures` (`id`, `uom_code`, `uom_name`, `base_uom_id`, `conversion_factor`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'PC', 'Pieces', 1, '1', 'aaaa', 1, '2025-04-24 14:21:28', '2025-05-12 06:10:50'),
(2, 'BX', 'Box', 2, '24', NULL, 1, '2025-05-06 01:18:38', '2025-05-12 06:11:02'),
(3, 'CRT', 'carton', 3, '4', NULL, 1, '2025-05-06 01:19:47', '2025-05-12 06:11:14'),
(5, 'PLT', 'Pallet', 3, '50', NULL, 1, '2025-05-12 06:11:59', '2025-05-12 06:11:59'),
(6, 'KG', 'Kilograms', 5, '1', NULL, 1, '2025-05-12 06:12:20', '2025-05-12 06:12:20'),
(7, 'GM', 'Gram', 5, '0.001', NULL, 1, '2025-05-12 06:14:24', '2025-05-12 06:14:24'),
(8, 'LTR', 'Liter', 7, '1', NULL, 1, '2025-05-12 06:14:41', '2025-05-12 06:14:41'),
(9, 'ML', 'Mililiter', 8, '1', NULL, 1, '2025-05-12 06:15:00', '2025-05-12 06:15:00');

-- --------------------------------------------------------

--
-- Table structure for table `unloading_sessions`
--

CREATE TABLE `unloading_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `unloading_session_code` varchar(255) NOT NULL,
  `inbound_shipment_id` bigint(20) UNSIGNED NOT NULL,
  `dock_id` bigint(20) UNSIGNED NOT NULL,
  `start_time` date DEFAULT NULL,
  `end_time` date DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 - in progress / 1 - planned / 2 - completed',
  `supervisor_id` bigint(20) UNSIGNED NOT NULL,
  `total_pallets_unloaded` int(11) DEFAULT NULL,
  `total_items_unloaded` int(11) DEFAULT NULL,
  `equipment_used` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `google_id` varchar(200) DEFAULT NULL,
  `dial_code` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `user_type` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 - user, 1 - admin',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `google_id`, `dial_code`, `phone_number`, `user_type`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Zaw Shine Htet', 'zaw@gmail.com', NULL, '+95', '9887876543', 0, NULL, '$2y$12$Er6CkOv4tNulU4Pzj3UrbeJ.EKcEFteVVhSr9EnmEbDI62Ilt32qO', NULL, '2025-04-24 14:20:54', '2025-04-24 14:20:54'),
(2, 'Zaw Shine Htet', 'primeshine.webdev@gmail.com', '102501245780153107431', NULL, NULL, 0, NULL, NULL, NULL, '2025-05-07 07:24:00', '2025-05-07 07:24:00'),
(3, 'Ko Hein Sithu Kyaw', 'heinsithu@gmail.com', NULL, '+95', '9773156789', 0, NULL, '$2y$12$cFU9X8VC3L7U57KjYJCOf.8wpTK0VOOiFsZRG6mdT/QBf8uImYiSa', NULL, '2025-05-21 01:09:24', '2025-05-21 01:09:24');

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

CREATE TABLE `warehouses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `warehouse_code` varchar(255) NOT NULL,
  `warehouse_name` varchar(255) NOT NULL,
  `warehouse_type` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state_region` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `manager_name` varchar(255) DEFAULT NULL,
  `storage_capacity` varchar(255) DEFAULT NULL,
  `operating_hours` varchar(255) DEFAULT NULL,
  `custom_attributes` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - inactive / 1 - active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `warehouse_code`, `warehouse_name`, `warehouse_type`, `description`, `address`, `city`, `state_region`, `country`, `postal_code`, `phone_number`, `email`, `contact_person`, `manager_name`, `storage_capacity`, `operating_hours`, `custom_attributes`, `status`, `created_at`, `updated_at`) VALUES
(1, 'WH001', 'WH-MAIN', 'General Storage', 'dfdfd', '123 Warehouse Blvd', 'New York', 'NY', 'UK', '1001', '+1234567890', 'main@shwelogix.com', 'John Smith', 'Mary Johnson', '200,000 sq ft', 'Mon-Fri:8am-6pm', NULL, 1, '2025-05-10 07:50:12', '2025-05-12 07:18:27'),
(2, 'WH002', 'WH-DIST', 'Distribution', 'Fast-moving goods distribution center', '456 Logistics Pkwy', 'Atlanta', 'GA', NULL, '30301', '+9876543210', 'distribution@shwelogix.com', 'Robert Brown', 'Jennifer Williams', '150,000 sq ft', NULL, NULL, 1, '2025-05-11 01:21:00', '2025-05-12 07:20:05');

-- --------------------------------------------------------

--
-- Table structure for table `zones`
--

CREATE TABLE `zones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `zone_code` varchar(255) NOT NULL,
  `zone_name` varchar(255) NOT NULL,
  `zone_type` varchar(255) NOT NULL,
  `area_id` bigint(20) UNSIGNED NOT NULL,
  `priority` int(11) NOT NULL COMMENT '0 - lower / 1 - higher for picking and putaway logic',
  `description` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 - in active / 1 - active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `zones`
--

INSERT INTO `zones` (`id`, `zone_code`, `zone_name`, `zone_type`, `area_id`, `priority`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Zone001', 'Zone A', 'Receiving', 1, 1, NULL, 0, '2025-05-11 02:51:27', '2025-05-12 07:24:40'),
(2, 'Zone 002', 'Zone B', 'Receiving', 3, 0, NULL, 1, '2025-05-11 02:52:25', '2025-06-17 01:28:26'),
(3, 'Zone003', 'Storage Zone A', 'Storage', 2, 1, NULL, 1, '2025-06-17 06:49:11', '2025-06-17 06:49:11'),
(4, 'Zone004', 'Shipping Zone', 'Shipping', 4, 0, NULL, 1, '2025-06-17 07:16:13', '2025-06-17 07:16:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_types`
--
ALTER TABLE `activity_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `advanced_shipping_notices`
--
ALTER TABLE `advanced_shipping_notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `advanced_shipping_notices_supplier_id_foreign` (`supplier_id`),
  ADD KEY `advanced_shipping_notices_carrier_id_foreign` (`carrier_id`);

--
-- Indexes for table `advanced_shipping_notice_details`
--
ALTER TABLE `advanced_shipping_notice_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `advanced_shipping_notice_details_asn_id_foreign` (`asn_id`),
  ADD KEY `advanced_shipping_notice_details_item_id_foreign` (`item_id`),
  ADD KEY `advanced_shipping_notice_details_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `areas_warehouse_id_foreign` (`warehouse_id`);

--
-- Indexes for table `base_uoms`
--
ALTER TABLE `base_uoms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `brands_brand_code_unique` (`brand_code`),
  ADD KEY `brands_category_id_foreign` (`category_id`),
  ADD KEY `brands_subcategory_id_foreign` (`subcategory_id`);

--
-- Indexes for table `business_contacts`
--
ALTER TABLE `business_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_contacts_business_party_id_foreign` (`business_party_id`);

--
-- Indexes for table `business_parties`
--
ALTER TABLE `business_parties`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_category_code_unique` (`category_code`),
  ADD KEY `categories_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cities_country_id_foreign` (`country_id`),
  ADD KEY `cities_state_id_foreign` (`state_id`);

--
-- Indexes for table `cost_types`
--
ALTER TABLE `cost_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cost_types_category_id_foreign` (`category_id`),
  ADD KEY `cost_types_subcategory_id_foreign` (`subcategory_id`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `countries_currency_id_foreign` (`currency_id`);

--
-- Indexes for table `cross_docking_tasks`
--
ALTER TABLE `cross_docking_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cross_docking_tasks_asn_id_foreign` (`asn_id`),
  ADD KEY `cross_docking_tasks_asn_detail_id_foreign` (`asn_detail_id`),
  ADD KEY `cross_docking_tasks_item_id_foreign` (`item_id`),
  ADD KEY `cross_docking_tasks_source_location_id_foreign` (`source_location_id`),
  ADD KEY `cross_docking_tasks_destination_location_id_foreign` (`destination_location_id`);

--
-- Indexes for table `currencies`
--
ALTER TABLE `currencies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dock_equipment`
--
ALTER TABLE `dock_equipment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dock_equipment_warehouse_id_foreign` (`warehouse_id`),
  ADD KEY `dock_equipment_area_id_foreign` (`area_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `financial_categories`
--
ALTER TABLE `financial_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `good_received_notes`
--
ALTER TABLE `good_received_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `good_received_notes_inbound_shipment_id_foreign` (`inbound_shipment_id`),
  ADD KEY `good_received_notes_supplier_id_foreign` (`supplier_id`),
  ADD KEY `good_received_notes_created_by_foreign` (`created_by`),
  ADD KEY `good_received_notes_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `good_received_note_items`
--
ALTER TABLE `good_received_note_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `good_received_note_items_grn_id_foreign` (`grn_id`),
  ADD KEY `good_received_note_items_product_id_foreign` (`product_id`),
  ADD KEY `good_received_note_items_uom_id_foreign` (`uom_id`),
  ADD KEY `good_received_note_items_location_id_foreign` (`location_id`);

--
-- Indexes for table `inbound_shipments`
--
ALTER TABLE `inbound_shipments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inbound_shipments_supplier_id_foreign` (`supplier_id`),
  ADD KEY `inbound_shipments_carrier_id_foreign` (`carrier_id`);

--
-- Indexes for table `inbound_shipment_details`
--
ALTER TABLE `inbound_shipment_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inbound_shipment_details_inbound_shipment_id_foreign` (`inbound_shipment_id`),
  ADD KEY `inbound_shipment_details_product_id_foreign` (`product_id`),
  ADD KEY `inbound_shipment_details_location_id_foreign` (`location_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `locations_zone_id_foreign` (`zone_id`);

--
-- Indexes for table `material_handling_eqs`
--
ALTER TABLE `material_handling_eqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `material_handling_eqs_supplier_id_foreign` (`supplier_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `oauth_access_tokens`
--
ALTER TABLE `oauth_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_access_tokens_user_id_index` (`user_id`);

--
-- Indexes for table `oauth_auth_codes`
--
ALTER TABLE `oauth_auth_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_auth_codes_user_id_index` (`user_id`);

--
-- Indexes for table `oauth_clients`
--
ALTER TABLE `oauth_clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_clients_user_id_index` (`user_id`);

--
-- Indexes for table `oauth_personal_access_clients`
--
ALTER TABLE `oauth_personal_access_clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `oauth_refresh_tokens`
--
ALTER TABLE `oauth_refresh_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`);

--
-- Indexes for table `order_types`
--
ALTER TABLE `order_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pallet_equipment`
--
ALTER TABLE `pallet_equipment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payment_terms`
--
ALTER TABLE `payment_terms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_product_code_unique` (`product_code`),
  ADD KEY `products_category_id_foreign` (`category_id`),
  ADD KEY `products_subcategory_id_foreign` (`subcategory_id`),
  ADD KEY `products_brand_id_foreign` (`brand_id`);

--
-- Indexes for table `product_commercials`
--
ALTER TABLE `product_commercials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_commercials_product_id_foreign` (`product_id`);

--
-- Indexes for table `product_dimensions`
--
ALTER TABLE `product_dimensions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_dimensions_product_id_foreign` (`product_id`);

--
-- Indexes for table `product_inventories`
--
ALTER TABLE `product_inventories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_inventories_product_id_foreign` (`product_id`),
  ADD KEY `product_inventories_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `product_others`
--
ALTER TABLE `product_others`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_others_product_id_foreign` (`product_id`);

--
-- Indexes for table `put_away_tasks`
--
ALTER TABLE `put_away_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `put_away_tasks_inbound_shipment_detail_id_foreign` (`inbound_shipment_detail_id`),
  ADD KEY `put_away_tasks_assigned_to_id_foreign` (`assigned_to_id`),
  ADD KEY `put_away_tasks_source_location_id_foreign` (`source_location_id`),
  ADD KEY `put_away_tasks_destination_location_id_foreign` (`destination_location_id`);

--
-- Indexes for table `quality_inspections`
--
ALTER TABLE `quality_inspections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quality_inspections_inbound_shipment_detail_id_foreign` (`inbound_shipment_detail_id`);

--
-- Indexes for table `receiving_appointments`
--
ALTER TABLE `receiving_appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receiving_appointments_inbound_shipment_id_foreign` (`inbound_shipment_id`),
  ADD KEY `receiving_appointments_supplier_id_foreign` (`supplier_id`),
  ADD KEY `receiving_appointments_dock_id_foreign` (`dock_id`);

--
-- Indexes for table `receiving_docks`
--
ALTER TABLE `receiving_docks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receiving_docks_zone_id_foreign` (`zone_id`);

--
-- Indexes for table `receiving_equipment`
--
ALTER TABLE `receiving_equipment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `receiving_exceptions`
--
ALTER TABLE `receiving_exceptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receiving_exceptions_asn_id_foreign` (`asn_id`),
  ADD KEY `receiving_exceptions_asn_detail_id_foreign` (`asn_detail_id`),
  ADD KEY `receiving_exceptions_item_id_foreign` (`item_id`);

--
-- Indexes for table `receiving_labor_trackings`
--
ALTER TABLE `receiving_labor_trackings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receiving_labor_trackings_emp_id_foreign` (`emp_id`),
  ADD KEY `receiving_labor_trackings_inbound_shipment_id_foreign` (`inbound_shipment_id`);

--
-- Indexes for table `shipping_carriers`
--
ALTER TABLE `shipping_carriers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staging_locations`
--
ALTER TABLE `staging_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `states`
--
ALTER TABLE `states`
  ADD PRIMARY KEY (`id`),
  ADD KEY `states_country_id_foreign` (`country_id`);

--
-- Indexes for table `statuses`
--
ALTER TABLE `statuses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `storage_equipment`
--
ALTER TABLE `storage_equipment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `storage_equipment_zone_id_foreign` (`zone_id`),
  ADD KEY `storage_equipment_supplier_id_foreign` (`supplier_id`);

--
-- Indexes for table `taxes`
--
ALTER TABLE `taxes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `unit_of_measures`
--
ALTER TABLE `unit_of_measures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unit_of_measures_base_uom_id_foreign` (`base_uom_id`);

--
-- Indexes for table `unloading_sessions`
--
ALTER TABLE `unloading_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unloading_sessions_inbound_shipment_id_foreign` (`inbound_shipment_id`),
  ADD KEY `unloading_sessions_dock_id_foreign` (`dock_id`),
  ADD KEY `unloading_sessions_supervisor_id_foreign` (`supervisor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_phone_number_unique` (`phone_number`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `zones`
--
ALTER TABLE `zones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `zones_area_id_foreign` (`area_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_types`
--
ALTER TABLE `activity_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `advanced_shipping_notices`
--
ALTER TABLE `advanced_shipping_notices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `advanced_shipping_notice_details`
--
ALTER TABLE `advanced_shipping_notice_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `areas`
--
ALTER TABLE `areas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `base_uoms`
--
ALTER TABLE `base_uoms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `business_contacts`
--
ALTER TABLE `business_contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `business_parties`
--
ALTER TABLE `business_parties`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cost_types`
--
ALTER TABLE `cost_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cross_docking_tasks`
--
ALTER TABLE `cross_docking_tasks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `currencies`
--
ALTER TABLE `currencies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `dock_equipment`
--
ALTER TABLE `dock_equipment`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_categories`
--
ALTER TABLE `financial_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `good_received_notes`
--
ALTER TABLE `good_received_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `good_received_note_items`
--
ALTER TABLE `good_received_note_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `inbound_shipments`
--
ALTER TABLE `inbound_shipments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inbound_shipment_details`
--
ALTER TABLE `inbound_shipment_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `material_handling_eqs`
--
ALTER TABLE `material_handling_eqs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `oauth_clients`
--
ALTER TABLE `oauth_clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `oauth_personal_access_clients`
--
ALTER TABLE `oauth_personal_access_clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_types`
--
ALTER TABLE `order_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pallet_equipment`
--
ALTER TABLE `pallet_equipment`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payment_terms`
--
ALTER TABLE `payment_terms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_commercials`
--
ALTER TABLE `product_commercials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_dimensions`
--
ALTER TABLE `product_dimensions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_inventories`
--
ALTER TABLE `product_inventories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `product_others`
--
ALTER TABLE `product_others`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `put_away_tasks`
--
ALTER TABLE `put_away_tasks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quality_inspections`
--
ALTER TABLE `quality_inspections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `receiving_appointments`
--
ALTER TABLE `receiving_appointments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `receiving_docks`
--
ALTER TABLE `receiving_docks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `receiving_equipment`
--
ALTER TABLE `receiving_equipment`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `receiving_exceptions`
--
ALTER TABLE `receiving_exceptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `receiving_labor_trackings`
--
ALTER TABLE `receiving_labor_trackings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipping_carriers`
--
ALTER TABLE `shipping_carriers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `staging_locations`
--
ALTER TABLE `staging_locations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `states`
--
ALTER TABLE `states`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `statuses`
--
ALTER TABLE `statuses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `storage_equipment`
--
ALTER TABLE `storage_equipment`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `taxes`
--
ALTER TABLE `taxes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `unit_of_measures`
--
ALTER TABLE `unit_of_measures`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `unloading_sessions`
--
ALTER TABLE `unloading_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `zones`
--
ALTER TABLE `zones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `advanced_shipping_notices`
--
ALTER TABLE `advanced_shipping_notices`
  ADD CONSTRAINT `advanced_shipping_notices_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `shipping_carriers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `advanced_shipping_notices_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `business_parties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `advanced_shipping_notice_details`
--
ALTER TABLE `advanced_shipping_notice_details`
  ADD CONSTRAINT `advanced_shipping_notice_details_asn_id_foreign` FOREIGN KEY (`asn_id`) REFERENCES `advanced_shipping_notices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `advanced_shipping_notice_details_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `advanced_shipping_notice_details_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `unit_of_measures` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `areas`
--
ALTER TABLE `areas`
  ADD CONSTRAINT `areas_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `brands`
--
ALTER TABLE `brands`
  ADD CONSTRAINT `brands_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `brands_subcategory_id_foreign` FOREIGN KEY (`subcategory_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_contacts`
--
ALTER TABLE `business_contacts`
  ADD CONSTRAINT `business_contacts_business_party_id_foreign` FOREIGN KEY (`business_party_id`) REFERENCES `business_parties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `unit_of_measures` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `cities_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cities_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cost_types`
--
ALTER TABLE `cost_types`
  ADD CONSTRAINT `cost_types_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `financial_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cost_types_subcategory_id_foreign` FOREIGN KEY (`subcategory_id`) REFERENCES `financial_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `countries`
--
ALTER TABLE `countries`
  ADD CONSTRAINT `countries_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cross_docking_tasks`
--
ALTER TABLE `cross_docking_tasks`
  ADD CONSTRAINT `cross_docking_tasks_asn_detail_id_foreign` FOREIGN KEY (`asn_detail_id`) REFERENCES `advanced_shipping_notice_details` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cross_docking_tasks_asn_id_foreign` FOREIGN KEY (`asn_id`) REFERENCES `advanced_shipping_notices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cross_docking_tasks_destination_location_id_foreign` FOREIGN KEY (`destination_location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cross_docking_tasks_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cross_docking_tasks_source_location_id_foreign` FOREIGN KEY (`source_location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dock_equipment`
--
ALTER TABLE `dock_equipment`
  ADD CONSTRAINT `dock_equipment_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dock_equipment_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `good_received_notes`
--
ALTER TABLE `good_received_notes`
  ADD CONSTRAINT `good_received_notes_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `good_received_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `good_received_notes_inbound_shipment_id_foreign` FOREIGN KEY (`inbound_shipment_id`) REFERENCES `inbound_shipments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `good_received_notes_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `business_parties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `good_received_note_items`
--
ALTER TABLE `good_received_note_items`
  ADD CONSTRAINT `good_received_note_items_grn_id_foreign` FOREIGN KEY (`grn_id`) REFERENCES `good_received_notes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `good_received_note_items_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `good_received_note_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `good_received_note_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `unit_of_measures` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inbound_shipments`
--
ALTER TABLE `inbound_shipments`
  ADD CONSTRAINT `inbound_shipments_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `shipping_carriers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inbound_shipments_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `business_parties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inbound_shipment_details`
--
ALTER TABLE `inbound_shipment_details`
  ADD CONSTRAINT `inbound_shipment_details_inbound_shipment_id_foreign` FOREIGN KEY (`inbound_shipment_id`) REFERENCES `inbound_shipments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inbound_shipment_details_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inbound_shipment_details_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `locations`
--
ALTER TABLE `locations`
  ADD CONSTRAINT `locations_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `material_handling_eqs`
--
ALTER TABLE `material_handling_eqs`
  ADD CONSTRAINT `material_handling_eqs_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `business_parties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_subcategory_id_foreign` FOREIGN KEY (`subcategory_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_commercials`
--
ALTER TABLE `product_commercials`
  ADD CONSTRAINT `product_commercials_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_dimensions`
--
ALTER TABLE `product_dimensions`
  ADD CONSTRAINT `product_dimensions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_inventories`
--
ALTER TABLE `product_inventories`
  ADD CONSTRAINT `product_inventories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_inventories_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `unit_of_measures` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_others`
--
ALTER TABLE `product_others`
  ADD CONSTRAINT `product_others_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `put_away_tasks`
--
ALTER TABLE `put_away_tasks`
  ADD CONSTRAINT `put_away_tasks_assigned_to_id_foreign` FOREIGN KEY (`assigned_to_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `put_away_tasks_destination_location_id_foreign` FOREIGN KEY (`destination_location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `put_away_tasks_inbound_shipment_detail_id_foreign` FOREIGN KEY (`inbound_shipment_detail_id`) REFERENCES `inbound_shipment_details` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `put_away_tasks_source_location_id_foreign` FOREIGN KEY (`source_location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quality_inspections`
--
ALTER TABLE `quality_inspections`
  ADD CONSTRAINT `quality_inspections_inbound_shipment_detail_id_foreign` FOREIGN KEY (`inbound_shipment_detail_id`) REFERENCES `inbound_shipment_details` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `receiving_appointments`
--
ALTER TABLE `receiving_appointments`
  ADD CONSTRAINT `receiving_appointments_dock_id_foreign` FOREIGN KEY (`dock_id`) REFERENCES `dock_equipment` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `receiving_appointments_inbound_shipment_id_foreign` FOREIGN KEY (`inbound_shipment_id`) REFERENCES `inbound_shipments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `receiving_appointments_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `business_parties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `receiving_docks`
--
ALTER TABLE `receiving_docks`
  ADD CONSTRAINT `receiving_docks_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `receiving_exceptions`
--
ALTER TABLE `receiving_exceptions`
  ADD CONSTRAINT `receiving_exceptions_asn_detail_id_foreign` FOREIGN KEY (`asn_detail_id`) REFERENCES `advanced_shipping_notice_details` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `receiving_exceptions_asn_id_foreign` FOREIGN KEY (`asn_id`) REFERENCES `advanced_shipping_notices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `receiving_exceptions_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `receiving_labor_trackings`
--
ALTER TABLE `receiving_labor_trackings`
  ADD CONSTRAINT `receiving_labor_trackings_emp_id_foreign` FOREIGN KEY (`emp_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `receiving_labor_trackings_inbound_shipment_id_foreign` FOREIGN KEY (`inbound_shipment_id`) REFERENCES `inbound_shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `states`
--
ALTER TABLE `states`
  ADD CONSTRAINT `states_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `storage_equipment`
--
ALTER TABLE `storage_equipment`
  ADD CONSTRAINT `storage_equipment_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `business_parties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `storage_equipment_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `unit_of_measures`
--
ALTER TABLE `unit_of_measures`
  ADD CONSTRAINT `unit_of_measures_base_uom_id_foreign` FOREIGN KEY (`base_uom_id`) REFERENCES `base_uoms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `unloading_sessions`
--
ALTER TABLE `unloading_sessions`
  ADD CONSTRAINT `unloading_sessions_dock_id_foreign` FOREIGN KEY (`dock_id`) REFERENCES `dock_equipment` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `unloading_sessions_inbound_shipment_id_foreign` FOREIGN KEY (`inbound_shipment_id`) REFERENCES `inbound_shipments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `unloading_sessions_supervisor_id_foreign` FOREIGN KEY (`supervisor_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `zones`
--
ALTER TABLE `zones`
  ADD CONSTRAINT `zones_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
