/* 
Find CIDs to transfer to Handover
*/


/* All student's from TAS trainings */
INSERT IGNORE temp.users_to_transfer SELECT tas.trainings.user_id FROM tas.trainings WHERE tas.trainings.user_vacc = "SCA";

/* All mentors from ACTIVE TAS trainings */
INSERT IGNORE temp.users_to_transfer SELECT SUBSTRING_INDEX(tas.trainings.tra_mentor, ",", 1) FROM tas.trainings WHERE tas.trainings.user_vacc = "SCA" AND tas.trainings.tra_state = 1;

/* All authors from TAS training reports */
INSERT IGNORE temp.users_to_transfer SELECT tas.training_reports.mentor FROM tas.trainings LEFT JOIN tas.training_reports ON tas.trainings.id = tas.training_reports.tra_id WHERE tas.trainings.user_vacc = "SCA";

/* All examinators from TAS trainings */
INSERT IGNORE temp.users_to_transfer SELECT tas.training_assessment.examinor FROM tas.trainings LEFT JOIN tas.training_assessment ON tas.trainings.id = tas.training_assessment.training_id WHERE tas.trainings.user_vacc = "SCA";


/* Cleanup */
DELETE FROM temp.users_to_transfer WHERE id = 0;

/* Handover */
INSERT IGNORE handover.users (id, email, first_name, last_name, rating, rating_short, rating_long, pilot_rating, region, accepted_privacy, last_login)
	SELECT temp.users_to_transfer.id, IF(tas.users.email = '', 'void@void.void', tas.users.email), tas.users.name_first, tas.users.name_last, tas.users.rating_id, 'N/A', 'N/A', tas.users.pilot_rating, tas.users.region_code, FALSE, FROM_UNIXTIME(tas.users.login_last)
	FROM temp.users_to_transfer RIGHT JOIN tas.users ON temp.users_to_transfer.id = tas.users.id 
	WHERE temp.users_to_transfer.id IS NOT NULL;

INSERT IGNORE santa.users (id, last_login)
	SELECT temp.users_to_transfer.id, FROM_UNIXTIME(tas.users.login_last)
	FROM temp.users_to_transfer RIGHT JOIN tas.users ON temp.users_to_transfer.id = tas.users.id 
	WHERE temp.users_to_transfer.id IS NOT NULL;