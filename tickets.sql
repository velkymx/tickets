-- Adminer 4.2.4 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

INSERT INTO `importances` (`id`, `name`) VALUES
(1,	'trivial'),
(2,	'minor'),
(3,	'major'),
(4,	'critical'),
(5,	'blocker');

INSERT INTO `milestones` (`id`, `name`, `active`) VALUES
(1,	'Unreviewed',	1),
(2,	'Future Backlog',	1),
(3,	'Backlog',	1),
(4,	'Scheduled',	1);

INSERT INTO `projects` (`id`, `name`, `description`, `active`) VALUES
(1,	'Unassigned',	NULL,	1);

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

INSERT INTO `types` (`id`, `name`) VALUES
(1,	'bug'),
(2,	'enhancement'),
(3,	'task'),
(4,	'proposal');

-- 2016-03-10 22:45:13
