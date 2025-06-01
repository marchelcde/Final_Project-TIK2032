-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 26, 2025 at 04:49 PM
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
-- Database: `e-lapor`
--

-- --------------------------------------------------------

--
-- Table structure for table `pengguna_admin`
--

CREATE TABLE `pengguna_admin` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengguna_admin`
--

INSERT INTO `pengguna_admin` (`id_admin`, `username`, `password`, `email`, `full_name`, `created_at`) VALUES
(1, 'admin', 'admin', NULL, 'Marchel Manullang', '2025-05-26 22:00:47'),
(2, 'admink', 'kipapaw', NULL, 'Keefa Lasut', '2025-05-26 22:45:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'Keefa Lasut', 'Keefa', 'kenola700@gmail.com', '$2y$10$saNRvP4xRfBHvHaXJpV9muSkWnsnc9E5Ek5dDyjyj6SMgtuinjzDu', '2025-05-24 19:17:29'),
(2, 'Marchel', 'Lerch', 'mrchl@gmail.com', '$2y$10$Ut2NdgLFXs9xv2rmBcUQ6uDdzhoXE6oKYtHM7wJFdlcPo.pWuXEc6', '2025-05-24 19:26:41'),
(6, 'Valen', 'Tino', 'insomniac@gmail.com', '$2y$10$F.UVaHFCGH1ym5iv35cWOORuGfYGrtCtT1b2PuP6LQ2qPK/uTyGpu', '2025-05-24 19:40:38'),
(7, 'Joka', 'Joka', 'joka@gmail.com', '$2y$10$wd18RU.5FrTvn5.RcFNPAOW3zruGHRRlbfTPZnQWDgWiWAKZcYJuS', '2025-05-24 20:08:52'),
(8, 'Claisty', 'Cazzy', 'cazzy@gmail.com', '$2y$10$4d.jYyOrl0lsqrOof/wKnOirQWN8Te8Q8bjx7LxckHJVS4vtXZYwe', '2025-05-26 01:25:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pengguna_admin`
--
ALTER TABLE `pengguna_admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pengguna_admin`
--
ALTER TABLE `pengguna_admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
