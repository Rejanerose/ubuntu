-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql307.infinityfree.com
-- Generation Time: Oct 28, 2026 at 08:52 AM
-- Server version: 11.4.13-MariaDB
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
-- Database: `if0_42809412_login_west`
--

-- --------------------------------------------------------

--
-- Table structure for table `member`
--

CREATE TABLE `member` (
  `id` int(11) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `lname` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `phone` int(11) NOT NULL,
  `dob` date NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(100) NOT NULL,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `member`
--

INSERT INTO `member` (`id`, `fname`, `lname`, `username`, `phone`, `dob`, `email`, `password`, `role`, `is_blocked`) VALUES
(1, 'High', 'Elder', 'admin', 912271659, '2026-08-02', 'westeria@gmail.com', '$2y$12$rTTnMIcHQNGEz8DhyjvXiuRe.SaRbvxRTT9ikTFmtloBv4sV3ADWq', 'elder', 0),
(2, 'sample1', 'sample1', 'sample1', 2147483647, '2026-08-12', 'sample1@gmail.com', '$2y$10$Ii9jX5pfRA96zuXqoalwY.dCtdgjQjtG84Ksv..Zvs6iw2VFD75A.', 'member', 0),
(3, 'Rayven', 'Crow', 'RC', 2147483647, '2026-08-12', 'rc@gmail.com', 'qwertyuiop', 'member', 1),
(4, 'staff', 'staff', 'staff', 912271659, '2026-08-02', 'staff@gmail.com', '$2y$12$hf/CrFF4avm6jJ5svExxgO3vMkU5oMaf/KHUFQnalmdgaucmiYrFa', 'staff', 0),
(5, 'blabla', 'bla', 'blablabla', 2147483647, '2003-03-03', 'blablabla@gmail.com', 'zxcvbnm,./', 'staff', 0),
(6, 'JOHN', '02', 'asdfghjkl', 2147483647, '2026-10-30', 'baylenjohnvincent127@gmail.com', '$2y$10$QJHtRnSnNLsxR2h.PovFdeBr4dYCQLmN.P1EW7x2lwZNCr491AXI2', 'member', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `member`
--
ALTER TABLE `member`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `member`
--
ALTER TABLE `member`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
