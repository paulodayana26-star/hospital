-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Oct 21, 2026 at 03:01 AM
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
-- Database: `medcare_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` datetime NOT NULL,
  `status` enum('PENDING','CONFIRMED','COMPLETED','CANCELLED') DEFAULT 'PENDING',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `appointment_date`, `status`, `notes`, `created_at`) VALUES
(16, 2, 3, '2026-10-22 10:00:00', 'CONFIRMED', 'Routine check-up', '2026-10-21 00:56:08'),
(17, 8, 4, '2026-10-22 11:30:00', 'PENDING', 'Child vaccination follow-up', '2026-10-21 00:56:08'),
(18, 9, 5, '2026-10-22 14:00:00', 'COMPLETED', 'Back pain consultation', '2026-10-21 00:56:08'),
(19, 10, 6, '2026-10-23 09:15:00', 'CONFIRMED', 'Blood pressure check', '2026-10-21 00:56:08'),
(20, 2, 7, '2026-10-23 16:45:00', 'CANCELLED', 'Rescheduled by patient', '2026-10-21 00:56:08');

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `bill_number` varchar(30) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `bill_date` date NOT NULL,
  `status` enum('PAID','PARTIAL','UNPAID') DEFAULT 'UNPAID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `bill_number`, `patient_id`, `amount`, `bill_date`, `status`, `created_at`) VALUES
(1, 'INV-1001', 2, 320.00, '2026-10-22', 'PAID', '2026-10-21 00:56:55'),
(2, 'INV-1002', 8, 180.00, '2026-10-21', 'PARTIAL', '2026-10-21 00:56:55'),
(3, 'INV-1003', 9, 450.00, '2026-10-20', 'UNPAID', '2026-10-21 00:56:55'),
(4, 'INV-1004', 10, 275.50, '2026-10-19', 'PAID', '2026-10-21 00:56:55'),
(5, 'INV-1005', 2, 120.00, '2026-10-18', 'UNPAID', '2026-10-21 00:56:55');

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `diagnosis` varchar(255) NOT NULL,
  `treatment` text DEFAULT NULL,
  `record_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`id`, `patient_id`, `doctor_id`, `diagnosis`, `treatment`, `record_date`, `created_at`) VALUES
(1, 2, 3, 'Hypertension', 'Medication & follow-up', '2026-10-20', '2026-10-21 00:56:31'),
(2, 8, 4, 'Flu', 'Rest & medication', '2026-10-19', '2026-10-21 00:56:31'),
(3, 9, 5, 'Back pain', 'Physiotherapy', '2026-10-18', '2026-10-21 00:56:31'),
(4, 10, 6, 'High cholesterol', 'Diet change & statins', '2026-10-17', '2026-10-21 00:56:31'),
(5, 2, 7, 'Appendicitis', 'Surgery performed successfully', '2026-10-16', '2026-10-21 00:56:31');

-- --------------------------------------------------------

--
-- Table structure for table `role_privileges`
--

CREATE TABLE `role_privileges` (
  `role` varchar(20) NOT NULL,
  `permission` varchar(50) NOT NULL,
  `allowed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_privileges`
--

INSERT INTO `role_privileges` (`role`, `permission`, `allowed`) VALUES
('DOCTOR', 'appointments.book', 0),
('DOCTOR', 'appointments.manage', 1),
('DOCTOR', 'bills.view', 0),
('DOCTOR', 'records.create', 1),
('DOCTOR', 'records.view', 1),
('PATIENT', 'appointments.book', 1),
('PATIENT', 'appointments.manage', 0),
('PATIENT', 'bills.view', 1),
('PATIENT', 'records.create', 0),
('PATIENT', 'records.view', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('ADMIN','DOCTOR','PATIENT') NOT NULL DEFAULT 'PATIENT',
  `specialization` varchar(150) DEFAULT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'APPROVED',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `role`, `specialization`, `status`, `created_at`) VALUES
(1, 'Hospital Administrator', 'admin@hospital.com', '0700000000', '$2y$10$h3IZKQfLGfsKiquuL9PanuJcCevaiGONOl7NXJKK5d5SfS3MHI2/K', 'ADMIN', NULL, 'APPROVED', '2026-10-19 09:17:54'),
(2, 'DAYANA MALONGO', 'paulodayana26@gmail.com', '069310123093', '$2y$10$LRtAngsjazb0AEDJ/bYlVevMlZnLh..wM/UWkxE5RQ./lN6G6tSti', 'PATIENT', NULL, 'APPROVED', '2026-10-21 00:44:16'),
(3, 'Peter James', 'peter@example.com', '0712000001', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS8N0kZ6pQ6r0n9R6c0iFzF8mFyK', 'PATIENT', NULL, 'APPROVED', '2026-10-21 00:51:45'),
(4, 'Mary Joseph', 'mary@example.com', '0712000002', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS8N0kZ6pQ6r0n9R6c0iFzF8mFyK', 'PATIENT', NULL, 'APPROVED', '2026-10-21 00:51:45'),
(5, 'Daniel Paul', 'daniel@example.com', '0712000003', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS8N0kZ6pQ6r0n9R6c0iFzF8mFyK', 'PATIENT', NULL, 'APPROVED', '2026-10-21 00:51:45'),
(6, 'Grace Mushi', 'grace@example.com', '0712000004', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS8N0kZ6pQ6r0n9R6c0iFzF8mFyK', 'PATIENT', NULL, 'APPROVED', '2026-10-21 00:51:45'),
(7, 'Ali Hassan', 'ali@example.com', '0712000005', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS8N0kZ6pQ6r0n9R6c0iFzF8mFyK', 'PATIENT', NULL, 'APPROVED', '2026-10-21 00:51:45'),
(8, 'Dr. Anna Lee', 'anna.lee@hospital.com', '0722000001', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS8N0kZ6pQ6r0n9R6c0iFzF8mFyK', 'DOCTOR', 'Pediatrics', 'APPROVED', '2026-10-21 00:51:45'),
(9, 'Dr. Michael Kim', 'michael.kim@hospital.com', '0722000002', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS8N0kZ6pQ6r0n9R6c0iFzF8mFyK', 'DOCTOR', 'Orthopedics', 'APPROVED', '2026-10-21 00:51:45'),
(10, 'Dr. Amina Musa', 'amina.musa@hospital.com', '0722000003', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS8N0kZ6pQ6r0n9R6c0iFzF8mFyK', 'DOCTOR', 'Cardiology', 'APPROVED', '2026-10-21 00:51:45'),
(11, 'Dr. James Samuel', 'james.samuel@hospital.com', '0722000004', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS8N0kZ6pQ6r0n9R6c0iFzF8mFyK', 'DOCTOR', 'General Surgery', 'APPROVED', '2026-10-21 00:51:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bill_number` (`bill_number`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `role_privileges`
--
ALTER TABLE `role_privileges`
  ADD PRIMARY KEY (`role`,`permission`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bills`
--
ALTER TABLE `bills`
  ADD CONSTRAINT `bills_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
