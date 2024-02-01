<?php


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class Jobs
{
    public function __construct()
    {
    }

    //Replaces windows line breaks with unix ones - slurm does not like \r\n :(
    private function replaceLineBreaks($string)
    {
        return str_replace("\r\n", "\n", $string);
    }
    
    private function extractJobID($str){
        preg_match('/\d+/', $str, $matches);
        $out = isset($matches[0]) ? (int)$matches[0] : null;
        if($out == null){
            throw "Invalid Input";
        }

        return $out;
    }


    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
            //Grab the users information from their decoded token
    $decodedToken = $request->getAttribute("decoded");
    //Grab the user ID to store with the job type
    $userID = $decodedToken->userID;
        $body = json_decode($request->getBody());
        $jobID = $body->jobID;
        $parameters = $body->parameters;

        $dbFile = __DIR__ . "/../data/db.db";
        $pdo = new PDO("sqlite:$dbFile");

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

        $script = $job["script"];

        foreach ($parameters as $parameter) {
            $key = $parameter->key;
            $value = $parameter->value;

            // Replace placeholder in the script
            $script = str_replace("{{" . $key . "}}", escapeshellarg($value), $script);
        }
            
        
            file_put_contents($jobID . date('Y-m-d_H-i-s') . ".sh", $this->replaceLineBreaks($script));
            $output = shell_exec('sbatch ' . $jobID . date('Y-m-d_H-i-s') . ".sh");
            $resp = array("output" => $output);
            $response->getBody()->write(json_encode($resp));

        
          try{
            $stmt = $pdo->prepare("INSERT INTO jobs (slurmID, userID, jobStartTime, jobComplete, jobTypeID) VALUES (:slurmID, :userID, :jobStartTime, :jobComplete, :jobTypeID)");
            $slurmID = $this->extractJobID($output);
            $stmt->bindParam(":slurmID", $slurmID);
            $stmt->bindParam(":userID", $userID);
            $currentTime = time();
            $stmt->bindParam(":jobStartTime", $currentTime);
            $jobComplete = false;
            $stmt->bindParam(":jobComplete", $jobComplete);
            $stmt->bindParam(":jobTypeID", $jobID);
            $stmt->execute();
        }catch(Exception $e){
            error_log($e);
            $response->getBody()->write("Error creating job");
            return $response->withStatus(500);
        }

        return $response->withStatus(200);
      
    }

    private function getSlurmJobs(){
        $output = shell_exec('squeue --json');
        $obj = json_decode($output);
        return $obj->jobs;
    }

    public function getJob(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface{
        $body = json_decode($request->getBody());
        $jobID = $body->jobID;
        $dbFile = __DIR__ . "/../data/db.db";
        $pdo = new PDO("sqlite:$dbFile");
        $stmt = $pdo->prepare("SELECT * FROM jobs WHERE jobID = :jobID");
        $stmt->bindParam(":jobID", $jobID);
        $stmt->execute();
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$job){
            $response->getBody()->write("Unknown Job");
            return $response->withStatus(404);
        }

        $slurmID = $job["slurmID"];
        $slurmJobs = $this->getSlurmJobs();
        error_log($slurmJobs);
        $resp = array("output" => $slurmJobs);
        $response->getBody()->write(json_encode($resp));
        return $response->withStatus(200);
    }

    public function getJobs(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface{
        $slurmJobs = $this->getSlurmJobs();
        $slurmJobs = array_map(function($job) {
            return [
                'job_id' => $job->job_id,
                'job_state' => $job->job_state,
                'name' => $job->name,
            ];
        }, $slurmJobs);

        $response->getBody()->write(json_encode($slurmJobs));
        return $response->withStatus(200);
    }

public function jobTest(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface{
    return $response->withStatus(200);
}


public function getUserJob(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface{
    //Grab the users information from their decoded token
    $decodedToken = $request->getAttribute("decoded");
    //Grab the user ID to store with the job type
    $userID = $decodedToken->userID;
    $dbFile = __DIR__ . "/../data/db.db";
    $pdo = new PDO("sqlite:$dbFile");
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE userID = :userID");
    $stmt->bindParam(":userID", $userID);
    $stmt->execute();
    $dbJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

  
    $allJobs = $this->getSlurmJobs();
    $jobs = array_filter($allJobs, function($job) use ($dbJobs) {
        foreach($dbJobs as $dbJob) {
            if($job->job_id == $dbJob["slurmID"]) {
                return true;
            }
        }
        return false;
    });

  
    $jobs = array_map(function($job) {
        return [
            'job_id' => $job->job_id,
            'job_state' => $job->job_state,
            'name' => $job->name,
        ];
    }, $jobs);


    $response->getBody()->write(json_encode($jobs));
    return $response->withStatus(200);
}

}