-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 30 Agu 2025 pada 07.05
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `virtual_laboratory_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `activity_log`
--

CREATE TABLE `activity_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `activity_log`
--

INSERT INTO `activity_log` (`log_id`, `user_id`, `action`, `entity_type`, `entity_id`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`) VALUES
(4, 4, 'update_lab', 'laboratory', 2, NULL, NULL, NULL, NULL, '2025-08-21 17:14:33'),
(5, 4, 'update_lab', 'laboratory', 2, NULL, NULL, NULL, NULL, '2025-08-21 17:20:35'),
(6, 4, 'update_lab', 'laboratory', 2, NULL, NULL, NULL, NULL, '2025-08-21 17:20:42'),
(7, 4, 'update_lab', 'laboratory', 2, NULL, NULL, NULL, NULL, '2025-08-21 17:20:46'),
(8, 4, 'update_lab', 'laboratory', 2, NULL, NULL, NULL, NULL, '2025-08-21 17:20:50'),
(9, 4, 'create_lab', 'laboratory', 4, NULL, NULL, NULL, NULL, '2025-08-21 17:33:56'),
(10, 4, 'create_lab', 'laboratory', 5, NULL, NULL, NULL, NULL, '2025-08-21 17:34:40'),
(11, 4, 'create_lab', 'laboratory', 6, NULL, NULL, NULL, NULL, '2025-08-21 17:39:54'),
(12, 4, 'create_lab', 'laboratory', 7, NULL, NULL, NULL, NULL, '2025-08-21 17:42:35'),
(13, 4, 'update_lab', 'laboratory', 7, NULL, NULL, NULL, NULL, '2025-08-22 14:39:36'),
(14, 4, 'update_lab', 'laboratory', 7, NULL, NULL, NULL, NULL, '2025-08-22 14:39:56'),
(15, 4, 'create_course', 'course', 4, NULL, NULL, NULL, NULL, '2025-08-22 16:02:09'),
(16, 4, 'create_software', 'software', 1, NULL, NULL, NULL, NULL, '2025-08-22 16:31:01'),
(17, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 17:07:10'),
(18, 3, 'logout', 'user', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 10:42:15'),
(19, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 10:43:00'),
(20, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 10:43:48'),
(21, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 10:43:54'),
(22, 3, 'logout', 'user', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 10:49:56'),
(23, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 10:50:04'),
(24, 3, 'logout', 'user', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 11:31:21'),
(25, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 11:31:26'),
(26, 3, 'logout', 'user', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 11:38:52'),
(27, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 11:38:58'),
(28, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:12:05'),
(29, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:13:01'),
(30, 4, 'create_course', 'course', 5, NULL, NULL, NULL, NULL, '2025-08-23 13:29:28'),
(31, 3, 'logout', 'user', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:29:43'),
(32, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:29:48'),
(33, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:35:03'),
(34, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:37:15'),
(35, 4, 'update_course', 'course', 3, NULL, NULL, NULL, NULL, '2025-08-23 13:42:31'),
(36, 4, 'toggle_course_status', 'course', 3, NULL, NULL, NULL, NULL, '2025-08-23 13:42:40'),
(37, 4, 'toggle_course_status', 'course', 3, NULL, NULL, NULL, NULL, '2025-08-23 13:42:47'),
(38, 4, 'toggle_course_status', 'course', 4, NULL, NULL, NULL, NULL, '2025-08-23 13:42:51'),
(39, 4, 'toggle_course_status', 'course', 4, NULL, NULL, NULL, NULL, '2025-08-23 13:43:00'),
(40, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:45:22'),
(41, 5, 'login', 'user', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:45:46'),
(42, 5, 'change_password', 'user', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:08:29'),
(43, 5, 'logout', 'user', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:08:35'),
(44, 5, 'login', 'user', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:08:46'),
(45, 5, 'change_password', 'user', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:09:15'),
(46, 5, 'logout', 'user', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:28:03'),
(47, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:28:08'),
(48, 5, 'login', 'user', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:52:10'),
(49, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 18:47:45'),
(50, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 20:23:28'),
(51, 5, 'login', 'user', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 20:23:40'),
(52, 5, 'logout', 'user', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 21:33:06'),
(53, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 21:33:12'),
(54, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 21:47:32'),
(55, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 21:47:47'),
(56, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 21:48:00'),
(57, 5, 'login', 'user', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 21:49:08'),
(58, 5, 'logout', 'user', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 21:49:15'),
(59, 5, 'login', 'user', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 21:52:39'),
(60, 5, 'logout', 'user', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 21:52:51'),
(61, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 21:55:57'),
(62, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 21:56:02'),
(63, 5, 'login', 'user', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 21:56:50'),
(64, 5, 'logout', 'user', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 21:56:54'),
(65, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 22:01:16'),
(66, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 22:01:44'),
(67, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 22:04:46'),
(68, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 22:04:50'),
(69, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 22:04:55'),
(70, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 22:07:26'),
(71, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 22:07:43'),
(72, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 22:09:53'),
(73, 2, 'login', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 22:09:58'),
(74, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 23:18:55'),
(75, 3, 'logout', 'user', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 23:22:22'),
(76, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 23:24:18'),
(77, 3, 'logout', 'user', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 23:34:11'),
(78, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 00:00:58'),
(79, 3, 'logout', 'user', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 00:01:29'),
(80, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 03:39:58'),
(81, 3, 'logout', 'user', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 03:40:19'),
(82, 3, 'logout', 'user', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 16:05:41'),
(83, 4, 'create_course', 'course', 6, NULL, NULL, NULL, NULL, '2025-08-24 16:54:04'),
(84, 3, 'logout', 'user', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 16:58:41'),
(85, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 18:32:25'),
(86, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 18:33:48'),
(87, 6, 'logout', 'user', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 18:42:48'),
(88, 4, 'logout', 'admin', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 20:05:51'),
(89, 4, 'logout', 'admin', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 20:12:41'),
(90, 4, 'logout', 'admin', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 20:29:29'),
(91, 4, 'logout', 'admin', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 20:41:19'),
(92, 3, 'logout', 'user', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 21:14:41'),
(93, 4, 'create_software', 'software', 2, NULL, NULL, NULL, NULL, '2025-08-26 21:27:50'),
(94, 4, 'update_software', 'software', 1, NULL, NULL, NULL, NULL, '2025-08-26 21:31:22'),
(95, 4, 'update_software', 'software', 1, NULL, NULL, NULL, NULL, '2025-08-26 21:32:05'),
(96, 4, 'logout', 'admin', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-28 00:38:23'),
(97, 4, 'logout', 'admin', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-28 00:57:09'),
(98, 4, 'logout', 'admin', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-28 01:37:31'),
(99, 4, 'logout', 'admin', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-28 01:40:06'),
(100, 4, 'logout', 'admin', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-28 01:53:18'),
(101, 7, 'logout', 'admin', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-28 01:55:45'),
(102, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-28 01:56:19'),
(103, 2, 'logout', 'user', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-28 13:05:42'),
(104, 3, 'logout', 'user', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-28 13:06:46'),
(105, 4, 'logout', 'admin', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 00:37:38'),
(106, 8, 'logout', 'user', 8, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 11:44:10');

-- --------------------------------------------------------

--
-- Struktur dari tabel `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Dosen yang membuat pengumuman',
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `is_important` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `course_id`, `user_id`, `title`, `content`, `is_important`, `created_at`) VALUES
(1, 4, 3, 'Perubahan Jadwal Praktikum', 'Diberitahukan, jadwal praktikum minggu ini dipindah ke hari Rabu pukul 13:00 WIB di Lab Database.', 1, '2025-08-22 16:45:00'),
(2, 4, 3, 'Materi Baru Telah Diupload', 'Silakan unduh materi terbaru mengenai SQL Dasar.', 0, '2025-08-22 09:30:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `assignment`
--

CREATE TABLE `assignment` (
  `assignment_id` int(11) NOT NULL,
  `id_tugas` int(11) NOT NULL,
  `id_matkul` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `due_date` datetime NOT NULL,
  `max_score` int(11) DEFAULT 100,
  `attachment_path` varchar(500) DEFAULT NULL,
  `status` enum('draft','published','closed') DEFAULT 'draft',
  `allow_edit` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Tidak Boleh, 1=Boleh Edit',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `assignment`
--

INSERT INTO `assignment` (`assignment_id`, `id_tugas`, `id_matkul`, `title`, `description`, `due_date`, `max_score`, `attachment_path`, `status`, `allow_edit`, `created_at`, `updated_at`) VALUES
(2, 0, 6, 'tes`tes', 'tes pembuatan tugas', '2025-08-25 17:13:00', 100, NULL, 'published', 0, '2025-08-24 17:13:16', '2025-08-24 17:16:33'),
(3, 0, 3, 'tugas 2', 'tes pengumupulan tugas', '2025-08-29 11:48:00', 100, NULL, 'published', 0, '2025-08-28 11:48:45', '2025-08-28 11:48:45'),
(4, 0, 6, 'tes cek edit1', 'tes funsgi checkbox', '2025-08-29 14:13:00', 100, NULL, 'published', 1, '2025-08-28 14:13:24', '2025-08-28 14:31:35'),
(5, 0, 4, 'Pembuatan Tugas Uji coba', 'Tugas Uji Coba', '2025-08-31 11:48:00', 100, NULL, 'published', 1, '2025-08-30 11:48:43', '2025-08-30 11:48:43');

-- --------------------------------------------------------

--
-- Struktur dari tabel `course`
--

CREATE TABLE `course` (
  `course_id` int(11) NOT NULL,
  `kode_matkul` varchar(20) NOT NULL,
  `nama_matkul` varchar(100) NOT NULL,
  `id_dosen` int(11) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `course`
--

INSERT INTO `course` (`course_id`, `kode_matkul`, `nama_matkul`, `id_dosen`, `semester`, `status`, `created_at`, `updated_at`) VALUES
(3, 'TIF-4001', 'Pemrograman Web', 3, 'Ganjil 2024/2025', 'active', '2025-08-21 16:38:57', '2025-08-23 13:42:47'),
(4, 'TIF-4002', 'BasisData', 3, 'Ganjil 2024/2025', 'active', '2025-08-22 16:02:09', '2025-08-23 13:43:00'),
(5, 'TIF-4003', 'jaringan Komputer', 3, 'Ganjil 2024/2025', 'active', '2025-08-23 13:29:28', '2025-08-23 13:29:28'),
(6, 'TIF-4004', 'Pemrograman Android', 6, 'Ganjil 2024/2025', 'active', '2025-08-24 16:54:04', '2025-08-24 16:54:04');

-- --------------------------------------------------------

--
-- Struktur dari tabel `course_materials`
--

CREATE TABLE `course_materials` (
  `material_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(20) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `course_materials`
--

INSERT INTO `course_materials` (`material_id`, `course_id`, `title`, `description`, `file_path`, `file_type`, `uploaded_at`) VALUES
(4, 6, 'Topik 1', 'tes upload materi', '../uploads/materials/1756033760_basisdata.jpeg', 'jpeg', '2025-08-24 18:09:20'),
(5, 4, 'tes tes', 'tes mengirim materi database', '../uploads/materials/1756351491_1756033760_basisdata1.jpeg', 'jpeg', '2025-08-28 10:24:51');

-- --------------------------------------------------------

--
-- Struktur dari tabel `enrollment`
--

CREATE TABLE `enrollment` (
  `enrollment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `enrolled_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `enrollment`
--

INSERT INTO `enrollment` (`enrollment_id`, `user_id`, `course_id`, `status`, `approved_by`, `approved_at`, `rejection_reason`, `enrolled_at`) VALUES
(3, 2, 4, 'approved', 3, '2025-08-23 10:12:53', NULL, '2025-08-22 23:08:14'),
(6, 5, 4, 'approved', 3, '2025-08-24 16:30:13', NULL, '2025-08-23 13:56:37'),
(8, 2, 5, 'approved', 3, '2025-08-24 16:30:15', NULL, '2025-08-23 21:38:32'),
(9, 2, 6, 'approved', 6, '2025-08-24 16:59:46', NULL, '2025-08-24 16:59:38'),
(10, 5, 6, 'approved', 6, '2025-08-24 18:34:31', NULL, '2025-08-24 18:34:15'),
(12, 5, 5, 'approved', 3, '2025-08-28 11:49:23', NULL, '2025-08-24 18:46:25'),
(13, 2, 3, 'approved', 3, '2025-08-28 11:49:41', NULL, '2025-08-28 11:49:19'),
(16, 8, 4, 'approved', 3, '2025-08-30 11:48:07', NULL, '2025-08-30 11:44:27');

-- --------------------------------------------------------

--
-- Struktur dari tabel `laboratory`
--

CREATE TABLE `laboratory` (
  `lab_id` int(11) NOT NULL,
  `lab_name` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 30,
  `lab_type` enum('programming','database','networking','multimedia') NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `laboratory`
--

INSERT INTO `laboratory` (`lab_id`, `lab_name`, `capacity`, `lab_type`, `description`, `location`, `created_at`, `updated_at`) VALUES
(2, 'Lab Programing 1', 32, 'programming', 'Lab Programing', 'Bandung', '2025-08-21 16:52:15', '2025-08-21 17:20:50'),
(4, 'Lab Database', 25, 'database', 'Laboratorium untuk pembelajaran jaringan komputer dan administrasi sistem', 'Gedung Teknik Lantai 3', '2025-08-21 17:33:56', '2025-08-21 17:33:56'),
(5, 'Lab Multimedia', 25, 'networking', 'Laboratorium untuk pembelajaran jaringan komputer dan administrasi sistem', 'Gedung Teknik Lantai 3', '2025-08-21 17:34:40', '2025-08-21 17:34:40'),
(6, 'Lab Networking', 20, 'networking', 'Laboratorium untuk pembelajaran jaringan komputer dan administrasi sistem', 'Gedung Teknik Lantai 3', '2025-08-21 17:39:54', '2025-08-21 17:39:54'),
(7, 'Lab Programing 2', 25, 'programming', 'Laboratorium lanjutan untuk pembelajaran framework dan mobile programming\'', 'Gedung Teknik Lantai 4', '2025-08-21 17:42:35', '2025-08-21 17:42:35');

-- --------------------------------------------------------

--
-- Struktur dari tabel `lab_software`
--

CREATE TABLE `lab_software` (
  `lab_software_id` int(11) NOT NULL,
  `lab_id` int(11) NOT NULL,
  `software_id` int(11) NOT NULL,
  `installation_date` datetime DEFAULT current_timestamp(),
  `installed_by` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `lab_software`
--

INSERT INTO `lab_software` (`lab_software_id`, `lab_id`, `software_id`, `installation_date`, `installed_by`, `status`) VALUES
(2, 2, 1, '2025-08-22 16:45:34', 4, 'active'),
(3, 4, 1, '2025-08-26 21:26:36', 4, 'active'),
(4, 5, 1, '2025-08-26 21:26:41', 4, 'active'),
(5, 6, 1, '2025-08-26 21:26:45', 4, 'active'),
(6, 7, 1, '2025-08-26 21:27:07', 4, 'active'),
(7, 2, 2, '2025-08-26 21:32:32', 4, 'active');

-- --------------------------------------------------------

--
-- Struktur dari tabel `matakuliah`
--

CREATE TABLE `matakuliah` (
  `course_id` int(11) NOT NULL,
  `kode_matkul` varchar(20) NOT NULL,
  `nama_matkul` varchar(100) NOT NULL,
  `id_dosen` int(11) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `notification`
--

CREATE TABLE `notification` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','urgent','success') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notification`
--

INSERT INTO `notification` (`notification_id`, `user_id`, `title`, `message`, `type`, `is_read`, `read_at`, `created_by`, `created_at`, `updated_at`) VALUES
(17, 2, 'tes mahasiswa', 'tes mahasiswa', 'info', 1, '2025-08-23 15:57:15', NULL, '2025-08-22 15:09:03', '2025-08-23 15:57:15'),
(18, 2, 'tes mahasiswa', 'tes mahasiswa', 'info', 1, '2025-08-23 15:57:15', NULL, '2025-08-22 15:09:11', '2025-08-23 15:57:15'),
(19, 2, 'tes tes', 'tes informasi', 'success', 1, '2025-08-26 13:54:44', NULL, '2025-08-26 13:54:25', '2025-08-26 13:54:44'),
(20, 5, 'tes tes', 'tes informasi', 'success', 0, NULL, NULL, '2025-08-26 13:54:25', '2025-08-26 13:54:25'),
(21, 3, 'tes tes', 'tes notifikasi dosen', 'info', 0, NULL, NULL, '2025-08-28 03:14:55', '2025-08-28 03:14:55'),
(22, 6, 'tes tes', 'tes notifikasi dosen', 'info', 0, NULL, NULL, '2025-08-28 03:14:55', '2025-08-28 03:14:55'),
(23, 2, 'pppp', 'tes tes tes tes tes final', 'info', 0, NULL, NULL, '2025-08-29 17:52:50', '2025-08-29 17:52:50'),
(24, 5, 'pppp', 'tes tes tes tes tes final', 'info', 0, NULL, NULL, '2025-08-29 17:52:50', '2025-08-29 17:52:50'),
(25, 7, 'pppp', 'tes tes tes tes tes final', 'info', 0, NULL, NULL, '2025-08-29 17:52:50', '2025-08-29 17:52:50');

-- --------------------------------------------------------

--
-- Struktur dari tabel `reservation`
--

CREATE TABLE `reservation` (
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lab_id` int(11) NOT NULL,
  `computer_id` int(11) DEFAULT NULL,
  `reservation_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `purpose` text DEFAULT NULL,
  `software_needed` varchar(100) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `reservation`
--

INSERT INTO `reservation` (`reservation_id`, `user_id`, `lab_id`, `computer_id`, `reservation_date`, `start_time`, `end_time`, `purpose`, `software_needed`, `status`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 2, 4, NULL, '2025-08-25', '10:00:00', '22:31:00', 'Praktikum Basisdata', NULL, 'confirmed', 3, '2025-08-23 22:31:39', '2025-08-23 23:27:10'),
(2, 2, 4, 4, '2025-08-25', '10:00:00', '22:00:00', 'Pratikum Basisdata', NULL, 'confirmed', 3, '2025-08-23 23:24:03', '2025-08-23 23:34:07'),
(3, 2, 4, 3, '2025-08-24', '00:00:00', '03:00:00', 'tes', NULL, 'confirmed', 3, '2025-08-24 00:00:47', '2025-08-24 00:01:24'),
(4, 2, 4, 3, '2025-08-24', '03:39:00', '07:00:00', 'testestes', 'heidisql', 'confirmed', 3, '2025-08-24 03:39:50', '2025-08-24 03:40:15'),
(5, 2, 2, 1, '2025-08-24', '16:12:00', '17:12:00', 'coba tes', 'vscode', '', 3, '2025-08-24 16:12:39', '2025-08-24 16:12:49'),
(6, 2, 2, 1, '2025-08-24', '16:13:00', '17:13:00', 'coba tes', 'vscode', 'confirmed', 3, '2025-08-24 16:13:51', '2025-08-24 16:14:00'),
(7, 2, 2, 1, '2025-08-27', '23:29:00', '23:50:00', 'pppp', 'Visual Studio Code', 'confirmed', 3, '2025-08-27 23:29:35', '2025-08-27 23:29:49');

-- --------------------------------------------------------

--
-- Struktur dari tabel `session`
--

CREATE TABLE `session` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `computer_id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` enum('active','completed','terminated') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `software`
--

CREATE TABLE `software` (
  `software_id` int(11) NOT NULL,
  `software_name` varchar(100) NOT NULL,
  `version` varchar(20) NOT NULL,
  `category` enum('programming','database','networking','design','office') NOT NULL,
  `license_type` varchar(50) DEFAULT NULL,
  `vendor` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `software`
--

INSERT INTO `software` (`software_id`, `software_name`, `version`, `category`, `license_type`, `vendor`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Visual Studio Code', '1.85.0', 'programming', 'free', 'Microsoft', 'active', '2025-08-22 16:31:01', '2025-08-26 21:32:05'),
(2, 'Mysql', '1.75.0', 'database', 'free', 'mysql', 'active', '2025-08-26 21:27:50', '2025-08-26 21:27:50');

-- --------------------------------------------------------

--
-- Struktur dari tabel `submission`
--

CREATE TABLE `submission` (
  `submission_id` int(11) NOT NULL,
  `id_tugas` int(11) NOT NULL,
  `id_mahasiswa` int(11) NOT NULL,
  `tanggal_dikumpulkan` datetime DEFAULT current_timestamp(),
  `file_path` varchar(500) NOT NULL,
  `submission_title` varchar(255) DEFAULT NULL,
  `student_comment` text DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `dinilai_oleh` int(11) DEFAULT NULL,
  `tanggal_dinilai` datetime DEFAULT NULL,
  `status` enum('submitted','graded','returned') DEFAULT 'submitted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `submission`
--

INSERT INTO `submission` (`submission_id`, `id_tugas`, `id_mahasiswa`, `tanggal_dikumpulkan`, `file_path`, `submission_title`, `student_comment`, `score`, `feedback`, `dinilai_oleh`, `tanggal_dinilai`, `status`) VALUES
(1, 2, 2, '2025-08-24 17:33:35', '../uploads/submissions/1756031615_2_SURATNo.005-SURATKESEDIAANPENERIMAANKERJAPRAKTIK-KP-UNIKOM-2025.pdf', 'tes pengumpulan', 'tes pengumpulan', 80, 'tes', 6, '2025-08-28 14:37:21', 'graded'),
(9, 3, 2, '2025-08-28 12:10:25', '../uploads/submissions/1756357825_2_pemrogramanweb.jpeg', 'tes pengumpulan 2', 'tes pengumpulan tugas 2', 80, 'tes', 3, '2025-08-28 12:51:23', 'graded'),
(10, 2, 5, '2025-08-28 13:06:21', '../uploads/submissions/1756361181_5_1756351491_1756033760_basisdata1.jpeg', 'tes pengumpulan', 'sahdgahsd', NULL, NULL, NULL, NULL, 'submitted'),
(11, 4, 5, '2025-08-28 14:31:13', '../uploads/submissions/1756366273_5_Template-TugasP7.Kuisversi1bMei2025.docx', 'tes pengumpulan', 'tes pengumpulan checkbox', NULL, NULL, NULL, NULL, 'submitted'),
(12, 5, 8, '2025-08-30 11:52:20', 'uploads/submissions/1756529540_8_basisdata.jpeg', 'tes pengumpulan 3', 'Pengumpualn Tugas Basis Data', 90, 'Baik', 3, '2025-08-30 11:54:12', 'graded');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `role` enum('mahasiswa','dosen','admin') NOT NULL,
  `status` enum('active','inactive','pending') NOT NULL DEFAULT 'pending',
  `profile_picture` varchar(255) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_verified` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `phone_number`, `role`, `status`, `profile_picture`, `password_reset_token`, `password_verified`, `last_login`, `created_at`, `updated_at`) VALUES
(2, 'dafa1', '$2y$10$1UAiZTYM1VXKEup4mAc38efxU6VptA1.Amb0vfko8MMoU7XxtLsUm', 'dafa@gmail.com', 'dafa', '081234567899', 'mahasiswa', 'active', NULL, NULL, 0, '2025-08-30 00:14:38', '2025-08-21 15:50:40', '2025-08-30 00:14:38'),
(3, 'Riski', '$2y$10$MZ0dhWMAlf58q045UqCvMO23wOR.8BMkiPbM/wWR.zOOQoyjBk/he', 'Riski@gmail.com', 'Muhammad Riski', '085123456781', 'dosen', 'active', NULL, NULL, 0, '2025-08-30 11:39:38', '2025-08-21 16:10:34', '2025-08-30 11:39:38'),
(4, 'admin123', '$2y$10$fvKSu93qBnmevtnZ9UCGB.wQTeWBcYY2apgzbEQFK0ktsCfF.uIeK', 'admin@gmail.com', 'admin', '0812345678999', 'admin', 'active', NULL, NULL, 0, NULL, '2025-08-21 17:04:32', '2025-08-21 17:04:32'),
(5, 'Ridho', '$2y$10$iDsR.pMuzKw/I.dMDV4IP.xdNVLOc6JuE222WQmgEJkqrCXKlp8la', 'rido@gmail.com', 'rido', '082122445677', 'mahasiswa', 'active', NULL, NULL, 0, '2025-08-28 13:05:50', '2025-08-23 13:45:15', '2025-08-28 13:05:50'),
(6, 'Adrian', '$2y$10$nICE3.yY0Xz5NggExLCMmuqnrpsOw3N5pzzUMnILEfG48ByHQzha.', 'adrian@gmail.com', 'Adrian ', '081234566788', 'dosen', 'active', NULL, NULL, 0, '2025-08-28 13:07:02', '2025-08-24 16:53:41', '2025-08-28 13:07:02'),
(7, 'Maulana', '$2y$10$ytcWEoQhk6zjaWrNvRZv2unvBp149HXndEbt7F8rgODmXDqxZuuva', 'maulana@gmail.com', 'Maulana', '', 'mahasiswa', 'active', NULL, NULL, 0, NULL, '2025-08-28 00:57:30', '2025-08-28 10:22:23'),
(8, 'candra', '$2y$10$YDEE/tt0LJ94AJ/iIkYzP.m0aCOWgAW5lmnxdV.bqTRYDJpoeNFWm', 'candra@gmail.com', 'candra', '081234567899', 'mahasiswa', 'active', NULL, NULL, 0, '2025-08-30 11:44:19', '2025-08-30 00:55:51', '2025-08-30 11:44:19');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_notifications`
--

CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `virtual_computer`
--

CREATE TABLE `virtual_computer` (
  `computer_id` int(11) NOT NULL,
  `lab_id` int(11) NOT NULL,
  `computer_name` varchar(50) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `mac_address` varchar(17) DEFAULT NULL,
  `cpu_cores` int(11) DEFAULT 2,
  `ram_size` int(11) DEFAULT 4,
  `storage_size` int(11) DEFAULT 50,
  `status` enum('available','occupied','maintenance') DEFAULT 'available',
  `current_user_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `maintenance_status` enum('normal','warning','error') DEFAULT 'normal',
  `rdp_host` varchar(255) DEFAULT NULL COMMENT 'IP Address atau Hostname VM',
  `rdp_username` varchar(100) DEFAULT NULL,
  `rdp_password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `virtual_computer`
--

INSERT INTO `virtual_computer` (`computer_id`, `lab_id`, `computer_name`, `ip_address`, `mac_address`, `cpu_cores`, `ram_size`, `storage_size`, `status`, `current_user_id`, `created_at`, `updated_at`, `maintenance_status`, `rdp_host`, `rdp_username`, `rdp_password`) VALUES
(1, 2, 'PC-PROG-001', '192.168.1.101', '00:11:22:33:44;01', 4, 4, 50, 'available', NULL, '2025-08-21 16:55:08', '2025-08-21 16:55:08', 'normal', NULL, NULL, NULL),
(2, 2, 'PC-PROG-002', '192.168.1.102', '00:11:22:33:44:02', 4, 8, 100, 'available', NULL, '2025-08-21 17:16:46', '2025-08-21 17:16:46', 'normal', NULL, NULL, NULL),
(3, 4, 'PC-DB-001', '192.168.2.101', '00:11:22:33:55:01', 4, 16, 150, 'available', NULL, '2025-08-21 17:35:56', '2025-08-27 16:33:23', 'normal', '192.168.100.117', 'pro-lab', '123456'),
(4, 4, 'PC-DB-002', '192.168.2.102', '00:11:22:33:55:02', 4, 16, 150, 'available', NULL, '2025-08-21 17:37:09', '2025-08-21 17:37:09', 'normal', NULL, NULL, NULL),
(5, 5, 'PC-MM-001', '192.168.4.101', '00:11:22:33:77:01', 6, 16, 200, 'maintenance', NULL, '2025-08-21 17:37:56', '2025-08-28 10:17:43', 'normal', NULL, NULL, NULL),
(6, 5, 'PC-MM-002', '192.168.4.102', '00:11:22:33:77:02', 6, 16, 200, 'available', NULL, '2025-08-21 17:38:37', '2025-08-21 17:38:37', 'normal', NULL, NULL, NULL),
(7, 6, 'PC-NET-001', '192.168.3.101', '00:11:22:33:66:01', 2, 8, 50, 'available', NULL, '2025-08-21 17:41:38', '2025-08-21 18:15:52', 'normal', NULL, NULL, NULL),
(8, 7, 'PC-PROG2-001', '192.168.5.101', '00:11:22:33:88:01', 8, 16, 200, 'available', NULL, '2025-08-21 17:43:24', '2025-08-22 14:39:51', 'normal', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_action` (`user_id`,`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indeks untuk tabel `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `assignment`
--
ALTER TABLE `assignment`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `idx_matkul` (`id_matkul`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `kode_matkul` (`kode_matkul`),
  ADD KEY `idx_dosen` (`id_dosen`),
  ADD KEY `idx_semester` (`semester`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `course_materials`
--
ALTER TABLE `course_materials`
  ADD PRIMARY KEY (`material_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indeks untuk tabel `enrollment`
--
ALTER TABLE `enrollment`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `unique_enrollment` (`user_id`,`course_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `idx_user_course` (`user_id`,`course_id`),
  ADD KEY `fk_enrollment_approved_by` (`approved_by`);

--
-- Indeks untuk tabel `laboratory`
--
ALTER TABLE `laboratory`
  ADD PRIMARY KEY (`lab_id`),
  ADD UNIQUE KEY `lab_name` (`lab_name`),
  ADD KEY `idx_lab_type` (`lab_type`),
  ADD KEY `idx_capacity` (`capacity`);

--
-- Indeks untuk tabel `lab_software`
--
ALTER TABLE `lab_software`
  ADD PRIMARY KEY (`lab_software_id`),
  ADD UNIQUE KEY `unique_lab_software` (`lab_id`,`software_id`),
  ADD KEY `software_id` (`software_id`),
  ADD KEY `installed_by` (`installed_by`);

--
-- Indeks untuk tabel `matakuliah`
--
ALTER TABLE `matakuliah`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `kode_matkul` (`kode_matkul`),
  ADD KEY `idx_dosen` (`id_dosen`),
  ADD KEY `idx_semester` (`semester`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indeks untuk tabel `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `lab_id` (`lab_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_reservation_date` (`reservation_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user_lab` (`user_id`,`lab_id`),
  ADD KEY `fk_reservation_computer` (`computer_id`);

--
-- Indeks untuk tabel `session`
--
ALTER TABLE `session`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `idx_user_session` (`user_id`),
  ADD KEY `idx_computer_session` (`computer_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_start_time` (`start_time`);

--
-- Indeks untuk tabel `software`
--
ALTER TABLE `software`
  ADD PRIMARY KEY (`software_id`),
  ADD UNIQUE KEY `unique_software_version` (`software_name`,`version`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `submission`
--
ALTER TABLE `submission`
  ADD PRIMARY KEY (`submission_id`),
  ADD UNIQUE KEY `unique_submission` (`id_tugas`,`id_mahasiswa`),
  ADD KEY `dinilai_oleh` (`dinilai_oleh`),
  ADD KEY `idx_tugas` (`id_tugas`),
  ADD KEY `idx_mahasiswa` (`id_mahasiswa`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- Indeks untuk tabel `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_notification_id` (`notification_id`);

--
-- Indeks untuk tabel `virtual_computer`
--
ALTER TABLE `virtual_computer`
  ADD PRIMARY KEY (`computer_id`),
  ADD UNIQUE KEY `unique_lab_computer` (`lab_id`,`computer_name`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_current_user` (`current_user_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT untuk tabel `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `assignment`
--
ALTER TABLE `assignment`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `course`
--
ALTER TABLE `course`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `course_materials`
--
ALTER TABLE `course_materials`
  MODIFY `material_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `enrollment`
--
ALTER TABLE `enrollment`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `laboratory`
--
ALTER TABLE `laboratory`
  MODIFY `lab_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `lab_software`
--
ALTER TABLE `lab_software`
  MODIFY `lab_software_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `matakuliah`
--
ALTER TABLE `matakuliah`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `notification`
--
ALTER TABLE `notification`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `reservation`
--
ALTER TABLE `reservation`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `session`
--
ALTER TABLE `session`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `software`
--
ALTER TABLE `software`
  MODIFY `software_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `submission`
--
ALTER TABLE `submission`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `virtual_computer`
--
ALTER TABLE `virtual_computer`
  MODIFY `computer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_announcement_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_announcement_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `assignment`
--
ALTER TABLE `assignment`
  ADD CONSTRAINT `assignment_ibfk_1` FOREIGN KEY (`id_matkul`) REFERENCES `course` (`course_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `course_ibfk_1` FOREIGN KEY (`id_dosen`) REFERENCES `users` (`user_id`);

--
-- Ketidakleluasaan untuk tabel `course_materials`
--
ALTER TABLE `course_materials`
  ADD CONSTRAINT `fk_material_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `enrollment`
--
ALTER TABLE `enrollment`
  ADD CONSTRAINT `enrollment_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollment_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_enrollment_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `lab_software`
--
ALTER TABLE `lab_software`
  ADD CONSTRAINT `lab_software_ibfk_1` FOREIGN KEY (`software_id`) REFERENCES `software` (`software_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lab_software_ibfk_2` FOREIGN KEY (`lab_id`) REFERENCES `laboratory` (`lab_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lab_software_ibfk_3` FOREIGN KEY (`installed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `matakuliah`
--
ALTER TABLE `matakuliah`
  ADD CONSTRAINT `matakuliah_ibfk_1` FOREIGN KEY (`id_dosen`) REFERENCES `users` (`user_id`);

--
-- Ketidakleluasaan untuk tabel `reservation`
--
ALTER TABLE `reservation`
  ADD CONSTRAINT `fk_reservation_computer` FOREIGN KEY (`computer_id`) REFERENCES `virtual_computer` (`computer_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `reservation_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservation_ibfk_2` FOREIGN KEY (`lab_id`) REFERENCES `laboratory` (`lab_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservation_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `session`
--
ALTER TABLE `session`
  ADD CONSTRAINT `session_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `session_ibfk_2` FOREIGN KEY (`computer_id`) REFERENCES `virtual_computer` (`computer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `session_ibfk_3` FOREIGN KEY (`reservation_id`) REFERENCES `reservation` (`reservation_id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `submission`
--
ALTER TABLE `submission`
  ADD CONSTRAINT `submission_ibfk_1` FOREIGN KEY (`id_tugas`) REFERENCES `assignment` (`assignment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submission_ibfk_2` FOREIGN KEY (`id_mahasiswa`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submission_ibfk_3` FOREIGN KEY (`dinilai_oleh`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `virtual_computer`
--
ALTER TABLE `virtual_computer`
  ADD CONSTRAINT `virtual_computer_ibfk_1` FOREIGN KEY (`lab_id`) REFERENCES `laboratory` (`lab_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `virtual_computer_ibfk_2` FOREIGN KEY (`current_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
