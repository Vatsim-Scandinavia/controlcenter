<?php

    $db = new mysqli("localhost","root","");

	// Check connection
	if ($db -> connect_errno) {
	  echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
	  exit();
    }

    // Transfer endorsements

    $usersQuery = "SELECT * FROM temp.users_to_transfer";
    $userResult = $db->query($usersQuery) or die(mysqli_error($db));

    $users = [];
    while($row = $userResult->fetch_assoc()) {
        $users[] = $row['id'];
    }

    foreach($users as $user){
        $maeQuery = "SELECT tas.users.mae FROM tas.users WHERE tas.users.id = ".$user;
        $maeResult = $db->query($maeQuery) or die(mysqli_error($db));

        while($row = $maeResult->fetch_assoc()) {

            $insertSql = [];
            
            $mae = explode(',', $row['mae']);

            foreach($mae as $m){
                switch($m){
            
                    case 1:
                        $insertSql[] = "INSERT IGNORE santa.rating_user (rating_id, user_id) VALUES (12, ".$user.");";
                        break;
                    case 2:
                        $insertSql[] = "INSERT IGNORE santa.rating_user (rating_id, user_id) VALUES (13, ".$user.");";
                        break;
                    case 13:
                        $insertSql[] = "INSERT IGNORE santa.rating_user (rating_id, user_id) VALUES (12, ".$user.");";
                        $insertSql[] = "INSERT IGNORE santa.rating_user (rating_id, user_id) VALUES (13, ".$user.");";
                        break;
                    case 15:
                    case 17:
                        $insertSql[] = "INSERT IGNORE santa.rating_user (rating_id, user_id) VALUES (10, ".$user.");";
                        break;
                    case 18:
                        $insertSql[] = "INSERT IGNORE santa.rating_user (rating_id, user_id) VALUES (11, ".$user.");";
                        break;
                    case 19:
                    case 21:
                    case 23:
                        $insertSql[] = "INSERT IGNORE santa.rating_user (rating_id, user_id) VALUES (10, ".$user.");";
                        $insertSql[] = "INSERT IGNORE santa.rating_user (rating_id, user_id) VALUES (11, ".$user.");";
                        break;
                    case 30:
                    case 32:
                        $insertSql[] = "INSERT IGNORE santa.rating_user (rating_id, user_id) VALUES (8, ".$user.");";
                        break;
                    case 33:
                        $insertSql[] = "INSERT IGNORE santa.rating_user (rating_id, user_id) VALUES (9, ".$user.");";
                        break;
                    case 34:
                    case 36:
                    case 39:
                        $insertSql[] = "INSERT IGNORE santa.rating_user (rating_id, user_id) VALUES (8, ".$user.");";
                        $insertSql[] = "INSERT IGNORE santa.rating_user (rating_id, user_id) VALUES (9, ".$user.");";
                        break;
                    case 41:
                    case 46:
                        $insertSql[] = "INSERT IGNORE santa.rating_user (rating_id, user_id) VALUES (14, ".$user.");";
                        break;
                    case 37:
                    case 40:
                        $insertSql[] = "INSERT IGNORE santa.rating_user (rating_id, user_id) VALUES (15, ".$user.");";
                        break;
                    
                }
            }
    
            if(!empty($insertSql)){
    
                foreach($insertSql as $q){
                    $db->query($q) or die(mysqli_error($db));
                }
               
            }
        }

        
    }

    // Transfer ratings

    $sql = "SELECT tas.trainings.id, tas.trainings.tra_level FROM tas.trainings WHERE tas.trainings.user_vacc = 'SCA'";
    $result = $db->query($sql);

    while($row = $result->fetch_assoc()) {
        
        $insertSql = [];

        switch($row["tra_level"]){
            
            case "S1":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (1, ".$row["id"].");";
                break;
            case "S2":
            case "S2 ":
            case "S2 + ":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (2, ".$row["id"].");";
                break;
            case "S3":
            case "S3 + ":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (3, ".$row["id"].");";
                break;
            case "C1":
            case "C1 + ":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (4, ".$row["id"].");";
                break;
            case "C3":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (5, ".$row["id"].");";
                break;
            case "S2 Transfer":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (2, ".$row["id"].");";
                $insertSql[] = "UPDATE santa.trainings SET santa.trainings.type = 3 WHERE santa.trainings.id = ".$row["id"].";";
                break;
            case "S3 Transfer":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (3, ".$row["id"].");";
                $insertSql[] = "UPDATE santa.trainings SET santa.trainings.type = 3 WHERE santa.trainings.id = ".$row["id"].";";
                break;
            case "S2 + Refresh S2":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (2, ".$row["id"].");";
                $insertSql[] = "UPDATE santa.trainings SET santa.trainings.type = 2 WHERE santa.trainings.id = ".$row["id"].";";
                break;
            case "S2 + MAE ENGM TWR":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (2, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (8, ".$row["id"].");";
                break;
            case "C1 (refresh)":
            case "C1 + Refresh C1":
            case "C1 (refresh)":
            case "C1 + refresh C1":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (4, ".$row["id"].");";
                $insertSql[] = "UPDATE santa.trainings SET santa.trainings.type = 2 WHERE santa.trainings.id = ".$row["id"].";";
                break;
            case "C1 + Fast Track":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (4, ".$row["id"].");";
                $insertSql[] = "UPDATE santa.trainings SET santa.trainings.type = 4 WHERE santa.trainings.id = ".$row["id"].";";
                break;
            case "S2 + MAE ESSA TWR":
            case "S2+ESSA_TWR MAE":
            case "S2+ESSA_TWR MAE Refresh":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (2, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (10, ".$row["id"].");";
                break;
            case "S2+EKCH_TWR MA":
            case "S2 + MAE EKCH TWR":
            case "S2 + EKCH_TWR MA":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (2, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (12, ".$row["id"].");";
                break;
            case "S3 + Refresh S3":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (3, ".$row["id"].");";
                $insertSql[] = "UPDATE santa.trainings SET santa.trainings.type = 2 WHERE santa.trainings.id = ".$row["id"].";";
                break;
            case "C3 + Refresh C3":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (5, ".$row["id"].");";
                $insertSql[] = "UPDATE santa.trainings SET santa.trainings.type = 2 WHERE santa.trainings.id = ".$row["id"].";";
                break;
            case "S3 + MAE ESSA APP":
            case "S3+ESSA_APP MAE":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (3, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (11, ".$row["id"].");";
                break;
            case "C1 + OCEANIC BICC":
            case "C1 + BICC_FSS":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (4, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (14, ".$row["id"].");";
                break;
            case "C1 BIRD familiarisation":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (4, ".$row["id"].");";
                $insertSql[] = "UPDATE santa.trainings SET santa.trainings.type = 5 WHERE santa.trainings.id = ".$row["id"].";";
                break;
            case "C1 + OCEANIC ENOB":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (4, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (15, ".$row["id"].");";
                break;
            case "C1 + MAE EKCH TWR+APP":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (4, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (12, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (13, ".$row["id"].");";
                break;
            case "S3+ESSA_TWR+APP MAE":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (3, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (10, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (11, ".$row["id"].");";
                break;
            case "C1+ESSA MAE TWR+APP":
            case "C1 + MAE ESSA TWR+APP":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (4, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (10, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (11, ".$row["id"].");";
                break;
            case "C1 + MAE ENGM TWR+APP":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (4, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (8, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (9, ".$row["id"].");";
                break;
            case "MAE ENGM TWR+APP":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (8, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (9, ".$row["id"].");";
                break;
            case "S3 + MAE ENGM TWR+APP":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (3, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (8, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (9, ".$row["id"].");";
                break;
            case "S3 + MAE ENGM TWR":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (3, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (8, ".$row["id"].");";
                break;
            case "S3 + MAE":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (8, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (9, ".$row["id"].");";
                break;
            case "S3+EKCH_APP MA refresh":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (3, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (13, ".$row["id"].");";
                $insertSql[] = "UPDATE santa.trainings SET santa.trainings.type = 2 WHERE santa.trainings.id = ".$row["id"].";";
                break;
            case "S3+EKCH_APP MA":
            case "S3 + MAE EKCH APP":
            case "S3 + EKCH_APP MA":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (3, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (13, ".$row["id"].");";
                break;
            case "EKCH_TWR/APP MA":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (12, ".$row["id"].");";
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (13, ".$row["id"].");";
                break;
            case "EKCH_TWR MA":
                $insertSql[] = "INSERT IGNORE santa.rating_training (rating_id, training_id) VALUES (12, ".$row["id"].");";
                break;
                
        }
        

        if(!empty($insertSql)){

            foreach($insertSql as $q){
                $db->query($q) or die(mysqli_error($db));
            }
           
        }

        
        
    }
    echo("Ratings transfer success<br>");

?>