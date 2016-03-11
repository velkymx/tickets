-- Adminer 4.2.4 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

CREATE DATABASE `tickets` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci */;
USE `tickets`;

CREATE TABLE `importances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `importances` (`id`, `name`) VALUES
(1,	'trivial'),
(2,	'minor'),
(3,	'major'),
(4,	'critical'),
(5,	'blocker');

CREATE TABLE `migrations` (
  `migration` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`) VALUES
('2014_10_12_000000_create_users_table',	1),
('2014_10_12_100000_create_password_resets_table',	1);

CREATE TABLE `milestones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `active` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `milestones` (`id`, `name`, `active`) VALUES
(1,	'Unreviewed',	1),
(2,	'Future Backlog',	1),
(3,	'Backlog',	1),
(4,	'Scheduled',	1);

CREATE TABLE `notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `body` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `hide` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`),
  CONSTRAINT `notes_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `password_resets_email_index` (`email`),
  KEY `password_resets_token_index` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `active` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `projects` (`id`, `name`, `description`, `active`) VALUES
(1,	'Unassigned',	NULL,	1);

CREATE TABLE `sprints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_start` datetime NOT NULL,
  `date_end` datetime NOT NULL,
  `date_completed` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `statuses` (`id`, `name`) VALUES
(1,	'new'),
(2,	'active'),
(3,	'testing'),
(4,	'ready to deploy'),
(5,	'completed'),
(6,	'waiting'),
(7,	'reopened'),
(8,	'duplicte'),
(9,	'declined');

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `importance_id` int(11) NOT NULL,
  `milestone_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `due_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  `closed_at` datetime DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL COMMENT 'Creator',
  `user_id2` int(10) unsigned NOT NULL COMMENT 'Assigned',
  PRIMARY KEY (`id`),
  KEY `importance_id` (`importance_id`),
  KEY `milestone_id` (`milestone_id`),
  KEY `project_id` (`project_id`),
  KEY `status_id` (`status_id`),
  KEY `type_id` (`type_id`),
  KEY `user_id` (`user_id`),
  KEY `user_id2` (`user_id2`),
  CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`importance_id`) REFERENCES `importances` (`id`),
  CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`milestone_id`) REFERENCES `milestones` (`id`),
  CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `tickets_ibfk_4` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`),
  CONSTRAINT `tickets_ibfk_5` FOREIGN KEY (`type_id`) REFERENCES `types` (`id`),
  CONSTRAINT `tickets_ibfk_6` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `tickets_ibfk_7` FOREIGN KEY (`user_id2`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `types` (`id`, `name`) VALUES
(1,	'bug'),
(2,	'enhancement'),
(3,	'task'),
(4,	'proposal');

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `watchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `ticket_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `watchers_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `watchers_ibfk_4` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- 2016-03-11 16:01:30
