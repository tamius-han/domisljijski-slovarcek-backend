-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 30, 2022 at 09:45 PM
-- Server version: 10.3.34-MariaDB-log-cll-lve
-- PHP Version: 7.4.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tamius18_domisljijski_slovarcek_2`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `parentId` int(11) DEFAULT NULL,
  `nameEn` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nameSl` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `communitySuggestion` tinyint(1) NOT NULL DEFAULT 1,
  `deleted` tinyint(1) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parentId`, `nameEn`, `nameSl`, `communitySuggestion`, `deleted`, `created`, `updated`) VALUES
(1, NULL, 'Creatures', 'Bitja', 0, NULL, '2022-05-28 13:03:27', '2022-05-28 13:03:27'),
(2, 1, 'Humanoids (races)', 'Človečnjaki (rase)', 0, NULL, '2022-05-28 13:03:27', '2022-05-28 13:05:38'),
(3, NULL, 'Magic', 'Čarovnija', 0, NULL, '2022-05-28 13:03:27', '2022-05-28 13:03:27'),
(4, NULL, 'Game terminology', 'Izrazi iger', 0, NULL, '2022-05-28 13:03:27', '2022-05-28 13:05:38'),
(5, 4, 'D&D terms', 'D&D izrazi', 0, NULL, '2022-05-28 13:03:27', '2022-05-28 13:03:27'),
(6, 5, 'D&D classess', 'D&D razredi', 0, NULL, '2022-05-28 13:03:27', '2022-05-28 13:03:27'),
(7, 5, 'D&D skills', 'D&D veščine', 0, NULL, '2022-05-28 13:03:27', '2022-05-28 13:03:27'),
(8, NULL, 'Weapons', 'Orožja', 0, NULL, '2022-05-28 13:03:27', '2022-05-28 13:03:27'),
(9, 1, 'Animals & Monsters', 'Živali & Pošasti', 0, NULL, '2022-05-30 19:33:59', '2022-05-30 19:33:59');

-- --------------------------------------------------------

--
-- Table structure for table `meanings`
--

CREATE TABLE `meanings` (
  `id` int(11) NOT NULL,
  `meaning` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` int(11) NOT NULL COMMENT 'Word type (noun, adjective, verb, ...)',
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `communitySuggestion` tinyint(1) NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `meanings`
--

