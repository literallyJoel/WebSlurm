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

    private function replaceScriptTemplate($jobName, $jobID, $outDir, $parameters, $script): string
    {
        //Adds in the job name
        $script = str_replace("*{name}*", escapeshellarg($jobName), $script);
        //Adds in the output directory
        $script = str_replace("*{out}*", $outDir . "/slurmout", $script);

        //Fills in the user provided parameters
        foreach ($parameters as $parameter) {
            $key = $parameter->key;
            $value = $parameter->value;

            //Replace placeholders in the script
            $script = str_replace("{{" . $key . "}}", escapeshellarg($value), $script);
        }

        //Append the job completion script to the script
       
        $url = "https://pgb.liv.ac.uk/~sgjvivia/api/jobs/" . $jobID . "/markcomplete";
        $script .= "\ncurl -X POST " . $url . " > /dev/null 2>&1";  

        return $script;

    }

    private function setupInputFiles($fileID, $fileUploadCount, $inDir, $script)
    {   

        if($fileUploadCount === 0 || $fileUploadCount === "0"){
            return $script;
        }
        //Check how many files are uploaded
        if ($fileUploadCount === 1 || $fileUploadCount === "1") {
            //If there's only one we can just fill it in
            $filePath = "file0=" . $inDir . $fileID;
            $scriptArr = explode("\n", $script);
            array_splice($scriptArr, 4, 0, $filePath);
            $script = implode("\n", $scriptArr);
        } else {
            //If there's multiple, it'll be a ZIP archive, so we need to unzip the and store the files
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

           

            //We then fill in the script with the file paths
            for ($i = 0; $i < (int)$fileUploadCount; $i++) {
                $filePath = "file" . $i . "=" . $inDir . $fileID . "-extracted/file" . $i;
                $scriptArr = explode("\n", $script);
                array_splice($scriptArr, 4 + $i, 0, $filePath);
                $script = implode("\n", $scriptArr);
            }
        }

        return $script;
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
            Logger::debug("File has $output files. \nScript:\n$script", "Jobs/setupOutputFiles");
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
            return ["success" => "false", "err" => $pdo->error_get_last()];
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
        $script = $this->replaceScriptTemplate($jobName, $jobID, $dirs['out'], $parameters, $script);


        
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

   
        $script = $this->setupInputFiles($fileID, $jobType['fileUploadCount'], $dirs['in'], $script);
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
        return $response->withStatus(500);

    }


    public function handleFileUpload(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            //Grab the users information from their decoded token
            $decodedToken = $request->getAttribute("decoded");
            //Grab the user ID to store with the job type
            $userID = $decodedToken->userID;


            $path = __DIR__ . "/../usr/in/$userID/";
            if (!file_exists($path)) {
                mkdir($path, 0775, true);
            }

            $this->server->setUploadDir($path);
            $psr17Factory = new Psr17Factory();
            $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
            
            $resp = $this->server->serve();
            return $psrHttpFactory->createResponse($resp);

        } catch (Exception $e) {
            Logger::error($e, "Jobs/handleFileUpload");
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
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

    //Used for getting the correct file extension for a given mime type
    //Taken from https://stackoverflow.com/questions/16511021/convert-mime-type-to-file-extension-php
    private function getExtension($mime)
    {
        $mime_map = [
            'video/3gpp2' => '3g2',
            'video/3gp' => '3gp',
            'video/3gpp' => '3gp',
            'application/x-compressed' => '7zip',
            'audio/x-acc' => 'aac',
            'audio/ac3' => 'ac3',
            'application/postscript' => 'ai',
            'audio/x-aiff' => 'aif',
            'audio/aiff' => 'aif',
            'audio/x-au' => 'au',
            'video/x-msvideo' => 'avi',
            'video/msvideo' => 'avi',
            'video/avi' => 'avi',
            'application/x-troff-msvideo' => 'avi',
            'application/macbinary' => 'bin',
            'application/mac-binary' => 'bin',
            'application/x-binary' => 'bin',
            'application/x-macbinary' => 'bin',
            'image/bmp' => 'bmp',
            'image/x-bmp' => 'bmp',
            'image/x-bitmap' => 'bmp',
            'image/x-xbitmap' => 'bmp',
            'image/x-win-bitmap' => 'bmp',
            'image/x-windows-bmp' => 'bmp',
            'image/ms-bmp' => 'bmp',
            'image/x-ms-bmp' => 'bmp',
            'application/bmp' => 'bmp',
            'application/x-bmp' => 'bmp',
            'application/x-win-bitmap' => 'bmp',
            'application/cdr' => 'cdr',
            'application/coreldraw' => 'cdr',
            'application/x-cdr' => 'cdr',
            'application/x-coreldraw' => 'cdr',
            'image/cdr' => 'cdr',
            'image/x-cdr' => 'cdr',
            'zz-application/zz-winassoc-cdr' => 'cdr',
            'application/mac-compactpro' => 'cpt',
            'application/pkix-crl' => 'crl',
            'application/pkcs-crl' => 'crl',
            'application/x-x509-ca-cert' => 'crt',
            'application/pkix-cert' => 'crt',
            'text/css' => 'css',
            'text/x-comma-separated-values' => 'csv',
            'text/comma-separated-values' => 'csv',
            'application/vnd.msexcel' => 'csv',
            'application/x-director' => 'dcr',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/x-dvi' => 'dvi',
            'message/rfc822' => 'eml',
            'application/x-msdownload' => 'exe',
            'video/x-f4v' => 'f4v',
            'audio/x-flac' => 'flac',
            'video/x-flv' => 'flv',
            'image/gif' => 'gif',
            'application/gpg-keys' => 'gpg',
            'application/x-gtar' => 'gtar',
            'application/x-gzip' => 'gzip',
            'application/mac-binhex40' => 'hqx',
            'application/mac-binhex' => 'hqx',
            'application/x-binhex40' => 'hqx',
            'application/x-mac-binhex40' => 'hqx',
            'text/html' => 'html',
            'image/x-icon' => 'ico',
            'image/x-ico' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'text/calendar' => 'ics',
            'application/java-archive' => 'jar',
            'application/x-java-application' => 'jar',
            'application/x-jar' => 'jar',
            'image/jp2' => 'jp2',
            'video/mj2' => 'jp2',
            'image/jpx' => 'jp2',
            'image/jpm' => 'jp2',
            'image/jpeg' => 'jpeg',
            'image/pjpeg' => 'jpeg',
            'application/x-javascript' => 'js',
            'application/json' => 'json',
            'text/json' => 'json',
            'application/vnd.google-earth.kml+xml' => 'kml',
            'application/vnd.google-earth.kmz' => 'kmz',
            'text/x-log' => 'log',
            'audio/x-m4a' => 'm4a',
            'audio/mp4' => 'm4a',
            'application/vnd.mpegurl' => 'm4u',
            'audio/midi' => 'mid',
            'application/vnd.mif' => 'mif',
            'video/quicktime' => 'mov',
            'video/x-sgi-movie' => 'movie',
            'audio/mpeg' => 'mp3',
            'audio/mpg' => 'mp3',
            'audio/mpeg3' => 'mp3',
            'audio/mp3' => 'mp3',
            'video/mp4' => 'mp4',
            'video/mpeg' => 'mpeg',
            'application/oda' => 'oda',
            'audio/ogg' => 'ogg',
            'video/ogg' => 'ogg',
            'application/ogg' => 'ogg',
            'font/otf' => 'otf',
            'application/x-pkcs10' => 'p10',
            'application/pkcs10' => 'p10',
            'application/x-pkcs12' => 'p12',
            'application/x-pkcs7-signature' => 'p7a',
            'application/pkcs7-mime' => 'p7c',
            'application/x-pkcs7-mime' => 'p7c',
            'application/x-pkcs7-certreqresp' => 'p7r',
            'application/pkcs7-signature' => 'p7s',
            'application/pdf' => 'pdf',
            'application/octet-stream' => 'pdf',
            'application/x-x509-user-cert' => 'pem',
            'application/x-pem-file' => 'pem',
            'application/pgp' => 'pgp',
            'application/x-httpd-php' => 'php',
            'application/php' => 'php',
            'application/x-php' => 'php',
            'text/php' => 'php',
            'text/x-php' => 'php',
            'application/x-httpd-php-source' => 'php',
            'image/png' => 'png',
            'image/x-png' => 'png',
            'application/powerpoint' => 'ppt',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.ms-office' => 'ppt',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-photoshop' => 'psd',
            'image/vnd.adobe.photoshop' => 'psd',
            'audio/x-realaudio' => 'ra',
            'audio/x-pn-realaudio' => 'ram',
            'application/x-rar' => 'rar',
            'application/rar' => 'rar',
            'application/x-rar-compressed' => 'rar',
            'audio/x-pn-realaudio-plugin' => 'rpm',
            'application/x-pkcs7' => 'rsa',
            'text/rtf' => 'rtf',
            'text/richtext' => 'rtx',
            'video/vnd.rn-realvideo' => 'rv',
            'application/x-stuffit' => 'sit',
            'application/smil' => 'smil',
            'text/srt' => 'srt',
            'image/svg+xml' => 'svg',
            'application/x-shockwave-flash' => 'swf',
            'application/x-tar' => 'tar',
            'application/x-gzip-compressed' => 'tgz',
            'image/tiff' => 'tiff',
            'font/ttf' => 'ttf',
            'text/plain' => 'txt',
            'text/x-vcard' => 'vcf',
            'application/videolan' => 'vlc',
            'text/vtt' => 'vtt',
            'audio/x-wav' => 'wav',
            'audio/wave' => 'wav',
            'audio/wav' => 'wav',
            'application/wbxml' => 'wbxml',
            'video/webm' => 'webm',
            'image/webp' => 'webp',
            'audio/x-ms-wma' => 'wma',
            'application/wmlc' => 'wmlc',
            'video/x-ms-wmv' => 'wmv',
            'video/x-ms-asf' => 'wmv',
            'font/woff' => 'woff',
            'font/woff2' => 'woff2',
            'application/xhtml+xml' => 'xhtml',
            'application/excel' => 'xl',
            'application/msexcel' => 'xls',
            'application/x-msexcel' => 'xls',
            'application/x-ms-excel' => 'xls',
            'application/x-excel' => 'xls',
            'application/x-dos_ms_excel' => 'xls',
            'application/xls' => 'xls',
            'application/x-xls' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-excel' => 'xlsx',
            'application/xml' => 'xml',
            'text/xml' => 'xml',
            'text/xsl' => 'xsl',
            'application/xspf+xml' => 'xspf',
            'application/x-compress' => 'z',
            'application/x-zip' => 'zip',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
            'application/s-compressed' => 'zip',
            'multipart/x-zip' => 'zip',
            'text/x-scriptzsh' => 'zsh',
        ];

        return $mime_map[$mime] ?? false;
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
            $stmt = $pdo->prepare("SELECT fileUploadCount FROM jobTypes JOIN jobs on JobTypes.jobTypeID = jobs.jobTypeID WHERE jobs.jobID = :jobID AND jobs.userID = :userID");
            $stmt->bindParam(":jobID", $jobID);
            $stmt->bindParam(":userID", $userId);
            $stmt->execute();
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$job) {
                $response->getBody()->write("Job not found");
                return $response->withStatus(404);
            } elseif ((int)$job["fileUploadCount"] < 2) {
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
            $stmt = $pdo->prepare("SELECT fileUploadCount FROM jobTypes JOIN jobs on JobTypes.jobTypeID = jobs.jobTypeID WHERE jobs.jobID = :jobID AND jobs.userID = :userID");
            $stmt->bindParam(":jobID", $jobID);
            $stmt->bindParam(":userID", $userId);
            $stmt->execute();
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$job) {
                $response->getBody()->write("Job not found");
                return $response->withStatus(404);
            } elseif ((int)$job["fileUploadCount"] < 2) {
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
            $stmt = $pdo->prepare("SELECT fileUploadCount FROM jobTypes JOIN jobs on JobTypes.jobTypeID = jobs.jobTypeID WHERE jobs.jobID = :jobID AND jobs.userID = :userID");
            $stmt->bindParam(":jobID", $jobID);
            $stmt->bindParam(":userID", $userId);
            $stmt->execute();
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$job) {
                $response->getBody()->write("Job not found");
                return $response->withStatus(404);
            } elseif ((int)$job["fileUploadCount"] < 2) {
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
                $response->getBody()->write("Record Updated");
                return $response->withStatus(204);
            }catch(Exception $e){
                Logger::error($e, $request->getRequestTarget());
                $response->getBody()->write("Internal Server Error");
                return $response->withStatus(500);
            }
    }
}