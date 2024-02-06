<?php
    $dbFile = __DIR__ . "/../data/db.db";
    $pdo = new PDO("sqlite:$dbFile");
    $stmt = $pdo->prepare("UPDATE jobs SET jobCompleteTime = :jobCompleteTime, jobComplete = :jobComplete WHERE jobID = :jobID");

    $jobID = $_SERVER['argv'][1];
    
    if($jobID == null){
        error_log("No jobID provided. Correct usage: php jobComplete.php <jobID>");
        exit(1);
    }

    $jobCompleteTime = time();
    $jobComplete = true;
    $stmt->bindParam(":jobCompleteTime", $jobCompleteTime);
    $stmt->bindParam(":jobComplete", $jobComplete);
    $stmt->bindParam(":jobID", $jobID);
    $stmt->execute();

    ?>