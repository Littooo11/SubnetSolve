-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 14, 2026 at 12:47 PM
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
-- Table structure for table `game_questions`
--

CREATE TABLE `game_questions` (
  `id` int(11) NOT NULL,
  `game_type` varchar(50) NOT NULL,
  `question_text` text NOT NULL,
  `option_a` varchar(255) NOT NULL,
  `option_b` varchar(255) NOT NULL,
  `option_c` varchar(255) NOT NULL,
  `option_d` varchar(255) NOT NULL,
  `correct_option` char(1) NOT NULL,
  `explanation` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_questions`
--

INSERT INTO `game_questions` (`id`, `game_type`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`, `explanation`, `created_at`) VALUES
(1, 'subnet_showdown', 'What is the default subnet mask for a /24 network?', '255.255.0.0', '255.255.255.0', '255.0.0.0', '255.255.255.128', 'B', 'A /24 prefix uses 24 network bits, which equals 255.255.255.0.', '2026-10-13 13:06:23'),
(2, 'subnet_showdown', 'What is the wildcard mask for a /24 network?', '0.0.255.255', '0.255.255.255', '0.0.0.255', '0.0.0.127', 'C', 'The wildcard mask is the inverse of 255.255.255.0, which is 0.0.0.255.', '2026-10-13 13:06:23'),
(3, 'subnet_showdown', 'What is the default subnet mask for a /26 network?', '255.255.255.128', '255.255.255.224', '255.255.255.240', '255.255.255.192', 'D', 'A /26 prefix borrows 2 bits in the 4th octet (128 + 64 = 192).', '2026-10-13 13:06:23'),
(4, 'subnet_showdown', 'What is the wildcard mask for a /26 network?', '0.0.0.63', '0.0.0.31', '0.0.0.127', '0.0.0.15', 'A', 'Block size for /26 is 64, making the wildcard mask 0.0.0.63.', '2026-10-13 13:06:23'),
(5, 'subnet_showdown', 'How many usable host addresses are available in a /24 network?', '256', '254', '512', '128', 'B', '2^8 - 2 = 254 usable host addresses.', '2026-10-13 13:06:23'),
(6, 'subnet_showdown', 'How many usable host addresses are available in a /26 network?', '64', '30', '62', '126', 'C', '2^6 - 2 = 62 usable host addresses.', '2026-10-13 13:06:23'),
(7, 'subnet_showdown', 'How many usable host addresses are available in a /28 network?', '16', '30', '62', '14', 'D', '2^4 - 2 = 14 usable host addresses.', '2026-10-13 13:06:23'),
(8, 'subnet_showdown', 'What class does the IP address 10.0.0.1 belong to?', 'Class A', 'Class B', 'Class C', 'Class D', 'A', 'Class A range is 1.0.0.0 to 126.255.255.255.', '2026-10-13 13:06:23'),
(9, 'subnet_showdown', 'What class does the IP address 172.16.0.1 belong to?', 'Class A', 'Class B', 'Class C', 'Class D', 'B', 'Class B range is 128.0.0.0 to 191.255.255.255.', '2026-10-13 13:06:23'),
(10, 'subnet_showdown', 'What class does the IP address 192.168.1.1 belong to?', 'Class A', 'Class B', 'Class C', 'Class D', 'C', 'Class C range is 192.0.0.0 to 223.255.255.255.', '2026-10-13 13:06:23'),
(11, 'subnet_showdown', 'What class does the IP address 224.0.0.1 belong to?', 'Class A', 'Class B', 'Class C', 'Class D', 'D', 'Class D (224.0.0.0 to 239.255.255.255) is reserved for multicast.', '2026-10-13 13:06:23'),
(12, 'scenario_game', 'The IT department needs a subnet that supports at least 50 usable host addresses for their new office. Which subnet mask provides enough addresses with the least waste?', '255.255.255.128', '255.255.255.192', '255.255.255.224', '255.255.255.240', 'B', 'A /26 mask (255.255.255.192) provides 62 usable host IPs, fitting 50 hosts with minimal waste.', '2026-10-13 13:06:23'),
(13, 'scenario_game', 'The Marketing department needs a subnet supporting at least 100 usable host addresses. Which subnet mask provides enough addresses with the least waste?', '255.255.255.192', '255.255.255.0', '255.255.255.128', '255.255.255.224', 'C', 'A /25 mask (255.255.255.128) provides 126 usable host IPs.', '2026-10-13 13:06:23'),
(14, 'scenario_game', 'The Finance department has been assigned the subnet 192.168.10.0/26. A technician needs the broadcast address to configure a network-wide alert. Which address should they use?', '192.168.10.0', '192.168.10.1', '192.168.10.62', '192.168.10.63', 'D', 'Block size is 64 (0 to 63). The broadcast address is 192.168.10.63.', '2026-10-13 13:06:23'),
(15, 'scenario_game', 'A new Sales workstation needs an IP address inside the 192.168.1.0/26 subnet. Which of these addresses can actually be assigned to the workstation?', '192.168.1.25', '192.168.1.0', '192.168.1.63', '192.168.1.64', 'A', 'Usable IPs range from 192.168.1.1 to 192.168.1.62. 192.168.1.25 is valid.', '2026-10-13 13:06:23'),
(16, 'scenario_game', 'An internal HR file server is configured with the IP address 10.1.5.20. Which statement is true about this server?', 'Directly reachable from the internet (public address)', 'Not directly reachable from the internet (private address)', 'Reserved exclusively for multicast traffic', 'It is a loopback address', 'B', '10.0.0.0/8 is a private IP address block (RFC 1918).', '2026-10-13 13:06:23'),
(17, 'scenario_game', 'A Public Web Server is configured with the IP address 203.0.113.5. Which statement is true about this server?', 'Not directly reachable from the internet (private address)', 'Used exclusively for internal local broadcasting', 'Directly reachable from the internet (public address)', 'Assigned automatically via APIPA', 'C', '203.0.113.5 is a public IP address.', '2026-10-13 13:06:23'),
(18, 'scenario_game', 'Scenario: Office Network Expansion\nA company is setting up a new office. Its HR department needs 25 usable IP addresses for computers and printers. The network administrator wants to use the smallest subnet that can support all 25 devices. Which subnet prefix should the administrator choose?', '/28', '/27', '/29', '/26', 'B', 'A /27 subnet provides 30 usable host IP addresses (2^5 - 2), which perfectly accommodates 25 devices with minimal waste.', '2026-10-14 14:52:54'),
(19, 'scenario_game', 'Scenario: Small Business Router\nA small business configures its router with the network 192.168.1.0/24. A technician needs to know how many usable IPv4 host addresses this network normally supports. What is the correct answer?', '24', '256', '254', '128', 'C', 'A /24 network has 8 host bits (2^8 = 256 total addresses). Subtracting network and broadcast addresses leaves 254 usable IPs.', '2026-10-14 14:52:54'),
(20, 'scenario_game', 'Scenario: University Computer Laboratory\nA university has been assigned the network 192.168.10.0/24. The computer laboratory needs 100 usable host addresses. The IT administrator must select a subnet that can accommodate all the computers while minimizing wasted IP addresses. Which subnet prefix is the best choice?', '/26', '/24', '/25', '/27', 'C', 'A /25 prefix provides 126 usable host addresses (2^7 - 2 = 126), which fits 100 hosts with minimal waste.', '2026-10-14 14:52:54'),
(21, 'scenario_game', 'Scenario: Company Department Allocation\nA company receives 192.168.20.0/24 for its internal network. It needs three separate subnets:\n• Sales: 100 usable hosts\n• Finance: 50 usable hosts\n• IT Support: 20 usable hosts\nWhich set of subnet prefixes meets all three requirements while using the smallest suitable subnet for each department?', 'Sales /26, Finance /27, IT /28', 'Sales /25, Finance /26, IT /27', 'Sales /24, Finance /25, IT /26', 'Sales /27, Finance /26, IT /25', 'B', 'Sales needs /25 (126 hosts), Finance needs /26 (62 hosts), and IT Support needs /27 (30 hosts).', '2026-10-14 14:52:54'),
(22, 'scenario_game', 'Scenario: Network Technician Troubleshooting\nA technician sees the IP configuration 192.168.10.25/26 on a workstation. The technician must identify the subnet mask to check whether the workstation is configured correctly. Which subnet mask corresponds to /26?', '255.255.255.0', '255.255.255.192', '255.255.255.224', '255.255.255.128', 'B', 'A /26 prefix converts to binary as 11111111.11111111.11111111.11000000, which equals 255.255.255.192.', '2026-10-14 14:52:54'),
(23, 'scenario_game', 'Scenario: Office Subnet Capacity\nA growing office has been assigned 10.10.10.0/27 for a group of computers. The IT manager plans to connect 28 computers and needs to know whether the subnet has enough usable addresses. Which statement is correct?', 'Yes, because /27 supports 32 usable hosts', 'Yes, because /27 supports 30 usable hosts', 'No, because /27 supports only 30 total addresses', 'No, because /27 supports only 14 usable hosts', 'B', 'A /27 subnet provides 30 usable host IP addresses (2^5 - 2 = 30), which is sufficient for 28 computers.', '2026-10-14 14:52:54'),
(24, 'scenario_game', 'Scenario: New Branch Office\nA business has the address block 10.0.0.0/24. It needs a subnet for 60 employee devices and another subnet for 12 network devices. The administrator assigns the employee subnet first using 10.0.0.0/26. Which network address is the correct next allocation for the 12-device subnet, using the smallest suitable subnet?', '10.0.0.64/28', '10.0.0.32/28', '10.0.0.64/29', '10.0.0.0/28', 'A', '10.0.0.0/26 occupies IPs 10.0.0.0 - 10.0.0.63. The next available address is 10.0.0.64. A /28 prefix fits 14 usable hosts, making 10.0.0.64/28 the smallest suitable subnet.', '2026-10-14 14:52:54'),
(25, 'scenario_game', 'Scenario: Hospital Network Design\nA hospital receives 172.16.5.0/24 for three departments:\n• Administration: 50 hosts\n• Medical staff: 25 hosts\n• Security systems: 10 hosts\nThe network engineer wants to allocate the largest subnet first and ensure that all subnets fit inside the assigned block without overlapping. Which allocation is valid and meets the minimum host requirements?', 'Admin 172.16.5.0/26, Medical 172.16.5.64/27, Security 172.16.5.96/28', 'Admin 172.16.5.0/27, Medical 172.16.5.32/27, Security 172.16.5.64/28', 'Admin 172.16.5.0/26, Medical 172.16.5.32/27, Security 172.16.5.96/28', 'Admin 172.16.5.0/26, Medical 172.16.5.64/28, Security 172.16.5.80/29', 'A', 'Admin gets 172.16.5.0/26 (IPs 0-63). Medical gets 172.16.5.64/27 (IPs 64-95). Security gets 172.16.5.96/28 (IPs 96-111). All subnets fit without overlap.', '2026-10-14 14:52:54'),
(26, 'scenario_game', 'Scenario: ISP Address Allocation\nAn internet service provider assigns a customer the IPv4 block 203.0.113.0/28 for a small test network. The customer wants to know the total number of IP addresses in the assigned block, including reserved network and broadcast addresses. How many total addresses are included?', '8', '14', '16', '32', 'C', 'A /28 subnet has 4 host bits (32 - 28 = 4). Total addresses = 2^4 = 16.', '2026-10-14 14:52:54'),
(27, 'scenario_game', 'Scenario: Multi-Branch Company Routing\nA company has multiple branch networks within 10.20.0.0/24. Its network engineer wants to advertise the entire block as one route rather than advertise four separate contiguous /26 networks. Which CIDR route summarizes the entire address block?', '10.20.0.0/25', '10.20.0.0/26', '10.20.0.0/23', '10.20.0.0/24', 'D', 'Four /26 subnets make up one full /24 network (10.20.0.0/24).', '2026-10-14 14:52:54'),
(28, 'scenario_game', 'Scenario: Office Network Expansion\nA company is setting up a new office. Its HR department needs 25 usable IP addresses for computers and printers. The network administrator wants to use the smallest subnet that can support all 25 devices. Which subnet prefix should the administrator choose?', '/28', '/27', '/29', '/26', 'B', 'A /27 subnet provides 30 usable host IP addresses.', '2026-10-14 15:14:41'),
(29, 'scenario_game', 'Scenario: Small Business Router\nA small business configures its router with the network 192.168.1.0/24. A technician needs to know how many usable IPv4 host addresses this network normally supports. What is the correct answer?', '24', '256', '254', '128', 'C', 'A /24 network has 254 usable host addresses.', '2026-10-14 15:14:41'),
(30, 'scenario_game', 'Scenario: University Computer Laboratory\nA university has been assigned the network 192.168.10.0/24. The computer laboratory needs 100 usable host addresses. The IT administrator must select a subnet that can accommodate all the computers while minimizing wasted IP addresses. Which subnet prefix is the best choice?', '/26', '/24', '/25', '/27', 'C', 'A /25 prefix provides 126 usable host addresses.', '2026-10-14 15:14:41'),
(31, 'scenario_game', 'Scenario: Network Technician Troubleshooting\nA technician sees the IP configuration 192.168.10.25/26 on a workstation. The technician must identify the subnet mask to check whether the workstation is configured correctly. Which subnet mask corresponds to /26?', '255.255.255.0', '255.255.255.192', '255.255.255.224', '255.255.255.128', 'B', 'A /26 prefix corresponds to 255.255.255.192.', '2026-10-14 15:14:41');

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

