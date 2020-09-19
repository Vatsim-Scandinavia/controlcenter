# Preperation:
- All training requests must be accepted
- TAS must be closed for new requests to avoid duplicates and complications.
- Add temporary position ids that need to be manually overwritten later on: 401 "ESSA_TWR" + 402 "ESSA_APP" + 403 "ENGM_APP" + 404 "ENGM_TWR"

# 1: Users
- Make sure you've clean `handover.users`, and `temp.users_to_transfer` table.	
- Run `step1-users.sql` in local environment, this moves all users and also populates CC & Handover users records
- Run `step2-trainings.sql` to transfer all training parent records

	
# 2: Trainings
- Find all trainings from TAS that were long-term paused, and set paused_at field to pause it.
`SELECT *, FROM_UNIXTIME(tra_started) FROM tas.trainings WHERE tas.trainings.tra_state = 100 AND tas.trainings.user_vacc = "SCA";`

- Reassign all mentors of active trainings with `step-mentors.php`.


# 3: Reports
- Insert all training and exam report parents by running `step3-reports.php`. Now there will be some trainings with ID 40X
that needs to be converted to correct position by doing this:

> UPDATE santa.training_examinations SET santa.training_examinations.position_id = 360 WHERE santa.training_examinations.position_id = 401;
> UPDATE santa.training_examinations SET santa.training_examinations.position_id = 355 WHERE santa.training_examinations.position_id = 402;
> UPDATE santa.training_examinations SET santa.training_examinations.position_id = 190 WHERE santa.training_examinations.position_id = 403;
> UPDATE santa.training_examinations SET santa.training_examinations.position_id = 195 WHERE santa.training_examinations.position_id = 404;

- Now we need to manually set the correct position for the rest of NULL's, best guess is reading the exam report to see the position and fill it in.

# 4: Training Ratings
- Run the `step4-ratings.php` to fill all the ratings of training requests
- Run the `step-training-ratings.sql` to pre-fill already finished manual slavery work to fill gaps
- Some trainings are now missing ratings, manually fill the gaps, training exam reports is best guess again. Caution: Some might be refresh etc, so make sure to set correct type as well.

# 5: Cleanup
- Run the `step5-attachments.php` to make the required attachment links
- Copy all attachments from TAS to /storage/app/files/legacy
- Run `step6-cleanup.php` which will delete all attachments not relevant anymore
- Delete the positions 401-404

- Add AUTO_INCREASE ID of trainings from 2000 `ALTER TABLE santa.trainings AUTO_INCREMENT=2000;`
- Add AUTO_INCREASE ID of training_examinations from X `ALTER TABLE santa.training_examinations AUTO_INCREMENT=X;`
- Add AUTO_INCREASE ID of training_reports from X `ALTER TABLE santa.training_reports AUTO_INCREMENT=X;`

- Export everything and insert into production :tada: