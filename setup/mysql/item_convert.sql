DROP TABLE IF EXISTS item;
CREATE TABLE item (
    item_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    old_id INT(11),
    create_date DATETIME DEFAULT NULL,
    name VARCHAR(255) NOT NULL DEFAULT 'Test',
    res_name VARCHAR(255) DEFAULT NULL,
    status ENUM('test','active','archive') DEFAULT 'test',
    item_type ENUM('exp','quest','set','project') DEFAULT NULL,
    template VARCHAR(32) DEFAULT NULL,
    item_order ENUM('fixed','random','one_equal','one_random') DEFAULT 'random',
    intro TEXT,
    feedback TEXT,
    labnotes TEXT,
    PRIMARY KEY (item_id)
);

DROP TABLE IF EXISTS item_attr;
CREATE TABLE item_attr (
    item_id INT(11) UNSIGNED NOT NULL DEFAULT '0',
    attr VARCHAR(32) DEFAULT NULL,
    val TEXT DEFAULT NULL,
    UNIQUE KEY item_id_attr (item_id,attr)
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
            status, 'set', NULL, `type`, 
            NULL, feedback_general, labnotes 
       FROM sets;
       
REPLACE INTO item 
     SELECT NULL, id, create_date, name, res_name, 
            status, 'project', NULL, NULL, 
            intro, NULL, labnotes 
       FROM project;