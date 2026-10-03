-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 03, 2026 at 10:49 AM
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
-- Database: `subnet_game`
--

-- --------------------------------------------------------

--
-- Table structure for table `badges`
--

CREATE TABLE `badges` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `icon` varchar(10) NOT NULL,
  `criteria_type` varchar(30) NOT NULL,
  `criteria_value` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `badges`
--

INSERT INTO `badges` (`id`, `code`, `name`, `description`, `icon`, `criteria_type`, `criteria_value`) VALUES
(1, 'first_quiz', 'First Steps', 'Complete your first quiz or game session.', '🎯', 'quizzes_completed', 1),
(2, 'ten_quizzes', 'Getting Warmed Up', 'Complete 10 quiz or game sessions.', '🔥', 'quizzes_completed', 10),
(3, 'fifty_quizzes', 'Subnet Sensei', 'Complete 50 quiz or game sessions.', '🥋', 'quizzes_completed', 50),
(4, 'level_5', 'Rising Star', 'Reach Level 5.', '⭐', 'level', 5),
(5, 'level_10', 'Network Novice', 'Reach Level 10.', '🌟', 'level', 10),
(6, 'level_20', 'Subnet Master', 'Reach Level 20.', '👑', 'level', 20),
(7, 'streak_3', 'Streak Starter', 'Hit a 3-day streak.', '🔥', 'current_streak', 3),
(8, 'streak_7', 'On Fire', 'Hit a 7-day streak.', '🚀', 'current_streak', 7),
(9, 'xp_5000', 'XP Grinder', 'Earn 5,000 career XP.', '💎', 'career_xp', 5000),
(10, 'xp_20000', 'XP Legend', 'Earn 20,000 career XP.', '🏆', 'career_xp', 20000);

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `id` int(11) NOT NULL,
  `room_code` varchar(10) NOT NULL,
  `room_name` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `game_mode` varchar(20) NOT NULL DEFAULT 'subnetting',
  `visibility` varchar(20) NOT NULL DEFAULT 'public',
  `max_players` int(11) NOT NULL DEFAULT 2,
  `host_user_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'waiting',
  `current_question_index` int(11) NOT NULL DEFAULT 0,
  `current_question_started_at` datetime DEFAULT NULL,
  `total_questions` int(11) NOT NULL DEFAULT 10,
  `winner_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `match_players`
--