--
-- Dumping data for table `matches`
--

INSERT INTO `matches` (`id`, `room_code`, `room_name`, `description`, `password_hash`, `game_mode`, `visibility`, `max_players`, `host_user_id`, `status`, `current_question_index`, `current_question_started_at`, `total_questions`, `winner_id`, `created_at`, `started_at`, `ended_at`) VALUES
(3, 'TRMCE6', '', '', NULL, 'binary', 'public', 2, 37, 'finished', 0, '2026-10-12 00:22:57', 10, 37, '2026-10-12 00:22:51', '2026-10-12 00:22:57', '2026-10-12 00:23:09'),
(6, '7LC539', '', '', NULL, 'binary', 'public', 2, 37, 'finished', 9, '2026-10-12 00:26:54', 10, 37, '2026-10-12 00:25:38', '2026-10-12 00:25:45', '2026-10-12 00:26:59'),
(7, 'NNQ7XW', '', '', NULL, 'hex', 'public', 2, 37, 'finished', 0, '2026-10-12 00:27:54', 10, 37, '2026-10-12 00:27:49', '2026-10-12 00:27:54', '2026-10-12 00:27:58'),
(8, '92V3GM', '', '', NULL, 'binary', 'public', 2, 37, 'finished', 1, '2026-10-12 00:37:37', 10, 37, '2026-10-12 00:33:53', '2026-10-12 00:33:57', '2026-10-12 00:37:40'),
(9, 'KTMK57', 'qasd', '', NULL, 'binary', 'public', 2, 39, 'finished', 2, '2026-10-12 16:07:41', 10, 39, '2026-10-12 16:05:50', '2026-10-12 16:06:14', '2026-10-12 16:08:15'),
(10, 'TA4L5R', 'g20', '', '$2y$10$GSLUPN940d82PhiBjFVTLu1ya4tfvf4jvz3nx5FR1IEbXjNBI7ZzK', 'subnetting', 'public', 2, 39, 'finished', 0, '2026-10-12 16:11:00', 10, 39, '2026-10-12 16:10:32', '2026-10-12 16:11:00', '2026-10-12 16:11:19'),
(11, '5DZR72', '', '', NULL, 'subnetting', 'public', 2, 39, 'finished', 0, '2026-10-14 17:16:40', 10, 39, '2026-10-14 17:16:29', '2026-10-14 17:16:40', '2026-10-14 17:16:42');

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

--
-- Dumping data for table `match_players`
--

INSERT INTO `match_players` (`id`, `match_id`, `user_id`, `score`, `correct_count`, `wrong_count`, `current_answer_index`, `answered_at`, `joined_at`) VALUES
(3, 3, 37, 0, 0, 0, NULL, NULL, '2026-10-12 00:22:51'),
(7, 6, 37, 14481, 3, 7, 2, '2026-10-12 00:26:59', '2026-10-12 00:25:38'),
(8, 6, 2, 9653, 2, 8, 3, '2026-10-12 00:26:57', '2026-10-12 00:25:45'),
(9, 7, 37, 0, 0, 0, NULL, NULL, '2026-10-12 00:27:49'),
(11, 8, 37, 0, 0, 1, NULL, NULL, '2026-10-12 00:33:53'),
(13, 9, 39, 4821, 1, 1, NULL, NULL, '2026-10-12 16:05:50'),
(15, 10, 39, 0, 0, 0, NULL, NULL, '2026-10-12 16:10:32'),
(17, 11, 39, 0, 0, 0, NULL, NULL, '2026-10-14 17:16:29');

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

--
-- Dumping data for table `match_questions`
--

