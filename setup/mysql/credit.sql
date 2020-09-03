DROP TABLE IF EXISTS `credit`;
CREATE TABLE `credit` (
  `project_id` int(11) unsigned NOT NULL,
  `credit` VARCHAR(255) NOT NULL,
  `percent_complete` tinyint(3) unsigned DEFAULT 0,
  UNIQUE KEY `credit_id` (`credit`, `project_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;