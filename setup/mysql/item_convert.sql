DROP TABLE IF EXISTS item;
CREATE TABLE item (
    item_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    old_id INT(11),
    create_date DATETIME DEFAULT NULL,
    name VARCHAR(255) NOT NULL DEFAULT 'Test',
    res_name VARCHAR(255) DEFAULT NULL,
    status ENUM('test','active','archive') DEFAULT 'test',
    item_type ENUM('exp','quest','sets','project') DEFAULT NULL,
    template VARCHAR(32) DEFAULT NULL,
    item_order ENUM('fixed','random','one_equal','one_random') DEFAULT 'random',
    intro TEXT,
    feedback TEXT,
    labnotes TEXT,
    PRIMARY KEY (item_id)
);


REPLACE INTO item 
     SELECT NULL, id, create_date, name, res_name, 
            status, 'exp', exptype, trial_order, 
            instructions, feedback_general, labnotes 
       FROM exp;
       
REPLACE INTO item 
     SELECT NULL, id, create_date, name, res_name, 
            status, 'quest', questtype, quest_order, 
            instructions, feedback_general, labnotes 
       FROM quest;
       
REPLACE INTO item 
     SELECT NULL, id, create_date, name, res_name, 
            status, 'sets', NULL, `type`, 
            NULL, feedback_general, labnotes 
       FROM sets;
       
REPLACE INTO item 
     SELECT NULL, id, create_date, name, res_name, 
            status, 'project', NULL, NULL, 
            intro, NULL, labnotes 
       FROM project;
       
       
DROP TABLE IF EXISTS item_attr;
CREATE TABLE item_attr (
    item_id INT(11) UNSIGNED NOT NULL DEFAULT '0',
    attr VARCHAR(32) DEFAULT NULL,
    val TEXT DEFAULT NULL,
    UNIQUE KEY item_id_attr (item_id,attr)
);


REPLACE INTO item_attr SELECT item_id, "subtype", subtype FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';
REPLACE INTO item_attr SELECT item_id, "design", design FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';
REPLACE INTO item_attr SELECT item_id, "side", side FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';
REPLACE INTO item_attr SELECT item_id, "question", question FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';
REPLACE INTO item_attr SELECT item_id, "label1", label1 FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';
REPLACE INTO item_attr SELECT item_id, "label2", label2 FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';
REPLACE INTO item_attr SELECT item_id, "label3", label3 FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';
REPLACE INTO item_attr SELECT item_id, "label4", label4 FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';
REPLACE INTO item_attr SELECT item_id, "rating_range", rating_range FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';
REPLACE INTO item_attr SELECT item_id, "low_anchor", low_anchor FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';
REPLACE INTO item_attr SELECT item_id, "high_anchor", high_anchor FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';
REPLACE INTO item_attr SELECT item_id, "default_time", default_time FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';
REPLACE INTO item_attr SELECT item_id, "increment_time", increment_time FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';
REPLACE INTO item_attr SELECT item_id, "orient", orient FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';
REPLACE INTO item_attr SELECT item_id, "total_stim", total_stim FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';
REPLACE INTO item_attr SELECT item_id, "random_stim", random_stim FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';
REPLACE INTO item_attr SELECT item_id, "sex", sex FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';
REPLACE INTO item_attr SELECT item_id, "lower_age", lower_age FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';
REPLACE INTO item_attr SELECT item_id, "upper_age", upper_age FROM exp LEFT JOIN item ON exp.id = old_id AND item_type='exp';

REPLACE INTO item_attr SELECT item_id, "sex", sex FROM quest LEFT JOIN item ON quest.id = old_id AND item_type='quest';
REPLACE INTO item_attr SELECT item_id, "lower_age", lower_age FROM quest LEFT JOIN item ON quest.id = old_id AND item_type='quest';
REPLACE INTO item_attr SELECT item_id, "upper_age", upper_age FROM quest LEFT JOIN item ON quest.id = old_id AND item_type='quest';

REPLACE INTO item_attr SELECT item_id, "sex", sex FROM sets LEFT JOIN item ON sets.id = old_id AND item_type='sets';
REPLACE INTO item_attr SELECT item_id, "lower_age", lower_age FROM sets LEFT JOIN item ON sets.id = old_id AND item_type='sets';
REPLACE INTO item_attr SELECT item_id, "upper_age", upper_age FROM sets LEFT JOIN item ON sets.id = old_id AND item_type='sets';

