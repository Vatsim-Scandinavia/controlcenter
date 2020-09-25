/* 
Insert all trainings
*/


/* Insert training parent */
INSERT IGNORE santa.trainings (id, user_id, TYPE, STATUS, country_id, notes, motivation, english_only_training, created_at, updated_at, closed_at)
	SELECT 
		id,
		user_id,
		1, 
		CASE
		    WHEN tra_state = 0 THEN 0
		    WHEN tra_state = 1 THEN 2
		    WHEN tra_state = 2 THEN -1
		    WHEN tra_state = 3 THEN 3
		    WHEN tra_state = 100 THEN 0
		    WHEN tra_state = 200 THEN -3
		    WHEN tra_state = 250 THEN -2
		END,
		CASE
		    WHEN tra_country = 'DK' THEN 1
		    WHEN tra_country = 'FI' THEN 2
		    WHEN tra_country = 'IS' THEN 3
		    WHEN tra_country = 'NO' THEN 4
		    WHEN tra_country = 'SE' THEN 5
		END,
		user_description,
		'N/A',
		0,
		FROM_UNIXTIME(tra_started),
		NOW(),
		IF(tra_ended = 0, NULL, FROM_UNIXTIME(tra_ended))	
	FROM tas.trainings;
	