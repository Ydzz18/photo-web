-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql100.infinityfree.com
-- Generation Time: Dec 08, 2025 at 01:03 AM
-- Server version: 10.6.22-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_40532602_photography_website`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `created_at`, `last_login`) VALUES
(1, 'admin', 'admin@lenscraft.com', '$2y$10$gSo0XLzUqSHhKi0keuT.AOxciQ.q69F4U96G7zf/Ri6oBXloIjxsq', '2025-11-27 11:29:49', '2025-12-03 18:30:21'),
(2, 'Ren', 'villegascarenetol@gmail.com', '$2y$10$bZEjhAhX/9QVSsNKH8q9R.FJKzp.Uk660gTWB3XEEbzZZqFLhDbdC', '2025-11-27 02:00:43', '2025-11-27 02:08:19'),
(3, 'villaneza11', 'angeliedelacruzdingding@gmail.com', '$2y$10$Jkf5Wb/Z9LngmoJijjalne9mR9idq9Kg3C6KH36tH8/9jiukA7z0m', '2025-11-27 02:01:43', '2025-11-27 20:17:05');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `photo_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `photo_id`, `user_id`, `comment_text`, `created_at`) VALUES
(1, 1, 2, 'Absolutely stunning! What camera did you use?', '2025-11-27 11:29:49'),
(2, 1, 3, 'The lighting is perfect!', '2025-11-27 11:29:49'),
(3, 2, 1, 'Love the composition and colors!', '2025-11-27 11:29:49'),
(4, 2, 3, 'Great street photography!', '2025-11-27 11:29:49'),
(5, 3, 1, 'Incredible shot! Where was this taken?', '2025-11-27 11:29:49'),
(6, 1, 1, 'Beautiful work! Keep it up!', '2025-11-27 11:29:49'),
(7, 3, 1, 'Amazing wildlife capture!', '2025-11-27 11:29:49'),
(8, 8, 4, 'ang ganda nyo po dyan', '2025-11-27 02:07:37'),
(9, 8, 5, '????', '2025-11-27 02:10:45'),
(10, 8, 5, 'Ganda', '2025-11-27 02:10:57'),
(11, 8, 6, 'Kambal ko yan', '2025-11-27 02:11:06'),
(12, 9, 6, 'Ganda bheee hahaha', '2025-11-27 04:46:03');

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `photo_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`id`, `user_id`, `photo_id`, `created_at`) VALUES
(1, 1, 1, '2025-11-27 11:29:49'),
(2, 1, 3, '2025-11-27 11:29:49'),
(3, 2, 1, '2025-11-27 11:29:49'),
(4, 2, 2, '2025-11-27 11:29:49'),
(5, 3, 1, '2025-11-27 11:29:49'),
(6, 3, 2, '2025-11-27 11:29:49'),
(7, 3, 3, '2025-11-27 11:29:49'),
(8, 6, 1, '2025-11-27 01:59:53'),
(9, 6, 8, '2025-11-27 02:02:07'),
(10, 4, 8, '2025-11-27 02:07:12'),
(11, 5, 9, '2025-11-27 02:10:31'),
(12, 5, 8, '2025-11-27 02:10:34'),
(13, 5, 6, '2025-11-27 02:11:21'),
(15, 7, 9, '2025-11-27 02:26:38'),
(16, 7, 8, '2025-11-27 02:26:40'),
(17, 6, 9, '2025-11-27 04:43:07'),
(18, 6, 2, '2025-11-27 04:44:16'),
(19, 6, 3, '2025-11-27 04:44:21'),
(20, 6, 4, '2025-11-27 04:44:24'),
(21, 6, 5, '2025-11-27 04:44:27'),
(22, 6, 6, '2025-11-27 04:45:31');

-- --------------------------------------------------------

--
-- Table structure for table `photos`
--

CREATE TABLE `photos` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `photos`
--

INSERT INTO `photos` (`id`, `user_id`, `title`, `description`, `image_path`, `uploaded_at`) VALUES
(1, 1, 'Mountain Majesty', 'A breathtaking view of the Alps at sunrise.', 'mountain_landscape.jpg', '2025-11-27 11:29:49'),
(2, 2, 'Urban Pulse', 'Street photography capturing city life in motion.', 'urban_street.jpg', '2025-11-27 11:29:49'),
(3, 3, 'Wild Encounter', 'Close encounter with wildlife in the Serengeti.', 'wildlife.jpg', '2025-11-27 11:29:49'),
(4, 1, 'Sunset Dreams', 'Golden hour at the beach.', 'sunset_beach.jpg', '2025-11-27 11:29:49'),
(5, 2, 'City Lights', 'Night photography in downtown.', 'city_night.jpg', '2025-11-27 11:29:49'),
(6, 4, 'pikachu', 'pikachu sya pero idk kung bakit ganyan yung kinalabasan', '69281dfe5b3a9_1764236798.jpeg', '2025-11-27 01:46:38'),
(8, 6, '#model', 'Girlqlu', '6928217d225dd_1764237693.jpg', '2025-11-27 02:01:33'),
(9, 5, '#GGSS', 'Si ate kim nag pic', '69282381ddaff_1764238209.jpg', '2025-11-27 02:10:09'),
(10, 8, 'gain-eagers', 'opo', '6930f27bcb30e_1764815483.jpg', '2025-12-03 18:31:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `is_admin`, `created_at`) VALUES
(1, 'photomaster', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, '2025-11-27 11:29:49'),
(2, 'naturelover', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, '2025-11-27 11:29:49'),
(3, 'streetphotographer', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, '2025-11-27 11:29:49'),
(4, 'kim', 'royroyquimado@gmail.com', '$2y$10$t5ku73QwP7H9TzkMzJsZAOsvV5JDp9Hsf4NNELCfOHZ8nhhiirPtu', 0, '2025-11-27 01:44:37'),
(5, 'villaneza11', 'angeliedelacruzdingding@gmail.com', '$2y$10$W57wPZHVwvMGV5dGR0gowecbCFAdE7vpCrSxQh0915JZ2IoMjZx7.', 0, '2025-11-27 01:57:47'),
(6, 'Ren', 'villegascarenetol@gmail.com', '$2y$10$XqsMS/k24.7FVsY4Wh1H4.t4oKD.eFiJFoYDbHrcOsa7Thtbo6hwq', 0, '2025-11-27 01:59:39'),
(7, 'Ydzz', 'yayenydrian@gmail.com', '$2y$10$OCK36NVMd4htMEzxPTsLH.7ASy.VSwMGC.ysexToiJyHlksuvX.4C', 0, '2025-11-27 02:24:29'),
(8, 'ratfucker', 'ratfucker@gmail.com', '$2y$10$ahOl2uAmewe7sUdo52Uw4.c2jo6yz2lJoY8K3IsH2jwnmLZUzyINi', 0, '2025-12-03 18:29:50');

-- --------------------------------------------------------

--
-- Table structure for table `user_logs`
--

CREATE TABLE `user_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `affected_table` varchar(50) DEFAULT NULL,
  `affected_id` int(11) DEFAULT NULL,
  `status` enum('success','failed','warning') DEFAULT 'success',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_logs`
--

INSERT INTO `user_logs` (`id`, `user_id`, `admin_id`, `action_type`, `action_description`, `ip_address`, `user_agent`, `affected_table`, `affected_id`, `status`, `created_at`) VALUES
(1, NULL, 1, 'admin_delete_comment', 'Admin deleted comment by \'\' on photo \'\' (Comment ID: )', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'comments', NULL, 'success', '2025-11-27 04:03:03'),
(2, NULL, 1, 'admin_delete_comment', 'Admin deleted comment by \'\' on photo \'\' (Comment ID: )', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'comments', NULL, 'success', '2025-11-27 05:13:58'),
(3, NULL, 1, 'admin_delete_comment', 'Admin deleted comment by \'\' on photo \'\' (Comment ID: )', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'comments', NULL, 'success', '2025-11-27 05:17:41'),
(4, NULL, 1, 'admin_login', 'Admin \'admin\' logged in successfully', '112.202.115.240', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', 'admins', 1, 'success', '2025-11-27 09:38:33'),
(5, 4, NULL, 'register', 'New user registered: \'kim\' (royroyquimado@gmail.com)', '124.217.60.24', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 [FBAN/FBIOS;FBAV/534.0.0.38.108;FBBV/827831877;FBDV/iPhone12,1;FBMD/iPhone;FBSN/iOS;FBSV/18.6.2;FBSS/2;FBCR/;FBID/phone;FBLC/en_US;FBOP/80]', 'users', 4, 'success', '2025-11-27 09:44:37'),
(6, 4, NULL, 'upload_photo', 'Uploaded photo: \'pikachu\' (File: 69281dfe5b3a9_1764236798.jpeg)', '124.217.60.24', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 [FBAN/FBIOS;FBAV/534.0.0.38.108;FBBV/827831877;FBDV/iPhone12,1;FBMD/iPhone;FBSN/iOS;FBSV/18.6.2;FBSS/2;FBCR/;FBID/phone;FBLC/en_US;FBOP/80]', 'photos', 6, 'success', '2025-11-27 09:46:38'),
(7, 4, NULL, 'login', 'User \'kim\' logged in successfully', '124.217.60.24', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1', 'users', 4, 'success', '2025-11-27 09:48:13'),
(8, 5, NULL, 'register', 'New user registered: \'villaneza11\' (angeliedelacruzdingding@gmail.com)', '112.202.110.225', 'Mozilla/5.0 (Linux; Android 14; SM-A135F Build/UP1A.231005.007; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.102 Mobile Safari/537.36[FBAN/EMA;FBLC/en_GB;FBAV/486.0.0.13.109;FBCX/modulariab;]', 'users', 5, 'success', '2025-11-27 09:57:47'),
(9, NULL, 1, 'admin_toggle_status', 'Admin toggled admin status for \'villaneza11\' to: Admin', '112.202.115.240', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'users', 5, 'success', '2025-11-27 09:59:05'),
(10, NULL, 1, 'admin_toggle_status', 'Admin toggled admin status for \'villaneza11\' to: User', '112.202.115.240', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'users', 5, 'success', '2025-11-27 09:59:28'),
(11, 6, NULL, 'register', 'New user registered: \'Ren\' (villegascarenetol@gmail.com)', '175.176.77.102', 'Mozilla/5.0 (Linux; Android 13; SM-A515F Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.106 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/529.0.0.43.109;]', 'users', 6, 'success', '2025-11-27 09:59:39'),
(12, NULL, 1, 'admin_add_admin', 'Admin \'admin\' created new admin account: \'Ren\'', '112.202.115.240', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'admins', 2, 'success', '2025-11-27 10:00:43'),
(13, 6, NULL, 'upload_photo', 'Uploaded photo: \'#model\' (File: 6928217c22e7d_1764237692.jpg)', '175.176.77.102', 'Mozilla/5.0 (Linux; Android 13; SM-A515F Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.106 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/529.0.0.43.109;]', 'photos', 7, 'success', '2025-11-27 10:01:32'),
(14, 6, NULL, 'upload_photo', 'Uploaded photo: \'#model\' (File: 6928217d225dd_1764237693.jpg)', '175.176.77.102', 'Mozilla/5.0 (Linux; Android 13; SM-A515F Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.106 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/529.0.0.43.109;]', 'photos', 8, 'success', '2025-11-27 10:01:33'),
(15, 6, NULL, 'delete_photo', 'User deleted their photo: \'#model\' (ID: 7)', '175.176.77.102', 'Mozilla/5.0 (Linux; Android 13; SM-A515F Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.106 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/529.0.0.43.109;]', 'photos', 7, 'success', '2025-11-27 10:01:41'),
(16, NULL, 1, 'admin_add_admin', 'Admin \'admin\' created new admin account: \'villaneza11\'', '112.202.115.240', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'admins', 3, 'success', '2025-11-27 10:01:43'),
(17, 6, NULL, 'like_photo', 'Liked photo \'#model\' by Ren', '175.176.77.102', 'Mozilla/5.0 (Linux; Android 13; SM-A515F Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.106 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/529.0.0.43.109;]', 'likes', 9, 'success', '2025-11-27 10:02:07'),
(18, NULL, NULL, 'failed_login', 'Failed admin login attempt - Username not found: Villegascarenetol@gmail.com', '175.176.77.102', 'Mozilla/5.0 (Linux; Android 13; SM-A515F Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.106 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/529.0.0.43.109;]', NULL, NULL, 'failed', '2025-11-27 10:04:56'),
(19, NULL, 3, 'admin_login', 'Admin \'villaneza11\' logged in successfully', '112.202.110.225', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/29.0 Chrome/136.0.0.0 Mobile Safari/537.36', 'admins', 3, 'success', '2025-11-27 10:05:14'),
(20, NULL, NULL, 'failed_login', 'Failed admin login attempt - Username not found: Villegascarenetol@gmail.com', '175.176.77.102', 'Mozilla/5.0 (Linux; Android 13; SM-A515F Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.106 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/529.0.0.43.109;]', NULL, NULL, 'failed', '2025-11-27 10:06:31'),
(21, 5, NULL, 'login', 'User \'villaneza11\' logged in successfully', '112.202.110.225', 'Mozilla/5.0 (Linux; Android 14; SM-A135F Build/UP1A.231005.007; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.102 Mobile Safari/537.36[FBAN/EMA;FBLC/en_GB;FBAV/486.0.0.13.109;FBCX/modulariab;]', 'users', 5, 'success', '2025-11-27 10:07:03'),
(22, 6, NULL, 'login', 'User \'Ren\' logged in successfully', '175.176.77.102', 'Mozilla/5.0 (Linux; Android 13; SM-A515F Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.106 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/529.0.0.43.109;]', 'users', 6, 'success', '2025-11-27 10:07:10'),
(23, NULL, 2, 'admin_login', 'Admin \'Ren\' logged in successfully', '175.176.77.102', 'Mozilla/5.0 (Linux; Android 13; SM-A515F Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.106 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/529.0.0.43.109;]', 'admins', 2, 'success', '2025-11-27 10:08:19'),
(24, 5, NULL, 'upload_photo', 'Upload validation failed: Invalid file type. Only JPG, JPEG, PNG, GIF, and WebP files are allowed.', '112.202.110.225', 'Mozilla/5.0 (Linux; Android 14; SM-A135F Build/UP1A.231005.007; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.102 Mobile Safari/537.36[FBAN/EMA;FBLC/en_GB;FBAV/486.0.0.13.109;FBCX/modulariab;]', NULL, NULL, 'warning', '2025-11-27 10:08:26'),
(25, 6, NULL, 'login', 'User \'Ren\' logged in successfully', '175.176.77.102', 'Mozilla/5.0 (Linux; Android 13; SM-A515F Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.106 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/529.0.0.43.109;]', 'users', 6, 'success', '2025-11-27 10:10:04'),
(26, 5, NULL, 'upload_photo', 'Uploaded photo: \'#GGSS\' (File: 69282381ddaff_1764238209.jpg)', '112.202.110.225', 'Mozilla/5.0 (Linux; Android 14; SM-A135F Build/UP1A.231005.007; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.102 Mobile Safari/537.36[FBAN/EMA;FBLC/en_GB;FBAV/486.0.0.13.109;FBCX/modulariab;]', 'photos', 9, 'success', '2025-11-27 10:10:09'),
(27, 7, NULL, 'register', 'New user registered: \'Ydzz\' (yayenydrian@gmail.com)', '131.226.106.121', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'users', 7, 'success', '2025-11-27 10:24:29'),
(28, NULL, 1, 'admin_login', 'Admin \'admin\' logged in successfully', '112.202.115.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'admins', 1, 'success', '2025-11-27 11:13:56'),
(29, 6, NULL, 'logout', 'User \'Ren\' logged out', '175.176.77.102', 'Mozilla/5.0 (Linux; Android 13; SM-A515F Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.106 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/529.0.0.43.109;]', NULL, NULL, 'success', '2025-11-27 12:43:44'),
(30, 6, NULL, 'login', 'User \'Ren\' logged in successfully', '175.176.77.102', 'Mozilla/5.0 (Linux; Android 13; SM-A515F Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.106 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/529.0.0.43.109;]', 'users', 6, 'success', '2025-11-27 12:44:09'),
(31, NULL, NULL, 'failed_login', 'Failed login attempt for email: villegascarenetol@gmail.com', '175.176.77.102', 'Mozilla/5.0 (Linux; Android 13; SM-A515F Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.106 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/529.0.0.43.109;]', NULL, NULL, 'failed', '2025-11-27 12:45:14'),
(32, 6, NULL, 'login', 'User \'Ren\' logged in successfully', '175.176.77.102', 'Mozilla/5.0 (Linux; Android 13; SM-A515F Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.106 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/529.0.0.43.109;]', 'users', 6, 'success', '2025-11-27 12:45:21'),
(33, NULL, 1, 'admin_login', 'Admin \'admin\' logged in successfully', '112.202.115.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'admins', 1, 'success', '2025-11-28 02:53:10'),
(34, NULL, NULL, 'failed_login', 'Failed login attempt for email: angeliedelacruzdingding@gmail.com', '112.202.110.225', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', NULL, NULL, 'failed', '2025-11-28 04:11:27'),
(35, NULL, NULL, 'failed_login', 'Failed login attempt for email: angeliedelacruzdingding@gmail.com', '112.202.110.225', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', NULL, NULL, 'failed', '2025-11-28 04:12:20'),
(36, NULL, NULL, 'failed_login', 'Failed login attempt for email: angeliedelacruzdingding@gmail.com', '112.202.110.225', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', NULL, NULL, 'failed', '2025-11-28 04:12:53'),
(37, NULL, NULL, 'failed_login', 'Failed login attempt for email: angeliedelacruzdingding@gmail.com', '112.202.110.225', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', NULL, NULL, 'failed', '2025-11-28 04:13:19'),
(38, NULL, NULL, 'failed_login', 'Failed login attempt for email: angeliedelacruzdingding@gmail.com', '112.202.110.225', 'Mozilla/5.0 (Linux; Android 14; SM-A135F Build/UP1A.231005.007; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.102 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/534.0.0.53.103;]', NULL, NULL, 'failed', '2025-11-28 04:14:06'),
(39, NULL, NULL, 'failed_login', 'Failed login attempt for email: angeliedelacruzdingding@gmail.com', '112.202.110.225', 'Mozilla/5.0 (Linux; Android 14; SM-A135F Build/UP1A.231005.007; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.102 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/534.0.0.53.103;]', NULL, NULL, 'failed', '2025-11-28 04:14:16'),
(40, NULL, NULL, 'failed_login', 'Failed login attempt for email: angeliedelacruzdingding@gmail.com', '112.202.110.225', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', NULL, NULL, 'failed', '2025-11-28 04:14:49'),
(41, NULL, NULL, 'failed_login', 'Failed login attempt for email: angeliedelacruzdingding@gmail.com', '112.202.110.225', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', NULL, NULL, 'failed', '2025-11-28 04:15:24'),
(42, NULL, NULL, 'failed_login', 'Failed login attempt for email: angeliedelacruzdingding@gmail.com', '112.202.110.225', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', NULL, NULL, 'failed', '2025-11-28 04:15:46'),
(43, NULL, NULL, 'failed_login', 'Failed login attempt for email: angeliedelacruzdingding@gmail.com', '112.202.110.225', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', NULL, NULL, 'failed', '2025-11-28 04:15:58'),
(44, NULL, NULL, 'failed_login', 'Failed login attempt for email: angeliedelacruzdingding@gmail.com', '112.202.110.225', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', NULL, NULL, 'failed', '2025-11-28 04:16:10'),
(45, NULL, 3, 'admin_login', 'Admin \'villaneza11\' logged in successfully', '112.202.110.225', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 'admins', 3, 'success', '2025-11-28 04:17:05'),
(46, NULL, NULL, 'failed_login', 'Failed login attempt for email: angeliedelacruzdingding@gmail.com', '112.202.110.225', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', NULL, NULL, 'failed', '2025-11-28 04:18:57'),
(47, NULL, NULL, 'failed_login', 'Failed login attempt for email: angeliedelacruzdingding@gmail.com', '112.202.110.225', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', NULL, NULL, 'failed', '2025-11-28 04:19:32'),
(48, 7, NULL, 'login', 'User \'Ydzz\' logged in successfully', '131.226.125.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'users', 7, 'success', '2025-12-04 02:28:10'),
(49, 8, NULL, 'register', 'New user registered: \'ratfucker\' (ratfucker@gmail.com)', '216.247.82.11', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', 'users', 8, 'success', '2025-12-04 02:29:50'),
(50, NULL, NULL, 'failed_login', 'Failed admin login attempt - Invalid password for username: admin', '131.226.125.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, NULL, 'failed', '2025-12-04 02:30:14'),
(51, NULL, 1, 'admin_login', 'Admin \'admin\' logged in successfully', '131.226.125.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'admins', 1, 'success', '2025-12-04 02:30:21'),
(52, 8, NULL, 'upload_photo', 'Uploaded photo: \'gain-eagers\' (File: 6930f27bcb30e_1764815483.jpg)', '216.247.82.11', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', 'photos', 10, 'success', '2025-12-04 02:31:23'),
(53, NULL, 1, 'admin_delete_comment', 'Admin deleted comment by \'ratfucker\' on photo \'gain-eagers\' (Comment ID: 13)', '131.226.125.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'comments', 13, 'success', '2025-12-04 02:33:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comments_photo_id` (`photo_id`),
  ADD KEY `idx_comments_user_id` (`user_id`),
  ADD KEY `idx_comments_created_at` (`created_at`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`user_id`,`photo_id`),
  ADD KEY `idx_likes_photo_id` (`photo_id`),
  ADD KEY `idx_likes_user_id` (`user_id`);

--
-- Indexes for table `photos`
--
ALTER TABLE `photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_photos_user_id` (`user_id`),
  ADD KEY `idx_photos_uploaded_at` (`uploaded_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_logs_user_action` (`user_id`,`action_type`,`created_at`),
  ADD KEY `idx_logs_combined` (`user_id`,`status`,`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `photos`
--
ALTER TABLE `photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`photo_id`) REFERENCES `photos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`photo_id`) REFERENCES `photos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `photos`
--
ALTER TABLE `photos`
  ADD CONSTRAINT `photos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD CONSTRAINT `user_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_logs_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