INSERT INTO `match_questions` (`id`, `match_id`, `question_index`, `prompt`, `options_json`, `correct_index`) VALUES
(1, 3, 0, 'Convert decimal 57 to an 8-bit binary string:', '[\"00111001\",\"10000000\",\"01110111\",\"11010011\"]', 0),
(2, 3, 1, 'Convert decimal 24 to an 8-bit binary string:', '[\"00011010\",\"10001101\",\"01011101\",\"00011000\"]', 3),
(3, 3, 2, 'Convert decimal 169 to an 8-bit binary string:', '[\"11110110\",\"10101001\",\"01111011\",\"00010011\"]', 1),
(4, 3, 3, 'Convert decimal 189 to an 8-bit binary string:', '[\"00011110\",\"10101010\",\"10111101\",\"01001000\"]', 2),
(5, 3, 4, 'Convert decimal 121 to an 8-bit binary string:', '[\"01001000\",\"01111001\",\"01000100\",\"11101110\"]', 1),
(6, 3, 5, 'Convert decimal 137 to an 8-bit binary string:', '[\"01010111\",\"10001001\",\"00011001\",\"11110110\"]', 1),
(7, 3, 6, 'Convert decimal 63 to an 8-bit binary string:', '[\"00101001\",\"00000001\",\"01101110\",\"00111111\"]', 3),
(8, 3, 7, 'Convert decimal 45 to an 8-bit binary string:', '[\"10000011\",\"01111110\",\"11111100\",\"00101101\"]', 3),
(9, 3, 8, 'Convert decimal 12 to an 8-bit binary string:', '[\"00110110\",\"00101101\",\"00001000\",\"00001100\"]', 3),
(10, 3, 9, 'Convert decimal 146 to an 8-bit binary string:', '[\"01000010\",\"01110101\",\"10010010\",\"00011110\"]', 2),
(11, 6, 0, 'Convert decimal 34 to an 8-bit binary string:', '[\"11110001\",\"00100010\",\"11101000\",\"11011001\"]', 1),
(12, 6, 1, 'Convert decimal 42 to an 8-bit binary string:', '[\"01110010\",\"00101010\",\"00111111\",\"11101100\"]', 1),
(13, 6, 2, 'Convert decimal 196 to an 8-bit binary string:', '[\"10001000\",\"01011010\",\"11100110\",\"11000100\"]', 3),
(14, 6, 3, 'Convert decimal 13 to an 8-bit binary string:', '[\"00111001\",\"00001101\",\"11010111\",\"11101111\"]', 1),
(15, 6, 4, 'Convert decimal 104 to an 8-bit binary string:', '[\"10000000\",\"01101000\",\"00001010\",\"01010001\"]', 1),
(16, 6, 5, 'Convert decimal 138 to an 8-bit binary string:', '[\"01100010\",\"10001010\",\"01001111\",\"00101000\"]', 1),
(17, 6, 6, 'Convert decimal 110 to an 8-bit binary string:', '[\"00001100\",\"11010000\",\"01001111\",\"01101110\"]', 3),
(18, 6, 7, 'Convert decimal 183 to an 8-bit binary string:', '[\"10110111\",\"10011001\",\"01001100\",\"10011111\"]', 0),
(19, 6, 8, 'Convert decimal 7 to an 8-bit binary string:', '[\"01101111\",\"01000111\",\"10101111\",\"00000111\"]', 3),
(20, 6, 9, 'Convert decimal 157 to an 8-bit binary string:', '[\"11000001\",\"10011101\",\"01010111\",\"11101110\"]', 1),
(21, 7, 0, 'Convert decimal 135 to Hexadecimal (8-bit):', '[\"D3\",\"87\",\"28\",\"A3\"]', 1),
(22, 7, 1, 'Convert decimal 96 to Hexadecimal (8-bit):', '[\"82\",\"5F\",\"60\",\"1E\"]', 2),
(23, 7, 2, 'Convert decimal 45 to Hexadecimal (8-bit):', '[\"2D\",\"F4\",\"4E\",\"E7\"]', 0),
(24, 7, 3, 'Convert decimal 118 to Hexadecimal (8-bit):', '[\"76\",\"66\",\"D\",\"FC\"]', 0),
(25, 7, 4, 'Convert decimal 95 to Hexadecimal (8-bit):', '[\"FB\",\"BA\",\"5F\",\"80\"]', 2),
(26, 7, 5, 'Convert decimal 27 to Hexadecimal (8-bit):', '[\"15\",\"74\",\"1B\",\"14\"]', 2),
(27, 7, 6, 'Convert decimal 251 to Hexadecimal (8-bit):', '[\"C\",\"E5\",\"D7\",\"FB\"]', 3),
(28, 7, 7, 'Convert decimal 172 to Hexadecimal (8-bit):', '[\"A2\",\"AC\",\"65\",\"AE\"]', 1),
(29, 7, 8, 'Convert decimal 236 to Hexadecimal (8-bit):', '[\"D7\",\"AE\",\"50\",\"EC\"]', 3),
(30, 7, 9, 'Convert decimal 50 to Hexadecimal (8-bit):', '[\"A7\",\"90\",\"9D\",\"32\"]', 3),
(31, 8, 0, 'Convert decimal 122 to an 8-bit binary string:', '[\"11011000\",\"11101100\",\"11101011\",\"01111010\"]', 3),
(32, 8, 1, 'Convert decimal 68 to an 8-bit binary string:', '[\"10101111\",\"00101100\",\"10011111\",\"01000100\"]', 3),
(33, 8, 2, 'Convert decimal 39 to an 8-bit binary string:', '[\"00100111\",\"01011011\",\"11111011\",\"11111111\"]', 0),
(34, 8, 3, 'Convert decimal 232 to an 8-bit binary string:', '[\"01011110\",\"00010111\",\"00110011\",\"11101000\"]', 3),
(35, 8, 4, 'Convert decimal 70 to an 8-bit binary string:', '[\"01001111\",\"11110100\",\"01000110\",\"01001100\"]', 2),
(36, 8, 5, 'Convert decimal 11 to an 8-bit binary string:', '[\"00101001\",\"00110110\",\"00001011\",\"00010101\"]', 2),
(37, 8, 6, 'Convert decimal 122 to an 8-bit binary string:', '[\"01111010\",\"01110111\",\"00001000\",\"00101100\"]', 0),
(38, 8, 7, 'Convert decimal 29 to an 8-bit binary string:', '[\"11000111\",\"01010011\",\"00011101\",\"00101001\"]', 2),
(39, 8, 8, 'Convert decimal 37 to an 8-bit binary string:', '[\"00100101\",\"01110101\",\"01110100\",\"01010101\"]', 0),
(40, 8, 9, 'Convert decimal 89 to an 8-bit binary string:', '[\"00011110\",\"11010000\",\"01011001\",\"01110101\"]', 2),
(41, 9, 0, 'Convert decimal 135 to an 8-bit binary string:', '[\"10000111\",\"10011111\",\"11101000\",\"01010110\"]', 0),
(42, 9, 1, 'Convert decimal 191 to an 8-bit binary string:', '[\"10111111\",\"01110000\",\"11111111\",\"11001001\"]', 0),
(43, 9, 2, 'Convert decimal 205 to an 8-bit binary string:', '[\"11010001\",\"00110111\",\"11001101\",\"10001111\"]', 2),
(44, 9, 3, 'Convert decimal 58 to an 8-bit binary string:', '[\"10100110\",\"11111101\",\"00111010\",\"11010011\"]', 2),
(45, 9, 4, 'Convert decimal 199 to an 8-bit binary string:', '[\"10101110\",\"01110000\",\"11111100\",\"11000111\"]', 3),
(46, 9, 5, 'Convert decimal 33 to an 8-bit binary string:', '[\"00010000\",\"00100001\",\"10101001\",\"00111010\"]', 1),
(47, 9, 6, 'Convert decimal 166 to an 8-bit binary string:', '[\"01111010\",\"00010101\",\"11000101\",\"10100110\"]', 3),
(48, 9, 7, 'Convert decimal 35 to an 8-bit binary string:', '[\"00100011\",\"11111011\",\"00000111\",\"01110110\"]', 0),
(49, 9, 8, 'Convert decimal 208 to an 8-bit binary string:', '[\"01011101\",\"11010010\",\"11010000\",\"11000101\"]', 2),
(50, 9, 9, 'Convert decimal 100 to an 8-bit binary string:', '[\"01000111\",\"01111100\",\"10011010\",\"01100100\"]', 3),
(51, 10, 0, 'What is the wildcard mask for a /30 network?', '[\"0.0.0.31\",\"0.0.0.7\",\"0.0.0.15\",\"0.0.0.3\"]', 3),
(52, 10, 1, 'What is the last usable host address for the IP address 192.168.45.182/27?', '[\"192.168.45.161\",\"192.168.45.190\",\"192.168.45.160\",\"192.168.45.191\"]', 1),
(53, 10, 2, 'What is the last usable host address for the IP address 192.168.219.149/26?', '[\"192.168.219.191\",\"192.168.219.129\",\"192.168.219.128\",\"192.168.219.190\"]', 3),
(54, 10, 3, 'What is the network address for the IP address 192.168.34.160/28?', '[\"192.168.34.175\",\"192.168.34.160\",\"192.168.34.161\",\"192.168.34.174\"]', 1),
(55, 10, 4, 'Which of the following is a private IP address?', '[\"43.176.77.101\",\"44.90.208.121\",\"192.168.252.240\",\"144.109.253.16\"]', 2),
(56, 10, 5, 'What is the wildcard mask for a /30 network?', '[\"0.0.0.31\",\"0.0.0.3\",\"0.0.0.255\",\"0.0.0.15\"]', 1),
(57, 10, 6, 'What is the subnet mask for a /28 network?', '[\"255.255.255.248\",\"255.255.255.192\",\"255.255.255.224\",\"255.255.255.240\"]', 3),
(58, 10, 7, 'What is the broadcast address for the IP address 192.168.179.198/27?', '[\"192.168.179.223\",\"192.168.179.222\",\"192.168.179.193\",\"192.168.179.192\"]', 0),
(59, 10, 8, 'Which of the following is a private IP address?', '[\"198.243.104.233\",\"15.206.143.168\",\"145.236.99.60\",\"172.17.158.151\"]', 3),
(60, 10, 9, 'What is the subnet mask for a /30 network?', '[\"255.255.255.252\",\"255.255.255.240\",\"255.255.255.128\",\"255.255.255.224\"]', 0),
(61, 11, 0, 'What is the subnet mask for 17.120.72.59/29?', '[\"255.255.255.248\",\"255.255.255.0\",\"255.255.255.240\",\"255.255.255.128\"]', 0),
(62, 11, 1, 'What is the subnet mask for 176.110.143.223/29?', '[\"255.255.255.0\",\"255.255.255.248\",\"255.255.255.128\",\"255.255.255.240\"]', 1),
(63, 11, 2, 'What is the subnet mask for 210.89.26.61/27?', '[\"255.255.255.128\",\"255.255.255.240\",\"255.255.255.0\",\"255.255.255.224\"]', 3),
(64, 11, 3, 'What is the subnet mask for 107.150.108.186/30?', '[\"255.255.255.252\",\"255.255.255.240\",\"255.255.255.0\",\"255.255.255.128\"]', 0),
(65, 11, 4, 'What is the subnet mask for 28.88.204.210/30?', '[\"255.255.255.0\",\"255.255.255.240\",\"255.255.255.128\",\"255.255.255.252\"]', 3),
(66, 11, 5, 'What is the subnet mask for 101.70.100.76/27?', '[\"255.255.255.128\",\"255.255.255.0\",\"255.255.255.240\",\"255.255.255.224\"]', 3),
(67, 11, 6, 'What is the subnet mask for 160.123.2.79/27?', '[\"255.255.255.128\",\"255.255.255.0\",\"255.255.255.240\",\"255.255.255.224\"]', 3),
(68, 11, 7, 'What is the subnet mask for 202.168.249.65/26?', '[\"255.255.255.0\",\"255.255.255.240\",\"255.255.255.128\",\"255.255.255.192\"]', 3),
(69, 11, 8, 'What is the subnet mask for 190.171.228.127/26?', '[\"255.255.255.240\",\"255.255.255.0\",\"255.255.255.192\",\"255.255.255.128\"]', 2),
(70, 11, 9, 'What is the subnet mask for 168.131.87.15/28?', '[\"255.255.255.240\",\"255.255.255.0\",\"255.255.255.128\",\"255.255.255.252\"]', 0);

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
(1, 4, '93d83fdce85cede763ef0784b4a73ad833f0f4f7df385aa0b1209e3fe1a87c9e', '2026-10-28 08:18:34'),
(2, 5, '6af8086bb23527c3f7f4c59247d8691a8dbe80ae37723c87a2f04fc51f84180d', '2026-11-05 10:17:13'),
(3, 5, '41545392b1ee5ce4ae20fe64fd5017e5d3afdb9527dd2681abaa294ae9d7132a', '2026-11-05 11:49:04'),
(4, 5, 'a4e5605f5831ae1fb68026dfc729ce2e1ec4697586886aa731d37231c3fd2e21', '2026-11-10 06:42:06'),
(5, 36, 'f6ed08a45d715c57791571a9eb4faf4d7bf8077efc759d25de7f07d32730ce3d', '2026-11-10 16:04:32');

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
  `played_at` datetime NOT NULL,
  `correct_count` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scores`
--

INSERT INTO `scores` (`id`, `user_id`, `match_id`, `game_type`, `points`, `played_at`, `correct_count`) VALUES
(1, 2, NULL, 'showdown_practice', 152, '2026-09-26 22:44:08', 0),
(2, 2, NULL, 'binary_practice', 180, '2026-10-01 14:44:28', 0),
(3, 5, NULL, 'binary_practice', 90, '2026-10-06 16:18:58', 0),
(4, 6, NULL, 'binary_practice', 130, '2026-10-06 16:23:42', 0),
(5, 5, NULL, 'binary_practice', 100, '2026-10-06 16:25:30', 0),
(6, 6, NULL, 'binary_game', 460, '2026-10-06 16:26:50', 0),
(7, 5, NULL, 'binary_practice', 360, '2026-10-06 16:29:27', 0),
(8, 6, NULL, 'binary_game', 525, '2026-10-06 16:30:01', 0),
(9, 5, NULL, 'binary_game', 560, '2026-10-06 16:32:45', 0),
(10, 5, NULL, 'binary_game', 620, '2026-10-06 16:36:04', 0),
(11, 5, NULL, 'binary_game', 580, '2026-10-06 16:39:24', 0),
(12, 5, NULL, 'binary_game', 780, '2026-10-06 16:43:08', 0),
(13, 5, NULL, 'binary_game', 640, '2026-10-06 16:46:22', 0),
(14, 5, NULL, 'binary_game', 800, '2026-10-06 16:49:26', 0),
(15, 5, NULL, 'binary_practice', 1580, '2026-10-06 17:00:11', 0),
(16, 7, NULL, 'binary_game', 1000, '2026-10-06 17:02:26', 0),
(17, 5, NULL, 'binary_game', 880, '2026-10-06 17:04:21', 0),
(18, 5, NULL, 'binary_game', 940, '2026-10-06 17:07:39', 0),
(19, 5, NULL, 'binary_game', 900, '2026-10-06 17:10:59', 0),
(20, 8, NULL, 'binary_game', 510, '2026-10-06 20:30:04', 0),
(21, 8, NULL, 'binary_game', 770, '2026-10-06 20:33:34', 0),
(22, 9, NULL, 'subnet_dissect_timed', 1850, '2026-10-10 21:50:50', 8),
(23, 2, NULL, 'showdown_timed', 22, '2026-10-11 14:36:00', 1),
(24, 10, NULL, 'subnet_dissect_practice', 180, '2026-10-06 20:27:16', 0),
(25, 10, NULL, 'subnet_dissect_practice', 180, '2026-10-06 20:28:16', 0),
(26, 10, NULL, 'subnet_dissect_practice', 180, '2026-10-06 20:29:16', 0),
(27, 10, NULL, 'subnet_dissect_practice', 180, '2026-10-06 20:30:16', 0),
(28, 11, NULL, 'subnet_dissect_practice', 205, '2026-10-06 20:31:42', 0),
(29, 11, NULL, 'subnet_dissect_practice', 205, '2026-10-06 20:32:42', 0),
(30, 11, NULL, 'subnet_dissect_practice', 205, '2026-10-06 20:33:42', 0),
(31, 11, NULL, 'subnet_dissect_practice', 205, '2026-10-06 20:34:42', 0),
(32, 11, NULL, 'subnet_dissect_practice', 205, '2026-10-06 20:35:42', 0),
(33, 11, NULL, 'subnet_dissect_practice', 205, '2026-10-06 20:36:42', 0),
(34, 11, NULL, 'subnet_dissect_practice', 205, '2026-10-06 20:37:42', 0),
(35, 11, NULL, 'subnet_dissect_practice', 205, '2026-10-06 20:38:42', 0),
(36, 11, NULL, 'subnet_dissect_practice', 205, '2026-10-06 20:39:42', 0),
(37, 11, NULL, 'subnet_dissect_practice', 205, '2026-10-06 20:40:42', 0),
(38, 11, NULL, 'subnet_dissect_practice', 205, '2026-10-06 20:41:42', 0),
(39, 11, NULL, 'subnet_dissect_practice', 195, '2026-10-06 20:42:42', 0),
(40, 12, NULL, 'subnet_dissect_practice', 220, '2026-10-06 20:36:08', 0),
(41, 12, NULL, 'subnet_dissect_practice', 220, '2026-10-06 20:37:08', 0),
(42, 12, NULL, 'subnet_dissect_practice', 220, '2026-10-06 20:38:08', 0),
(43, 12, NULL, 'subnet_dissect_practice', 220, '2026-10-06 20:39:08', 0),
(44, 12, NULL, 'subnet_dissect_practice', 220, '2026-10-06 20:40:08', 0),
(45, 12, NULL, 'subnet_dissect_practice', 220, '2026-10-06 20:41:08', 0),
(46, 13, NULL, 'subnet_dissect_practice', 196, '2026-10-06 20:40:25', 0),
(47, 13, NULL, 'subnet_dissect_practice', 196, '2026-10-06 20:41:25', 0),
(48, 13, NULL, 'subnet_dissect_practice', 196, '2026-10-06 20:42:25', 0),
(49, 13, NULL, 'subnet_dissect_practice', 196, '2026-10-06 20:43:25', 0),
(50, 13, NULL, 'subnet_dissect_practice', 196, '2026-10-06 20:44:25', 0),
(51, 14, NULL, 'subnet_dissect_practice', 186, '2026-10-06 20:45:13', 0),
(52, 14, NULL, 'subnet_dissect_practice', 186, '2026-10-06 20:46:13', 0),
(53, 14, NULL, 'subnet_dissect_practice', 186, '2026-10-06 20:47:13', 0),
(54, 14, NULL, 'subnet_dissect_practice', 186, '2026-10-06 20:48:13', 0),
(55, 14, NULL, 'subnet_dissect_practice', 186, '2026-10-06 20:49:13', 0),
(56, 14, NULL, 'subnet_dissect_practice', 186, '2026-10-06 20:50:13', 0),
(57, 14, NULL, 'subnet_dissect_practice', 186, '2026-10-06 20:51:13', 0),
(58, 14, NULL, 'subnet_dissect_practice', 186, '2026-10-06 20:52:13', 0),
(59, 14, NULL, 'subnet_dissect_practice', 186, '2026-10-06 20:53:13', 0),
(60, 14, NULL, 'subnet_dissect_practice', 186, '2026-10-06 20:54:13', 0),
(61, 14, NULL, 'subnet_dissect_practice', 186, '2026-10-06 20:55:13', 0),
(62, 14, NULL, 'subnet_dissect_practice', 186, '2026-10-06 20:56:13', 0),
(63, 14, NULL, 'subnet_dissect_practice', 186, '2026-10-06 20:57:13', 0),
(64, 14, NULL, 'subnet_dissect_practice', 186, '2026-10-06 20:58:13', 0),
(65, 14, NULL, 'subnet_dissect_practice', 176, '2026-10-06 20:59:13', 0),
(66, 15, NULL, 'subnet_dissect_practice', 174, '2026-10-06 20:49:37', 0),
(67, 15, NULL, 'subnet_dissect_practice', 174, '2026-10-06 20:50:37', 0),
(68, 15, NULL, 'subnet_dissect_practice', 174, '2026-10-06 20:51:37', 0),
(69, 15, NULL, 'subnet_dissect_practice', 174, '2026-10-06 20:52:37', 0),
(70, 15, NULL, 'subnet_dissect_practice', 174, '2026-10-06 20:53:37', 0),
(71, 15, NULL, 'subnet_dissect_practice', 174, '2026-10-06 20:54:37', 0),
(72, 15, NULL, 'subnet_dissect_practice', 174, '2026-10-06 20:55:37', 0),
(73, 15, NULL, 'subnet_dissect_practice', 174, '2026-10-06 20:56:37', 0),
(74, 15, NULL, 'subnet_dissect_practice', 168, '2026-10-06 20:57:37', 0),
(75, 16, NULL, 'subnet_dissect_practice', 195, '2026-10-06 20:54:02', 0),
(76, 16, NULL, 'subnet_dissect_practice', 195, '2026-10-06 20:55:02', 0),
(77, 16, NULL, 'subnet_dissect_practice', 195, '2026-10-06 20:56:02', 0),
(78, 16, NULL, 'subnet_dissect_practice', 195, '2026-10-06 20:57:02', 0),
(79, 16, NULL, 'subnet_dissect_practice', 195, '2026-10-06 20:58:02', 0),
(80, 16, NULL, 'subnet_dissect_practice', 195, '2026-10-06 20:59:02', 0),
(81, 16, NULL, 'subnet_dissect_practice', 195, '2026-10-06 21:00:02', 0),
(82, 16, NULL, 'subnet_dissect_practice', 195, '2026-10-06 21:01:02', 0),
(83, 16, NULL, 'subnet_dissect_practice', 195, '2026-10-06 21:02:02', 0),
(84, 16, NULL, 'subnet_dissect_practice', 195, '2026-10-06 21:03:02', 0),
(85, 16, NULL, 'subnet_dissect_practice', 190, '2026-10-06 21:04:02', 0),
(86, 17, NULL, 'subnet_dissect_practice', 214, '2026-10-06 20:58:41', 0),
(87, 17, NULL, 'subnet_dissect_practice', 213, '2026-10-06 20:59:41', 0),
(88, 17, NULL, 'subnet_dissect_practice', 213, '2026-10-06 21:00:41', 0),
(89, 18, NULL, 'subnet_dissect_practice', 192, '2026-10-06 21:03:19', 0),
(90, 18, NULL, 'subnet_dissect_practice', 192, '2026-10-06 21:04:19', 0),
(91, 18, NULL, 'subnet_dissect_practice', 192, '2026-10-06 21:05:19', 0),
(92, 18, NULL, 'subnet_dissect_practice', 192, '2026-10-06 21:06:19', 0),
(93, 18, NULL, 'subnet_dissect_practice', 192, '2026-10-06 21:07:19', 0),
(94, 18, NULL, 'subnet_dissect_practice', 192, '2026-10-06 21:08:19', 0),
(95, 18, NULL, 'subnet_dissect_practice', 192, '2026-10-06 21:09:19', 0),
(96, 18, NULL, 'subnet_dissect_practice', 192, '2026-10-06 21:10:19', 0),
(97, 18, NULL, 'subnet_dissect_practice', 192, '2026-10-06 21:11:19', 0),
(98, 18, NULL, 'subnet_dissect_practice', 192, '2026-10-06 21:12:19', 0),
(99, 19, NULL, 'subnet_dissect_practice', 187, '2026-10-06 21:08:44', 0),
(100, 19, NULL, 'subnet_dissect_practice', 187, '2026-10-06 21:09:44', 0),
(101, 19, NULL, 'subnet_dissect_practice', 187, '2026-10-06 21:10:44', 0),
(102, 19, NULL, 'subnet_dissect_practice', 187, '2026-10-06 21:11:44', 0),
(103, 19, NULL, 'subnet_dissect_practice', 186, '2026-10-06 21:12:44', 0),
(104, 19, NULL, 'subnet_dissect_practice', 186, '2026-10-06 21:13:44', 0),
(105, 20, NULL, 'subnet_dissect_practice', 190, '2026-10-06 21:14:06', 0),
(106, 20, NULL, 'subnet_dissect_practice', 190, '2026-10-06 21:15:06', 0),
(107, 20, NULL, 'subnet_dissect_practice', 190, '2026-10-06 21:16:06', 0),
(108, 20, NULL, 'subnet_dissect_practice', 190, '2026-10-06 21:17:06', 0),
(109, 20, NULL, 'subnet_dissect_practice', 190, '2026-10-06 21:18:06', 0),
(110, 20, NULL, 'subnet_dissect_practice', 190, '2026-10-06 21:19:06', 0),
(111, 20, NULL, 'subnet_dissect_practice', 190, '2026-10-06 21:20:06', 0),
(112, 20, NULL, 'subnet_dissect_practice', 190, '2026-10-06 21:21:06', 0),
(113, 20, NULL, 'subnet_dissect_practice', 190, '2026-10-06 21:22:06', 0),
(114, 20, NULL, 'subnet_dissect_practice', 190, '2026-10-06 21:23:06', 0),
(115, 20, NULL, 'subnet_dissect_practice', 190, '2026-10-06 21:24:06', 0),
(116, 20, NULL, 'subnet_dissect_practice', 190, '2026-10-06 21:25:06', 0),
(117, 20, NULL, 'subnet_dissect_practice', 190, '2026-10-06 21:26:06', 0),
(118, 20, NULL, 'subnet_dissect_practice', 190, '2026-10-06 21:27:06', 0),
(243, 21, NULL, 'hex_conversion', 210, '2026-10-07 20:24:50', 0),
(244, 21, NULL, 'scenario_practice', 210, '2026-10-07 20:25:50', 0),
(245, 21, NULL, 'ipv6_practice', 210, '2026-10-07 20:26:50', 0),
(246, 21, NULL, 'subnet_dissect_practice', 210, '2026-10-07 20:27:50', 0),
(247, 22, NULL, 'scenario_practice', 208, '2026-10-07 20:29:50', 0),
(248, 22, NULL, 'ipv6_practice', 207, '2026-10-07 20:30:50', 0),
(249, 22, NULL, 'subnet_dissect_practice', 207, '2026-10-07 20:31:50', 0),
(250, 22, NULL, 'binary_practice', 207, '2026-10-07 20:32:50', 0),
(251, 22, NULL, 'showdown_practice', 207, '2026-10-07 20:33:50', 0),
(252, 22, NULL, 'hex_conversion', 207, '2026-10-07 20:34:50', 0),
(253, 22, NULL, 'scenario_practice', 207, '2026-10-07 20:35:50', 0),
(254, 23, NULL, 'ipv6_practice', 184, '2026-10-07 20:34:50', 0),
(255, 23, NULL, 'subnet_dissect_practice', 184, '2026-10-07 20:35:50', 0),
(256, 23, NULL, 'binary_practice', 184, '2026-10-07 20:36:50', 0),
(257, 23, NULL, 'showdown_practice', 184, '2026-10-07 20:37:50', 0),
(258, 23, NULL, 'hex_conversion', 184, '2026-10-07 20:38:50', 0),
(259, 24, NULL, 'subnet_dissect_practice', 192, '2026-10-07 20:39:50', 0),
(260, 24, NULL, 'binary_practice', 192, '2026-10-07 20:40:50', 0),
(261, 24, NULL, 'showdown_practice', 192, '2026-10-07 20:41:50', 0),
(262, 24, NULL, 'hex_conversion', 192, '2026-10-07 20:42:50', 0),
(263, 24, NULL, 'scenario_practice', 192, '2026-10-07 20:43:50', 0),
(264, 24, NULL, 'ipv6_practice', 192, '2026-10-07 20:44:50', 0),
(265, 24, NULL, 'subnet_dissect_practice', 191, '2026-10-07 20:45:50', 0),
(266, 24, NULL, 'binary_practice', 191, '2026-10-07 20:46:50', 0),
(267, 24, NULL, 'showdown_practice', 191, '2026-10-07 20:47:50', 0),
(268, 24, NULL, 'hex_conversion', 191, '2026-10-07 20:48:50', 0),
(269, 24, NULL, 'scenario_practice', 191, '2026-10-07 20:49:50', 0),
(270, 24, NULL, 'ipv6_practice', 191, '2026-10-07 20:50:50', 0),
(271, 24, NULL, 'subnet_dissect_practice', 191, '2026-10-07 20:51:50', 0),
(272, 24, NULL, 'binary_practice', 191, '2026-10-07 20:52:50', 0),
(273, 25, NULL, 'binary_practice', 195, '2026-10-07 20:44:50', 0),
(274, 25, NULL, 'showdown_practice', 195, '2026-10-07 20:45:50', 0),
(275, 25, NULL, 'hex_conversion', 195, '2026-10-07 20:46:50', 0),
(276, 25, NULL, 'scenario_practice', 195, '2026-10-07 20:47:50', 0),
(277, 25, NULL, 'ipv6_practice', 195, '2026-10-07 20:48:50', 0),
(278, 25, NULL, 'subnet_dissect_practice', 195, '2026-10-07 20:49:50', 0),
(279, 26, NULL, 'showdown_practice', 196, '2026-10-07 20:49:50', 0),
(280, 26, NULL, 'hex_conversion', 196, '2026-10-07 20:50:50', 0),
(281, 26, NULL, 'scenario_practice', 196, '2026-10-07 20:51:50', 0),
(282, 26, NULL, 'ipv6_practice', 196, '2026-10-07 20:52:50', 0),
(283, 26, NULL, 'subnet_dissect_practice', 196, '2026-10-07 20:53:50', 0),
(284, 26, NULL, 'binary_practice', 196, '2026-10-07 20:54:50', 0),
(285, 26, NULL, 'showdown_practice', 196, '2026-10-07 20:55:50', 0),
(286, 26, NULL, 'hex_conversion', 196, '2026-10-07 20:56:50', 0),
(287, 26, NULL, 'scenario_practice', 196, '2026-10-07 20:57:50', 0),
(288, 26, NULL, 'ipv6_practice', 196, '2026-10-07 20:58:50', 0),
(289, 26, NULL, 'subnet_dissect_practice', 195, '2026-10-07 20:59:50', 0),
(290, 26, NULL, 'binary_practice', 195, '2026-10-07 21:00:50', 0),
(291, 27, NULL, 'hex_conversion', 170, '2026-10-07 20:54:50', 0),
(292, 27, NULL, 'scenario_practice', 170, '2026-10-07 20:55:50', 0),
(293, 27, NULL, 'ipv6_practice', 170, '2026-10-07 20:56:50', 0),
(294, 27, NULL, 'subnet_dissect_practice', 170, '2026-10-07 20:57:50', 0),
(295, 28, NULL, 'scenario_practice', 198, '2026-10-07 20:59:50', 0),
(296, 28, NULL, 'ipv6_practice', 198, '2026-10-07 21:00:50', 0),
(297, 28, NULL, 'subnet_dissect_practice', 198, '2026-10-07 21:01:50', 0),
(298, 28, NULL, 'binary_practice', 198, '2026-10-07 21:02:50', 0),
(299, 28, NULL, 'showdown_practice', 198, '2026-10-07 21:03:50', 0),
(300, 28, NULL, 'hex_conversion', 198, '2026-10-07 21:04:50', 0),
(301, 28, NULL, 'scenario_practice', 198, '2026-10-07 21:05:50', 0),
(302, 28, NULL, 'ipv6_practice', 198, '2026-10-07 21:06:50', 0),
(303, 28, NULL, 'subnet_dissect_practice', 198, '2026-10-07 21:07:50', 0),
(304, 28, NULL, 'binary_practice', 198, '2026-10-07 21:08:50', 0),
(305, 29, NULL, 'ipv6_practice', 180, '2026-10-07 21:04:50', 0),
(306, 29, NULL, 'subnet_dissect_practice', 180, '2026-10-07 21:05:50', 0),
(307, 29, NULL, 'binary_practice', 180, '2026-10-07 21:06:50', 0),
(308, 29, NULL, 'showdown_practice', 180, '2026-10-07 21:07:50', 0),
(309, 29, NULL, 'hex_conversion', 180, '2026-10-07 21:08:50', 0),
(310, 29, NULL, 'scenario_practice', 180, '2026-10-07 21:09:50', 0),
(311, 29, NULL, 'ipv6_practice', 180, '2026-10-07 21:10:50', 0),
(312, 30, NULL, 'subnet_dissect_practice', 192, '2026-10-07 21:09:50', 0),
(313, 30, NULL, 'binary_practice', 192, '2026-10-07 21:10:50', 0),
(314, 30, NULL, 'showdown_practice', 192, '2026-10-07 21:11:50', 0),
(315, 30, NULL, 'hex_conversion', 192, '2026-10-07 21:12:50', 0),
(316, 30, NULL, 'scenario_practice', 192, '2026-10-07 21:13:50', 0),
(317, 30, NULL, 'ipv6_practice', 192, '2026-10-07 21:14:50', 0),
(318, 30, NULL, 'subnet_dissect_practice', 192, '2026-10-07 21:15:50', 0),
(319, 30, NULL, 'binary_practice', 192, '2026-10-07 21:16:50', 0),
(320, 30, NULL, 'showdown_practice', 192, '2026-10-07 21:17:50', 0),
(321, 30, NULL, 'hex_conversion', 192, '2026-10-07 21:18:50', 0),
(322, 30, NULL, 'scenario_practice', 192, '2026-10-07 21:19:50', 0),
(323, 30, NULL, 'ipv6_practice', 192, '2026-10-07 21:20:50', 0),
(324, 30, NULL, 'subnet_dissect_practice', 192, '2026-10-07 21:21:50', 0),
(325, 30, NULL, 'binary_practice', 192, '2026-10-07 21:22:50', 0),
(326, 30, NULL, 'showdown_practice', 192, '2026-10-07 21:23:50', 0),
(327, 31, NULL, 'binary_practice', 178, '2026-10-07 21:14:50', 0),
(328, 31, NULL, 'showdown_practice', 178, '2026-10-07 21:15:50', 0),
(329, 31, NULL, 'hex_conversion', 178, '2026-10-07 21:16:50', 0),
(330, 31, NULL, 'scenario_practice', 178, '2026-10-07 21:17:50', 0),
(331, 31, NULL, 'ipv6_practice', 178, '2026-10-07 21:18:50', 0),
(332, 32, NULL, 'showdown_practice', 194, '2026-10-07 21:19:50', 0),
(333, 32, NULL, 'hex_conversion', 194, '2026-10-07 21:20:50', 0),
(334, 32, NULL, 'scenario_practice', 194, '2026-10-07 21:21:50', 0),
(335, 32, NULL, 'ipv6_practice', 193, '2026-10-07 21:22:50', 0),
(336, 32, NULL, 'subnet_dissect_practice', 193, '2026-10-07 21:23:50', 0),
(337, 32, NULL, 'binary_practice', 193, '2026-10-07 21:24:50', 0),
(338, 32, NULL, 'showdown_practice', 193, '2026-10-07 21:25:50', 0),
(339, 32, NULL, 'hex_conversion', 193, '2026-10-07 21:26:50', 0),
(340, 32, NULL, 'scenario_practice', 193, '2026-10-07 21:27:50', 0),
(341, 33, NULL, 'hex_conversion', 175, '2026-10-07 21:24:50', 0),
(342, 33, NULL, 'scenario_practice', 175, '2026-10-07 21:25:50', 0),
(343, 33, NULL, 'ipv6_practice', 175, '2026-10-07 21:26:50', 0),
(344, 33, NULL, 'subnet_dissect_practice', 175, '2026-10-07 21:27:50', 0),
(345, 33, NULL, 'binary_practice', 175, '2026-10-07 21:28:50', 0),
(346, 33, NULL, 'showdown_practice', 175, '2026-10-07 21:29:50', 0),
(347, 34, NULL, 'scenario_practice', 188, '2026-10-07 21:29:50', 0),
(348, 34, NULL, 'ipv6_practice', 188, '2026-10-07 21:30:50', 0),
(349, 34, NULL, 'subnet_dissect_practice', 188, '2026-10-07 21:31:50', 0),
(350, 34, NULL, 'binary_practice', 188, '2026-10-07 21:32:50', 0),
(351, 34, NULL, 'showdown_practice', 188, '2026-10-07 21:33:50', 0),
(352, 34, NULL, 'hex_conversion', 188, '2026-10-07 21:34:50', 0),
(353, 34, NULL, 'scenario_practice', 187, '2026-10-07 21:35:50', 0),
(354, 34, NULL, 'ipv6_practice', 187, '2026-10-07 21:36:50', 0),
(355, 34, NULL, 'subnet_dissect_practice', 187, '2026-10-07 21:37:50', 0),
(356, 34, NULL, 'binary_practice', 187, '2026-10-07 21:38:50', 0),
(357, 34, NULL, 'showdown_practice', 187, '2026-10-07 21:39:50', 0),
(358, 34, NULL, 'hex_conversion', 187, '2026-10-07 21:40:50', 0),
(359, 35, NULL, 'ipv6_practice', 190, '2026-10-07 21:34:50', 0),
(360, 35, NULL, 'subnet_dissect_practice', 190, '2026-10-07 21:35:50', 0),
(361, 35, NULL, 'binary_practice', 190, '2026-10-07 21:36:50', 0),
(362, 35, NULL, 'showdown_practice', 190, '2026-10-07 21:37:50', 0),
(370, 2, NULL, 'ipv6_practice', 90, '2026-10-11 17:39:13', 0),
(371, 2, NULL, 'showdown_timed', 144, '2026-10-12 00:08:44', 6),
(372, 37, 3, 'multiplayer_match', 0, '2026-10-12 00:23:09', 0),
(373, 37, 6, 'multiplayer_match', 14481, '2026-10-12 00:26:59', 0),
(374, 2, 6, 'multiplayer_match', 9653, '2026-10-12 00:26:59', 0),
(375, 37, 7, 'multiplayer_match', 0, '2026-10-12 00:27:58', 0),
(376, 37, 8, 'multiplayer_match', 0, '2026-10-12 00:37:40', 0),
(377, 39, 9, 'multiplayer_match', 4821, '2026-10-12 16:08:15', 0),
(378, 39, 10, 'multiplayer_match', 0, '2026-10-12 16:11:19', 0),
(379, 39, NULL, 'binary_practice', 90, '2026-10-12 16:27:48', 0),
(380, 39, NULL, 'hex_practice', 40, '2026-10-12 16:31:34', 0),
(381, 2, NULL, 'showdown_timed', 146, '2026-10-13 13:22:02', 6),
(382, 2, NULL, 'showdown_timed', 0, '2026-10-13 13:24:08', 0),
(383, 2, NULL, 'scenario_timed', 99, '2026-10-13 13:58:21', 3),
(384, 2, NULL, 'showdown_timed', 68, '2026-10-13 14:02:02', 3),
(385, 2, NULL, 'showdown_timed', 100, '2026-10-13 14:32:14', 4),
(386, 2, NULL, 'showdown_timed', 152, '2026-10-13 17:46:06', 6),
(387, 2, NULL, 'showdown_timed', 94, '2026-10-13 18:38:18', 4),
(388, 2, NULL, 'scenario_timed', 68, '2026-10-13 18:40:33', 3),
(389, 2, NULL, 'scenario_timed', 44, '2026-10-13 18:44:21', 2),
(390, 2, NULL, 'showdown_timed', 135, '2026-10-14 15:04:08', 4),
(391, 2, NULL, 'showdown_timed', 44, '2026-10-14 15:05:37', 1),
(392, 39, NULL, 'scenario_timed', 135, '2026-10-14 15:10:23', 4),
(393, 39, NULL, 'scenario_timed', 66, '2026-10-14 15:15:58', 2),
(394, 39, 11, 'multiplayer_match', 0, '2026-10-14 17:16:42', 0);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_applications`
--

