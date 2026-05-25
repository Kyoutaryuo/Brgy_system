-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 25, 2026 at 02:53 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `barangay_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(2, 3, 'Login', 'User logged in successfully', '::1', '2026-05-24 10:11:35'),
(3, 3, 'Logout', 'User logged out', '::1', '2026-05-24 10:11:37'),
(8, 3, 'Account Locked', 'User account locked after failed logins', '::1', '2026-05-24 10:23:09'),
(11, 3, 'Login', 'User logged in successfully', '::1', '2026-05-24 10:23:30'),
(12, 3, 'Logout', 'User logged out', '::1', '2026-05-24 10:23:32'),
(17, 3, 'Login', 'User logged in successfully', '::1', '2026-05-24 10:25:56'),
(18, 3, 'Logout', 'User logged out', '::1', '2026-05-24 10:25:57'),
(23, 3, 'Login', 'User logged in successfully', '::1', '2026-05-24 10:26:22'),
(24, 3, 'Logout', 'User logged out', '::1', '2026-05-24 10:26:25'),
(25, 3, 'Login', 'User logged in successfully', '::1', '2026-05-24 10:26:29'),
(26, 3, 'Logout', 'User logged out', '::1', '2026-05-24 10:26:33'),
(31, 6, 'Registration', 'New user registered', '::1', '2026-05-24 10:30:49'),
(32, 6, 'Login', 'User logged in successfully', '::1', '2026-05-24 10:30:57'),
(33, 6, 'Logout', 'User logged out', '::1', '2026-05-24 10:30:58'),
(35, 7, 'Registration', 'New user registered', '::1', '2026-05-24 10:34:06'),
(36, 7, 'Login', 'User logged in successfully', '::1', '2026-05-24 10:34:14'),
(37, 7, 'Logout', 'User logged out', '::1', '2026-05-24 10:34:16'),
(38, 7, 'Account Locked', 'User account locked after failed logins', '::1', '2026-05-24 10:34:39'),
(41, 7, 'Login', 'User logged in successfully', '::1', '2026-05-24 10:35:06'),
(42, 7, 'Logout', 'User logged out', '::1', '2026-05-24 10:35:08'),
(43, 7, 'Account Locked', 'User account locked after failed logins', '::1', '2026-05-24 10:35:23'),
(46, 7, 'Login', 'User logged in successfully', '::1', '2026-05-24 10:36:14'),
(47, 7, 'Logout', 'User logged out', '::1', '2026-05-24 10:36:15'),
(55, 7, 'Login', 'User logged in successfully', '::1', '2026-05-24 10:37:28'),
(56, 7, 'Logout', 'User logged out', '::1', '2026-05-24 10:37:29'),
(59, 7, 'Login', 'User logged in successfully', '::1', '2026-05-24 10:37:51'),
(60, 7, 'Logout', 'User logged out', '::1', '2026-05-24 10:37:53'),
(69, 3, 'Login', 'User logged in successfully', '::1', '2026-05-25 11:34:19'),
(70, 3, 'Logout', 'User logged out', '::1', '2026-05-25 11:34:21'),
(85, 3, 'Login', 'User logged in successfully', '::1', '2026-05-25 11:47:51'),
(86, 3, 'Logout', 'User logged out', '::1', '2026-05-25 11:47:53'),
(93, 3, 'Login', 'User logged in successfully', '::1', '2026-05-25 11:55:14'),
(94, 3, 'Logout', 'User logged out', '::1', '2026-05-25 11:55:18'),
(98, 3, 'Login', 'User logged in successfully', '::1', '2026-05-25 12:00:00'),
(99, 3, 'Logout', 'User logged out', '::1', '2026-05-25 12:00:04'),
(101, 8, 'Registration', 'New user registered', '::1', '2026-05-25 12:00:50'),
(102, 8, 'Login', 'User logged in successfully', '::1', '2026-05-25 12:01:00'),
(103, 8, 'Logout', 'User logged out', '::1', '2026-05-25 12:01:09'),
(106, 8, 'Login', 'User logged in successfully', '::1', '2026-05-25 12:01:28'),
(107, 8, 'Logout', 'User logged out', '::1', '2026-05-25 12:01:31'),
(109, 8, 'Account Locked', 'User account locked after failed logins', '::1', '2026-05-25 12:08:02'),
(112, 8, 'Account Locked', 'User account locked after failed logins', '::1', '2026-05-25 12:08:23'),
(116, 8, 'Login', 'User logged in successfully', '::1', '2026-05-25 12:09:25'),
(117, 8, 'Logout', 'User logged out', '::1', '2026-05-25 12:09:26'),
(122, 8, 'Login', 'User logged in successfully', '::1', '2026-05-25 12:20:57'),
(123, 8, 'Logout', 'User logged out', '::1', '2026-05-25 12:20:57'),
(124, 8, 'Account Locked', 'User account locked after failed logins', '::1', '2026-05-25 12:21:06'),
(127, 8, 'Login', 'User logged in successfully', '::1', '2026-05-25 12:22:15'),
(128, 8, 'Logout', 'User logged out', '::1', '2026-05-25 12:22:16'),
(131, 8, 'Login', 'User logged in successfully', '::1', '2026-05-25 12:22:45'),
(132, 8, 'Logout', 'User logged out', '::1', '2026-05-25 12:22:48'),
(133, 8, 'Login', 'User logged in successfully', '::1', '2026-05-25 12:22:56'),
(134, 8, 'New Request', 'Submitted request #7', '::1', '2026-05-25 12:23:06'),
(135, 8, 'Logout', 'User logged out', '::1', '2026-05-25 12:23:10'),
(138, 8, 'Login', 'User logged in successfully', '::1', '2026-05-25 12:23:31'),
(139, 8, 'Logout', 'User logged out', '::1', '2026-05-25 12:23:43'),
(142, 8, 'Login', 'User logged in successfully', '::1', '2026-05-25 12:24:03'),
(143, 8, 'Logout', 'User logged out', '::1', '2026-05-25 12:24:06'),
(145, 8, 'Login', 'User logged in successfully', '::1', '2026-05-25 12:35:39'),
(146, 8, 'Logout', 'User logged out', '::1', '2026-05-25 12:36:10'),
(148, 8, 'Login', 'User logged in successfully', '::1', '2026-05-25 12:36:45'),
(149, 8, 'Logout', 'User logged out', '::1', '2026-05-25 12:42:54');

-- --------------------------------------------------------

--
-- Table structure for table `deleted_documents_archive`
--

CREATE TABLE `deleted_documents_archive` (
  `id` int(11) NOT NULL,
  `original_document_id` int(11) DEFAULT NULL,
  `document_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `processing_days` int(11) DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT NULL,
  `document_status` varchar(50) DEFAULT NULL,
  `document_created_at` datetime DEFAULT NULL,
  `deleted_by_id` int(11) DEFAULT NULL,
  `deleted_by_name` varchar(255) DEFAULT NULL,
  `deleted_by_role` varchar(50) DEFAULT NULL,
  `deletion_reason` text DEFAULT NULL,
  `ip_address` varchar(100) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `restored_at` datetime DEFAULT NULL,
  `restored_by_id` int(11) DEFAULT NULL,
  `restored_by_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deleted_documents_archive`
