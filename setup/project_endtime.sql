DROP TABLE tmp_sess;
DROP TABLE tmp_end;

CREATE TEMPORARY TABLE tmp_sess
SELECT session_id, 
MAX(endtime) as endt
FROM quest_data 
GROUP BY session_id;

INSERT INTO tmp_sess
SELECT session_id, 
MAX(dt) as endt
FROM exp_data
GROUP BY session_id;

CREATE TEMPORARY TABLE tmp_end 
SELECT session_id, MAX(endt) AS endt
FROM tmp_sess
GROUP BY session_id;

UPDATE session 
LEFT JOIN tmp_end ON tmp_end.session_id = session.id
SET endtime = endt 
WHERE endtime IS NULL;