CREATE TABLE `teacher_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `student_emails` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `applied_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_applications`
--

INSERT INTO `teacher_applications` (`id`, `user_id`, `student_emails`, `status`, `applied_at`) VALUES
(1, 41, 'g20@gmail.com', 'approved', '2026-10-14 13:56:14'),
(2, 43, 'cma@gm.com', 'approved', '2026-10-14 15:54:36');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_invitations`
--

CREATE TABLE `teacher_invitations` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_invitations`
--

INSERT INTO `teacher_invitations` (`id`, `teacher_id`, `student_id`, `status`, `created_at`) VALUES
(1, 41, 2, 'rejected', '2026-10-14 15:52:29'),
(5, 43, 2, 'accepted', '2026-10-14 15:54:42'),
(6, 43, 39, 'accepted', '2026-10-14 16:48:15');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_students`
--

CREATE TABLE `teacher_students` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `assigned_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_students`
--

INSERT INTO `teacher_students` (`id`, `teacher_id`, `student_id`, `assigned_at`) VALUES
(1, 41, 39, '2026-10-14 13:56:35'),
(2, 43, 2, '2026-10-14 15:54:58'),
(3, 43, 39, '2026-10-14 16:48:42');

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
  `created_at` datetime NOT NULL,
  `is_teacher` tinyint(1) DEFAULT 0,
  `teacher_status` enum('none','pending','approved','rejected') DEFAULT 'none'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `avatar`, `is_admin`, `created_at`, `is_teacher`, `teacher_status`) VALUES