--

INSERT INTO `deleted_documents_archive` (`id`, `original_document_id`, `document_name`, `description`, `requirements`, `processing_days`, `fee`, `document_status`, `document_created_at`, `deleted_by_id`, `deleted_by_name`, `deleted_by_role`, `deletion_reason`, `ip_address`, `deleted_at`, `expires_at`, `restored_at`, `restored_by_id`, `restored_by_name`) VALUES
(1, 6, 'barangay clearance 1', 't', 'valid Id, Sedula', 3, 90.00, 'active', '2026-05-19 17:56:27', 0, '0', 'admin', 're', '::1', '2026-05-24 11:29:34', '2026-06-23 11:29:34', '2026-05-24 11:30:05', 0, 'System Administrator'),
(2, 6, 'barangay clearance 1', 't', 'valid Id, Sedula', 3, 90.00, 'active', '2026-05-19 17:56:27', 0, '0', 'admin', 're', '::1', '2026-05-24 11:31:35', '2026-06-23 11:31:35', '2026-05-24 11:32:00', 0, 'System Administrator'),
(3, 6, 'barangay clearance 1', 't', 'valid Id, Sedula', 3, 90.00, 'active', '2026-05-19 17:56:27', 0, '0', 'admin', 're', '::1', '2026-05-24 11:32:21', '2026-06-23 11:32:21', '2026-05-24 11:32:29', 0, 'System Administrator'),
(4, 6, 'barangay clearance 1', 't', 'valid Id, Sedula', 3, 90.00, 'active', '2026-05-19 17:56:27', 0, '0', 'admin', 're', '::1', '2026-05-24 12:27:33', '2026-06-23 12:27:33', '2026-05-24 12:27:42', 0, 'System Administrator');

