-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 29, 2025 at 03:47 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `telehealth_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_datetime` datetime NOT NULL,
  `status` enum('booked','free') DEFAULT 'free',
  `jitsi_link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `doctor_id`, `appointment_datetime`, `status`, `jitsi_link`, `created_at`) VALUES
(3, 2, 1, '2025-04-09 10:00:00', 'booked', 'https://meet.jit.si/Appointment_1_2_1745259388', '2025-04-21 18:16:28'),
(4, 2, 1, '2025-04-09 12:01:00', 'booked', 'https://meet.jit.si/Appointment_1_2_1745259455', '2025-04-21 18:17:35'),
(5, 5, 1, '2025-04-09 15:00:00', 'booked', 'https://meet.jit.si/Appointment_1_5_1745260471', '2025-04-21 18:34:31'),
(6, 5, 1, '2025-04-09 18:00:00', 'booked', 'https://meet.jit.si/Appointment_1_5_1745262910', '2025-04-21 19:15:10'),
(7, 5, 1, '2025-04-16 16:00:00', 'booked', 'https://meet.jit.si/Appointment_1_5_1745265736', '2025-04-21 20:02:16'),
(8, 5, 1, '2025-04-08 11:11:00', 'booked', 'https://meet.jit.si/Appointment_1_5_1745265795', '2025-04-21 20:03:15'),
(9, 2, 1, '2025-04-28 11:11:00', 'booked', 'https://meet.jit.si/Appointment_1_2_1745785449', '2025-04-27 20:24:09'),
(10, 2, 1, '2025-04-28 09:00:00', 'booked', 'https://meet.jit.si/Appointment_1_2_1745785650', '2025-04-27 20:27:30'),
(11, 2, 1, '2025-04-28 14:00:00', 'booked', 'https://meet.jit.si/Appointment_1_2_1745785826', '2025-04-27 20:30:26'),
(12, 2, 4, '2025-04-28 09:00:00', 'booked', 'https://meet.jit.si/Appointment_4_2_1745785956', '2025-04-27 20:32:36'),
(13, 2, 4, '2025-04-28 13:00:00', 'booked', 'https://meet.jit.si/Appointment_4_2_1745786008', '2025-04-27 20:33:28'),
(14, 2, 1, '2025-04-29 12:12:00', 'booked', 'https://meet.jit.si/Appointment_1_2_1745786299', '2025-04-27 20:38:19'),
(15, 2, 4, '2025-04-29 11:11:00', 'booked', 'https://meet.jit.si/Appointment_4_2_1745786548', '2025-04-27 20:42:28'),
(17, 2, 1, '2025-04-29 00:00:00', 'booked', 'https://meet.jit.si/Appointment_1_2_1745787970', '2025-04-27 21:06:10'),
(18, 2, 1, '2025-05-08 00:00:00', 'booked', 'https://meet.jit.si/Appointment_1_2_1745787978', '2025-04-27 21:06:18'),
(43, 2, 1, '2025-05-30 11:00:00', 'booked', 'https://meet.jit.si/Appointment_1_2_1748106032', '2025-05-24 17:00:32'),
(44, 2, 1, '2025-05-30 12:00:00', 'booked', 'https://meet.jit.si/Appointment_1_2_1748519745', '2025-05-29 11:55:45'),
(45, 2, 1, '2025-05-31 10:00:00', 'booked', 'https://meet.jit.si/Appointment_1_2_1748521253', '2025-05-29 12:20:53');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`message_id`, `sender_id`, `receiver_id`, `message`, `sent_at`, `is_read`) VALUES
(1, 1, 2, 'hello', '2025-04-21 14:13:02', 1),
(3, 1, 3, 'hello\r\n', '2025-04-21 14:54:04', 1),
(4, 4, 3, 'hello\r\n', '2025-04-21 15:02:44', 1),
(5, 1, 2, 'qweqwe\r\n', '2025-04-21 16:11:22', 1),
(6, 1, 2, 'qweqwe\r\n', '2025-04-21 20:05:58', 1),
(8, 2, 4, 'heello\r\n', '2025-05-04 15:53:43', 1),
(11, 1, 2, 'geia back\r\n', '2025-05-04 15:58:29', 1),
(13, 1, 2, 'hello\r\n', '2025-05-04 16:17:34', 1),
(15, 1, 10, 'dont txt me i got a gf\r\n', '2025-05-10 17:26:24', 1),
(16, 1, 2, 'hello\r\n', '2025-05-10 19:35:13', 1),
(18, 1, 2, 'this is to test \r\n', '2025-05-10 19:40:51', 1),
(19, 2, 1, 'haha nice\r\n', '2025-05-10 19:41:49', 1),
(20, 1, 2, 'yeah indeed\r\n', '2025-05-10 19:45:15', 1),
(21, 2, 1, 'haha yes\r\n', '2025-05-10 19:45:32', 1),
(22, 2, 1, 'ooooooooooooooooh', '2025-05-10 19:45:35', 1),
(23, 1, 2, 'true \r\n', '2025-05-10 19:46:49', 1),
(24, 1, 2, 'isnt it', '2025-05-10 19:46:53', 1),
(25, 4, 2, 'hioiiiiiiiiiiiiiiiiii', '2025-05-10 19:48:07', 1),
(26, 2, 1, 'haha\r\n', '2025-05-10 19:51:20', 1),
(27, 1, 2, 'testttttttttttt', '2025-05-10 19:54:07', 1),
(28, 1, 2, '1', '2025-05-10 19:54:34', 1),
(29, 1, 2, '2', '2025-05-10 19:54:36', 1),
(30, 4, 2, '3', '2025-05-10 19:54:52', 1),
(31, 4, 2, '4\r\n', '2025-05-10 19:54:54', 1),
(32, 4, 2, '1\r\n', '2025-05-10 19:56:23', 1),
(33, 1, 2, '2', '2025-05-10 19:56:34', 1),
(34, 4, 2, '1', '2025-05-10 19:58:19', 1),
(35, 1, 2, '2', '2025-05-10 19:58:33', 1),
(36, 1, 2, '1', '2025-05-10 20:01:44', 1),
(37, 1, 2, '2', '2025-05-10 20:01:45', 1),
(38, 4, 2, '3', '2025-05-10 20:01:59', 1),
(39, 4, 2, '4', '2025-05-10 20:02:00', 1),
(40, 2, 1, 'hhh', '2025-05-10 20:02:25', 1),
(41, 2, 1, 'gaga', '2025-05-10 20:05:19', 1),
(42, 2, 1, 'eee', '2025-05-10 20:05:22', 1),
(43, 1, 2, 'hello\r\n', '2025-05-17 14:05:23', 1),
(44, 1, 2, 'qweqwe', '2025-05-17 16:58:46', 1),
(45, 2, 1, 'trial\r\n', '2025-05-18 18:09:28', 1),
(46, 1, 2, 'hello aleko', '2025-05-18 18:12:14', 1),
(47, 2, 1, 'hello dule', '2025-05-18 18:12:27', 1),
(48, 2, 1, 'qweqwe', '2025-05-18 18:22:02', 1),
(49, 1, 2, 'TwPWwui8Qvm9BP0oDTmP4UoXRIVrpiV313sPfIVo0iprMA==', '2025-05-18 19:32:10', 1),
(50, 2, 1, 'jzZ+osXq9EHuMtxfI6Kh8G2HQhp6g3iHv9ztUjOvgIx9xS1sEKo=', '2025-05-18 19:33:06', 1),
(51, 1, 2, 'A7DazrhyvVNgua1QbUC8JblZS734JqMVinWGEOpHXgyFDdRxHUc=', '2025-05-18 19:37:47', 1),
(52, 2, 1, 'VY9ct89ZP/lpGFdvyaJAK5MUkG7ZQ2yY8JjUUnhDOAk=', '2025-05-18 20:01:22', 1);

-- --------------------------------------------------------

--
-- Table structure for table `doctor_profiles`
--

CREATE TABLE `doctor_profiles` (
  `doctor_id` int(11) NOT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `availability` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_profiles`
