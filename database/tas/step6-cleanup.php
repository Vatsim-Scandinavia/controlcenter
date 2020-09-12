<?php

    $db = new mysqli("localhost","root","");

	// Check connection
	if ($db -> connect_errno) {
	  echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
	  exit();
    }

    // Cleanup files
    // Delete files that ain't attached.
    $files = scandir("D:/GitHub/controlcenter/storage/app/public/files/legacy");
    foreach($files as $file){

        if($file == "." || $file == "..") {continue;}

        $sql = "SELECT santa.files.path FROM santa.files WHERE santa.files.path = 'legacy/".$file."'";
        $result = $db->query($sql) or die(mysqli_error($db));
        if ($result->num_rows == 0) {
            echo "Deleting ".$file."<br>";
            unlink("D:/GitHub/controlcenter/storage/app/public/files/legacy/".$file);
        }

    }
    
    echo("Cleanup success");

?>

