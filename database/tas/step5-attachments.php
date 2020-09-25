<?php

    $db = new mysqli("localhost","root","");

	// Check connection
	if ($db -> connect_errno) {
	  echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
	  exit();
    }

    // Transfer attachments
    $sql = "SELECT tas.training_assessment.training_id, tas.training_assessment.examinor, tas.training_assessment.`file`, tas.training_assessment.time FROM tas.training_assessment WHERE tas.training_assessment.position NOT LIKE '%LI%' AND tas.training_assessment.position NOT LIKE '%LM%' AND tas.training_assessment.position != '';";
    $result = $db->query($sql) or die(mysqli_error($db));

    while($row = $result->fetch_assoc()) {

        $fileName = explode("/", $row["file"]);
        $fileName = end($fileName);

        $id = sha1($fileName . rand(1000, 9999));

        $examinator = $row["examinor"];
        
        $queryFiles = "INSERT IGNORE santa.files (id, uploaded_by, name, path, created_at, updated_at) VALUES ('".$id."', ".$examinator.", '".$fileName."', 'legacy/".$fileName."', FROM_UNIXTIME(".$row["time"]."), FROM_UNIXTIME(".$row["time"]."))";
        $db->query($queryFiles) or die(mysqli_error($db));

        $queryAmountExams = "SELECT santa.training_examinations.id FROM santa.training_examinations WHERE santa.training_examinations.training_id = ".$row["training_id"]." AND santa.training_examinations.created_at = FROM_UNIXTIME(".$row["time"].")";
        $amountExams = $db->query($queryAmountExams) or die(mysqli_error($db));

        while($row2 = $amountExams->fetch_assoc()) {
            $queryFileObject = "INSERT IGNORE santa.training_object_attachments (object_type, object_id, file_id, hidden, created_at, updated_at) VALUES ('App\\\\TrainingExamination', ".$row2["id"].", '".$id."', 0, FROM_UNIXTIME(".$row["time"]."), FROM_UNIXTIME(".$row["time"]."))";
            $db->query($queryFileObject) or die(mysqli_error($db));
        }
        
    }
    
    echo("Attachments success<br>");

?>