-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 06, 2025 at 03:29 AM
-- Server version: 9.1.0
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `premiergolfleaguetracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

DROP TABLE IF EXISTS `address`;
CREATE TABLE IF NOT EXISTS `address` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `addressLine1` varchar(255) DEFAULT NULL,
  `addressLine2` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `postalCode` varchar(255) DEFAULT NULL,
  `version` int DEFAULT '1',
  `region_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_address_region_id` (`region_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `country`
--

DROP TABLE IF EXISTS `country`;
CREATE TABLE IF NOT EXISTS `country` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `version` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

DROP TABLE IF EXISTS `course`;
CREATE TABLE IF NOT EXISTS `course` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `version` int DEFAULT '1',
  `website` varchar(255) DEFAULT NULL,
  `address_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_169E6FB9F5B7AF75` (`address_id`),
  KEY `fk_course_address_id` (`address_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `email`
--

DROP TABLE IF EXISTS `email`;
CREATE TABLE IF NOT EXISTS `email` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `address` varchar(255) DEFAULT NULL,
  `type` int DEFAULT NULL,
  `version` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

DROP TABLE IF EXISTS `event`;
CREATE TABLE IF NOT EXISTS `event` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `course_id` bigint DEFAULT NULL,
  `eventtype` smallint DEFAULT NULL,
  `format` smallint DEFAULT NULL,
  `eventnumber` smallint DEFAULT NULL,
  `playersperteam` int UNSIGNED NOT NULL DEFAULT '2',
  `startdateandtime` datetime DEFAULT NULL,
  `version` int DEFAULT '1',
  `nine_id` bigint DEFAULT NULL,
  `session_id` bigint DEFAULT NULL,
  `tee_id` bigint DEFAULT NULL,
  `secondnine_id` bigint DEFAULT NULL,
  `teamspergame` int UNSIGNED NOT NULL DEFAULT '2',
  `withhandicapping` tinyint(1) DEFAULT '1',
  `mixedteesenabled` tinyint(1) DEFAULT '0',
  `minutesbetweengames` tinyint(1) NOT NULL DEFAULT '8',
  PRIMARY KEY (`id`),
  KEY `fk_event_course_id` (`course_id`),
  KEY `fk_event_nine_id` (`nine_id`),
  KEY `fk_event_tee_id` (`tee_id`),
  KEY `fk_event_session_id` (`session_id`),
  KEY `fk_event_secondnine_id` (`secondnine_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `event_registrants`
--

DROP TABLE IF EXISTS `event_registrants`;
CREATE TABLE IF NOT EXISTS `event_registrants` (
  `event_id` bigint NOT NULL,
  `player_id` bigint NOT NULL,
  PRIMARY KEY (`event_id`,`player_id`),
  UNIQUE KEY `event_registrants_player_id_unique` (`player_id`),
  KEY `registrant_events` (`event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `fullname`
--

DROP TABLE IF EXISTS `fullname`;
CREATE TABLE IF NOT EXISTS `fullname` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `firstName` varchar(255) DEFAULT NULL,
  `generation` varchar(255) DEFAULT NULL,
  `lastName` varchar(255) DEFAULT NULL,
  `middleNameOrInitial` varchar(255) DEFAULT NULL,
  `version` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `game`
--

DROP TABLE IF EXISTS `game`;
CREATE TABLE IF NOT EXISTS `game` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `recorded` tinyint(1) DEFAULT '0',
  `format` tinyint(1) DEFAULT NULL,
  `startingtime` time DEFAULT NULL,
  `version` int DEFAULT '1',
  `event_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_game_event_id` (`event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `game_players`
--

DROP TABLE IF EXISTS `game_players`;
CREATE TABLE IF NOT EXISTS `game_players` (
  `game_id` bigint NOT NULL,
  `player_id` bigint NOT NULL,
  PRIMARY KEY (`game_id`,`player_id`),
  KEY `game_players_player_id_index` (`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `game_scores`
--

DROP TABLE IF EXISTS `game_scores`;
CREATE TABLE IF NOT EXISTS `game_scores` (
  `game_id` bigint NOT NULL,
  `score_id` bigint NOT NULL,
  PRIMARY KEY (`game_id`,`score_id`),
  UNIQUE KEY `game_scores_score_id_unique` (`score_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `hole`
--

DROP TABLE IF EXISTS `hole`;
CREATE TABLE IF NOT EXISTS `hole` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `handicap` int DEFAULT NULL,
  `holenumber` int DEFAULT NULL,
  `length` int DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `par` int DEFAULT NULL,
  `version` int DEFAULT '1',
  `tee_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_hole_tee_id` (`tee_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `league`
--

DROP TABLE IF EXISTS `league`;
CREATE TABLE IF NOT EXISTS `league` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `version` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `league_courses`
--

DROP TABLE IF EXISTS `league_courses`;
CREATE TABLE IF NOT EXISTS `league_courses` (
  `league_id` bigint NOT NULL,
  `course_id` bigint NOT NULL,
  PRIMARY KEY (`league_id`,`course_id`),
  KEY `course_id` (`course_id`),
  KEY `IDX_100C433D58AFC4DE` (`league_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `nine`
--

DROP TABLE IF EXISTS `nine`;
CREATE TABLE IF NOT EXISTS `nine` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `version` int DEFAULT '1',
  `course_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_nine_course_id` (`course_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `phonenumber`
--

DROP TABLE IF EXISTS `phonenumber`;
CREATE TABLE IF NOT EXISTS `phonenumber` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `number` varchar(255) DEFAULT NULL,
  `type` int DEFAULT NULL,
  `version` int DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `photo`
--

DROP TABLE IF EXISTS `photo`;
CREATE TABLE IF NOT EXISTS `photo` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `version` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `player`
--

DROP TABLE IF EXISTS `player`;
CREATE TABLE IF NOT EXISTS `player` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `playernumber` int DEFAULT NULL,
  `defunct` tinyint(1) DEFAULT '0',
  `seedhandicapindex` float DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `version` int NOT NULL DEFAULT '1',
  `league_id` bigint DEFAULT NULL,
  `address_id` bigint DEFAULT NULL,
  `name_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_98197A65F5B7AF75` (`address_id`),
  UNIQUE KEY `UNIQ_98197A6571179CD6` (`name_id`),
  KEY `fk_player_league_id` (`league_id`),
  KEY `fk_player_name_id` (`name_id`),
  KEY `fk_player_address_id` (`address_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `playermatch`
--

DROP TABLE IF EXISTS `playermatch`;
CREATE TABLE IF NOT EXISTS `playermatch` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `version` int DEFAULT '1',
  `playerone_id` bigint DEFAULT NULL,
  `playertwo_id` bigint DEFAULT NULL,
  `game_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_playermatch_playertwo_id` (`playertwo_id`),
  KEY `fk_playermatch_playerone_id` (`playerone_id`),
  KEY `fk_playermatch_game_id` (`game_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `playermatch_scores`
--

DROP TABLE IF EXISTS `playermatch_scores`;
CREATE TABLE IF NOT EXISTS `playermatch_scores` (
  `match_id` bigint NOT NULL,
  `score_id` bigint DEFAULT NULL,
  UNIQUE KEY `UNIQ_A0B5953412EB0A51` (`score_id`),
  KEY `FK_A0B595342ABEACD6` (`match_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `player_emails`
--

DROP TABLE IF EXISTS `player_emails`;
CREATE TABLE IF NOT EXISTS `player_emails` (
  `player_id` bigint NOT NULL,
  `email_id` bigint NOT NULL,
  PRIMARY KEY (`player_id`,`email_id`),
  UNIQUE KEY `UNIQ_D3817E8CA832C1C9` (`email_id`),
  KEY `IDX_D3817E8C99E6F5DF` (`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `player_phonenumbers`
--

DROP TABLE IF EXISTS `player_phonenumbers`;
CREATE TABLE IF NOT EXISTS `player_phonenumbers` (
  `player_id` bigint NOT NULL,
  `phonenumber_id` bigint NOT NULL,
  PRIMARY KEY (`player_id`,`phonenumber_id`),
  UNIQUE KEY `UNIQ_F301DE57D626887C` (`phonenumber_id`),
  KEY `IDX_F301DE5799E6F5DF` (`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `region`
--

DROP TABLE IF EXISTS `region`;
CREATE TABLE IF NOT EXISTS `region` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `code` varchar(2) NOT NULL,
  `name` varchar(40) NOT NULL,
  `version` int NOT NULL DEFAULT '1',
  `country_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_region_country_id` (`country_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `score`
--

DROP TABLE IF EXISTS `score`;
CREATE TABLE IF NOT EXISTS `score` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `adjustedstrokes` varchar(9) DEFAULT NULL,
  `currenthandicapIndex` float DEFAULT NULL,
  `duplicatescore` tinyint(1) DEFAULT '0',
  `partialscore` tinyint(1) DEFAULT '0',
  `fairwayshit` varchar(9) DEFAULT NULL,
  `girs` varchar(9) DEFAULT NULL,
  `handicapdifferential` float DEFAULT NULL,
  `putts` varchar(9) DEFAULT NULL,
  `strokes` varchar(9) DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  `timezone` varchar(255) DEFAULT NULL,
  `upanddowns` varchar(9) DEFAULT NULL,
  `version` int DEFAULT '1',
  `player_id` bigint DEFAULT NULL,
  `tee_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_score_player_id` (`player_id`),
  KEY `fk_score_tee_id` (`tee_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `season`
--

DROP TABLE IF EXISTS `season`;
CREATE TABLE IF NOT EXISTS `season` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `enddate` date DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `startdate` date DEFAULT NULL,
  `version` int DEFAULT '1',
  `league_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_season_league_id` (`league_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `sequence`
--

DROP TABLE IF EXISTS `sequence`;
CREATE TABLE IF NOT EXISTS `sequence` (
  `SEQ_NAME` varchar(50) NOT NULL,
  `SEQ_COUNT` decimal(38,0) DEFAULT NULL,
  PRIMARY KEY (`SEQ_NAME`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sequence`
--

INSERT INTO `sequence` (`SEQ_NAME`, `SEQ_COUNT`) VALUES
('SEQ_GEN', 1);

-- --------------------------------------------------------

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
CREATE TABLE IF NOT EXISTS `session` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `enddate` date DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `startdate` date DEFAULT NULL,
  `version` int NOT NULL DEFAULT '1',
  `season_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_session_season_id` (`season_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `team`
--

DROP TABLE IF EXISTS `team`;
CREATE TABLE IF NOT EXISTS `team` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `teamnumber` int DEFAULT NULL,
  `defunct` tinyint(1) DEFAULT '0',
  `version` int DEFAULT '1',
  `league_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_team_league_id` (`league_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `teamgame`
--

DROP TABLE IF EXISTS `teamgame`;
CREATE TABLE IF NOT EXISTS `teamgame` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `recorded` tinyint(1) DEFAULT '0',
  `startingtime` time DEFAULT NULL,
  `teamone` varchar(255) DEFAULT NULL,
  `teamonescore` varchar(18) DEFAULT NULL,
  `teamtwo` varchar(255) DEFAULT NULL,
  `teamtwoscore` varchar(18) DEFAULT NULL,
  `version` int DEFAULT '1',
  `event_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_teamgame_event_id` (`event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `teamgameplayer`
--

DROP TABLE IF EXISTS `teamgameplayer`;
CREATE TABLE IF NOT EXISTS `teamgameplayer` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `player_id` bigint DEFAULT NULL,
  `teamnumber` int DEFAULT NULL,
  `playerscore` varchar(18) DEFAULT NULL,
  `version` int DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_player_id` (`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `teamgame_players`
--

DROP TABLE IF EXISTS `teamgame_players`;
CREATE TABLE IF NOT EXISTS `teamgame_players` (
  `teamgame_id` bigint NOT NULL,
  `player_id` bigint NOT NULL,
  PRIMARY KEY (`teamgame_id`,`player_id`),
  KEY `player_id` (`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `teammatch`
--

DROP TABLE IF EXISTS `teammatch`;
CREATE TABLE IF NOT EXISTS `teammatch` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `version` int DEFAULT NULL,
  `teamone_id` bigint DEFAULT NULL,
  `teamtwo_id` bigint DEFAULT NULL,
  `game_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_teammatch_teamtwo_id` (`teamtwo_id`),
  KEY `fk_teammatch_teamone_id` (`teamone_id`),
  KEY `fk_teammatch_game_id` (`game_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `team_players`
--

DROP TABLE IF EXISTS `team_players`;
CREATE TABLE IF NOT EXISTS `team_players` (
  `team_id` bigint NOT NULL,
  `player_id` bigint NOT NULL,
  PRIMARY KEY (`team_id`,`player_id`),
  KEY `player_id` (`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tee`
--

DROP TABLE IF EXISTS `tee`;
CREATE TABLE IF NOT EXISTS `tee` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `length` int DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `par` int DEFAULT NULL,
  `rating` float DEFAULT NULL,
  `slope` int DEFAULT NULL,
  `version` int DEFAULT '1',
  `nine_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_tee_nine_id` (`nine_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `username` varchar(180) NOT NULL,
  `league_id` bigint DEFAULT NULL,
  `roles` json NOT NULL,
  `password` varchar(255) NOT NULL,
  `version` int DEFAULT '1',
  `api_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_username` (`username`),
  UNIQUE KEY `unique_api_tokem` (`api_token`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
