<?php


use DI\Container;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\StreamFactory;
use SpazzMarticus\Tus\Factories\FilenameFactoryInterface;
use SpazzMarticus\Tus\Factories\OriginalFilenameFactory;
use SpazzMarticus\Tus\Providers\LocationProviderInterface;
use SpazzMarticus\Tus\Providers\PathLocationProvider;
use SpazzMarticus\Tus\TusServer;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\EventDispatcher\EventDispatcher;

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


        if ($fileID !== null) {

            $stmt = $pdo->prepare("SELECT * FROM fileIDs WHERE fileID = :fileID AND userID = :userID");
            $stmt->bindParam(":fileID", $fileID);
            $stmt->bindParam(":userID", $userID);
            $stmt->execute();
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$file) {
                $response->getBody()->write("Bad Request");
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
            if ($fileID !== null) {
                $stmt->bindParam(":fileID", $fileID);
            }
            $stmt->execute();
            $newId = $pdo->lastInsertId();
        } catch (Exception $e) {
            error_log($e);
            $response->getBody()->write("Error creating job");
            return $response->withStatus(500);
        }


        //We insert the parameters provided into the database for logging purposes.
        try {
            foreach ($parameters as $parameter) {
                $stmt = $pdo->prepare("INSERT INTO jobParameters (jobID, key, value) VALUES (:jobID, :key, :value)");
                $stmt->bindParam(":jobID", $newId);
                $stmt->bindParam(":key", $parameter->key);
                $stmt->bindParam(":value", $parameter->value);
                $stmt->execute();
            }
        } catch (Exception $e) {
            error_log($e);
            $repsonse->getBody()->write("Error creating job");
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
            //Append the file input location to the script
            if($fileID !== null){
                $filePath = "file0=" . __DIR__ . "/../usr/in/" . $userID . "/" . $fileID;
                $scriptArr = explode("\n", $script);
                array_splice($scriptArr, 4, 0, $filePath);
                $script = implode("\n", $scriptArr);

            }
            file_put_contents(__DIR__ . "/../usr/script/" . $userID . "-" . $newId . "-" . date('Y-m-d_H-i-s') . ".sh", $this->replaceLineBreaks($script));

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


        //Run the script in slurm and return the output to the client
        $output = shell_exec('cd ' . __DIR__ . '/../usr/script/ && sbatch ' . $userID . "-" . $newId . "-" . date('Y-m-d_H-i-s') . ".sh");
        $resp = array("output" => $output);
        $response->getBody()->write(json_encode($resp));
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
        };

        return $response->withStatus(200);

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

            $container = new Container();

            $container->set(ResponseFactoryInterface::class, new ResponseFactory());
            $container->set(StreamFactoryInterface::class, new StreamFactory());
            $container->set(SimpleCacheInterface::class, new Psr16Cache(new FilesystemAdapter()));
            $container->set(EventDispatcherInterface::class, new EventDispatcher());
            $container->set(FilenameFactoryInterface::class, function () use ($path) {
                return new OriginalFilenameFactory($path);
            });

            $container->set(LocationProviderInterface::class, new PathLocationProvider);
            $tus = new TusServer(
                $container->get(ResponseFactoryInterface::class),
                $container->get(StreamFactoryInterface::class),
                $container->get(SimpleCacheInterface::class),
                $container->get(EventDispatcherInterface::class),
                $container->get(FilenameFactoryInterface::class),
                $container->get(LocationProviderInterface::class)
            );


            $tus->setMaxSize(10737419264 * 1.5);
            return $tus->handle($request);
        } catch (Exception $e) {
            error_log($e);
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
            return $response->withStatus(200);
        } catch (Exception $e) {
            error_log($e);
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
            $response->getBody()->write(json_encode($job));
            return $response->withStatus(200);
        } else {
            $response->getBody()->write("Job not found");
            return $response->withStatus(404);
        }
    }

    public function getParameters(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
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

    public function generateFileID(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
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
    }

    public function downloadOutputFile(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $decoded = $request->getAttribute("decoded");
        $userID = $decoded->userID;
        $jobID = $args["jobID"];

        $filePath = __DIR__ . "/../usr/out/" . $userID . "/" . $jobID;
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($filePath);
        $response = $response->withHeader("Content-Type", $mime);
        $response = $response->withHeader("Content-Disposition", "attachment; filename=" . $jobID . "." . $this->getExtension($mime));
        $response->getBody()->write(file_get_contents($filePath));
        return $response->withStatus(200);
    }
}   
