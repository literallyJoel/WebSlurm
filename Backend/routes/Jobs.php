<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

include_once __DIR__ . "/../config/Config.php";
require_once __DIR__ . "/../helpers/Logger.php";

class Jobs
{
    public function __construct()
    {
    }
    //==============================================================================//
    //===============================Helper Functions==============================//
    //============================================================================//

    //Checks if the provided fileId is valid for the given user
    private function validateFileId($fileId, $userId): ?bool
    {
        try {
            $pdo = new PDO(DB_CONN);
            $getFileIdStmt = $pdo->prepare("SELECT * FROM fileIds where fileId = :fileId AND userId = :userId");#
            $getFileIdStmt->bindParam(":fileId", $fileId);
            $getFileIdStmt->bindParam(":userId", $userId);
            $ok = $getFileIdStmt->execute();
            if (!$ok) {
                throw new Error("PDO Exception: " . print_r($getFileIdStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, "Jobs/validateFileId");
            return null;
        }

        $file = $getFileIdStmt->fetch(PDO::FETCH_ASSOC);
        return !!$file;
    }


    //Gets the details of the provided Job Type
    private function getJobType($jobTypeId)
    {

        try {
            $pdo = new PDO(DB_CONN);
            $getJobTypeStmt = $pdo->prepare("SELECT * FROM jobTypes WHERE jobTypeId = :jobTypeId");
            $getJobTypeStmt->bindParam("jobTypeId", $jobTypeId);
            $ok = $getJobTypeStmt->execute();
            if (!$ok) {
                throw new Error("PDO ERROR: " . print_r($getJobTypeStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, "Jobs/getJobType");
            return false;
        }


        return $getJobTypeStmt->fetch(PDO::FETCH_ASSOC);
    }

    private function createInitialJobRecord($userId, $jobTypeId, $jobName, $fileId, $organisationId = null)
    {
        $pdo = new PDO(DB_CONN);
        try {
            $query = "INSERT INTO jobs (slurmId, userId, jobStartTime, jobComplete, jobTypeId, jobName, jobComplete";
            $query .= $fileId ? ", fileId)" : ")";
            $query .= "VALUES (-1, :userId, :jobStartTime, 0, :jobTypeId, :jobName, 0";
            $query .= $fileId ? ", :fileId)" : ")";

            $createJobRecordStmt = $pdo->prepare($query);
            $createJobRecordStmt->bindParam(":userId", $userId);
            $currentTime = time();
            $createJobRecordStmt->bindParam(":jobStartTime", $currentTime);
            $createJobRecordStmt->bindParam(":jobTypeId", $jobTypeId);
            $createJobRecordStmt->bindparam(":jobName", $jobName);

            if ($fileId) {
                $createJobRecordStmt->bindParam("fileId", $fileId);
            }

            $ok = $createJobRecordStmt->execute();
            if (!$ok) {
                throw new Error("PDO ERROR: " . print_r($createJobRecordStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, "Jobs/createInitialJobRecord");
            return false;
        }

        $lastId = $pdo->lastInsertId();
        Logger::info("Created new job record $lastId for user with ID $userId", "Jobs/createIntialJobRecord");
        return $lastId;
    }

    //Ensures the directories for input, output, and script files are present
    private function setupUserDir(string $userId, string $jobId)
    {
        try {
            $inDir = __DIR__ . "/../usr/in/" . $userId . "/";
            $outDir = __DIR__ . "/../usr/out/" . $userId . "/" . $jobId . "/";
            $scriptDir = __DIR__ . "/../usr/script/" . $userId . "/" . $jobId . "/";

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

    //Creates the job script from the template using the provided info.
    private function createFromScriptTemplate($script, $jobName, $jobId, $parameters, $dirs, $jobType, $fileId)
    {
        $arrayJobSupport = $jobType["arrayJobSupport"] ?? false;
        $arrayJobCount = $jobType["arrayJobCount"] ?? false;
        $hasOutputFile = $jobType["hasOutputFile"] ?? false;
        //Swap out windows carriage returns
        $modified = str_replace("\r\n", "\n", $script);

        //Add in the enforced lines. This is done on the front-end also, here is just validation/enforcement
        $modified = "#!/bin/bash\n#SBATCH --job-name=*{name}*\n#SBATCH --output=*{out}*" . "\n" . implode("\n", array_slice(explode("\n", $modified), 3));
        $modified = str_replace("*{name}*", escapeshellarg($jobName), $modified);
        $modified = str_replace("*{out}*", $dirs["out"] . ($arrayJobSupport ? "/slurmout-%a" : "/slurmout"), $modified);

        //Do the parameter swap
        foreach ($parameters as $parameter) {
            $modified = str_replace("{{" . $parameter->key . "}}", escapeshellarg($parameter->value), $modified);
        }

        //Setup the input files
        if ($arrayJobSupport) {
            $scriptArr = explode("\n", $modified);
            $inDir = $dirs["in"] . $jobId . "/";
            $files = scandir($inDir);
            $count = 0;
            $fileCount = 0;
            foreach ($files as $file) {
                if (preg_match("/^$fileId(-\d+)?$/", $file)) {
                    $zip = new ZipArchive;

                    if ($zip->open($inDir . $file)) {
                        $extractDir = $inDir . $file . "-extracted";
                        if (!file_exists($extractDir)) {
                            mkdir($extractDir, 0775, true);
                        }
                        $zip->extractTo($extractDir);
                        $extractedFiles = glob($extractDir . "/*");
                        foreach ($extractedFiles as $extractedFile) {
                            $newName = preg_replace('/\\.[^.\\s]{3,4}$/', '', $extractedFile);
                            rename($extractedFile, $newName);
                        }
                        $count++;
                        $fileCount = count($extractedFiles);
                        $zip->close();
                    }
                }
            }

            if ($count > 0) {

                if (intval($arrayJobCount) < 2) {
                    for ($i = 0; $i < $count; $i++) {
                        $toAppend = ('arrayfile' . ($i === 0 ? "" : $i) . "=\"" . $dirs["in"] . "$jobId/" . $fileId . ($i === 0 ? "" : "-$i") . "-extracted" . '/file${SLURM_ARRAY_TASK_ID}' . "\"\n");
                        array_splice($scriptArr, 4, 0, $toAppend);
                    }
                } else {
                    for ($i = 0; $i < $count; $i++) {
                        $toAppend = ('arrayfile' . $i . "=\"" . $dirs["in"] . "$jobId/" . $fileId . ($i === 0 ? "" : "-$i") . "-extracted" . '/file${SLURM_ARRAY_TASK_ID}' . "\"\n");
                        array_splice($scriptArr, 4, 0, $toAppend);
                    }
                }


                $toAppend = "#SBATCH --array=0-" . ($fileCount - 1) . "\n";
                array_splice($scriptArr, 3, 0, $toAppend);
                $modified = implode("\n", $scriptArr);
            }
        } else if ($fileId) {
            $filePath = $dirs["in"] . "$jobId/$fileId";
            if (file_exists($filePath)) {
                if (mime_content_type($filePath) === "application/zip") {
                    $zip = new ZipArchive();
                    $extractDir = $filePath . "-extracted";
                    if (!is_dir($extractDir)) {
                        mkdir($extractDir, 0775, true);
                    }

                    if ($zip->open($filePath)) {
                        $zip->extractTo($extractDir);

                        $extractedFiles = glob($extractDir . "/*");
                        foreach ($extractedFiles as $file) {
                            $newName = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file);
                            rename($file, $newName);
                        }
                        $zip->close();

                        $scriptArr = explode("\n", $modified);
                        for ($i = 0; $i < count($extractedFiles); $i++) {
                            $toAdd = "file$i=$extractDir/file$i";
                            array_splice($scriptArr, 4, 0, $toAdd);
                        }

                        $modified = implode("\n", $scriptArr);

                    } else {
                        return false;
                    }
                } else {
                    $toAdd = "file0=" . $dirs["in"] . "$jobId/$fileId";
                    $scriptArr = explode("\n", $modified);
                    array_splice($scriptArr, 4, 0, $toAdd);
                    $modified = implode("\n", $scriptArr);
                }
            }
        }


        if ($hasOutputFile) {
            $outputCount = $jobType["outputCount"] ?? 0;
            $outputVars = [];
            for ($i = 0; i < $outputCount; $i++) {
                $outputVars[] = "out$i=" . $dirs["out"] . "out$i";
            }

            $outputVars = implode("\n", $outputVars);
            $scriptArr = explode("\n", $modified);
            array_splice($scriptArr, 4, 0, $outputVars);
            $modified = implode("\n", $modified);
        }
        //Append job completion script
        $url = APP_URI . "/api/jobs/" . $jobId . "/markcomplete";
        $modified .= "\ncurl -X POST $url > /dev/null 2>&1";

        return $modified;
    }


    //Schedules a job in SLURM
    private function scheduleSlurmJob($script, $scriptDir, $jobId)
    {

        //Create the name for the script file and save it to the filesystem for slurm to run
        $scriptFile = $jobId . "-" . date("Y-m-d+H-i-s") . ".sh";
        $scriptPath = $scriptDir . $scriptFile;
        Logger::debug("Created new script file $scriptPath", "Jobs/scheduleSlurmJob");
        file_put_contents($scriptPath, $script);

        //Execute the script in Slurm
        $output = shell_exec("cd $scriptDir && sbatch $scriptFile");
        Logger::debug("Scheduled Slurm Job: $output", "Jobs/scheduleSlurmJob");
        $resp = ["output" => $output];
        $pdo = new PDO(DB_CONN);

        try {

            //Grab the Task ID from Slurm
            preg_match('/\d+/', $output, $matches);
            $slurmId = isset($matches[0]) ? (int)$matches[0] : null;
            if (!$slurmId) {
                Logger::error("Invalid Input Received: $output", "Jobs/scheduleSlurmJob");
                return false;
            }
            $updateJobStmt = $pdo->prepare("UPDATE Jobs set slurmId = :slurmId WHERE jobId = :jobId");
            $updateJobStmt->bindParam(":slurmId", $slurmId);
            $updateJobStmt->bindParam(":jobId", $jobId);
            $ok = $updateJobStmt->execute();
            if (!$ok) {
                throw new Error("PDO ERROR: " . print_r($updateJobStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, "Jobs/scheduleSlurmJob");
            $deleteJobStmt = $pdo->prepare("DELETE FROM Jobs WHERE jobId = :jobId");
            $deleteJobStmt->bindParam(":jobId", $jobId);
            $deleteJobStmt->execute();
            return false;
        }

        return json_encode($resp);
    }

    private function getUserJobs($userId, $filter = "", $jobId = null, $limit = null, $organisationIds = null)
    {
        $pdo = new PDO(DB_CONN);
        try {
            $query = "SELECT jobs.*, jobTypes.jobTypeName, users.userName as createdByName
FROM jobs 
JOIN jobTypes ON jobs.jobTypeId = jobTypes.jobTypeId 
LEFT JOIN organisationJobs ON jobs.jobId = organisationJobs.jobId 
LEFT JOIN organisationUsers ON organisationJobs.organisationId = organisationUsers.organisationId
LEFT JOIN users ON jobs.userId = users.userId
WHERE jobs.userId = :userId OR organisationUsers.userId = :userId
";

            if (!empty($organisationIds)) {
                $orgPlaceholders = implode(',', array_fill(0, count($organisationIds), '?'));
                $query .= " OR (organisationJobs.organisationId IN ($orgPlaceholders))";
            }

            if ($jobId) {
                $query .= " AND jobs.jobId = :jobId";
            } else {
                switch ($filter) {
                    case "complete":
                        $query .= " AND jobComplete = 1";
                        break;
                    case "running":
                        $query .= " AND jobComplete = 0";
                        break;
                    case "failed":
                        $query .= " AND jobComplete = 2";
                        break;
                }

                if ($limit) {
                    $query .= " LIMIT :limit";
                }
            }

            $getJobsStmt = $pdo->prepare($query);
            if (!$getJobsStmt) {
                throw new Error("PDO Error: " . print_r($pdo->errorInfo(), true));
            }
            $getJobsStmt->bindParam(":userId", $userId);
            if (!empty($organisationIds)) {
                foreach ($organisationIds as $index => $orgId) {
                    $getJobsStmt->bindValue(($index + 1), $orgId);
                }
            }
            if ($jobId) {
                $getJobsStmt->bindParam(":jobId", $jobId);
            }
            if ($limit) {
                $getJobsStmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            }
            $ok = $getJobsStmt->execute();

            if (!$ok) {
                throw new Error("PDO ERROR: " . print_r($getJobsStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, "jobs/getUserJobs");
            return null;
        }

        return $getJobsStmt->fetchAll(PDO::FETCH_ASSOC);
    }


    //Updates the list of failed jobs by checking if there are any jobs marked as running in the DB that are not running in Slurm
    private function updateFailed($userId): ?bool
    {
        try {
            $pdo = new PDO(DB_CONN);
            $runningJobs = $this->getUserJobs($userId, "running");

            if ($runningJobs === null) {
                return null;
            }

            if (!$runningJobs) {
                return true;
            }

            $slurmJobs = shell_exec("squeue --nohead --format=%F | uniq");
            $slurmJobs = explode("\n", $slurmJobs);

            $failedJobs = array_filter($slurmJobs, function ($slurmJob) use ($runningJobs) {
                foreach ($runningJobs as $job) {
                    if ($slurmJob === $job["slurmId"]) {
                        return true;
                    }
                }

                return false;
            });

            $placeholders = implode(",", array_fill(0, count($failedJobs), '?'));
            $updateFailedStmt = $pdo->prepare("UPDATE jobs SET jobComplete = 2 WHERE jobId IN ($placeholders)");
            foreach ($failedJobs as $key => $id) {
                $updateFailedStmt->bindValue(($key + 1), $id, PDO::PARAM_INT);
            }
            $ok = $updateFailedStmt->execute();
            if (!$ok) {
                error_log("NOTOK");
                throw new Error("PDO ERROR: " . print_r($updateFailedStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, "Jobs/updateFailed");
            return null;
        }

        return $ok;
    }

    private function addToOrganisation($organisationId, $jobId): bool
    {
        $pdo = new PDO(DB_CONN);
        $pdo->beginTransaction();
        try {
            $addJobStmt = $pdo->prepare("INSERT INTO organisationJobs (jobId, organisationId) VALUES (:jobId, :organisationId)");


            $addJobStmt->bindParam(":jobId", $jobId);
            $addJobStmt->bindParam(":organisationId", $organisationId);

            if (!$addJobStmt->execute()) {
                throw new Error("Failed to add job with ID $jobId, to organisation with ID $organisationId: " . print_r($addJobStmt->errorInfo(), true));
            }


            if (!$pdo->commit()) {
                throw new Error("Failed to add job with ID $jobId to organisations");
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            Logger::error($e, "Jobs/addToOrganisation");
            return false;
        }

        return true;
    }



    //===========================================================================//
    //=================================Routes===================================//
    //=========================================================================//

    //=============================Create Job=============================//
    //============================Method: POST===========================//
    //=========================Route: /api/jobs/========================//

    public function create(Request $request, Response $response): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $body = json_decode($request->getBody());
        $jobTypeId = $body->jobTypeId;
        $jobName = $body->jobName;
        $parameters = $body->parameters;
        $fileId = $body->fileId;
        $organisationId = $body->organisationId ?? null;

        try {
            //If there is a fileId included, we check if it is a valid fileId associated with the User
            if (!!$fileId) {
                $validationResult = $this->validateFileId($fileId, $userId);
                //If null then there's been an error
                if (is_null($validationResult)) {
                    //The error gets logged in the function, so we use this to prevent double logging.
                    //It would make more sense to just throw the error in the function but this was a quicker change
                    throw new Error("~reported~");
                }

                //If false then invalid
                if (!$validationResult) {
                    $response->getBody()->write("Bad Request");
                    return $response->withStatus(400);
                }

            }

            $organisations = new Organisations();
            if ($organisationId) {
                if ($organisations->_getUserRole($userId, $organisationId) !== 0) {
                    $response->getBody()->write("Unauthorized");
                    return $response->withStatus(401);
                }
            }
            //Grab the jobType info
            $jobType = $this->getJobType($jobTypeId);
            if (!$jobType) {
                $response->getBody()->write("Bad Request");
                return $response->withStatus(400);
            }

            //We need the database ID, so we create an initial record and grab the ID.
            $jobId = $this->createInitialJobRecord($userId, $jobTypeId, $jobName, $fileId);

            if (!$jobId) {
                throw new Error("~reported~");
            }

            //Move the files into a folder associated with the job.
            if (!!$fileId) {
                $userDir = __DIR__ . "/../usr/in/$userId";
                if (!is_dir("$userDir/$jobId")) {
                    mkdir("$userDir/$jobId");
                }
                $files = glob("$userDir/*");
                foreach ($files as $file) {
                    error_log("FileId: $fileId\nFile: " . basename($file));
                    if (strpos(basename($file), $fileId) !== false) {
                        error_log("Match");
                        $newDir = "$userDir/$jobId/" . basename($file);
                        rename($file, $newDir);
                    } else {
                        error_log(strpos(basename($file), $fileId));
                    }
                }
            }
            //Setup the user directories for this job
            $userDirectories = $this->setupUserDir($userId, $jobId);
            if (!$userDirectories) {
                throw new Error("Failed to create directories for job with ID $jobId for user with ID $userId");
            }

            //Perform the templating
            $script = $this->createFromScriptTemplate($jobType["script"], $jobName, $jobId, $parameters, $userDirectories, $jobType, $fileId);

            if (!$script) {
                $pdo = new PDO(DB_CONN);
                $stmt = $pdo->prepare("DELETE FROM jobs WHERE jobId = :jobId");
                $stmt->bindParam(":jobId", $jobId);
                $stmt->execute();
                Logger::warning("Failed to setup input files for job with ID $jobId", $request->getRequestTarget());
                $response->getBody()->write("Internal Server Error");
                return $response->withStatus(500);
            }

            //Schedule the job with SLURM
            $resp = $this->scheduleSlurmJob($script, $userDirectories["script"], $jobId);
            if (!$resp) {
                throw new Error("~reported~");
            }
            if (!empty($organisationId)) {
                if (!$this->addToOrganisation($organisationId, $jobId)) {
                    throw new Error("~reported~");
                }
            }

        } catch (Exception $e) {
            $pdo = new PDO(DB_CONN);
            $stmt = $pdo->prepare("DELETE FROM jobs WHERE jobId = :jobId");
            $stmt->bindParam(":jobId", $jobId);
            $stmt->execute();
            $response->getBody()->write("Internal Server Error");
            if ($e->getMessage() !== "~reported~") {
                Logger::error($e, $request->getRequestTarget());
            }
            return $response->withStatus(500);
        }

        Logger::debug("Crea ted new Job with ID $jobId for user with ID $userId", $request->getRequestTarget());
        $response->getBody()->write($resp);
        return $response->withStatus(200);
    }


    //==========================Mark Job Complete=========================//
    //============================Method: POST===========================//
    //================Route: /api/jobs/{jobId}/markcomplete=============//

    public function markComplete(Request $request, Response $response, array $args): Response
    {
        $jobId = $args["jobId"] ?? null;
        if (!$jobId) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        $pdo = new PDO(DB_CONN);
        try {
            $markCompleteStmt = $pdo->prepare("UPDATE jobs set jobCompleteTime = :jobCompleteTime, jobComplete = 1 WHERE jobId = :jobId");
            $markCompleteStmt->bindParam(":jobId", $jobId);
            $time = time();
            $markCompleteStmt->bindParam(":jobCompleteTime", $time);
            $ok = $markCompleteStmt->execute();
            if (!$ok) {
                throw new Error("PDO ERROR: " . print_r($markCompleteStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }


        $response->getBody()->write("OK");
        return $response->withStatus(200);
    }

    //=============================DELETE Job=============================//
    //============================Method: DELETE=========================//
    //======================Route: /api/jobs/{jobId}====================//
    public function delete(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $jobId = $args["jobId"] ?? null;

        if (!$jobId) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        $pdo = new PDO(DB_CONN);

        try {
            $deleteJobStmt = $pdo->prepare("DELETE from Jobs WHERE jobId = :jobId AND userId = :userId");
            $deleteJobStmt->bindParam(":jobId", $jobId);
            $deleteJobStmt->bindParam("userId", $userId);
            $ok = $deleteJobStmt->execute();
            if (!$ok) {
                throw new Error("PDO ERROR: " . print_r($deleteJobStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $response->getBody()->write("Record Deleted");
        return $response->withStatus("200");
    }


    //============================Get Running Jobs========================//
    //============================Method: GET============================//
    //======================Route: /api/jobs/running===================//
    public function getRunning(Request $request, Response $response): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $queryParams = $request->getQueryParams();
        $limit = $queryParams["limit"];

        $jobs = $this->getUserJobs($userId, "running", null, $limit);
        if ($jobs === null) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
        if (!$jobs) {
            $response->getBody()->write(json_encode([]));
            return $response->withStatus(200);
        }

        if (!is_array($jobs)) {
            $jobs = [$jobs];
        }
        $response->getBody()->write(json_encode($jobs));
        return $response->withStatus(200);

    }

    //============================Get Failed Jobs=========================//
    //============================Method: GET============================//
    //======================Route: /api/jobs/failed=====================//
    public function getFailed(Request $request, Response $response): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $queryParams = $request->getQueryParams();
        $limit = $queryParams["limit"];

        if ($this->updateFailed($userId) === null) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $jobs = $this->getUserJobs($userId, "failed", null, $limit);
        if ($jobs === null) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
        if (!$jobs) {
            $response->getBody()->write(json_encode([]));
            return $response->withStatus(200);
        }
        $response->getBody()->write(json_encode($jobs));
        return $response->withStatus(200);

    }

    //==========================Get Completed Jobs========================//
    //============================Method: GET============================//
    //=====================Route: /api/jobs/complete=====================//
    public function getComplete(Request $request, Response $response): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $queryParams = $request->getQueryParams();
        $limit = $queryParams["limit"];
        $jobs = $this->getUserJobs($userId, "complete", null, $limit);
        if ($jobs === null) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        if (!$jobs) {
            $response->getBody()->write(json_encode([]));
            return $response->withStatus(200);
        }
        $response->getBody()->write(json_encode($jobs));
        return $response->withStatus(200);
    }

    //==============================Get Jobs=============================//
    //============================Method: GET============================//
    //======================Route: /api/jobs[/{jobId}]===================//
    public function getJob(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $jobId = $args["jobId"] ?? null;
        try {
            $pdo = new PDO(DB_CONN);
            $getOrgsStmt = $pdo->prepare("SELECT organisationId from organisationUsers WHERE userId = :userId");
            $getOrgsStmt->bindParam(":userId", $userId);
            if (!$getOrgsStmt->execute()) {
                throw new Error("Failed to get organisations belonging to user with ID $userId: " . print_r($getOrgsStmt->errorInfo(), true));
            }

            $organisatonIds = $getOrgsStmt->fetchAll(PDO::FETCH_COLUMN);
            $jobs = $this->getUserJobs($userId, "", $jobId);
            if ($jobs === null) {
                throw new Error("~reported~");
            }

            if (!$jobs) {
                $response->getBody()->write(json_encode([]));
                return $response->withStatus(200);
            }
        } catch (Exception $e) {
            if ($e->getMessage() !== "~reported~") {
                Logger::error($e, $request->getRequestTarget());
            }
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
        $response->getBody()->write(json_encode($jobs));
        return $response->withStatus(200);
    }


    //============================Get Job Params=========================//
    //============================Method: GET============================//
    //================Route: /api/jobs/{jobId}/parameters================//
    public function getParameters(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $jobId = $args["jobId"] ?? null;

        if (!$jobId) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        $pdo = new PDO(DB_CONN);
        try {
            $getParamsStmt = $pdo->prepare("SELECT jobParameters.key, jobParemeters.value from jobParameters JOIN jobs ON jobParameters.jobId = jobs.jobId WHERE jobParameters.jobId = :jobId AND jobs.userId = :userId");
            $getParamsStmt->bindParam(":jobId", $jobId);
            $getParamsStmt->bindParam(":userId", $userId);
            $ok = $getParamsStmt->execute();
            if (!$ok) {
                throw new Error("PDO ERROR: " . print_r($getParamsStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }


        $parameters = $getParamsStmt->fetchAll(PDO::FETCH_ASSOC);
        $response->getBody()->write(json_encode($parameters));
        return $response->withStatus(200);
    }


}