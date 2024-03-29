<?php


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use TusPhp\Tus\Server;

include_once __DIR__ . "/../config/Config.php";
require_once __DIR__ . "/../helpers/Logger.php";
class Jobs
{
    private $server;
    public function __construct()

    {
         $this->server= new Server();
          $this->server->setMaxUploadSize(0)->setApiPath('/api/jobs/upload');

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
            Logger::error("Invalid input: Received: " . $str, "Jobs/extractJobID");
        }

        return $out;
    }

    private function formatOutput($jobs): array
    {
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


    private function scheduleSlurmJob($script, $scriptDir, $jobID)
    {
        //Create the name for the script file and save it to the file system
        $scriptFile = $jobID . "-" . date('Y-m-d+H-i-s') . ".sh";
        $scriptPath = $scriptDir . $scriptFile;
        Logger::info("Created new script file $scriptPath", "Jobs/scheduleSlurmJob");
        file_put_contents($scriptPath, $this->replaceLineBreaks($script));

        //Execute the script in SLURM

        $output = shell_exec("cd " . $scriptDir . "&& sbatch " . $scriptFile);
        Logger::info("Scheduled Slurm Job: $output", "Jobs/scheduleSlurmJob");
        $resp = array("output" => $output);

        //Attempt to update the database and rollback if there are any issues
        $pdo = new PDO(DB_CONN);
        try {
            $stmt = $pdo->prepare("UPDATE jobs SET slurmID = :slurmID WHERE jobID = :jobID");
            $slurmID = $this->extractJobID($output);
            $stmt->bindParam(":jobID", $jobID);
            $stmt->bindParam(":slurmID", $slurmID);
            $stmt->execute();
            return json_encode($resp);
        } catch (Exception $e) {
            Logger::error($e, "Jobs/scheduleSlurmJob");
            $stmt = $pdo->prepare("DELETE FROM jobs WHERE jobID = :jobID");
            $stmt->bindParam(":jobID", $jobID);
            $stmt->bindParam(":slurmID", $slurmID);
            $stmt->execute();
            return false;
        }
    }

    private function replaceScriptTemplate($jobName, $jobID, $outDir, $parameters, $script, $arrayJobSupport): string
    {
        //Adds in the job name
        $script = str_replace("*{name}*", escapeshellarg($jobName), $script);
        //Adds in the output directory
        $script = str_replace("*{out}*", $outDir . ($arrayJobSupport ? "/slurmout-%a" : "/slurmout"), $script);

        //Fills in the user provided parameters
        foreach ($parameters as $parameter) {
            $key = $parameter->key;
            $value = $parameter->value;

            //Replace placeholders in the script
            $script = str_replace("{{" . $key . "}}", escapeshellarg($value), $script);
        }

        //Append the job completion script to the script
       //!TEMP
        $url = "http://localhost:8080/api/jobs/" . $jobID . "/markcomplete";
        $script .= "\ncurl -X POST " . $url . " > /dev/null 2>&1";  

        return $script;

    }

    private function setupInputFiles($fileID, $inDir, $script)
    {   
        //Check if there is an input file ready
        if(file_exists($inDir. $fileID)){
            //If it's a zip then there's multiple
            if(mime_content_type($inDir . $fileID) === "application/zip"){
                $zip = new ZipArchive();
                $zipPath = $inDir . $fileID;
                $extractDir = $inDir . $fileID . "-extracted";
                if(!is_dir($extractDir)){
                    mkdir($extractDir, 0775, true);
                }
                $open = $zip->open($zipPath);

                if ($open === true) {
                    $zip->extractTo($extractDir);
                    //Now we go through all the extracted files and remove the file extensions
                    $extractedFiles = glob($extractDir . "/*");
                    foreach($extractedFiles as $file){
                        $newName = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file);
                        rename($file, $newName);
                    }
                    $zip->close();
                } else {
                    return false;
                }
            }else{
                //Otherwise it's just one
                $filePath = "file0=" . $inDir . $fileID;
                $scriptArr = explode("\n", $script);
                array_splice($scriptArr, 4, 0, $filePath);
                $script = implode("\n", $scriptArr);
            }
        }else{
            return $script;
        }
    }


    private function setupOutputFiles($outputCount, $outDir, $script)
    {

        try {
            $outputVars = [];

            for ($i = 0; $i < $outputCount; $i++) {
                $outputVars[] = "out" . $i . "=" . $outDir . "out" . $i;
            }

            $outputVars = implode("\n", $outputVars);
            $scriptArr = explode("\n", $script);
            array_splice($scriptArr, 4, 0, $outputVars);
            $script = implode("\n", $scriptArr);
            Logger::debug("File has $outputCount files. \nScript:\n$script", "Jobs/setupOutputFiles");
            return implode("\n", $scriptArr);
        } catch (Exception $e) {
            Logger::error($e, "Jobs/setupOutputFiles");
            return false;
        }

    }

    private function setupDirectories(string $userID, string $jobID)
    {
        try {
            $inDir = __DIR__ . "/../usr/in/" . $userID . "/";
            $outDir = __DIR__ . "/../usr/out/" . $userID . "/" . $jobID . "/";
            $scriptDir = __DIR__ . "/../usr/script/" . $userID . "/" . $jobID . "/";

            if (!is_dir($inDir)) {
                mkdir($inDir, 0775, true);
            }

            if (!is_dir($outDir)) {
                mkdir($outDir, 0775, true);
            }

            if (!is_dir($scriptDir)) {
                mkdir($scriptDir, 0775, true);
            }

            return ['in' => $inDir, 'out' => $outDir, 'script' => $scriptDir];
        } catch (Exception $e) {
            Logger::error($e, "Jobs/setupDirectories");
            return false;
        }
    }

    private function createInitialJobRecord(string $userID, string $jobTypeID, string $jobName, $fileID)
    {
        $pdo = new PDO(DB_CONN);

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
            $stmt->bindParam(":jobTypeID", $jobTypeID);
            $stmt->bindParam(":jobName", $jobName);

            if ($fileID !== null) {
                $stmt->bindParam(":fileID", $fileID);
            }

            $stmt->execute();
            $lastId = $pdo->lastInsertId();
             Logger::info("Created new job record {$lastId} for user with id {$userID}", "Jobs/createInitialJobRecord");
            return $lastId;
        } catch (Exception $e) {
            Logger::error($e, "Jobs/createInitialJobRecord");
            return ["success" => "false", "err" => $pdo->errorInfo()];
        }
    }


    private function getJobTypeInfo($jobTypeID)
    {
        $pdo = new PDO(DB_CONN);
        $stmt = $pdo->prepare("SELECT * FROM jobTypes WHERE jobTypeID = :jobTypeID");
        $stmt->bindParam(":jobTypeID", $jobTypeID);
        $stmt->execute();

        $jobType = $stmt->fetch(PDO::FETCH_ASSOC);

        //Check if job type exists;
        if (!$jobType) {
            return false;
        }

        return $jobType;
    }

    private function validateFileID(string $fileID, string $userID): bool
    {
        //Check if the fileID was generated by the server for the current user
        $pdo = new PDO(DB_CONN);
        $stmt = $pdo->prepare("SELECT * FROM fileIDs WHERE fileID = :fileID AND userID = :userID");
        $stmt->bindParam(":fileID", $fileID);
        $stmt->bindParam(":userID", $userID);
        $stmt->execute();

        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$file){
            Logger::warning("User with ID $userID attempted to access file with ID $fileID", "Jobs/validateFileID");
        }
        return !!$file;
    }

    private function setupArrayFiles($script, $inDir, $fileId){
        $scriptArr = explode("\n", $script);
        
        //Open the user input file directory
        $files = scandir($inDir);
        //Loop through the files and match any file with the correct fileID
        $count = 0;
        $fileCount = 0;
        foreach($files as $file){
            if(preg_match("/^$fileId(-\d+)?$/", $file)){
                $zip = new ZipArchive;
                //Open the ZIP archive
                if($zip->open($inDir . $file) === true){
                    //Extract the files to a new directory
                    $extractDir = $inDir . $file . '-extracted';
                    if(!file_exists($extractDir)){
                        mkdir($extractDir, 0775, true);
                    }
                    $zip->extractTo($extractDir);
                    //Rename the files to remove the file extension
                    $extractedFiles = glob($extractDir . "/*");
                    foreach($extractedFiles as $extractedFile){
                        $newName = preg_replace('/\\.[^.\\s]{3,4}$/', '', $extractedFile);
                        rename($extractedFile, $newName);
                    }
                    $count++;
                    $fileCount = count($extractedFiles);
                    $zip->close();
                }else{
                    Logger::error("Failed to open zip for file " . $fileId, "Jobs/setupArrayFiles");
                    return false;
                }
                
            }
        }

        //If we found any files, we need to update the script to reflect the new array job
        if($count > 0 ){
            for($i = 0; $i < $count; $i++){
                $toAppend = ('arrayfile' . ($i) . "=\"" . $inDir . $fileId .($i === 0 ? "" : "-$i") . "-extracted" .  '/file${SLURM_ARRAY_TASK_ID}' .  "\"\n");
                array_splice($scriptArr, 4, 0, $toAppend);
            }

            $toAppend = '#SBATCH --array=0-' . ($fileCount-1) . "\n";
            array_splice($scriptArr, 3, 0, $toAppend);

            $script = implode("\n", $scriptArr);
        }
    
        return $script;
        
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        //Grab the users token and get their user idea
        $decodedToken = $request->getAttribute("decoded");
        $userID = $decodedToken->userID;
        //Grab the job info from the request body
        $body = json_decode($request->getBody());
        $jobTypeID = $body->jobID;
        $jobName = $body->jobName;
        $parameters = $body->parameters;
        $fileID = $body->fileID ?? null;
        //If there is a fileID included, we check if it is a valid file ID associated with the user
        if (!!$fileID) {
            if (!$this->validateFileID($fileID, $userID)) {
                $response->getBody()->write("Bad Request");
                return $response->withStatus(400);
            }
        }

        //Get Job Type Information and 400 if it doesn't exist
        $jobType = $this->getJobTypeInfo($jobTypeID);
        if (!$jobType) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        //Grab the job script and format it
        $script = $this->formatScript($jobType['script']);

        //The script needs the jobID generated by the database in order to update the database when it's complete
        //So we need to create a record and grab its ID, so we can then update it later with the script.
        $jobID = $this->createInitialJobRecord($userID, $jobTypeID, $jobName, $fileID);

        Logger::debug("New jobID: " . $jobID, $request->getRequestTarget());
        if (!$jobID || $jobID == -1 || isset($jobID["err"])) {
            Logger::error("Failed to create job record: " . $jobID["err"], $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        //Setup the directory structure for input and output files
        $dirs = $this->setupDirectories($userID, $jobID);

        if (!$dirs) {
            Logger::warning("Failed to create directories for job with ID $jobID", $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        //Fill in the template in the script
        $script = $this->replaceScriptTemplate($jobName, $jobID, $dirs['out'], $parameters, $script, $jobType['arrayJobSupport']);


        
        //If the jobType has custom outputs we need to do some more variable replacement in the script
        if ($jobType['hasOutputFile'] == 1) {
            //Sets up the input files
            $script = $this->setupOutputFiles($jobType['outputCount'], $dirs['out'], $script);
            if (!$script) {
                Logger::warning("Failed to setup output files for job with ID $jobID", $request->getRequestTarget());
                $response->getBody()->write("Internal Server Error");
                return $response->withStatus(500);
            }
        }

        if($jobType['arrayJobSupport'] == 1){
            Logger::debug("Job type supports array jobs", $request->getRequestTarget());
            $script = $this->setupArrayFiles($script, $dirs['in'], $fileID);
        }else{
             $script = $this->setupInputFiles($fileID, $dirs['in'], $script);
        }
       
        if (!$script) {
            Logger::warning("Failed to setup input files for job with ID $jobID", $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        //Schedule the Slurm Job
        $resp = $this->scheduleSlurmJob($script, $dirs['script'], $jobID);

        if (!$resp) {
            Logger::warning("Failed to schedule Slurm Job for job with ID $jobID", $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
        Logger::info("Created new job with ID $jobID for user with ID $userID", $request->getRequestTarget());
        $response->getBody()->write($resp);
        return $response->withStatus(200);

    }




    private function getRunningSlurmJobs()
    {
        $output = shell_exec('squeue --json');
        $obj = json_decode($output);
        return $obj->jobs;
    }

    public function getAll(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $decoded = $request->getAttribute("decoded");
            $userID = $decoded->userID;
            $pdo = new PDO(DB_CONN);
            $stmt = $pdo->prepare("SELECT jobs.*, jobTypes.jobName AS jobTypeName FROM jobs JOIN jobTypes ON jobs.jobTypeID = jobTypes.jobTypeID WHERE jobs.userID = :userID ORDER BY jobs.jobStartTime DESC;");
            $stmt->bindParam(":userID", $userID);
            $stmt->execute();
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response->getBody()->write(json_encode($jobs));
            Logger::info("Retrieved all jobs for user with ID $userID", $request->getRequestTarget());
            return $response->withStatus(200);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

    public function getJob(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $decodedToken = $request->getAttribute("decoded");
        $userID = $decodedToken->userID;
        $jobID = $args["jobID"];
        $pdo = new PDO(DB_CONN);

        $stmt = $pdo->prepare("SELECT jobs.*, jobTypes.jobName as jobTypeName FROM jobs JOIN jobTypes ON jobs.jobTypeID = jobTypes.jobTypeID WHERE jobs.jobId = :jobID AND jobs.userId = :userID");
        $stmt->bindParam(":jobID", $jobID);
        $stmt->bindParam(":userID", $userID);
        $stmt->execute();
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($job) {
            Logger::info("Retrieved job with ID $jobID for user with ID $userID", $request->getRequestTarget());
            $response->getBody()->write(json_encode($job));
            return $response->withStatus(200);
        } else {
            $response->getBody()->write("Job not found");
            return $response->withStatus(404);
        }
    }

    public function getParameters(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {

        try {
            $decodedToken = $request->getAttribute("decoded");
            $userID = $decodedToken->userID;
            $jobID = $args["jobID"];
            $pdo = new PDO(DB_CONN);
            $stmt = $pdo->prepare("SELECT jobParameters.key, jobParameters.value from jobParameters JOIN jobs ON jobParameters.jobID = jobs.jobID WHERE jobParameters.jobID = :jobID AND jobs.userID = :userID");
            $stmt->bindParam(":jobID", $jobID);
            $stmt->bindParam(":userID", $userID);
            $stmt->execute();
            $parameters = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log(print_r($parameters, true));
            if ($parameters) {
                $response->getBody()->write(json_encode($parameters));
                return $response->withStatus(200);
            } else {
                $response->getBody()->write("Job not found");
                return $response->withStatus(404);
            }
        }catch(Exception $e){
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
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
            Logger::error($e, $request->getRequestTarget());
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
            $query = "SELECT * FROM jobs WHERE jobComplete = 0";

            if ($userID !== null) {
                $query .= " AND userID = :userID ";
                $params[":userID"] = $userID;
            }

            $query .= "ORDER BY jobCompleteTime DESC";


            if ($limit !== null) {
                $query .= " LIMIT :limit";
                $params[":limit"] = $limit;
            }

            $stmt = $pdo->prepare($query);
            if (isset($params)) {
                foreach ($params as $key => &$val) {
                    $stmt->bindParam($key, $val);
                }
            }

            $stmt->execute();
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $slurmJobs = shell_exec("squeue --nohead --format=%F | uniq");

            $slurmJobs = explode("\n", $slurmJobs);

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
        try {
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
        }catch(Exception $e){
            Logger::error($e, "Jobs/updateFailed");
        }

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
            Logger::info("Retrieved all failed jobs for user with ID $userID", $request->getRequestTarget());
            return $response->withStatus(200);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

    public function generateFileID(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $decoded = $request->getAttribute("decoded");
            $userID = $decoded->userID;

            $fileID = uniqid();
            $pdo = new PDO(DB_CONN);
            $stmt = $pdo->prepare("INSERT INTO fileIDS (fileID, userID) VALUES (:fileID, :userID)");
            $stmt->bindParam(":fileID", $fileID);
            $stmt->bindParam(":userID", $userID);

            $stmt->execute();
            Logger::info("Generated new file ID $fileID for user with ID $userID", "Jobs/generateFileID");
            $response->getBody()->write(json_encode(["fileID" => $fileID]));
            return $response->withStatus(200);
        }catch(Exception $e){
            Logger::error($e, "Jobs/generateFileID");
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }



    public function downloadInputFile(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $decoded = $request->getAttribute("decoded");
            $userID = $decoded->userID;
            $jobID = $args["jobID"];
            $pdo = new PDO(DB_CONN);
            $stmt = $pdo->prepare("SELECT fileID FROM jobs WHERE jobID = :jobID AND userID = :userID");
            $stmt->bindParam(":jobID", $jobID);
            $stmt->bindParam(":userID", $userID);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$data) {
                $response->getBody()->write("Job not found");
                return $response->withStatus(404);
            }

            $fileID = $data["fileID"];

            $filePath = __DIR__ . "/../usr/in/" . $userID . "/" . $fileID;
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($filePath);

            //Return the file to the client, appending the correct extension to the response for the given mime type
            $response = $response->withHeader('Content-Type', $mime);
            $response = $response->withHeader('Content-Disposition', 'attachment; filename=' . $fileID . "." . $this->getExtension($mime));
            $response->getBody()->write(file_get_contents($filePath));
            return $response->withStatus(200);
        }catch(Exception $e){
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }


    public function downloadOutputFile(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //Grab userID and JobID
        $decoded = $request->getAttribute("decoded");
        $userID = $decoded->userID;
        $jobID = $args["jobID"];
        try {
            //Check the database to see if this job has custom output or just uses the SLURM default
            $pdo = new PDO(DB_CONN);
            $stmt = $pdo->prepare("SELECT * FROM jobs JOIN jobTypes on jobs.jobTypeID = jobTypes.jobTypeID WHERE jobs.jobID = :jobID AND jobs.userID = :userID AND jobTypes.hasOutputFile = 1");
            $stmt->bindParam(":jobID", $jobID);
            $stmt->bindParam(":userID", $userID);
            $stmt->execute();
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            //If the job has custom output, we grab all the files from the directory and just return the metadata
            if ($job) {
                $dir = __DIR__ . "/../usr/out/" . $userID . "/" . $jobID;
                error_log("DIR: " . $dir);
                $files = scandir($dir);
                $files = array_filter($files, function ($file) {
                    return $file !== "." && $file !== "..";
                });
                $result = [];
                foreach ($files as $file) {
                    $fileName = pathinfo($file, PATHINFO_FILENAME);
                    if (!empty($fileName)) {
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mime = $finfo->file($dir . "/" . $file);
                        $ext = $this->getExtension($mime);
                        if ($ext !== false) $result[] = ["fileName" => $fileName, "fileExtension" => $ext];
                    }
                }
                Logger::info("Retrieved output files for job with ID $jobID for user with ID $userID", $request->getRequestTarget());
                $response->getBody()->write(json_encode($result));
                //Otherwise we just return the slurmout file
            } else {
                $filePath = __DIR__ . "/../usr/out/" . $userID . "/" . $jobID . "/slurmout";
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($filePath);
                $response = $response->withHeader("Content-Type", $mime);
                $response = $response->withHeader("Content-Disposition", "attachment; filename=" . $jobID . "." . $this->getExtension($mime));
                Logger::info("Retrieved output file for job with ID $jobID for user with ID $userID", $request->getRequestTarget());
                $response->getBody()->write(file_get_contents($filePath));
            }
            return $response->withStatus(200);
        }catch(Exception $e){
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }


    }

    public function downloadMultiOut(ServerRequestInterface $request, ResponseInterface $response, array $args){
        $decoded = $request->getAttribute("decoded");
        $userID = $decoded->userID;
        $jobID = $args["jobID"];
        $file = $args["file"];

        try{
        $filePath = __DIR__ . "/../usr/out/" . $userID . "/" . $jobID . "/" . $file;
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($filePath);
        $response = $response->withHeader("Content-Type", $mime);
        $response = $response->withHeader("Content-Disposition", "attachment; filename=" . $file . "." . $this->getExtension($mime));
        $response->getBody()->write(file_get_contents($filePath));
        Logger::info("Retrieved output file $file for job with ID $jobID for user with ID $userID", $request->getRequestTarget());
        return $response->withStatus(200);
        }catch(Exception $e){
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

    }

    public function getZipData(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $decoded = $request->getAttribute("decoded");
        $userId = $decoded->userID;
        $jobID = $args["jobID"];
        try {
            $pdo = new PDO(DB_CONN);
            $stmt = $pdo->prepare("SELECT hasFileUpload FROM jobTypes JOIN jobs on JobTypes.jobTypeID = jobs.jobTypeID WHERE jobs.jobID = :jobID AND jobs.userID = :userID");
            $stmt->bindParam(":jobID", $jobID);
            $stmt->bindParam(":userID", $userId);
            $stmt->execute();
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$job) {
                $response->getBody()->write("Job not found");
                return $response->withStatus(404);
            } elseif ($job["hasFileUpload"] === false) {
                $response->getBody()->write("Bad Request");
                return $response->withStatus(400);
            }
        }catch(Exception $e){
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        try {
            $stmt = $pdo->prepare("SELECT fileID from jobs WHERE jobID = :jobID AND userID = :userID");
            $stmt->bindParam(":jobID", $jobID);
            $stmt->bindParam(":userID", $userId);
            $stmt->execute();
            $fileID = $stmt->fetch(PDO::FETCH_ASSOC)["fileID"];


            //If the job has more than one file, we know the application will have already extracted them into this folder.
            //We can therefore assume the folder already exists and contains files
            $dir = __DIR__ . "/../usr/in/" . $userId . "/" . $fileID . "-extracted";

            $files = scandir($dir);

            if (!$files) {
                $response->getBody()->write("No output files associated with job");
                return $response->withStatus(400);
            }
            // Filter out unwanted entries (e.g., ".", "..")
            $files = array_filter($files, function ($file) {
                return $file !== "." && $file !== "..";
            });

            // Initialize an empty array to store the results
            $result = [];

            foreach ($files as $file) {
                $fileName = pathinfo($file, PATHINFO_FILENAME);
                if (!empty($fileName)) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($dir . "/" . $file);
                    $ext = $this->getExtension($mime);
                    if ($ext !== false) {
                        $result[] = ["fileName" => $fileName, "fileExtension" => $ext];
                    }

                }
            }

            //Now $result contains the desired array
            $response->getBody()->write(json_encode($result));
            Logger::info("Retrieved output files for job with ID $jobID for user with ID $userId", $request->getRequestTarget());
            return $response->withStatus(200);
        }catch(Exception $e){
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);

        }

    }

    public function getExtractedFile(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $decoded = $request->getAttribute("decoded");
        $userId = $decoded->userID;
        $jobID = $args["jobID"];
        $fileNum = $args["file"];
        try {
            $pdo = new PDO(DB_CONN);
            $stmt = $pdo->prepare("SELECT hasFileUpload FROM jobTypes JOIN jobs on JobTypes.jobTypeID = jobs.jobTypeID WHERE jobs.jobID = :jobID AND jobs.userID = :userID");
            $stmt->bindParam(":jobID", $jobID);
            $stmt->bindParam(":userID", $userId);
            $stmt->execute();
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$job) {
                $response->getBody()->write("Job not found");
                return $response->withStatus(404);
            } elseif ($job["hasFileUpload"] === false) {
                $response->getBody()->write("Bad Request");
                return $response->withStatus(400);
            }
        }catch(Exception $e){
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        try {

            $stmt = $pdo->prepare("SELECT fileID from jobs WHERE jobID = :jobID AND userID = :userID");
            $stmt->bindParam(":jobID", $jobID);
            $stmt->bindParam(":userID", $userId);
            $stmt->execute();
            $fileID = $stmt->fetch(PDO::FETCH_ASSOC)["fileID"];
            $dir = __DIR__ . "/../usr/in/" . $userId . "/" . $fileID . "-extracted/" . "file" . $fileNum;
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($dir);
            $response = $response->withHeader("Content-Type", $mime);
            $response = $response->withHeader("Content-Disposition", "attachment; filename=" . "file" . $fileNum . "." . $this->getExtension($mime));
            Logger::info("Retrieved extracted file file$fileNum for job with ID $jobID for user with ID $userId", $request->getRequestTarget());
            $response->getBody()->write(file_get_contents($dir));
            return $response->withStatus(200);
        }catch(Exception $e){
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

    public function downloadZip(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface{
        $decoded = $request->getAttribute("decoded");
        $userId = $decoded->userID;
        $jobID = $args["jobID"];

        try {
            $pdo = new PDO(DB_CONN);
            $stmt = $pdo->prepare("SELECT hasFileUpload FROM jobTypes JOIN jobs on JobTypes.jobTypeID = jobs.jobTypeID WHERE jobs.jobID = :jobID AND jobs.userID = :userID");
            $stmt->bindParam(":jobID", $jobID);
            $stmt->bindParam(":userID", $userId);
            $stmt->execute();
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$job) {
                $response->getBody()->write("Job not found");
                return $response->withStatus(404);
            } elseif ($job["hasFileUpload"] === false) {
                $response->getBody()->write("Bad Request");
                return $response->withStatus(400);
            }

        }catch(Exception $e){
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        try {
            $stmt = $pdo->prepare("SELECT fileID from jobs WHERE jobID = :jobID AND userID = :userID");
            $stmt->bindParam(":jobID", $jobID);
            $stmt->bindParam(":userID", $userId);
            $stmt->execute();
            $fileID = $stmt->fetch(PDO::FETCH_ASSOC)["fileID"];
            $filePath = __DIR__ . "/../usr/in/" . $userId . "/" . $fileID;
            $file = file_get_contents($filePath);
            $response = $response->withHeader("Content-Type", "application/zip");
            $response = $response->withHeader("Content-Disposition", "attachment; filename=" . $fileID . ".zip");
            $response->getBody()->write($file);
            Logger::info("Retrieved input files for job with ID $jobID for user with ID $userId", $request->getRequestTarget());
            return $response->withStatus(200);
        }catch(Exception $e){
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

    //This is called by the scripts. I used to use php directly in the bash script but the server doesn't always support php commands
    public function markComplete(ServerRequestInterface $request, ResponseInterface $response, array $args):ResponseInterface{

            $jobID = $args["jobId"];
            if(!isset($jobID)){
                $response->getBody()->write("Bad Request");
                return $response->withStatus(400);
            }

            $jobCompleteTime = time();
            $jobComplete = true;
            try{
                $pdo = new PDO(DB_CONN);
                $stmt = $pdo->prepare("UPDATE jobs SET jobCompleteTime = :jobCompleteTime, jobComplete = :jobComplete WHERE jobID = :jobID");
                $stmt->bindParam(":jobCompleteTime", $jobCompleteTime);
                $stmt->bindParam(":jobComplete", $jobComplete);
                $stmt->bindParam(":jobID", $jobID);
                $stmt->execute();
                Logger::debug("Marked job with ID $jobID as complete", $request->getRequestTarget());
                $response->getBody()->write("Record Updated");
                return $response->withStatus(204);
            }catch(Exception $e){
                Logger::error($e, $request->getRequestTarget());
                $response->getBody()->write("Internal Server Error");
                return $response->withStatus(500);
            }
    }
}