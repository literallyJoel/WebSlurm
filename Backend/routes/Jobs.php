<?php


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
require_once __DIR__ . "/../config/config.php";

class Jobs
{   
 
    public function __construct()

    {
     
    }

    //Replaces windows line breaks with unix ones - slurm does not like \r\n :(
    private function replaceLineBreaks($string): string
    {
        return str_replace("\r\n", "\n", $string);
    }

    private function extractJobID($str)
    {
        preg_match('/\d+/', $str, $matches);
        $out = isset($matches[0]) ? (int)$matches[0] : null;
        if ($out == null) {
            error_log("Invalid input: Received: " . $str);
        }

        return $out;
    }

    private function formatOutput($jobs): array
    {
        error_log(print_r($jobs, true));
        return array_map(function ($job) {
            return [
                'job_id' => $job->job_id,
                'job_state' => $job->job_state,
                'name' => $job->name,
            ];
        }, $jobs);


    }

    private function formatScript($script): string
    {
        //This is done on the front-end for the users benefit, but we do it here to ensure the request isn't manually modified.
        return "#!/bin/bash\n#SBATCH --job-name=*{name}*\n#SBATCH --output=*{out}*" . "\n" . implode("\n", array_slice(explode("\n", $script), 3));
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        //Grab the users information from their decoded token
        $decodedToken = $request->getAttribute("decoded");
        //Grab the user ID to store with the job type
        $userID = $decodedToken->userID;
        //Grab the Job ID, Job Name, and Parameters from the request body
        $body = json_decode($request->getBody());
        $jobID = $body->jobID;
        $jobName = $body->jobName;
        $parameters = $body->parameters;
        $fileID = isset($body->fileID) ? $body->fileID : null;

        $pdo = new PDO(DB_CONN);

        if($fileID !== null){
            
            $stmt = $pdo->prepare("SELECT * FROM fileIDs WHERE fileID = :fileID AND userID = :userID");
            $stmt->bindParam(":fileID", $fileID);
            $stmt->bindParam(":userID", $userID);
            $stmt->execute();
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!$file){
                $response->getBody()->write("Bad Requset");
                return $response->withStatus(400);
            }
        }

       

        $stmt = $pdo->prepare("SELECT * FROM jobTypes WHERE jobTypeID = :jobID");
        $stmt->bindParam(":jobID", $jobID);

        $stmt->execute();
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if jobID exists
        if (!$job) {
            $response->getBody()->write("Unknown Job Type");
            // Return 404 response if jobID doesn't exist
            return $response->withStatus(404);
        }

        //Format the script - this just makes sure the correct 3 starting lines are in place.
        $script = $this->formatScript($job["script"]);

        //Loop through and swap out the parameters in the script
        foreach ($parameters as $parameter) {
            $key = $parameter->key;
            $value = $parameter->value;

            // Replace placeholders in the script
            $script = str_replace("{{" . $key . "}}", escapeshellarg($value), $script);
        }

        //If there's a file upload, we add the file0 variable. 
        //We handle multiple separately, as we'll need to unzip the zip file.
  

      
        if((int)$job["fileUploadCount"] === 1){
            $fileLine = "file0=" . escapeshellarg(__DIR__ . "/../usr/in/" . $userID . "/" . $fileID );
            $substring = "\\\$file0";
            $script = preg_replace("/^(.*$substring.*)$/m", "$fileLine\n$1", $script);
         
        }

