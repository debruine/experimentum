DROP TABLE IF EXISTS `exp`;
CREATE TABLE `exp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT 'Experiment',
  `url` varchar(255) DEFAULT NULL,
  `exptype` enum('2afc','jnd','rating','buttons','xafc','sort','nback','interactive','motivation','other') DEFAULT NULL,
  `subtype` enum('standard','adapt','speeded','adapt_nopre','large_n') DEFAULT NULL,
  `design` enum('between','within') DEFAULT NULL,
  `trial_order` enum('random','norepeat','fixed') DEFAULT 'random',
  `side` enum('random','fixed') DEFAULT 'random',
  `status` enum('active','inactive','test') DEFAULT 'test',
  `instructions` text DEFAULT NULL,
  `question` varchar(255) DEFAULT NULL,
  `label1` varchar(255) DEFAULT NULL,
  `label2` varchar(255) DEFAULT NULL,
  `label3` varchar(255) DEFAULT NULL,
  `label4` varchar(255) DEFAULT NULL,
  `range` tinyint(3) unsigned DEFAULT NULL,
  `low_anchor` varchar(255) DEFAULT NULL,
  `high_anchor` varchar(255) DEFAULT NULL,
  `feedback_query` text DEFAULT NULL,
  `feedback_specific` text DEFAULT NULL,
  `feedback_general` text DEFAULT NULL,
  `labnotes` text DEFAULT NULL,
  `total_people` int(4) DEFAULT NULL,
  `total_men` int(4) DEFAULT NULL,
  `total_women` int(4) DEFAULT NULL,
  `lower_age` tinyint(2) unsigned DEFAULT NULL,
  `upper_age` tinyint(2) unsigned DEFAULT NULL,
  `create_date` datetime DEFAULT NULL,
  `password` varchar(32) DEFAULT NULL,
  `blurb` varchar(255) DEFAULT NULL,
  `res_name` varchar(255) DEFAULT NULL,
  `orient` enum('horiz','vert') DEFAULT 'horiz',
  `randomx` int(4) unsigned DEFAULT NULL,
  `total_fempref` int(4) DEFAULT NULL,
  `total_malepref` int(4) DEFAULT NULL,
  `forward` varchar(255) DEFAULT NULL,
  `chart_id` int(11) DEFAULT NULL,
  `default_time` int(6) unsigned DEFAULT NULL,
  `increment_time` int(6) unsigned DEFAULT NULL,
  `rating_range` tinyint(3) unsigned DEFAULT NULL,
  `sex` enum('both','male','female') DEFAULT 'both',
  `sexpref` enum('men','women','either') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `quest`;
CREATE TABLE `quest` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT 'Questionnaire',
  `url` varchar(255) DEFAULT NULL,
  `questtype` enum('mixed','radiopage','ranking') DEFAULT NULL,
  `quest_order` enum('fixed','random') DEFAULT 'fixed',
  `status` enum('active','inactive','test') DEFAULT 'test',
  `instructions` text DEFAULT NULL,
  `feedback_query` text DEFAULT NULL,
  `feedback_specific` text DEFAULT NULL,
  `feedback_general` text DEFAULT NULL,
  `labnotes` text DEFAULT NULL,
  `sex` enum('both','male','female') DEFAULT 'both',
  `lower_age` tinyint(2) unsigned DEFAULT NULL,
  `upper_age` tinyint(2) unsigned DEFAULT NULL,
  `create_date` datetime DEFAULT NULL,
  `password` varchar(32) DEFAULT NULL,
  `blurb` varchar(255) DEFAULT NULL,
  `res_name` varchar(255) DEFAULT NULL,
  `custom_record` enum('false','true') DEFAULT 'false',
  `forward` varchar(255) DEFAULT NULL,
  `chart_id` int(11) DEFAULT NULL,
  `sexpref` enum('men','women','either') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `question`;
CREATE TABLE `question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quest_id` int(11) NOT NULL DEFAULT 0,
  `n` int(3) NOT NULL DEFAULT 0,
  `name` varchar(32) DEFAULT NULL,
  `question` text DEFAULT NULL,
  `type` enum('radio','select','selectnum','datemenu','text','radiorow','radiorev','radioanchor','ranking','countries') DEFAULT NULL,
  `startnum` smallint(4) unsigned DEFAULT NULL,
  `endnum` smallint(4) unsigned DEFAULT NULL,
  `maxlength` tinyint(3) unsigned DEFAULT NULL,
  `include_path` varchar(50) DEFAULT NULL,
  `low_anchor` varchar(255) DEFAULT NULL,
  `high_anchor` varchar(255) DEFAULT NULL,
  `hidesex` enum('male','female') DEFAULT NULL,
  `sensitiv` enum('no','yes') DEFAULT 'no',
  `connect` enum('first','middle','last') DEFAULT NULL,
  `custom` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quest_id` (`quest_id`,`n`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `res_name` varchar(255) DEFAULT NULL,
  `url` varchar(50) DEFAULT NULL,
  `intro` text DEFAULT NULL,
  `status` enum('active','inactive','test') DEFAULT 'test',
  `labnotes` text DEFAULT NULL,
  `create_date` date DEFAULT NULL,
  `sex` enum('both','male','female') DEFAULT 'both',
  `lower_age` tinyint(2) unsigned DEFAULT NULL,
  `upper_age` tinyint(2) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `sets`;
CREATE TABLE `sets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `res_name` varchar(64) DEFAULT NULL,
  `status` enum('active','inactive','test','lab') DEFAULT NULL,
  `create_date` datetime DEFAULT NULL,
  `labnotes` text DEFAULT NULL,
  `type` enum('fixed','random','one_equal','one_random') DEFAULT 'one_equal',
  `sex` enum('both','male','female') DEFAULT 'both',
  `lower_age` tinyint(2) unsigned DEFAULT NULL,
  `upper_age` tinyint(2) unsigned DEFAULT NULL,
  `feedback_query` text DEFAULT NULL,
  `feedback_specific` text DEFAULT NULL,
  `feedback_general` text DEFAULT NULL,
  `forward` varchar(255) DEFAULT NULL,
  `chart_id` int(11) DEFAULT NULL,
  `sexpref` enum('men','women','either') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `set_exp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `set_exp` (
  `set_id` int(11) DEFAULT NULL,
  `exp_id` int(11) DEFAULT NULL,
  `type` enum('exp','quest','econ','set') DEFAULT 'exp',
  UNIQUE KEY `set_id` (`set_id`,`exp_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `set_items`;
CREATE TABLE `set_items` (
  `set_id` int(11) DEFAULT NULL,
  `item_type` enum('exp','quest','econ','set') DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `item_n` int(4) DEFAULT NULL,
  KEY `set_id` (`set_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `trial`;
CREATE TABLE `trial` (
  `exp_id` int(11) NOT NULL DEFAULT 0,
  `trial_n` int(3) NOT NULL DEFAULT 0,
  `name` varchar(32) DEFAULT NULL,
  `left_img` int(11) DEFAULT NULL,
  `center_img` int(11) DEFAULT NULL,
  `right_img` int(11) DEFAULT NULL,
  `question` varchar(255) DEFAULT NULL,
  `label1` varchar(255) DEFAULT NULL,
  `label2` varchar(255) DEFAULT NULL,
  `label3` varchar(255) DEFAULT NULL,
  `label4` varchar(255) DEFAULT NULL,
  `q_image` int(11) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`exp_id`,`trial_n`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `xafc`;
CREATE TABLE `xafc` (
  `exp_id` int(11) NOT NULL DEFAULT 0,
  `trial_n` int(3) NOT NULL DEFAULT 0,
  `n` tinyint(2) unsigned NOT NULL DEFAULT 0,
  `image` int(11) DEFAULT NULL,
  PRIMARY KEY (`exp_id`,`trial_n`,`n`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `radiorow_options`;
CREATE TABLE `radiorow_options` (
  `quest_id` int(11) NOT NULL DEFAULT 0,
  `opt_order` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `opt_value` int(3) DEFAULT NULL,
  `display` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`quest_id`,`opt_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `project_items`;
CREATE TABLE `project_items` (
  `project_id` int(11) DEFAULT NULL,
  `item_type` enum('exp','quest','econ','set') DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `item_n` int(3) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  KEY `project_id` (`project_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `options`;
CREATE TABLE `options` (
  `q_id` int(11) NOT NULL DEFAULT 0,
  `opt_order` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `opt_value` int(3) DEFAULT NULL,
  `display` varchar(255) DEFAULT NULL,
  `quest_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`q_id`,`opt_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `buttons`;
CREATE TABLE `buttons` (
  `exp_id` int(11) NOT NULL AUTO_INCREMENT,
  `dv` int(3) NOT NULL DEFAULT 0,
  `display` varchar(255) DEFAULT NULL,
  `n` int(3) DEFAULT NULL,
  UNIQUE KEY `all_4` (`exp_id`,`dv`,`display`,`n`),
  KEY `exp_id` (`exp_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `images`;
DROP TABLE IF EXISTS `stimuli`;
CREATE TABLE stimuli (
    `id` int(11) auto_increment NOT NULL,
    `path` varchar(255),
    `type` enum('image','audio','video'),
    `size` int(10),
    `description` text,
    PRIMARY KEY (id),
    UNIQUE KEY (path)
);

DROP TABLE IF EXISTS `yoke`;
CREATE TABLE yoke (
    `user_id` int(11),
    `type` enum('exp','quest'),
    `id` int(11),
    `self` varchar(255),
    `other` varchar(255)
);

DROP TABLE IF EXISTS `versions`;
CREATE TABLE versions(
    `exp_id`    int(11),
    `version`   tinyint(1) unsigned,
    `name`      varchar(32),
    `notes`     text,
    `question`  text 
);

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(32) DEFAULT NULL,
  `regdate` datetime DEFAULT NULL,
  `sex` enum('male','female','nonbinary', 'na') DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `pquestion` varchar(100) DEFAULT NULL,
  `panswer` varchar(100) DEFAULT NULL,
  `status` enum('test','guest','registered','student','researcher','admin') DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `researcher`;
CREATE TABLE `researcher` (
  `user_id` int(11),
  `firstname` varchar(50) DEFAULT NULL,
  `initials` char(3) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `institution` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `login`;
CREATE TABLE `login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `logintime` datetime DEFAULT NULL,
  `logoutime` datetime DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `browser` varchar(255) DEFAULT NULL,
  `referer` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM AUTO_INCREMENT=770419 DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `access`;
CREATE TABLE `access` (
  `type` enum('exp','quest','econ','chain','file','project','sets') NOT NULL DEFAULT 'exp',
  `id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`type`,`id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `dashboard`;
CREATE TABLE `dashboard` (
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `type` enum('query','exp','quest','econ','set','project','chart') DEFAULT NULL,
  `dt` datetime DEFAULT NULL,
  UNIQUE KEY `user_id` (`user_id`,`type`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `countries` (
  `id` char(2) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `countries` WRITE;
INSERT INTO `countries` VALUES 
('AF','Afghanistan'),
('AX','Ã…land Islands'),
('AL','Albania'),
('DZ','Algeria'),
('AS','American Samoa'),
('AD','Andorra'),
('AO','Angola'),
('AI','Anguilla'),
('AQ','Antarctica'),
('AG','Antigua and Barbuda'),
('AR','Argentina'),
('AM','Armenia'),
('AW','Aruba'),
('AU','Australia'),
('AT','Austria'),
('AZ','Azerbaijan'),
('BS','Bahamas'),
('BH','Bahrain'),
('BD','Bangladesh'),
('BB','Barbados'),
('BY','Belarus'),
('BE','Belgium'),
('BZ','Belize'),
('BJ','Benin'),
('BM','Bermuda'),
('BT','Bhutan'),
('BO','Bolivia (Plurinational State of)'),
('BQ','Bonaire, Sint Eustatius and Saba'),
('BA','Bosnia and Herzegovina'),
('BW','Botswana'),
('BV','Bouvet Island'),
('BR','Brazil'),
('IO','British Indian Ocean Territory'),
('BN','Brunei Darussalam'),
('BG','Bulgaria'),
('BF','Burkina Faso'),
('BI','Burundi'),
('CV','Cabo Verde'),
('KH','Cambodia'),
('CM','Cameroon'),
('CA','Canada'),
('KY','Cayman Islands'),
('CF','Central African Republic'),
('TD','Chad'),
('CL','Chile'),
('CN','China'),
('CX','Christmas Island'),
('CC','Cocos (Keeling) Islands'),
('CO','Colombia'),
('KM','Comoros'),
('CG','Congo'),
('CD','Congo (Democratic Republic of the)'),
('CK','Cook Islands'),
('CR','Costa Rica'),
('CI','CÃ´te d\'Ivoire'),
('HR','Croatia'),
('CU','Cuba'),
('CW','CuraÃ§ao'),
('CY','Cyprus'),
('CZ','Czechia'),
('DK','Denmark'),
('DJ','Djibouti'),
('DM','Dominica'),
('DO','Dominican Republic'),
('EC','Ecuador'),
('EG','Egypt'),
('SV','El Salvador'),
('GQ','Equatorial Guinea'),
('ER','Eritrea'),
('EE','Estonia'),
('ET','Ethiopia'),
('FK','Falkland Islands (Malvinas)'),
('FO','Faroe Islands'),
('FJ','Fiji'),
('FI','Finland'),
('FR','France'),
('GF','French Guiana'),
('PF','French Polynesia'),
('TF','French Southern Territories'),
('GA','Gabon'),
('GM','Gambia'),
('GE','Georgia'),
('DE','Germany'),
('GH','Ghana'),
('GI','Gibraltar'),
('GR','Greece'),
('GL','Greenland'),
('GD','Grenada'),
('GP','Guadeloupe'),
('GU','Guam'),
('GT','Guatemala'),
('GG','Guernsey'),
('GN','Guinea'),
('GW','Guinea-Bissau'),
('GY','Guyana'),
('HT','Haiti'),
('HM','Heard Island and McDonald Islands'),
('VA','Holy See'),
('HN','Honduras'),
('HK','Hong Kong'),
('HU','Hungary'),
('IS','Iceland'),
('IN','India'),
('ID','Indonesia'),
('IR','Iran (Islamic Republic of)'),
('IQ','Iraq'),
('IE','Ireland'),
('IM','Isle of Man'),
('IL','Israel'),
('IT','Italy'),
('JM','Jamaica'),
('JP','Japan'),
('JE','Jersey'),
('JO','Jordan'),
('KZ','Kazakhstan'),
('KE','Kenya'),
('KI','Kiribati'),
('KP','Korea (Democratic People\'s Republic of)'),
('KR','Korea (Republic of)'),
('KW','Kuwait'),
('KG','Kyrgyzstan'),
('LA','Lao People\'s Democratic Republic'),
('LV','Latvia'),
('LB','Lebanon'),
('LS','Lesotho'),
('LR','Liberia'),
('LY','Libya'),
('LI','Liechtenstein'),
('LT','Lithuania'),
('LU','Luxembourg'),
('MO','Macao'),
('MK','Macedonia (the former Yugoslav Republic of)'),
('MG','Madagascar'),
('MW','Malawi'),
('MY','Malaysia'),
('MV','Maldives'),
('ML','Mali'),
('MT','Malta'),
('MH','Marshall Islands'),
('MQ','Martinique'),
('MR','Mauritania'),
('MU','Mauritius'),
('YT','Mayotte'),
('MX','Mexico'),
('FM','Micronesia (Federated States of)'),
('MD','Moldova (Republic of)'),
('MC','Monaco'),
('MN','Mongolia'),
('ME','Montenegro'),
('MS','Montserrat'),
('MA','Morocco'),
('MZ','Mozambique'),
('MM','Myanmar'),
('NA','Namibia'),
('NR','Nauru'),
('NP','Nepal'),
('NL','Netherlands'),
('NC','New Caledonia'),
('NZ','New Zealand'),
('NI','Nicaragua'),
('NE','Niger'),
('NG','Nigeria'),
('NU','Niue'),
('NF','Norfolk Island'),
('MP','Northern Mariana Islands'),
('NO','Norway'),
('OM','Oman'),
('PK','Pakistan'),
('PW','Palau'),
('PS','Palestine, State of'),
('PA','Panama'),
('PG','Papua New Guinea'),
('PY','Paraguay'),
('PE','Peru'),
('PH','Philippines'),
('PN','Pitcairn'),
('PL','Poland'),
('PT','Portugal'),
('PR','Puerto Rico'),
('QA','Qatar'),
('RE','RÃ©union'),
('RO','Romania'),
('RU','Russian Federation'),
('RW','Rwanda'),
('BL','Saint BarthÃ©lemy'),
('SH','Saint Helena, Ascension and Tristan da Cunha'),
('KN','Saint Kitts and Nevis'),
('LC','Saint Lucia'),
('MF','Saint Martin (French part)'),
('PM','Saint Pierre and Miquelon'),
('VC','Saint Vincent and the Grenadines'),
('WS','Samoa'),
('SM','San Marino'),
('ST','Sao Tome and Principe'),
('SA','Saudi Arabia'),
('SN','Senegal'),
('RS','Serbia'),
('SC','Seychelles'),
('SL','Sierra Leone'),
('SG','Singapore'),
('SX','Sint Maarten (Dutch part)'),
('SK','Slovakia'),
('SI','Slovenia'),
('SB','Solomon Islands'),
('SO','Somalia'),
('ZA','South Africa'),
('GS','South Georgia and the South Sandwich Islands'),
('SS','South Sudan'),
('ES','Spain'),
('LK','Sri Lanka'),
('SD','Sudan'),
('SR','Suriname'),
('SJ','Svalbard and Jan Mayen'),
('SZ','Swaziland'),
('SE','Sweden'),
('CH','Switzerland'),
('SY','Syrian Arab Republic'),
('TW','Taiwan, Province of China'),
('TJ','Tajikistan'),
('TZ','Tanzania, United Republic of'),
('TH','Thailand'),
('TL','Timor-Leste'),
('TG','Togo'),
('TK','Tokelau'),
('TO','Tonga'),
('TT','Trinidad and Tobago'),
('TN','Tunisia'),
('TR','Turkey'),
('TM','Turkmenistan'),
('TC','Turks and Caicos Islands'),
('TV','Tuvalu'),
('UG','Uganda'),
('UA','Ukraine'),
('AE','United Arab Emirates'),
('GB','United Kingdom'),
('US','United States of America'),
('UM','United States Minor Outlying Islands'),
('UY','Uruguay'),
('UZ','Uzbekistan'),
('VU','Vanuatu'),
('VE','Venezuela (Bolivarian Republic of)'),
('VN','Viet Nam'),
('VG','Virgin Islands (British)'),
('VI','Virgin Islands (U.S.)'),
('WF','Wallis and Futuna'),
('EH','Western Sahara'),
('YE','Yemen'),
('ZM','Zambia'),
('ZW','Zimbabwe'),
('--','none');
UNLOCK TABLES;