CREATE TABLE `match_players` (
  `id` int(11) NOT NULL,
  `match_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `score` int(11) NOT NULL DEFAULT 0,
  `correct_count` int(11) NOT NULL DEFAULT 0,
  `wrong_count` int(11) NOT NULL DEFAULT 0,
  `current_answer_index` int(11) DEFAULT NULL,
  `answered_at` datetime DEFAULT NULL,
  `joined_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `match_questions`
--

CREATE TABLE `match_questions` (
  `id` int(11) NOT NULL,
  `match_id` int(11) NOT NULL,
  `question_index` int(11) NOT NULL,
  `prompt` text NOT NULL,
  `options_json` text NOT NULL,
  `correct_index` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `prompt` text NOT NULL,
  `correct_answer` varchar(100) NOT NULL,
  `difficulty` varchar(20) DEFAULT 'medium'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `remember_tokens`
--

INSERT INTO `remember_tokens` (`id`, `user_id`, `token`, `expires_at`) VALUES
(1, 4, '93d83fdce85cede763ef0784b4a73ad833f0f4f7df385aa0b1209e3fe1a87c9e', '2026-10-28 08:18:34');

-- --------------------------------------------------------

--
-- Table structure for table `scores`
--

CREATE TABLE `scores` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `match_id` int(11) DEFAULT NULL,
  `game_type` varchar(50) NOT NULL DEFAULT 'general',
  `points` int(11) NOT NULL DEFAULT 0,
  `played_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scores`
--

INSERT INTO `scores` (`id`, `user_id`, `match_id`, `game_type`, `points`, `played_at`) VALUES
(1, 2, NULL, 'showdown_practice', 152, '2026-09-26 22:44:08'),
(2, 2, NULL, 'binary_practice', 180, '2026-10-01 14:44:28');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(30) NOT NULL DEFAULT 'fox',
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `avatar`, `is_admin`, `created_at`) VALUES
(1, 'Admin1', 'admin1@gg.com', '$2y$10$ar5q20r5lI8eswq5BRKZEeHHTOjWl.BcdF1.TmZ5Clu31y6jpTIQG', 'fox', 1, '2026-09-26 19:03:39'),
(2, 'Carlo', 'cma@gm.com', '$2y$10$m2GgOmwTRKb0fEWwC4mwSOKCzglQMmbOB7s/h0bAWoJ5B7SRX5tR.', 'fox', 0, '2026-09-26 19:08:38'),
(3, 'test', 'test@m.com', '$2y$10$WTCSTDqewk6Tma71Ed0N8OLTpe.c38rLsEOquwrxh34AQ7NtzhDmW', 'fox', 0, '2026-09-26 19:10:12'),
(4, 'aki00', 'aki@gmail.com', '$2y$10$Pmliz1t9MmktkI8Zc0V6iOLx1Xwc3RhLvsaKOWa6yO/ioUJweVOyu', 'fox', 0, '2026-09-28 14:18:19');

-- --------------------------------------------------------

--
-- Table structure for table `user_badges`
--

CREATE TABLE `user_badges` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `earned_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_badges`
--

INSERT INTO `user_badges` (`id`, `user_id`, `badge_id`, `earned_at`) VALUES
(1, 2, 1, '2026-09-26 22:44:08');

-- --------------------------------------------------------

--
-- Table structure for table `user_progress`
--

CREATE TABLE `user_progress` (
  `user_id` int(11) NOT NULL,
  `level` int(11) NOT NULL DEFAULT 1,
  `career_xp` int(11) NOT NULL DEFAULT 0,
  `total_xp` int(11) NOT NULL DEFAULT 0,
  `xp_to_next_level` int(11) NOT NULL DEFAULT 500,
  `lessons_completed` int(11) NOT NULL DEFAULT 0,
  `quizzes_completed` int(11) NOT NULL DEFAULT 0,
  `total_correct` int(11) NOT NULL DEFAULT 0,
  `total_wrong` int(11) NOT NULL DEFAULT 0,
  `current_streak` int(11) NOT NULL DEFAULT 0,
  `last_activity_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_progress`
--

INSERT INTO `user_progress` (`user_id`, `level`, `career_xp`, `total_xp`, `xp_to_next_level`, `lessons_completed`, `quizzes_completed`, `total_correct`, `total_wrong`, `current_streak`, `last_activity_date`) VALUES
(1, 1, 0, 0, 400, 0, 0, 0, 0, 0, NULL),
(2, 1, 332, 332, 400, 0, 2, 6, 4, 0, NULL),
(3, 1, 0, 0, 400, 0, 0, 0, 0, 0, NULL),
(4, 1, 0, 0, 400, 0, 0, 0, 0, 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `badges`
--
ALTER TABLE `badges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_code` (`room_code`),
  ADD KEY `host_user_id` (`host_user_id`),
  ADD KEY `winner_id` (`winner_id`);

--
-- Indexes for table `match_players`
--
ALTER TABLE `match_players`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `match_user` (`match_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `match_questions`
--
ALTER TABLE `match_questions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `match_qindex` (`match_id`,`question_index`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `scores`
--
ALTER TABLE `scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `match_id` (`match_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_badge` (`user_id`,`badge_id`),
  ADD KEY `badge_id` (`badge_id`);

--
-- Indexes for table `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `badges`
--
ALTER TABLE `badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `match_players`
--
ALTER TABLE `match_players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `match_questions`
--
ALTER TABLE `match_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `scores`
--
ALTER TABLE `scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_badges`
--
ALTER TABLE `user_badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`host_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`winner_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `match_players`
--
ALTER TABLE `match_players`
  ADD CONSTRAINT `match_players_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`),
  ADD CONSTRAINT `match_players_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `match_questions`
--
ALTER TABLE `match_questions`
  ADD CONSTRAINT `match_questions_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`);

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `scores`
--
ALTER TABLE `scores`
  ADD CONSTRAINT `scores_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `scores_ibfk_2` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`);

--
-- Constraints for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD CONSTRAINT `user_badges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_badges_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`);

--
-- Constraints for table `user_progress`
--
ALTER TABLE `user_progress`
  ADD CONSTRAINT `user_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
