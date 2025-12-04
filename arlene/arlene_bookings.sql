-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 04, 2025
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
-- Database: `arlene_bookings`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `organizer` varchar(255) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `date_time` datetime NOT NULL,
  
  -- Ticket Counts
  `child_my_1to5` int(11) NOT NULL DEFAULT 0,
  `child_my_above5` int(11) NOT NULL DEFAULT 0,
  `child_foreign_1to5` int(11) NOT NULL DEFAULT 0,
  `child_foreign_above5` int(11) NOT NULL DEFAULT 0,
  `adult_my` int(11) NOT NULL DEFAULT 0,
  `adult_foreign` int(11) NOT NULL DEFAULT 0,
  `oku_my` int(11) NOT NULL DEFAULT 0,
  `oku_foreign` int(11) NOT NULL DEFAULT 0,
  
  -- Guide & Documents (Matches sendemail.php logic)
  `guide_count` int(11) NOT NULL DEFAULT 0,
  `guide_file_path` varchar(255) DEFAULT NULL,
  `oku_file_path` varchar(255) DEFAULT NULL,
  
  -- Details & Financials
  `remark` text DEFAULT NULL,
  `subtotal_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `final_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  
  -- Admin Calculations
  `paid_teachers` int(11) NOT NULL DEFAULT 0,
  `foc_teachers` int(11) NOT NULL DEFAULT 0,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;