--

INSERT INTO `doctor_profiles` (`doctor_id`, `specialty`, `license_number`, `availability`) VALUES
(1, 'cardiologist', '1234', 'free'),
(4, 'Otorinolaryngologist', '6969420', 'Available'),
(1000000, 'qq', 'qq', 'qq');

-- --------------------------------------------------------

--
-- Table structure for table `emr`
--

CREATE TABLE `emr` (
  `emr_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `notes` text NOT NULL,
  `diagnosis` text DEFAULT NULL,
  `prescribed_medications` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emr`
--

INSERT INTO `emr` (`emr_id`, `patient_id`, `doctor_id`, `notes`, `diagnosis`, `prescribed_medications`, `created_at`) VALUES
(2, 2, 4, 'WR6WFVucHlwSQOUyaXMY76S13cpXPJxkbx5im30oAqtdc/wHCu603TM=', 'Xay9CrAfi0hgAfl6MZZ6LE7aIifWOCm0KzFRm1IPULNs33H5XcIMI58+qaiK', '2mymkHmUp3tPgpvhYZYjbZBvrvQBSs+AxX9ZJ8ULdSA7LmVdgg==', '2025-04-19 10:49:37'),
(3, 5, 1, 'qwee2', 'qwe', 'qwe', '2025-04-21 20:06:07'),
(6, 10, 1, 'ZU0xkrB1P6Pj61u94WkR6CI95tfrdlPUITfxoILAagZEc0gCCnM=', '8DLD9lHiHuzwLfaV1loHFH9KghNBenHlbOIgePulq9f2TKmD/hN0MClu', 'oziHRDpoaUGCZc8U91qGMule6AWKZm/YBaFDfWLs1JcPW65U7ocIxE+0fw==', '2025-05-22 13:22:03');

-- --------------------------------------------------------

--
-- Table structure for table `patient_profiles`
--

CREATE TABLE `patient_profiles` (
  `patient_id` int(11) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `current_medications` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_profiles`
--

INSERT INTO `patient_profiles` (`patient_id`, `date_of_birth`, `gender`, `contact_number`, `address`, `medical_history`, `allergies`, `current_medications`) VALUES
(2, '2003-10-01', 'other', '3333333331', 'athens1231', NULL, NULL, NULL),
(3, '2025-04-02', 'Female', '21112121', 'qwe', NULL, NULL, NULL),
(5, '1998-10-24', 'Male', '6999999999', 'thermopilon', NULL, NULL, NULL),
(9, '2025-05-03', 'other', '6969696969', 'gott', 'kinda healthy', 'a few', 'asthma'),
(10, '2025-05-01', 'female', '699999999', 'eeeeeeeeeeee', 'kinda healthy', 'a few', 'asthma pump');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','doctor','patient') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'dule rodha', 'dule@gmail.com', '$2y$10$0cJ8JlKcC7g1lmVGOf/h2eQ6IGkFZ26hcKM0DH9ckBMrwL7zNSWBG', 'doctor', '2025-04-19 10:35:56'),
(2, 'alekos12', 'aggelosg21@hotmail.com', '$2y$10$yTqWpL3TVqLvtGjUrOkNTOKfYS.7DyFYacnaXIcTYZRWM9i797WSm', 'patient', '2025-04-19 10:36:45'),
(3, 'hanka', 'hanka@gmail.com', '$2y$10$h273xxpbmEnFz71mvx5SXOo9u.lbedtZ5pYTIMjgS/di9SMUNNHke', 'patient', '2025-04-19 10:38:17'),
(4, 'HankaRodha', 'hankaro@gmail.com', '$2y$10$i5dJCjuygpLth02Da/dVMezxxGVsV3pIT0GF8Iitqcb9bpTt0gy0W', 'doctor', '2025-04-19 10:47:51'),
(5, 'dhmhtrhs', 'peterland28@hotmail.com', '$2y$10$2BHMHnT9b/TLZJ42pQq6kOYRxnlojkDWWeaXJixAoX0IxncQISX7u', 'patient', '2025-04-21 18:29:24'),
(6, 'dule2', 'dule2@gmail.com', '$2y$10$8E2sHAnQ9K9ZwLmRKg1Zfu7zDr4UEHlYCnJjJZc8Zis4IayRa77d.', 'doctor', '2025-05-10 15:54:33'),
(7, 'dule3', 'dule3@gmail.com', '$2y$10$i6UvH7QIKIGjCgq8y7EfvO9Yrvpodl2cqvq2sdpL/1iL9vxIoG7h2', 'patient', '2025-05-10 15:58:18'),
(8, 'dule5', 'dule4@gmail.com', '$2y$10$4Kg48lgV4JYZxLVzUo8gzu6v20UaU9I.I.fYGkwkLOAP1nzPKcO/O', 'doctor', '2025-05-10 16:37:52'),
(9, 'hanka taro', 'hankataro@gmail.com', '$2y$10$lTudLYeOnd8wBZK2SjQSO.7UnjeM4mfyB8Vwrn0.WgFx6REW6yh4q', 'patient', '2025-05-10 17:09:49'),
(10, 'hanka ta', 'hankatahirovic269@gmail.com', '$2y$10$PGqN8Enq82jm.G./vvKmn.LNW3xWw3q0hz8Hwp5yekShJWuI5EvXO', 'patient', '2025-05-10 17:13:39'),
(26, 'dule rodha3333', 'dule3333@gmail.com', '$2y$10$8J3cmIlvBayZ0THZneaRNeit96a/1U0o9LVpxm2C0rQYGF3yX0i3m', 'patient', '2025-05-17 14:46:39'),
(27, 'qweqwe', 'dududu@gmail.com', '$2y$10$sBOOXu1CA/.OxsBlq.991OvKMnlO.zBjWpQbqQoZ0BR/qblIkRJkW', 'patient', '2025-05-17 14:50:39'),
(37, 'dule222', 'dulee@gmail.com', '$2y$10$yr255ztojABh0TfjCiys/OC/.J876Y8kK.5P0rinMDPvhyxakKHqS', 'patient', '2025-05-18 18:18:32'),
(38, 'dudud', 'dudure@gmail.com', '$2y$10$COC.vIrviOoKbqIUKGYzNO1WtO0X5.q6IqDShmvfZiMkPT7Yxm8I6', 'doctor', '2025-05-18 18:24:52'),
(999999, 'Test Patient', '', '', 'patient', '2025-05-18 20:00:10'),
(1000000, 'dudududududu', 'dudu2222@gmail.com', '$2y$10$EJBs1bHERTb/iddyomBBt.KCzzPz33L8CqPYm1OV1zs/hskOx20Ui', 'doctor', '2025-05-29 13:39:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `doctor_profiles`
--
ALTER TABLE `doctor_profiles`
  ADD PRIMARY KEY (`doctor_id`);

--
-- Indexes for table `emr`
--
ALTER TABLE `emr`
  ADD PRIMARY KEY (`emr_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `patient_profiles`
--
ALTER TABLE `patient_profiles`
  ADD PRIMARY KEY (`patient_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `emr`
--
ALTER TABLE `emr`
  MODIFY `emr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000001;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `doctor_profiles`
--
ALTER TABLE `doctor_profiles`
  ADD CONSTRAINT `doctor_profiles_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `emr`
--
ALTER TABLE `emr`
  ADD CONSTRAINT `emr_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `emr_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `patient_profiles`
--
ALTER TABLE `patient_profiles`
  ADD CONSTRAINT `patient_profiles_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
