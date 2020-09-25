
/* 
Insert all trainings reports
*/


/* Insert all report parents */
INSERT IGNORE santa.training_reports (training_id, written_by_id, report_date, content, contentimprove, POSITION, draft, created_at, updated_at)
	SELECT tas.training_reports.tra_id, tas.training_reports.mentor, FROM_UNIXTIME(tas.training_reports.date), CONCAT(tas.training_reports.focus_points, CHAR(13), CHAR(13), tas.training_reports.comments), tas.training_reports.improvements, tas.training_reports.position, FALSE, FROM_UNIXTIME(tas.training_reports.date), FROM_UNIXTIME(tas.training_reports.date)
	FROM tas.training_reports;


/* Insert all exam parents */
INSERT IGNORE santa.training_examinations (training_id, position_id, examiner_id, result, examination_date, created_at, updated_at)	
	SELECT
		tas.training_assessment.training_id,
		(SELECT santa.positions.id FROM santa.positions WHERE tas.training_assessment.position != '' AND santa.positions.callsign LIKE CONCAT('%', tas.training_assessment.position, '%') LIMIT 1),
		tas.training_assessment.examinor,
		CASE
			WHEN tas.training_assessment.result = 'passed' THEN 'PASSED'
			WHEN tas.training_assessment.result = 'failed' THEN 'FAILED'
			WHEN tas.training_assessment.result = 'discontinued' THEN 'INCOMPLETE'
		END,
		FROM_UNIXTIME(tas.training_assessment.time),
		FROM_UNIXTIME(tas.training_assessment.time),
		FROM_UNIXTIME(tas.training_assessment.time)
	FROM tas.training_assessment;
	