REPLACE INTO item_attr SELECT item_id, "url", url FROM project LEFT JOIN item ON project.id = old_id AND item_type='project';
REPLACE INTO item_attr SELECT item_id, "blurb", blurb FROM project LEFT JOIN item ON project.id = old_id AND item_type='project';
REPLACE INTO item_attr SELECT item_id, "sex", sex FROM project LEFT JOIN item ON project.id = old_id AND item_type='project';
REPLACE INTO item_attr SELECT item_id, "lower_age", lower_age FROM project LEFT JOIN item ON project.id = old_id AND item_type='project';
REPLACE INTO item_attr SELECT item_id, "upper_age", upper_age FROM project LEFT JOIN item ON project.id = old_id AND item_type='project';

DELETE FROM  item_attr where val IS NULL;


DROP TABLE IF EXISTS `container_item`;
CREATE TABLE `container_item` (
  `container_id` int(11) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `item_n` int(3) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  KEY `container_id` (`container_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO container_item SELECT c.item_id, i.item_id, item_n, NULL 
FROM set_items as si
LEFT JOIN item AS c ON c.item_type = "set" AND c.old_id = si.set_id
LEFT JOIN item AS i ON si.item_type = i.item_type AND i.old_id = si.item_id
ORDER BY si.set_id, si.item_n;

INSERT INTO container_item SELECT c.item_id, i.item_id, item_n, icon 
FROM project_items as si
LEFT JOIN item AS c ON c.item_type = "project" AND c.old_id = si.project_id
LEFT JOIN item AS i ON si.item_type = i.item_type AND i.old_id = si.item_id
ORDER BY si.project_id, si.item_n;


/* generic item id change */

ALTER TABLE access ADD item_id int(11) DEFAULT NULL;
UPDATE access AS t, item AS i SET t.item_id = i.item_id WHERE t.type=i.item_type AND t.id = i.old_id;

ALTER TABLE dashboard ADD item_id int(11) DEFAULT NULL;
UPDATE dashboard AS t, item AS i SET t.item_id = i.item_id WHERE t.type=i.item_type AND t.id = i.old_id;

ALTER TABLE downloads ADD item_id int(11) DEFAULT NULL;
UPDATE downloads AS t, item AS i SET t.item_id = i.item_id WHERE t.type=i.item_type AND t.id = i.old_id;

ALTER TABLE yoke ADD item_id int(11) DEFAULT NULL;
UPDATE yoke AS t, item AS i SET t.item_id = i.item_id WHERE t.type=i.item_type AND t.id = i.old_id;

/* experiment-specific item id change */

ALTER TABLE buttons ADD item_id int(11) DEFAULT NULL;
UPDATE buttons AS t, item AS i SET t.item_id = i.item_id WHERE "exp"=i.item_type AND t.exp_id = i.old_id;

ALTER TABLE exp_data ADD item_id int(11) DEFAULT NULL;
UPDATE exp_data AS t, item AS i SET t.item_id = i.item_id WHERE "exp"=i.item_type AND t.exp_id = i.old_id;

ALTER TABLE trial ADD item_id int(11) DEFAULT NULL;
UPDATE trial AS t, item AS i SET t.item_id = i.item_id WHERE "exp"=i.item_type AND t.exp_id = i.old_id;

ALTER TABLE xafc ADD item_id int(11) DEFAULT NULL;
UPDATE xafc AS t, item AS i SET t.item_id = i.item_id WHERE "exp"=i.item_type AND t.exp_id = i.old_id;

/* questionnaire-specific item id change */

ALTER TABLE quest_data ADD item_id int(11) DEFAULT NULL;
UPDATE quest_data AS t, item AS i SET t.item_id = i.item_id WHERE "quest"=i.item_type AND t.quest_id = i.old_id;

ALTER TABLE question ADD item_id int(11) DEFAULT NULL;
UPDATE question AS t, item AS i SET t.item_id = i.item_id WHERE "quest"=i.item_type AND t.quest_id = i.old_id;

ALTER TABLE radiorow_options ADD item_id int(11) DEFAULT NULL;
UPDATE question AS t, item AS i SET t.item_id = i.item_id WHERE "quest"=i.item_type AND t.quest_id = i.old_id;

/* project-specific item id change */

ALTER TABLE session ADD item_id int(11) DEFAULT NULL;
UPDATE session AS t, item AS i SET t.item_id = i.item_id WHERE "project"=i.item_type AND t.project_id = i.old_id;
ALTER TABLE session ADD session_id int(11) DEFAULT NULL;
UPDATE session SET session_id = id;

ALTER TABLE item CHANGE item_type item_type enum('exp','quest','sets','project', 'set');
UPDATE item SET item_type = "set" WHERE item_type = "sets";
ALTER TABLE item CHANGE item_type item_type enum('exp','quest','set','project');