INSERT INTO `meanings` (`id`, `meaning`, `type`, `notes`, `communitySuggestion`, `created`, `updated`, `deleted`) VALUES
(1, 'large winged, flying lizard, typically capable of breathing fire', 0, NULL, 0, '2022-05-28 16:30:59', '2022-05-28 16:30:59', NULL),
(2, 'veliki krilati, leteči kuščar, ki lahko ponavadi bruha ogenj', 0, NULL, 0, '2022-05-28 16:30:59', '2022-05-28 16:30:59', NULL),
(3, 'a race of tall, approximately human-size and human-shaped bipedal lizards with draconic appearance.', 0, NULL, 0, '2022-05-28 16:30:59', '2022-05-28 16:30:59', NULL),
(4, 'na dveh nogah hodeči kuščarji, ki so približno človeške velikosti in oblike ter z zmajevsko podobo', 0, NULL, 0, '2022-05-28 16:30:59', '2022-05-28 16:30:59', NULL),
(5, 'Usually large, strong humanoids, who usually like to fight and destroy', 0, NULL, 0, '2022-05-28 17:45:13', '2022-05-28 17:45:13', NULL),
(6, 'Navadno veliki, močni človečnjaki, ki se navadno radi borijo in uničujejo', 0, NULL, 0, '2022-05-28 17:45:13', '2022-05-28 17:45:13', NULL),
(7, 'belonging to, made by, or an attribute of orcs', 0, NULL, 0, '2022-05-28 17:45:13', '2022-05-28 17:45:13', NULL),
(8, 'nekaj, kar so naredili, je lastnost, ali pa pripada orkom.', 0, NULL, 0, '2022-05-28 17:45:13', '2022-05-28 17:45:13', NULL),
(9, 'large breed of wolves', 0, NULL, 0, '2022-05-28 17:45:13', '2022-05-28 17:45:13', NULL),
(10, 'pasma/rod velikih volkov', 0, NULL, 0, '2022-05-28 17:45:13', '2022-05-28 17:45:13', NULL),
(11, 'small, typically ugly humanoid, who are largely hostile to other races', 0, NULL, 0, '2022-05-28 17:45:13', '2022-05-28 17:45:13', NULL),
(12, 'majhni, navadno grdi človečnjaki, ki so večinoma sovražni do drugih ras', 0, NULL, 0, '2022-05-28 17:45:13', '2022-05-28 17:45:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `meanings2categories`
--

CREATE TABLE `meanings2categories` (
  `meaning_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `meanings2categories`
--

INSERT INTO `meanings2categories` (`meaning_id`, `category_id`, `created`, `updated`) VALUES
(1, 1, '2022-05-28 16:44:29', '2022-05-28 16:44:29'),
(1, 9, '2022-05-30 19:38:35', '2022-05-30 19:38:35'),
(2, 1, '2022-05-28 16:44:29', '2022-05-28 16:44:29'),
(2, 9, '2022-05-30 19:38:35', '2022-05-30 19:38:35'),
(3, 1, '2022-05-28 16:44:29', '2022-05-28 16:44:29'),
(3, 2, '2022-05-28 16:44:29', '2022-05-28 16:44:29'),
(4, 1, '2022-05-28 16:44:29', '2022-05-28 16:44:29'),
(4, 2, '2022-05-28 16:44:29', '2022-05-28 16:44:29'),
(5, 1, '2022-05-30 19:38:35', '2022-05-30 19:38:35'),
(5, 2, '2022-05-30 19:38:35', '2022-05-30 19:38:35'),
(6, 1, '2022-05-30 19:38:35', '2022-05-30 19:38:35'),
(6, 2, '2022-05-30 19:38:35', '2022-05-30 19:38:35'),
(7, 1, '2022-05-30 19:38:35', '2022-05-30 19:38:35'),
(7, 2, '2022-05-30 19:38:35', '2022-05-30 19:38:35'),
(8, 1, '2022-05-30 19:38:35', '2022-05-30 19:38:35'),
(8, 2, '2022-05-30 19:38:35', '2022-05-30 19:38:35'),
(9, 1, '2022-05-30 19:38:35', '2022-05-30 19:38:35'),
(9, 9, '2022-05-30 19:38:35', '2022-05-30 19:38:35'),
(10, 1, '2022-05-30 19:38:35', '2022-05-30 19:38:35'),
(10, 9, '2022-05-30 19:38:35', '2022-05-30 19:38:35'),
(11, 1, '2022-05-30 19:38:35', '2022-05-30 19:38:35'),
(11, 2, '2022-05-30 19:38:35', '2022-05-30 19:38:35'),
(12, 1, '2022-05-30 19:38:35', '2022-05-30 19:38:35'),
(12, 2, '2022-05-30 19:38:35', '2022-05-30 19:38:35');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`) VALUES
(1, 'admin'),
(2, 'addUsers'),
(3, 'addTranslations');

-- --------------------------------------------------------

--
-- Table structure for table `translations`
--

CREATE TABLE `translations` (
  `meaning_en` int(11) NOT NULL,
  `meaning_sl` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `translations`
--

INSERT INTO `translations` (`meaning_en`, `meaning_sl`, `created`, `updated`) VALUES
(1, 2, '2022-05-28 20:17:33', '0000-00-00 00:00:00'),
(3, 4, '2022-05-28 20:17:33', '0000-00-00 00:00:00'),
(5, 6, '2022-05-28 20:17:33', '0000-00-00 00:00:00'),
(7, 8, '2022-05-28 20:17:33', '0000-00-00 00:00:00'),
(9, 10, '2022-05-28 20:17:33', '0000-00-00 00:00:00'),
(11, 12, '2022-05-28 20:17:33', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nickname` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `canManageTranslations` tinyint(1) NOT NULL,
  `canManageUsers` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `words`
--

CREATE TABLE `words` (
  `id` int(11) NOT NULL,
  `language` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `word` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `altSpellings` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Alternative spellings of the word, will be displayed in the GUI',
  `altSpellingsHidden` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Alternative spellings of the word, should be hidden in GUI by default',
  `genderExtras` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `etymology` text DEFAULT NULL COMMENT 'Etymological explanation',
  `credit` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credit_userId` int(11) DEFAULT NULL,
  `communitySuggestion` tinyint(1) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `words`
--

INSERT INTO `words` (`id`, `language`, `word`, `altSpellings`, `altSpellingsHidden`, `genderExtras`, `notes`, `etymology`, `credit`, `credit_userId`, `communitySuggestion`, `created`, `updated`, `deleted`) VALUES
(1, 'en', 'dragon', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0),
(2, 'sl', 'zmaj', NULL, NULL, '{\r\n  \"f\": \"zmajevka\"\r\n}', NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0),
(3, 'en', 'dragonborn', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0),
(4, 'sl', 'zmajerodni', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0),
(5, 'en', 'orc', 'ork', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2022-05-28 17:14:28', '2022-05-28 17:14:28', 0),
(6, 'sl', 'ork', NULL, NULL, '{\r\n  \"f\": \"orkinja\",\r\n  \"plural\": { \"m2\": \"orka\", \"m3\": \"orki\", \"f2\": \"orkinji\", \"f3\": \"orkinje\"},\r\n  \"common\": \"m\"\r\n}', NULL, NULL, NULL, NULL, NULL, '2022-05-28 17:14:28', '2022-05-28 17:14:28', 0),
(7, 'en', 'orkish', 'orcish', NULL, '', NULL, NULL, NULL, NULL, NULL, '2022-05-28 17:14:28', '2022-05-28 17:14:28', 0),
(8, 'sl', 'orkovski', '', NULL, '{\r\n  f: \"orkinjski\"\r\n}', NULL, NULL, NULL, NULL, NULL, '2022-05-28 17:14:28', '2022-05-28 17:14:28', 0),
(9, 'en', 'direwolf', 'dire wolf', NULL, '', NULL, NULL, NULL, NULL, NULL, '2022-05-28 17:14:28', '2022-05-28 17:14:28', 0),
(10, 'sl', 'pravolk', '', NULL, '{\r\n  \"f\": \"pravolkulja\",\r\n  \"plural\": { \"m2\": \"pravolka\", \"m3\": \"pravolki\", \"f2\": \"pravolkulji\", \"f3\": \"pravolkulje\"}\r\n  \"common\": \"m\"\r\n}', NULL, NULL, NULL, NULL, NULL, '2022-05-28 17:14:28', '2022-05-28 17:14:28', 0),
(11, 'sl', 'krvovolk', '', NULL, '', 'Ta slovenski izraz je baje zrasel na zelniku igre prestolov.', NULL, 'Boštjan Gorenc [Mladinska Knjiga]', NULL, NULL, '2022-05-28 17:14:28', '2022-05-28 17:24:33', 0),
(12, 'en', 'goblin', '', NULL, '', NULL, NULL, NULL, NULL, NULL, '2022-05-28 17:14:28', '2022-05-28 17:14:28', 0),
(13, 'sl', 'gôblin', '', NULL, '{\r\n  \"f\": \"gôblinka\",\r\n  \"plural\": {\"m2\": \"gôblina\", \"m3\": \"gôblini\", \"f2\": \"gôblinki\", \"f3\": \"gôblinke\"}\r\n}', NULL, NULL, NULL, NULL, NULL, '2022-05-28 17:14:28', '2022-05-28 17:14:28', 0),
(14, 'sl', 'grdin', '', NULL, '{\r\n  \"f\": \"grdinka\",\r\n  \"plural\": { \"m2\": \"grdina\", \"m3\": \"grdini\", \"f2\": \"grdinki\", \"f3\": \"grdinke\"}\r\n}', 'Prevod opažen v slovenskem prevodu Hobita.', NULL, 'Dušan Ogrizek [Mladinska knjiga]', NULL, NULL, '2022-05-28 17:14:28', '2022-05-30 19:41:20', 0);

-- --------------------------------------------------------

--
-- Table structure for table `words2meanings`
--

CREATE TABLE `words2meanings` (
  `word_id` int(11) NOT NULL,
  `meaning_id` int(11) NOT NULL,
  `word_priority` int(11) NOT NULL DEFAULT 0,
  `meaning_priority` int(11) NOT NULL DEFAULT 0,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `words2meanings`
--

INSERT INTO `words2meanings` (`word_id`, `meaning_id`, `word_priority`, `meaning_priority`, `created`, `updated`) VALUES
(1, 1, 0, 0, '2022-05-28 16:31:53', '2022-05-28 16:31:53'),
(2, 2, 0, 0, '2022-05-28 16:31:53', '2022-05-28 16:31:53'),
(3, 3, 0, 0, '2022-05-28 16:31:53', '2022-05-28 16:31:53'),
(4, 4, 0, 0, '2022-05-28 16:31:53', '2022-05-28 16:31:53'),
(5, 5, 0, 0, '2022-05-28 17:48:22', '2022-05-28 17:48:22'),
(5, 7, 0, 0, '2022-05-28 17:48:22', '2022-05-28 17:48:22'),
(6, 6, 0, 0, '2022-05-28 17:48:22', '2022-05-28 17:48:22'),
(7, 7, 0, 0, '2022-05-28 17:48:22', '2022-05-28 17:48:22'),
(8, 8, 0, 0, '2022-05-28 17:48:22', '2022-05-28 17:48:22'),
(9, 9, 0, 0, '2022-05-28 17:48:22', '2022-05-28 17:48:22'),
(10, 10, 0, 0, '2022-05-28 17:48:22', '2022-05-28 17:48:22'),
(11, 10, -7068, 0, '2022-05-28 17:48:22', '2022-05-30 18:19:50'),
(12, 11, 0, 0, '2022-05-28 17:48:22', '2022-05-28 17:48:22'),
(13, 12, 0, 0, '2022-05-28 17:48:22', '2022-05-28 17:48:22'),
(14, 12, -1, 0, '2022-05-28 17:48:22', '2022-05-30 19:42:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meanings`
--
ALTER TABLE `meanings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meanings2categories`
--
ALTER TABLE `meanings2categories`
  ADD UNIQUE KEY `meaning_id` (`meaning_id`,`category_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `translations`
--
ALTER TABLE `translations`
  ADD UNIQUE KEY `translation` (`meaning_en`,`meaning_sl`) USING BTREE;

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD UNIQUE KEY `permission_index` (`user_id`,`permission_id`);

--
-- Indexes for table `words`
--
ALTER TABLE `words`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `word-lang` (`language`,`word`) USING BTREE;

--
-- Indexes for table `words2meanings`
--
ALTER TABLE `words2meanings`
  ADD UNIQUE KEY `word_id` (`word_id`,`meaning_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `meanings`
--
ALTER TABLE `meanings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `words`
--
ALTER TABLE `words`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