        //This is in every script, as is enforced by both the frontend and backend.
        $script = str_replace("*{name}*", escapeshellarg($jobName), $script);
        mkdir(__DIR__ . "/../usr/out/" . $userID, 0775, true);

       
        //The script needs the database ID of the Job in order to update the database when it is complete.
        //In order to achieve this, we create the record, run the slurm job, and then update the record with the correct slurm ID.
        try {
            $query = "INSERT INTO jobs (slurmID, userID, jobStartTime, jobComplete, jobTypeID, jobName, jobComplete";
            $query = $fileID !== null ? $query . ", fileID)" : $query . ")";
            $query = $query . " VALUES (-1, :userID, :jobStartTime, :jobComplete, :jobTypeID, :jobName, :jobComplete";
            $query = $fileID !== null ? $query . ", :fileID)" : $query . ")";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":userID", $userID);
            $currentTime = time();
            $stmt->bindParam(":jobStartTime", $currentTime);
            $jobComplete = 0;
            $stmt->bindParam(":jobComplete", $jobComplete);
            $stmt->bindParam(":jobTypeID", $jobID);
            $stmt->bindParam(":jobName", $jobName);
            if($fileID !== null){
                $stmt->bindParam(":fileID", $fileID);
            }
            $stmt->execute();
            $newId = $pdo->lastInsertId();
        } catch (Exception $e) {
            error_log($e);
            $response->getBody()->write("Error creating job");
            return $response->withStatus(500);
        }

        if ($newId == -1) {
            error_log("Error creating job");
            $response->getBody()->write("Error creating job");
            return $response->withStatus(500);
        }
        try {
            $script .= "\nphp ../../script/jobComplete.php " . $newId;
            //Appends the self deletion to the script
            // $script .= "\n";
            // $script .= 'rm -- ' . __DIR__ . "/../usr/script/" . $userID . "-" . $jobID . "-" . date('Y-m-d_H-i-s') . ".sh";
            //Writes the script to a file so Slurm can run it
            $script = str_replace("*{out}*", __DIR__ . "/../usr/out/" . $userID . "/" . $newId, $script);
            file_put_contents(__DIR__ . "/../usr/script/" . $userID . "-" . $newId . "-" . date('Y-m-d_H-i-s') . ".sh", $this->replaceLineBreaks($script));
            //Run the script in slurm and return the output to the client
            $output = shell_exec('cd ' . __DIR__ . '/../usr/script/ && sbatch ' . $userID . "-" . $newId . "-" . date('Y-m-d_H-i-s') . ".sh");
            $resp = array("output" => $output);
            $response->getBody()->write(json_encode($resp));
        } catch (Exception $e) {
            //If it fails here we want to rollback the database changes.
            //I've opted not to use transactions for this, because otherwise I have to handld:\Users\Joel\OneDrive - JDVivian\Design Docs.docxe concurrency myself
            //And risk the lastInserID function returning the wrong value, if I don't use transactions, SQLite handles this.
            $stmt = $pdo->prepare("DELETE FROM jobs WHERE jobID = :jobID");
            $stmt->bindParam(":jobID", $newId);
            $stmt->execute();
            error_log($e);
            $response->getBody()->write("Error creating job");
            return $response->withStatus(500);
        }


        try {
            //Once the job has been submitted, add it to the database.
            $stmt = $pdo->prepare("UPDATE jobs SET slurmID = :slurmID WHERE jobID = :jobID");
            $slurmID = $this->extractJobID($output);
            $stmt->bindParam(":jobID", $newId);
            $stmt->bindParam(":slurmID", $slurmID);
            $stmt->execute();
        } catch (Exception $e) {
            //Once again if it fails we want to rollback the database changes.
            $stmt = $pdo->prepare("DELETE FROM jobs WHERE jobID = :jobID");
            $stmt->bindParam(":jobID", $newId);
            $stmt->execute();
            error_log($e);
            $response->getBody()->write("Error creating job");
            return $response->withStatus(500);
        }

        return $response->withStatus(200);

    }

    private function getRunningSlurmJobs()
    {
        $output = shell_exec('squeue --json');
        $obj = json_decode($output);
        return $obj->jobs;
    }

    public function getJob(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $jobID = $queryParams["jobID"];
        
        $pdo = new PDO(DB_CONN);
        $stmt = $pdo->prepare("SELECT * FROM jobs WHERE jobID = :jobID");
        $stmt->bindParam(":jobID", $jobID);
        $stmt->execute();
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            $response->getBody()->write("Unknown Job");
            return $response->withStatus(404);
        }

        $slurmID = $job["slurmID"];
        $slurmJobs = $this->getRunningSlurmJobs();
        $slurmJobs = array_filter($slurmJobs, function ($slurmJob) use ($slurmID) {
            return $slurmJob->job_id == $slurmID;
        });


        $slurmJobs = $this->formatOutput($slurmJobs);

        $response->getBody()->write(json_encode($slurmJobs));
        return $response->withStatus(200);
    }




    public function getComplete(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $decodedToken = $request->getAttribute("decoded");
            $queryParams = $request->getQueryParams();

            $limit = $queryParams["limit"] ?? null;
            $userID = $queryParams["userID"] ?? null;

            if ($decodedToken->role != 1) {
                $userID = $decodedToken->userID;
            }

            
            $pdo = new PDO(DB_CONN);
            $query = "SELECT * FROM jobs WHERE jobComplete = 1 ";

            if ($userID !== null) {
                $query .= "AND userID = :userID ";
                $params[":userID"] = $userID;
            }

            $query .= "ORDER BY jobCompleteTime DESC";


            if ($limit !== null) {
                $query .= " LIMIT :limit";
                $params[":limit"] = $limit;
            }
            error_log($query);
            $stmt = $pdo->prepare($query);
            if (isset($params)) {
                foreach ($params as $key => &$val) {
                    error_log($key . " " . $val);
                    $stmt->bindParam($key, $val);
                }
            }

            $stmt->execute();
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response->getBody()->write(json_encode($jobs));
            return $response->withStatus(200);
        } catch (Exception $e) {
            error_log($e);
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

    }

    public function getRunning(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $decodedToken = $request->getAttribute("decoded");
            $queryParams = $request->getQueryParams();

            $limit = $queryParams["limit"] ?? null;
            $userID = $queryParams["userID"] ?? null;

            if ($decodedToken->role != 1) {
                $userID = $decodedToken->userID;
            }

            
            $pdo = new PDO(DB_CONN);
            $query = "SELECT * FROM jobs WHERE jobComplete = 0 ";

            if ($userID !== null) {
                $query .= "AND userID = :userID ";
                $params[":userID"] = $userID;
            }

            $query .= "ORDER BY jobCompleteTime DESC";


            if ($limit !== null) {
                $query .= " LIMIT :limit";
                $params[":limit"] = $limit;
            }
            error_log($query);
            $stmt = $pdo->prepare($query);
            if (isset($params)) {
                foreach ($params as $key => &$val) {
                    error_log($key . " " . $val);
                    $stmt->bindParam($key, $val);
                }
            }

            $stmt->execute();
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $slurmJobs = shell_exec("squeue --nohead --format=%F | uniq");
            error_log($slurmJobs);
            $slurmJobs = explode("\n", $slurmJobs);
            error_log(print_r($slurmJobs, true));
            $runningJobs = array_filter($jobs, function ($job) use ($slurmJobs) {
                foreach ($slurmJobs as $slurmJob) {
                    if ($job["slurmID"] == $slurmJob) {
                        return true;
                    }
                }
                return false;
            });


            $response->getBody()->write(json_encode($runningJobs));
            return $response->withStatus(200);
        } catch (Exception $e) {
            error_log($e);
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

    }

    private function updateFailed($userID)
    {
        //Open the DB
        
        $pdo = new PDO(DB_CONN);
        $query = "SELECT * FROM jobs WHERE jobComplete = 0 ";

        if ($userID !== null) {
            $query .= "AND userID = :userID ";
            $params[":userID"] = $userID;
        }

        //We grab all the jobs that are marked as running in the database
        $stmt = $pdo->prepare($query);
        if (isset($params)) {
            foreach ($params as $key => &$val) {
                error_log($key . " " . $val);
                $stmt->bindParam($key, $val);
            }
        }

        $stmt->execute();
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //Now we get the list of jobs Slurm says is running
        $slurmJobs = shell_exec("squeue --nohead --format=%F | uniq");

        //Get it into an array
        $slurmJobs = explode("\n", $slurmJobs);

        //Grab the jobs that are actually running in both Slurm and the database
        $runningJobs = array_filter($slurmJobs, function ($slurmJob) use ($jobs) {
            foreach ($jobs as $job) {
                if ($slurmJob == $job["slurmID"]) {
                    return true;
                }
            }
            return false;
        });

        //Form a SQL statement to grab all the jobs that are not running in Slurm but are marked as running in the database
        $placeholders = implode(',', array_fill(0, count($runningJobs), '?'));

        $stmt = $pdo->prepare("SELECT * FROM jobs WHERE jobID NOT IN ($placeholders) AND jobComplete = 0");
        foreach ($runningJobs as $key => $id) {
            $stmt->bindValue(($key + 1), $id, PDO::PARAM_INT);
        }

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //Map this into a list of IDs
        $results = array_map(function ($job) {
            return $job["jobID"];

        }, $results);

        //Update them in the database to say they failed
        $placeholders = implode(',', array_fill(0, count($results), '?'));
        $stmt = $pdo->prepare("UPDATE jobs SET jobComplete = 2 WHERE jobID IN ($placeholders)");
        foreach ($results as $key => $id) {
            $stmt->bindValue(($key + 1), $id, PDO::PARAM_INT);
        }
        $stmt->execute();


    }

    public function getFailed(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            //Open the DB
            
            $pdo = new PDO(DB_CONN);

            $decodedToken = $request->getAttribute("decoded");
            $queryParams = $request->getQueryParams();

            $limit = $queryParams["limit"] ?? null;
            $userID = $queryParams["userID"] ?? null;

            //Update the database to mark any jobs that are not running in Slurm but are marked as running in the database as failed
            $this->updateFailed($userID);
            if ($decodedToken->role != 1) {
                $userID = $decodedToken->userID;
            }

            //Grab all the jobs marked as failed
            $query = "SELECT * FROM jobs WHERE jobComplete = 2";

            if ($userID !== null) {
                $query .= " AND userID = :userID ";
                $params[":userID"] = $userID;
            }

            $query .= " ORDER BY jobCompleteTime DESC";


            if ($limit !== null) {
                $query .= " LIMIT :limit";
                $params[":limit"] = $limit;
            }
            error_log($query);
            $stmt = $pdo->prepare($query);
            if (isset($params)) {
                foreach ($params as $key => &$val) {
                    error_log($key . " " . $val);
                    $stmt->bindParam($key, $val);
                }
            }

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response->getBody()->write(json_encode($results));
            return $response->withStatus(200);
        } catch (Exception $e) {
            error_log($e);
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

    public function generateFileID(ServerRequestInterface $request,ResponseInterface $response): ResponseInterface{
        $decoded = $request->getAttribute("decoded");
        $userID = $decoded->userID;

        $fileID = uniqid();
        $pdo = new PDO(DB_CONN);
        $stmt = $pdo->prepare("INSERT INTO fileIDS (fileID, userID) VALUES (:fileID, :userID)");
        $stmt->bindParam(":fileID", $fileID);
        $stmt->bindParam(":userID", $userID);

        $stmt->execute();

        $response->getBody()->write(json_encode(["fileID" => $fileID]));
        return $response->withStatus(200);
    }

    public function getJobOutput(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface{
        $decoded = $request->getAttribute("decoded");
        $userID = $decoded->userID; 
        $queryParams = $request->getQueryParams();
        $jobID = $queryParams["jobID"];
        $fileDir = __DIR__ . "/../usr/out/" . $userID . "/" . $jobID;
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($fileDir);
        $response->getBody()->write($mime);
        return $response->withStatus(200);
    }

    
}   
