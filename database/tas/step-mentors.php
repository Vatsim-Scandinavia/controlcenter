<?php

    $db = new mysqli("localhost","root","");

	// Check connection
	if ($db -> connect_errno) {
	  echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
	  exit();
    }

    // Assign mentors

    $sql = "SELECT * FROM tas.trainings WHERE (tas.trainings.tra_state = 1 OR tas.trainings.tra_state = 3) AND tas.trainings.user_vacc = 'SCA'";
    $result = $db->query($sql) or die(mysqli_error($db));

    while($row = $result->fetch_assoc()) {

        $mentors = explode(",", $row["tra_mentor"]);

        foreach($mentors as $mentor){

            if(!empty($mentor)){
                $sqlAddMentor = "INSERT IGNORE santa.training_mentor (user_id, training_id, expire_at, created_at, updated_at) VALUES (".$mentor.", ".$row["id"].", DATE_ADD(NOW(), INTERVAL 1 YEAR), NOW(), NOW())";
                $db->query($sqlAddMentor) or die(mysqli_error($db));

                $sqlSetMentor = "INSERT IGNORE santa.training_role_country (user_id, country_id, created_at, updated_at) VALUES (".$mentor.", (SELECT santa.trainings.country_id FROM santa.trainings WHERE santa.trainings.id = ".$row["id"]."), NOW(), NOW())";
                $db->query($sqlSetMentor) or die(mysqli_error($db));

                $sqlSetMentor = "UPDATE santa.users SET santa.users.group = 3 WHERE santa.users.id = ".$mentor;
                $db->query($sqlSetMentor) or die(mysqli_error($db));
            }

        }

        
    }
    
    echo("Mentors success<br>");


?>