(1, 'Admin1', 'admin1@gg.com', '$2y$10$ar5q20r5lI8eswq5BRKZEeHHTOjWl.BcdF1.TmZ5Clu31y6jpTIQG', 'robot', 1, '2026-09-26 19:03:39', 0, 'none'),
(2, 'Carlo', 'cma@gm.com', '$2y$10$m2GgOmwTRKb0fEWwC4mwSOKCzglQMmbOB7s/h0bAWoJ5B7SRX5tR.', 'wolf', 0, '2026-09-26 19:08:38', 0, 'none'),
(3, 'test', 'test@m.com', '$2y$10$WTCSTDqewk6Tma71Ed0N8OLTpe.c38rLsEOquwrxh34AQ7NtzhDmW', 'fox', 0, '2026-09-26 19:10:12', 0, 'none'),
(4, 'aki00', 'aki@gmail.com', '$2y$10$Pmliz1t9MmktkI8Zc0V6iOLx1Xwc3RhLvsaKOWa6yO/ioUJweVOyu', 'fox', 0, '2026-09-28 14:18:19', 0, 'none'),
(5, 'RaynBSIT1A', 'ingarraynelp@gmail.com', '$2y$10$EH7UBYdxl.AX94B8nF15KeteIHMYHZ9ZW3kGIuKMSXrTkld0snFpu', 'fox', 0, '2026-10-06 16:16:58', 0, 'none'),
(6, 'jams', 'jamienathaliegraagas@gmail.com', '$2y$10$RY4B4yA55xCB38O1QZgLfe4tlXHOqF.PJsBiX1/j/KrWZB.Anj2CK', 'fox', 0, '2026-10-06 16:17:42', 0, 'none'),
(7, 'Graham', 'judicio.o01@gmail.com', '$2y$10$5f1V8gL0h0uiAPgyRk99luDq.ilbOkHJ11FWCZ8Zt8fByb8ROpPOe', 'fox', 0, '2026-10-06 16:58:25', 0, 'none'),
(8, 'Roldan Eborde', 'mirandachadramiel@gmail.com', '$2y$10$XJhiLXtB/DMRXPm4N0ZsPeYFtVHSmDRLD3FuxWufS3DSmxtMj0mCa', 'fox', 0, '2026-10-06 20:23:54', 0, 'none'),
(9, 'JGBSIT', 'jg03@gmail.com', '$2y$10$ekkf14BNh0H79CEdaX.lQ.TOZBcLfLW2zq3wOLUyIXAzd4svaE1Ui', 'fox', 0, '2026-10-06 20:23:50', 0, 'none'),
(10, 'ash', 'ashbash@gmail.com', '$2y$10$.rSDdajRq/qBuN9ZK3I8zeXssEGKB82xT3Lp1w7CBVPJwSuhwUsFG', 'fox', 0, '2026-10-06 20:27:16', 0, 'none'),
(11, 'Noobie', 'secret@gmail.com', '$2y$10$U1vLf3Ex691u16cMn7.GTOyQaRZ8YOOF6CqZqm1ftHF7ahPHksy3i', 'panda', 0, '2026-10-06 20:31:42', 0, 'none'),
(12, 'sean', 'sean123@gmail.com', '$2y$10$uyAR9jjMErDdonG/bWp8beSaICCYE4cRXx6/B84l4S7FpGTkGPpFm', 'lion', 0, '2026-10-06 20:36:08', 0, 'none'),
(13, 'Tala', 't.garcia@gmail.com', '$2y$10$7npeniHWmi83xGPCRVYPluHaBYykkTY1yR0qkgVHEwk9ikzsT.Skq', 'panda', 0, '2026-10-06 20:40:25', 0, 'none'),
(14, 'rigbaby', 'irigbooks39@gmail.com', '$2y$10$j/0cLeWZJa8AnMIITs5DleQrFBE18OrSP2rDJSbkrEL2myVl.3yXG', 'fox', 0, '2026-10-06 20:45:13', 0, 'none'),
(15, 'MaRkkk', 'mark.reyes195@gmail.com', '$2y$10$0R79ENUb//MiV.5neFHRjeJ4o7zgk6N6Y18938swclUtaCKx1RlAm', 'lion', 0, '2026-10-06 20:49:37', 0, 'none'),
(16, 'Drew', 'andrew.aquino402@gmail.com', '$2y$10$kfaOykc3.qxx/9/MbuRwGuvQoiJka5GXalwpaoiCpah4N3xejRHsi', 'fox', 0, '2026-10-06 20:54:02', 0, 'none'),
(17, 'Ozzirne', 'oza417@gmail.com', '$2y$10$axbQ1ehC8a1oGj4AAr0LU.SMSU9BpOaXH5q1qqK6.FiNlDoTe5tqi', 'fox', 0, '2026-10-06 20:58:41', 0, 'none'),
(18, 'RandyBSIT', 'santi07@gmail.com', '$2y$10$PYk3HBPK1xOc4ctus/epFedCErfKlUn7iI9RDmK/NQpUX4JfGzlkW', 'fox', 0, '2026-10-06 21:03:19', 0, 'none'),
(19, 'Potato', 'hahaha@gmail.com', '$2y$10$IzJYYM5TqFJQmfmULtOuVuaAlGCg3qnchqpM7T8xqB9cuZzNxndq2', 'fox', 0, '2026-10-06 21:08:44', 0, 'none'),
(20, 'Guttie\'', 'l.gutierrez29@gmail.com', '$2y$10$buDIKt2grUrhYNX6YK3myuqoMYCySoTcpHiRbVEO781gtp6YjJEIu', 'fox', 0, '2026-10-06 21:14:06', 0, 'none'),
(21, 'Kevs', 'domin19@gmail.com', '$2y$10$Sls596AooMuGFVF5n1UapOsNaWfUrImmjx7LLxOaJ1WIWSqX8u82O', 'fox', 0, '2026-10-07 20:23:50', 0, 'none'),
(22, 'Anlala', 'grabeanlala@gmail.com', '$2y$10$SOwRk5qAQGwF7vKYoFbgMu/Itltm./VUCppCDB12fycJy7kyC9ZIG', 'fox', 0, '2026-10-07 20:28:14', 0, 'none'),
(23, 'Ryzen', 'bsbs@gmail.com', '$2y$10$4iCi/OiSlnqRese7AaSx..WMKWZoRt1oMj.YqTTMIxZipUK7iakGG', 'fox', 0, '2026-10-07 20:32:39', 0, 'none'),
(24, 'baymax', 'brera15@gmail.com', '$2y$10$buPfNpdkw2omZVSP6rPoo.jI9YCzaPjKoQVulWPy3j.9zjA7MC4aG', 'fox', 0, '2026-10-07 20:37:05', 0, 'none'),
(25, 'Beans', 'vinceno20@gmail.com', '$2y$10$g1RBNNc15qzZTMyQe/1UpOmsQ3Aze3oZaL/9yCfYApaWW7D2jWiIi', 'fox', 0, '2026-10-07 20:41:28', 0, 'none'),
(26, 'Mansory', 'minesori30@gmail.com', '$2y$10$.k5uNcEXt3Hon0IdhiEL5ua9jnyDyhjY3MEJObwsUdsofi57VcAq.', 'fox', 0, '2026-10-07 20:46:12', 0, 'none'),
(27, 'Pat', 'patricklim16@gmail.com', '$2y$10$ymepiNeOrQb8TTpJnyHXBeNubQaz/W4T5PM5pVfuYLGNS7bfVsq5K', 'fox', 0, '2026-10-07 20:50:37', 0, 'none'),
(28, 'Boy Puyat', 'boypuyat24@gmail.com', '$2y$10$MpFWf9Oor1g7s4/6gxitx.8bqvGXslDkhvVH2976b266VtkbeH6n2', 'fox', 0, '2026-10-07 20:55:03', 0, 'none'),
(29, 'G na G', 'gnagaming@gmail.com', '$2y$10$rJn8w0j8VfGbWGwULOiakerHcLzB5OT4gBtGs92G7rBsa3qz5K06.', 'fox', 0, '2026-10-07 20:59:46', 0, 'none'),
(30, 'Neneng B', 'manoulam@gmail.com', '$2y$10$Tn98ypKrNgw/ABItwxlq/ufioL9TCSs01bZ.nqtyRXUZOLh.rp79G', 'fox', 0, '2026-10-07 21:04:18', 0, 'none'),
(31, 'Ella Vated', 'ellavated17@gmail.com', '$2y$10$8YgRsfTLEQx4P4wC/P8wv.jpI3EN3hCNfghK1O69QWgHNSSMrvX8G', 'fox', 0, '2026-10-07 21:08:52', 0, 'none'),
(32, 'Sam Ting Wong', 'samting@gmail.com', '$2y$10$5k/KYJb9xCCy7eTt88dbdezBXtcdPfTLU2fBg6geBD5QykNe1PxmG', 'fox', 0, '2026-10-07 21:13:27', 0, 'none'),
(33, 'Dominguez', 'minguez05@gmail.com', '$2y$10$Li2.9yPYab9faiN7AFLjMuRDi2V29XyQUdxgl7sInGuUMQT1exE76', 'fox', 0, '2026-10-07 21:18:04', 0, 'none'),
(34, 'Mark', 'markmywords18@gmail.com', '$2y$10$YQXZzdTsBn3L9q/mk1IW1Of9fG4rsURXOFAtxpbjaxHAaUoOcxHla', 'fox', 0, '2026-10-07 21:22:41', 0, 'none'),
(35, 'Noah Idea', 'noahidea06@gmail.com', '$2y$10$g/BXKcCbysf2/jQ1I2NdnON3.nv40ilxfcroFRyqrLJeYjtvBtAGe', 'fox', 0, '2026-10-07 21:27:19', 0, 'none'),
(36, '000999', 'revenraintrinidad@gmail.com', '$2y$10$atLXwZqtADm9zh7p3og3Xu/2o8roURfYq3Ozsj8XbvH5lmCWd3zFy', 'fox', 0, '2026-10-11 22:04:10', 0, 'none'),
(37, 'ggg', 'ss.s@gm.com', '$2y$10$/8JdIVdS95rjjkklfqsG/.kuX18fS3F3aq2MhCiYz4poHiUM4c1T.', 'fox', 0, '2026-10-11 23:02:25', 0, 'none'),
(38, 'asdasds', 'asdasd@fa', '$2y$10$1nWW5u5HyurZmlnGBr/NE.C.NLGlchHglFJXYJXRpXNuM6iMbzJh2', 'fox', 0, '2026-10-11 23:05:52', 0, 'none'),
(39, 'Group20', 'g20@gmail.com', '$2y$10$Kvl2oO.5CuNguKFCrBFb1ewQsdTyAa5ZUZ5Gak3PLJUn1xQWAmM7e', 'fox', 0, '2026-10-12 15:52:53', 0, 'none'),
(40, 'asddasd', 'ddd@dd', '$2y$10$Ko9HiIuggBCDnuiK87L1DOBSuZCPVI8W5rrUhicVeyrEzlrtpPqKm', 'fox', 0, '2026-10-13 11:49:12', 0, 'none'),
(41, 'Guro', 'g@ro', '$2y$10$NUu8aDPtlC6ZdJQtnKE2gOe360JwBlGf6vtc0LwfpptO1BNNiMX6.', 'fox', 0, '2026-10-14 13:34:24', 1, 'approved'),
(42, 'teach', 'te@ch', '$2y$10$ZiKow.CnvCnYg2M4PEn5juuFis299llO83UvVmLcBgFqqYq7TO/Xa', 'fox', 0, '2026-10-14 15:53:41', 0, 'none'),
(43, 'tea@ch', 'tea@ch', '$2y$10$1bRjupw09HGGjcvXGapSLeMFwaGKGgbaWieZ6L5OfNJ1yUV2diOQ6', 'fox', 0, '2026-10-14 15:54:09', 1, 'approved');

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
(1, 2, 1, '2026-09-26 22:44:08'),
(2, 9, 1, '2026-10-10 21:50:50'),
(3, 37, 1, '2026-10-12 00:23:09'),
(4, 37, 4, '2026-10-12 00:26:59'),
(5, 37, 5, '2026-10-12 00:26:59'),
(6, 37, 9, '2026-10-12 00:26:59'),
(7, 2, 4, '2026-10-12 00:26:59'),
(8, 2, 9, '2026-10-12 00:26:59'),
(9, 39, 1, '2026-10-12 16:08:15'),
(10, 39, 4, '2026-10-12 16:08:15'),
(11, 2, 2, '2026-10-13 14:02:02'),
(12, 2, 5, '2026-10-14 15:04:08'),
(13, 39, 9, '2026-10-14 15:10:23');

-- --------------------------------------------------------

--
-- Table structure for table `user_module_progress`
--

CREATE TABLE `user_module_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module_key` varchar(50) NOT NULL,
  `completed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_module_progress`
--

INSERT INTO `user_module_progress` (`id`, `user_id`, `module_key`, `completed_at`) VALUES
(1, 2, 'binary_game', '2026-10-11 20:02:36'),
(5, 2, 'dissect_ip', '2026-10-11 20:28:06'),
(6, 2, 'hex_conversion', '2026-10-11 20:30:52'),
(7, 2, 'ipv6_game', '2026-10-11 20:33:18'),
(8, 2, 'subnet_showdown', '2026-10-11 20:34:24'),
(9, 39, 'binary_game', '2026-10-12 15:57:20'),
(12, 40, 'binary_game', '2026-10-13 11:49:29');

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
  `last_activity_date` date DEFAULT NULL,
  `last_daily_challenge_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_progress`
--

INSERT INTO `user_progress` (`user_id`, `level`, `career_xp`, `total_xp`, `xp_to_next_level`, `lessons_completed`, `quizzes_completed`, `total_correct`, `total_wrong`, `current_streak`, `last_activity_date`, `last_daily_challenge_date`) VALUES
(1, 1, 0, 0, 400, 0, 0, 0, 0, 0, NULL, NULL),
(2, 10, 11341, 45, 2368, 5, 17, 55, 102, 0, NULL, '2026-10-14'),
(3, 1, 0, 0, 400, 0, 0, 0, 0, 0, NULL, NULL),
(4, 1, 0, 0, 400, 0, 0, 0, 0, 0, NULL, NULL),
(5, 8, 8830, 1485, 1912, 0, 13, 0, 0, 0, NULL, NULL),
(6, 3, 1115, 10, 815, 0, 3, 0, 0, 0, NULL, NULL),
(7, 2, 600, 100, 605, 0, 1, 0, 0, 0, NULL, NULL),
(8, 3, 1280, 175, 815, 0, 2, 0, 0, 0, NULL, NULL),
(9, 2, 1850, 30, 605, 0, 8, 8, 2, 0, NULL, '2026-10-10'),
(10, 1, 720, 0, 500, 0, 4, 0, 0, 0, NULL, NULL),
(11, 1, 2450, 0, 500, 0, 12, 0, 0, 0, NULL, NULL),
(12, 1, 1320, 0, 500, 0, 6, 0, 0, 0, NULL, NULL),
(13, 1, 980, 0, 500, 0, 5, 0, 0, 0, NULL, NULL),
(14, 1, 2780, 0, 500, 0, 15, 0, 0, 0, NULL, NULL),
(15, 1, 1560, 0, 500, 0, 9, 0, 0, 0, NULL, NULL),
(16, 1, 2140, 0, 500, 0, 11, 0, 0, 0, NULL, NULL),
(17, 1, 640, 0, 500, 0, 3, 0, 0, 0, NULL, NULL),
(18, 1, 1920, 0, 500, 0, 10, 0, 0, 0, NULL, NULL),
(19, 1, 1120, 0, 500, 0, 6, 0, 0, 0, NULL, NULL),
(20, 1, 2650, 0, 500, 0, 14, 0, 0, 0, NULL, NULL),
(21, 1, 840, 0, 500, 0, 4, 0, 0, 0, NULL, NULL),
(22, 1, 1450, 0, 500, 0, 0, 0, 0, 0, NULL, NULL),
(23, 1, 920, 0, 500, 0, 0, 0, 0, 0, NULL, NULL),
(24, 1, 2680, 0, 500, 0, 0, 0, 0, 0, NULL, NULL),
(25, 1, 1170, 0, 500, 0, 0, 0, 0, 0, NULL, NULL),
(26, 1, 2350, 0, 500, 0, 0, 0, 0, 0, NULL, NULL),
(27, 1, 680, 0, 500, 0, 0, 0, 0, 0, NULL, NULL),
(28, 1, 1980, 0, 500, 0, 0, 0, 0, 0, NULL, NULL),
(29, 1, 1260, 0, 500, 0, 0, 0, 0, 0, NULL, NULL),
(30, 1, 2880, 0, 500, 0, 0, 0, 0, 0, NULL, NULL),
(31, 1, 890, 0, 500, 0, 0, 0, 0, 0, NULL, NULL),
(32, 1, 1740, 0, 500, 0, 0, 0, 0, 0, NULL, NULL),
(33, 1, 1050, 0, 500, 0, 0, 0, 0, 0, NULL, NULL),
(34, 1, 2250, 0, 500, 0, 0, 0, 0, 0, NULL, NULL),
(35, 1, 760, 0, 500, 0, 0, 0, 0, 0, NULL, NULL),
(36, 1, 0, 0, 500, 0, 0, 0, 0, 0, NULL, NULL),
(37, 11, 14481, 717, 2599, 0, 4, 3, 8, 0, NULL, NULL),
(38, 1, 0, 0, 500, 0, 0, 0, 0, 0, NULL, NULL),
(39, 6, 5202, 1009, 1465, 1, 7, 7, 15, 0, NULL, '2026-10-14'),
(40, 1, 0, 0, 500, 1, 0, 0, 0, 0, NULL, NULL),
(41, 1, 0, 0, 500, 0, 0, 0, 0, 0, NULL, NULL),
(42, 1, 0, 0, 500, 0, 0, 0, 0, 0, NULL, NULL),
(43, 1, 0, 0, 500, 0, 0, 0, 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_question_stats`
--

CREATE TABLE `user_question_stats` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `times_encountered` int(11) DEFAULT 1,
  `times_correct` int(11) DEFAULT 0,
  `last_answered_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_question_stats`
--

INSERT INTO `user_question_stats` (`id`, `user_id`, `question_id`, `times_encountered`, `times_correct`, `last_answered_at`) VALUES
(1, 2, 8, 2, 2, '2026-10-14 15:03:59'),
(2, 2, 2, 5, 1, '2026-10-14 15:05:36'),
(3, 2, 9, 2, 0, '2026-10-14 15:05:25'),
(5, 2, 3, 4, 1, '2026-10-14 15:04:06'),
(11, 2, 7, 2, 0, '2026-10-14 15:05:30'),
(12, 2, 6, 1, 0, '2026-10-14 15:04:55'),
(13, 2, 10, 1, 0, '2026-10-14 15:04:57'),
(14, 2, 1, 1, 0, '2026-10-14 15:05:06'),
(15, 2, 5, 1, 0, '2026-10-14 15:05:10'),
(17, 2, 11, 1, 1, '2026-10-14 15:05:21'),
(21, 39, 16, 2, 2, '2026-10-14 15:15:23'),
(22, 39, 21, 1, 0, '2026-10-14 15:15:21'),
(24, 39, 18, 1, 0, '2026-10-14 15:15:26'),
(25, 39, 31, 1, 0, '2026-10-14 15:15:41'),
(26, 39, 23, 1, 0, '2026-10-14 15:15:44'),
(27, 39, 19, 1, 1, '2026-10-14 15:15:49'),
(28, 39, 28, 1, 0, '2026-10-14 15:15:52'),
(29, 39, 17, 1, 0, '2026-10-14 15:15:54'),
(30, 39, 22, 1, 0, '2026-10-14 15:15:56'),
(31, 39, 29, 1, 0, '2026-10-14 15:15:57');

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
-- Indexes for table `game_questions`
--
ALTER TABLE `game_questions`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `match_id` (`match_id`),
  ADD KEY `idx_played_at` (`played_at`);

--
-- Indexes for table `teacher_applications`
--
ALTER TABLE `teacher_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `teacher_invitations`
--
ALTER TABLE `teacher_invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_student_invitation` (`teacher_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `teacher_students`
--
ALTER TABLE `teacher_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_student_pair` (`teacher_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

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
-- Indexes for table `user_module_progress`
--
ALTER TABLE `user_module_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_module` (`user_id`,`module_key`),
  ADD KEY `idx_completed_at` (`completed_at`);

--
-- Indexes for table `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_question_stats`
--
ALTER TABLE `user_question_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_question` (`user_id`,`question_id`),
  ADD KEY `question_id` (`question_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `badges`
--
ALTER TABLE `badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `game_questions`
--
ALTER TABLE `game_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `match_players`
--
ALTER TABLE `match_players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `match_questions`
--
ALTER TABLE `match_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `scores`
--
ALTER TABLE `scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=395;

--
-- AUTO_INCREMENT for table `teacher_applications`
--
ALTER TABLE `teacher_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `teacher_invitations`
--
ALTER TABLE `teacher_invitations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `teacher_students`
--
ALTER TABLE `teacher_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `user_badges`
--
ALTER TABLE `user_badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_module_progress`
--
ALTER TABLE `user_module_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `user_question_stats`
--
ALTER TABLE `user_question_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

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
-- Constraints for table `teacher_applications`
--
ALTER TABLE `teacher_applications`
  ADD CONSTRAINT `teacher_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_invitations`
--
ALTER TABLE `teacher_invitations`
  ADD CONSTRAINT `teacher_invitations_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_invitations_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_students`
--
ALTER TABLE `teacher_students`
  ADD CONSTRAINT `teacher_students_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_students_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD CONSTRAINT `user_badges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_badges_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`);

--
-- Constraints for table `user_module_progress`
--
ALTER TABLE `user_module_progress`
  ADD CONSTRAINT `user_module_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_progress`
--
ALTER TABLE `user_progress`
  ADD CONSTRAINT `user_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_question_stats`
--
ALTER TABLE `user_question_stats`
  ADD CONSTRAINT `user_question_stats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_question_stats_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `game_questions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