-- --------------------------------------------------------

--
-- Table structure for table `deleted_users_archive`
--

CREATE TABLE `deleted_users_archive` (
  `id` int(11) NOT NULL,
  `original_user_id` int(11) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `user_status` varchar(50) DEFAULT NULL,
  `user_created_at` datetime DEFAULT NULL,
  `deleted_by_id` int(11) DEFAULT NULL,
  `deleted_by_name` varchar(255) DEFAULT NULL,
  `deleted_by_role` varchar(50) DEFAULT NULL,
  `deletion_reason` text DEFAULT NULL,
  `ip_address` varchar(100) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `restored_at` datetime DEFAULT NULL,
  `restored_by_id` int(11) DEFAULT NULL,
  `restored_by_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deleted_users_archive`
--

INSERT INTO `deleted_users_archive` (`id`, `original_user_id`, `full_name`, `username`, `email`, `address`, `contact_number`, `role`, `user_status`, `user_created_at`, `deleted_by_id`, `deleted_by_name`, `deleted_by_role`, `deletion_reason`, `ip_address`, `deleted_at`, `expires_at`, `restored_at`, `restored_by_id`, `restored_by_name`) VALUES
(1, 5, 'tyty', 'tyyyyyyyyyyyy', 'ryan@gmail.com', 'yt', '12345678900', 'user', 'active', '2026-05-19 21:47:55', 0, 'System Administrator', 'admin', 're', '::1', '2026-05-24 11:15:55', '2026-06-23 11:15:55', '2026-05-24 11:18:01', 0, 'System Administrator'),
(2, 3, 'qwer', 'qwer', 'ren@gmail.com', 'ra', '12345678900', 'user', 'active', '2026-05-19 11:03:48', 0, 'System Administrator', 'admin', 're', '::1', '2026-05-24 11:18:11', '2026-06-23 11:18:11', '2026-05-24 11:18:47', 0, 'System Administrator'),
(3, 5, 'tyty', 'tyyyyyyyyyyyy', 'ryan@gmail.com', 'yt', '12345678900', 'user', 'active', '2026-05-19 21:47:55', 0, 'System Administrator', 'admin', 're', '::1', '2026-05-24 11:18:53', '2026-06-23 11:18:53', '2026-05-24 11:18:58', 0, 'System Administrator'),
(4, 3, 'qwer', 'qwer', 'ren@gmail.com', 'ra', '12345678900', 'user', 'active', '2026-05-19 11:03:48', 0, 'System Administrator', 'admin', 're', '::1', '2026-05-24 11:22:08', '2026-06-23 11:22:08', '2026-05-24 11:22:32', 0, 'System Administrator'),
(5, 3, 'qwer', 'qwer', 'ren@gmail.com', 'ra', '12345678900', 'user', 'active', '2026-05-19 11:03:48', 0, 'System Administrator', 'admin', 're', '::1', '2026-05-24 12:25:17', '2026-06-23 12:25:17', '2026-05-24 12:25:45', 0, 'System Administrator'),
(6, 7, 'poalo', 'poalo', 'poalo@gmail.com', 're', '535345435', 'user', 'active', '2026-05-24 18:34:06', 0, 'System Administrator', 'admin', 're', '::1', '2026-05-24 12:36:52', '2026-06-23 12:36:52', '2026-05-24 12:37:14', 0, 'System Administrator'),
(7, 3, 'qwer', 'qwer', 'ren@gmail.com', 'ra', '12345678900', 'user', 'active', '2026-05-19 11:03:48', 0, 'System Administrator', 'admin', 're', '::1', '2026-05-25 13:48:05', '2026-06-24 13:48:05', '2026-05-25 13:49:28', 0, 'System Administrator');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `document_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `processing_days` int(11) DEFAULT 3,
  `fee` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `document_name`, `description`, `requirements`, `processing_days`, `fee`, `status`, `created_at`, `deleted_at`) VALUES
(1, 'Barangay Clearance', 'General clearance certificate from the barangay', 'Valid ID,Community Tax Certificate (Cedula),2x2 ID Photo', 1, 50.00, 'active', '2026-05-19 02:11:45', NULL),
(2, 'Certificate of Residency', 'Proof of residence in the barangay', 'Valid ID,Proof of Address (Utility Bill)', 1, 30.00, 'active', '2026-05-19 02:11:45', NULL),
(3, 'Certificate of Indigency', 'Certificate for indigent residents', 'Valid ID,Request Letter stating purpose', 1, 0.00, 'active', '2026-05-19 02:11:45', NULL),
(4, 'Business Clearance', 'Clearance for business permit application', 'Valid ID,DTI Registration,Lease Contract or Land Title,Barangay Clearance', 3, 200.00, 'active', '2026-05-19 02:11:45', NULL),
(5, 'Barangay ID', 'Official barangay identification card', 'Valid ID,2x2 ID Photo (2 pieces),Proof of Address', 5, 100.00, 'active', '2026-05-19 02:11:45', NULL),
(6, 'barangay clearance 1', 't', 'valid Id, Sedula', 3, 90.00, 'active', '2026-05-19 09:56:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `purpose` text DEFAULT NULL,
  `schedule_date` date DEFAULT NULL,
  `schedule_time` time DEFAULT NULL,
  `status` enum('pending','processing','approved','rejected','claimed') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `user_id`, `document_id`, `purpose`, `schedule_date`, `schedule_time`, `status`, `remarks`, `processed_by`, `processed_at`, `created_at`) VALUES
(1, 3, 5, 're', '2026-05-22', '13:00:00', 'approved', '', NULL, '2026-05-19 08:13:24', '2026-05-19 03:14:45'),
(2, 3, 5, 're', '2026-05-21', '14:00:00', 'approved', '', NULL, '2026-05-19 08:45:52', '2026-05-19 03:28:39'),
(3, 3, 1, 'Financial assistance', '2026-05-22', '13:00:00', 'approved', '', NULL, '2026-05-19 23:55:33', '2026-05-19 04:51:40'),
(4, 3, 1, 'ra', '2026-05-22', '11:00:00', 'rejected', 're', NULL, '2026-05-24 09:33:06', '2026-05-19 06:30:31'),
(5, 3, 6, 're', '2026-05-21', '11:00:00', 'approved', '', NULL, '2026-05-24 09:33:37', '2026-05-19 13:03:18'),
(6, 3, 1, 'new', '2026-05-29', '14:00:00', 'claimed', '', NULL, '2026-05-25 11:59:48', '2026-05-20 01:18:19'),
(7, 8, 1, 'dew', '2026-05-29', '14:00:00', 'claimed', '', NULL, '2026-05-25 12:23:54', '2026-05-25 12:23:06');

-- --------------------------------------------------------

--
-- Table structure for table `request_files`
--

CREATE TABLE `request_files` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request_files`
--

INSERT INTO `request_files` (`id`, `request_id`, `file_name`, `file_path`, `uploaded_at`) VALUES
(1, 2, 'Picture.jpg', '../uploads/1779161319_Picture.jpg', '2026-05-19 03:28:39'),
(2, 3, 'Picture.jpg', '../uploads/1779166300_Picture.jpg', '2026-05-19 04:51:40'),
(3, 3, 'phillid.jpg', '../uploads/1779166300_phillid.jpg', '2026-05-19 04:51:40'),
(4, 3, 'BDOID.jpg', '../uploads/1779166300_BDOID.jpg', '2026-05-19 04:51:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `role` enum('user','staff','admin') DEFAULT 'user',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `account_status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `username`, `password`, `email`, `address`, `contact_number`, `role`, `status`, `created_at`, `deleted_at`, `failed_attempts`, `account_status`) VALUES
(1, 'System Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 'admin', 'active', '2026-05-19 02:11:45', NULL, 1, 'active'),
(2, 'Staff Member', 'staff', '$2y$10$Dow5elN8O/5FJ.3BxGDSaeuxrhfb.CWdCVdnFRxPbKdBu5MQKQN6a', NULL, NULL, NULL, 'staff', 'active', '2026-05-19 02:11:45', NULL, 0, 'active'),
(3, 'qwer', 'qwer', '$2y$10$uBhObJKv5wXDJYAI9axcb.Fl4OMd6dIZgK7SwDTp45frQeIhhegz6', 'ren@gmail.com', 'ra', '12345678900', 'user', 'active', '2026-05-19 03:03:48', NULL, 0, 'active'),
(4, 'Ryan Beltran', 'reeeeeeeee', '$2y$10$Iym4bHEFpx.NO00i5BjWa.RAG/rcupBX0xhbV/mNDf9UXuFvdjDdu', 'betrangerry116@yahoo.com', 'jhygj', '12345678900', 'user', 'active', '2026-05-19 13:41:22', NULL, 0, 'active'),
(5, 'tyty', 'tyyyyyyyyyyyy', '$2y$10$pNybpAFB5ZoI0mlzlGPx9ORVxtq9IzrolwQzNfCwTaUBVtQS563dO', 'ryan@gmail.com', 'yt', '12345678900', 'user', 'active', '2026-05-19 13:47:55', NULL, 0, 'active'),
(6, 'qwer', 'rens', '$2y$10$gJHqf3WpidDMiYsLY37WUujnc2xX5EQPFMCvxz37/raeV30f8qdEu', 'ren@gmail.com', 'rere', '565656565655', 'user', 'active', '2026-05-24 10:30:49', NULL, 0, 'active'),
(7, 'poalo', 'poalo', '$2y$10$Yzvn9l2Y.SxkJfXz.68DLuF13l6RQrfWP/NXePDr0lx/diZfO82My', 'poalo@gmail.com', 're', '535345435', 'staff', 'active', '2026-05-24 10:34:06', NULL, 0, 'active'),
(8, 'qwert', 'qwert', '$2y$10$XPyAONRPUsaTBxKE.eKNv.eckAmbVM/7gK2d8fG5FeusW2wcHYzHq', 'qwert@gmail.com', 're', '53453534534543', 'user', 'active', '2026-05-25 12:00:50', NULL, 0, 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `deleted_documents_archive`
--
ALTER TABLE `deleted_documents_archive`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deleted_users_archive`
--
ALTER TABLE `deleted_users_archive`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `document_id` (`document_id`),
  ADD KEY `fk_requests_processed_by` (`processed_by`);

--
-- Indexes for table `request_files`
--
ALTER TABLE `request_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT for table `deleted_documents_archive`
--
ALTER TABLE `deleted_documents_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `deleted_users_archive`
--
ALTER TABLE `deleted_users_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `request_files`
--
ALTER TABLE `request_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `fk_requests_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`);

--
-- Constraints for table `request_files`
--
ALTER TABLE `request_files`
  ADD CONSTRAINT `request_files_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
