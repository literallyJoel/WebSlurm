<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

include_once __DIR__ . "/../config/Config.php";
require_once __DIR__ . "/../helpers/Logger.php";

class JobTypes
{
    public function __construct()
    {
    }

    //===========================================================================//
    //=============================Helper Functions=============================//
    //=========================================================================//

    //Gets job types, with option for ID filter
    private function getJobTypes($id = null)
    {
        $pdo = new PDO(DB_CONN);

        try {
            $query = "SELECT jt.jobTypeId, jt.jobTypeName, jt.jobTypeDescription, jt.script, jt.userId AS createdBy, 
            jt.hasFileUpload, jt.arrayJobSupport, jt.hasOutputFile, jt.arrayJobCount, u.userName as createdByName, jtp.paramName, 
            jtp.paramType, jtp.defaultValue FROM jobTypes jt LEFT JOIN Users u ON jt.userId = u.userId LEFT JOIN 
            jobTypeParams jtp ON jt.jobTypeId = jtp.jobTypeID";
            $query .= $id ? " WHERE jt.jobTypeId = :jobTypeId" : "";
            $getJobTypesStmt = $pdo->prepare($query);
            if ($id) {
                $getJobTypesStmt->bindParam(":jobTypeId", $id);
            }
            $ok = $getJobTypesStmt->execute();
            if (!$ok) {

                throw new Error("PDO ERROR: " . print_r($getJobTypesStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, "JobTypes/getJobTypes");
            return null;
        }
        $result = [];
        //Split the data into a format the front-end can use
        while ($row = $getJobTypesStmt->fetch(PDO::FETCH_ASSOC)) {
            $jobTypeId = $row['jobTypeId'];

            $paramData = [
                'name' => $row['paramName'],
                'type' => $row['paramType'],
                'defaultValue' => $row['defaultValue'],
            ];

            if($paramData['name'] === null){
                $paramData = null;
            }

            // Check if the job type already exists in the result array
            if (!isset($result[$jobTypeId])) {
                $result[$jobTypeId] = [
                    'jobTypeId' => $jobTypeId,
                    'parameters' => [],
                    'script' => $row['script'],
                    'jobTypeName' => $row['jobTypeName'],
                    'jobTypeDescription' => $row['jobTypeDescription'],
                    'createdBy' => $row['createdBy'],
                    'createdByName' => $row['createdByName'],
                    'hasFileUpload' => $row['hasFileUpload'],
                    'hasOutputFile' => $row['hasOutputFile'],
                    'arrayJobSupport' => $row['arrayJobSupport'],
                    'arrayJobCount' => $row['arrayJobCount']
                ];
            }

            // Add parameters to the existing job type entry
            if($paramData){
                $result[$jobTypeId]['parameters'][] = $paramData;
            }

        }

        if($id){
            return $result[$id];
        }
        return array_values($result);
    }

    //Validates the input to the job type creation endpoint
    private function validateJobType(...$params): bool
    {
        //Loops through the parameters and does some checks based on type
        return (array_reduce($params, function ($carry, $param) {

            $isValid = $param !== null;
            $type = gettype($param);
            switch ($type) {
                case "string":
                {
                    $isValid = $isValid && strlen($param) !== 0;
                    break;
                }

                case "array":
                {
                    $isValid = $isValid && count($param) !== 0;
                }
            }
            if (gettype($param) === "string") {
                $isValid = $isValid && strlen($param) !== 0;
            }
            return $carry || $isValid;
        }, false));
    }
    //===========================================================================//
    //=================================Routes===================================//
    //=========================================================================//

    //===========================Create JobType===========================//
    //============================Method: POST===========================//
    //========================Route: /api/jobtypes/=====================//
    public function createJobType(Request $request, Response $response): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $body = json_decode($request->getBody());

        $jobTypeName = $body->jobTypeName;
        $jobTypeDescription = $body->jobTypeDescription;
        $script = $body->script;
        $parameters = $body->parameters;
        $arrayJobSupport = $body->arrayJobSupport;
        $hasFileUpload = $body->hasFileUpload;
        $hasOutputFile = $body->hasOutputFile;
        $outputCount = $body->outputCount ?? 0;
        $arrayJobCount = $body->arrayJobCount ?? 0;

        if (!$this->validateJobType($jobTypeName, $script, $parameters, $arrayJobSupport, $hasFileUpload, $hasOutputFile, $outputCount, $arrayJobCount, $jobTypeDescription)) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        $pdo = new PDO(DB_CONN);
        $pdo->beginTransaction();
        try {

            $createJobTypeStmt = $pdo->prepare("INSERT INTO jobTypes 
    (jobTypeName, jobTypeDescription, script, userId, hasOutputFile, outputCount, hasFileUpload, arrayJobSupport, arrayJobCount)
VALUES (:jobTypeName, :jobTypeDescription, :script, :userId, :hasOutputFile, :outputCount, :hasFileUpload, :arrayJobSupport, :arrayJobCount)");

            if(!$createJobTypeStmt){
                error_log(print_r($pdo->errorInfo(), true));
                throw new Error("PDO Error: " . print_r($pdo->errorInfo(), true));
            }
            $createJobTypeStmt->bindParam(":jobTypeName", $jobTypeName);
            $createJobTypeStmt->bindParam(":jobTypeDescription", $jobTypeDescription);
            $createJobTypeStmt->bindParam(":script", $script);
            $createJobTypeStmt->bindParam(":userId", $userId);
            $createJobTypeStmt->bindParam(":hasOutputFile", $hasOutputFile, PDO::PARAM_BOOL);
            $createJobTypeStmt->bindParam(":outputCount", $outputCount);
            $createJobTypeStmt->bindParam(":hasFileUpload", $hasFileUpload, PDO::PARAM_BOOL);
            $createJobTypeStmt->bindParam(":arrayJobSupport", $arrayJobSupport, PDO::PARAM_BOOL);
            $createJobTypeStmt->bindParam(":arrayJobCount", $arrayJobCount);
            $ok = $createJobTypeStmt->execute();
            if (!$ok) {
                throw new Error("PDO Error: " . print_r($createJobTypeStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            Logger::error($e, $request->getRequestTarget());
            $pdo->rollBack();
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        try {
            $jobTypeId = $pdo->lastInsertId();

            foreach ($parameters as $param) {
                $paramStmt = $pdo->prepare("INSERT INTO jobTypeParams (paramName, paramType, defaultValue, jobTypeId) VALUE (:paramName, :paramType, :defaultValue, :jobTypeId)");
                $paramStmt->bindParam(":paramName", $param->name);
                $paramStmt->bindParam("paramType", $param->type);
                $paramStmt->bindParam(":jobTypeId", $jobTypeId);
                $defaultVal = strval($param->defaultVal);
                $paramStmt->bindParam(":defaultValue", $defaultVal);
                $ok = $paramStmt->execute();
                if (!$ok) {
                    throw new Error("PDO Error: " . print_r($paramStmt->errorInfo(), true), $request->getRequestTarget());
                }
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $pdo->rollBack();
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $ok = $pdo->commit();
        if (!$ok) {
            Logger::error("PDO Error: " . print_r($pdo->errorInfo(), true), $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        Logger::debug("JobType with ID $jobTypeId created by user with ID $userId", $request->getRequestTarget());
        $response->getBody()->write(json_encode(["jobTypeId" => $jobTypeId]));
        return $response->withStatus(201);
    }

    //==========================Get All JobTypes==========================//
    //=============================Method: GET===========================//
    //================Route: /api/jobtypes[/{$jobTypeId}]===============//

    public function getJobType(Request $request, Response $response, array $args): Response
    {
        $jobTypeId = $args["jobTypeId"] ?? null;
        $jobTypes = $this->getJobTypes($jobTypeId);

        if ($jobTypes === null) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $response->getBody()->write(json_encode($jobTypes));
        return $response->withStatus(200);
    }



    //===========================Update JobType===========================//
    //============================Method: PUT===========================//
    //=================Route: /api/jobtypes/{jobTypeId}================//
    public function update(Request $request, Response $response, array $args): Response
    {
        $jobTypeId = $args['jobTypeId'] ?? null;
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $body = json_decode($request->getBody());
        $name = $body->jobTypeName;
        $description = $body->jobTypeDescription;
        $script = $body->script;
        $hasOutputFile = $body->hasOutputFile;
        $hasFileUpload = $body->hasFileUpload;
        $arrayJobSupport = $body->arrayJobSupport;
        $arrayJobCount = $body->arrayJobCount ?? 0;
        $parameters = $body->parameters ?? [];

        if (!$this->validateJobType($jobTypeId, $name, $description, $script, $hasOutputFile, $hasFileUpload, $arrayJobSupport, $arrayJobCount, $parameters)) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        $pdo = new PDO(DB_CONN);
        $pdo->beginTransaction();
        try {
            $deleteParamsStmt = $pdo->prepare("DELETE from jobTypeParams WHERE jobTypeId = :jobTypeId");
            $deleteParamsStmt->bindParam(":jobTypeId", $jobTypeId);
            $ok = $deleteParamsStmt->execute();

            if (!$ok) {
                throw new Error("PDO Exception: " . print_r($deleteParamsStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $pdo->rollBack();
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        try {
            $updateJobTypeStmt = $pdo->prepare("UPDATE jobTypes SET jobTypeName = :jobTypeName, script = :script, jobTypeDescription = :jobTypeDescription, hasOutputFile = :hasOutputFile, outputCount = :outputCount, hasFileUpload = :hasFileUpload, arrayJobSupport = :arrayJobSupport, arrayJobCount = :arrayJobCount WHERE jobTypeId= :jobTypeId");
            $updateJobTypeStmt->bindParam(":jobTypeName", $name);
            $updateJobTypeStmt->bindParam("jobTypeDescription", $description);
            $updateJobTypeStmt->bindParam(":hasOutputFile", $hasOutputFile, PDO::PARAM_BOOL);
            $updateJobTypeStmt->bindParam(":hasFileUpload", $hasFileUpload, PDO::PARAM_BOOL);
            $updateJobTypeStmt->bindParam(":arrayJobSupport", $arrayJobSupport, PDO::PARAM_BOOL);
            $updateJobTypeStmt->bindParam(":outputCount", $outputCount);
            $updateJobTypeStmt->bindParam(":script", $script);
            $updateJobTypeStmt->bindParam(":jobTypeId", $jobTypeId);
            $updateJobTypeStmt->bindParam(":arrayJobCount", $arrayJobCount);
            $ok = $updateJobTypeStmt->execute();
            if (!$ok) {
                throw new Error("PDO Error: " . print_r($updateJobTypeStmt->errorInfo(), true), $request->getRequestTarget());
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $pdo->rollBack();
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        if (!$pdo->commit()) {
            Logger::error("Failed to update job type with ID $jobTypeId for user with ID $userId. PDO Error: " . print_r($pdo->errorInfo(), true), $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        Logger::debug("JoType with ID $jobTypeId, for user with ID $userId", $request->getRequestTarget());
        $response->getBody()->write("Record Updated");
        return $response->withStatus(200);

    }

    //===========================DeleteJobType===========================//
    //==========================Method: DELETE==========================//
    //=================Route: /api/jobtypes/{jobTypeId}================//
    public function deleteById(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $jobTypeId = $args["jobTypeId"] ?? null;

        if (!$jobTypeId) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        $pdo = new PDO(DB_CONN);
        try {
            $pdo->beginTransaction();

            $deleteParamsStmt = $pdo->prepare("DELETE FROM jobTypeParams WHERE jobTypeId = :jobTypeId");
            $deleteParamsStmt->bindParam(":jobTypeId", $jobTypeId);
            if (!$deleteParamsStmt->execute()) {
                throw new Error("PDO Error: " . print_r($deleteParamsStmt->errorInfo(), true), $request->getRequestTarget());
            }

            $deleteJobTypeStmt = $pdo->prepare("DELETE FROM jobTypes WHERE jobTypeId = :jobTypeId");
            $deleteJobTypeStmt->bindParam(":jobTypeId", $jobTypeId);
            if (!$deleteJobTypeStmt->execute()) {
                throw new Error("PDO Error " . print_r($deleteJobTypeStmt->errorInfo(), true), $request->getRequestTarget());
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $pdo->rollBack();
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        if (!$pdo->commit()) {
            Logger::error("Failed to delete jobType with ID $jobTypeId foruser with ID $userId. PDO Error: " . print_r($pdo->errorInfo(), true), $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        Logger::debug("Deleted Job Type with ID $jobTypeId for user with ID $userId", $request->getRequestTarget());
        return $response->withStatus(200);
    }
}