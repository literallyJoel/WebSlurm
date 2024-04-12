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
    private function getJobTypes($id = null, $userId = null)
    {
        $pdo = new PDO(DB_CONN);

        $query = "SELECT jt.jobTypeId, jt.jobTypeName, jt.outputCount, jt.jobTypeDescription, jt.script, jt.userId AS createdBy, 
        jt.hasFileUpload, jt.arrayJobSupport, jt.hasOutputFile, jt.arrayJobCount, u.userName as createdByName, jtp.paramName, 
        jtp.paramType, jtp.defaultValue FROM jobTypes jt LEFT JOIN Users u ON jt.userId = u.userId LEFT JOIN 
        jobTypeParams jtp ON jt.jobTypeId = jtp.jobTypeID";
        if ($userId) {
            $query .= " INNER JOIN organisationJobTypes ojt ON jt.jobTypeId = ojt.jobTypeId
                    INNER JOIN organisationUsers ou ON ojt.organisationId = ou.organisationId AND ou.userId = :userId";
        }
        $query .= $id ? " WHERE jt.jobTypeId = :jobTypeId" : "";
        $getJobTypesStmt = $pdo->prepare($query);
        if ($id) {
            $getJobTypesStmt->bindParam(":jobTypeId", $id);
        }
        if ($userId) {
            $getJobTypesStmt->bindParam(":userId", $userId);
        }
        $ok = $getJobTypesStmt->execute();
        if (!$ok) {
            throw new Error("Failed to get job types: " . print_r($getJobTypesStmt->errorInfo(), true));
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

            if ($paramData['name'] === null) {
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
                    'arrayJobCount' => $row['arrayJobCount'],
                    'outputCount' => $row['outputCount']
                ];
            }

            // Add parameters to the existing job type entry
            if ($paramData) {
                $result[$jobTypeId]['parameters'][] = $paramData;
            }

        }

        if ($id) {
            return $result[$id];
        }
        return array_values($result);
    }


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
        $role = intval($tokenData->role);
        $jobTypeName = $body->jobTypeName;
        $jobTypeDescription = $body->jobTypeDescription;
        $script = $body->script;
        $parameters = $body->parameters;
        $arrayJobSupport = $body->arrayJobSupport;
        $hasFileUpload = $body->hasFileUpload;
        $hasOutputFile = $body->hasOutputFile;
        $outputCount = $body->outputCount ?? 0;
        $arrayJobCount = $body->arrayJobCount ?? 0;
        $organisationId = $body->organisationId ?? null;

        $organisations = new Organisations();
        if (($role !== 1) && (!$organisationId || $organisations->_getUserRole($userId, $organisationId) < 1)
        ) {
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        }

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

            if (!$createJobTypeStmt) {
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
                throw new Error("Failed to create job type for user with ID $userId: " . print_r($createJobTypeStmt->errorInfo(), true));
            }

            $jobTypeId = $pdo->lastInsertId();
            $paramStmt = $pdo->prepare("INSERT INTO jobTypeParams (paramName, paramType, defaultValue, jobTypeId) VALUES (:paramName, :paramType, :defaultValue, :jobTypeId)");
            foreach ($parameters as $param) {
                $paramStmt->bindParam(":paramName", $param->name);
                $paramStmt->bindParam(":paramType", $param->type);
                $paramStmt->bindParam(":jobTypeId", $jobTypeId);
                $defaultValue = strval($param->defaultVal);
                $paramStmt->bindParam(":defaultValue", $defaultValue);
                if (!$paramStmt->execute()) {
                    throw new Error("Failed to create new job type for user with ID $userId. Creating parameters failed: " . print_r($paramStmt->errorInfo(), true));
                }
            }

            if ($organisationId) {
                $addJobTypeToOrgStmt = $pdo->prepare("INSERT INTO organisationJobTypes (organisationId, jobTypeId) VALUES (:organisationId, :jobTypeId)");
                $addJobTypeToOrgStmt->bindParam(":organisationId", $organisationId);
                $addJobTypeToOrgStmt->bindParam(":jobTypeId", $jobTypeId);
                if (!$addJobTypeToOrgStmt->execute()) {
                    throw new Error("Failed to create new job type for user with ID $userId. Failed to add to organisation with ID $organisationId: " . print_r($addJobTypeToOrgStmt->errorInfo(), true));
                }
            }

            if (!$pdo->commit()) {
                throw new Error("Failed to create new job type for user with ID $userId. Failed to commit: " . print_r($pdo->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            $pdo->rollback();
            return $response->withStatus(500);
        }

        $response->getBody()->write("Record Created");
        return $response->withStatus(200);
    }

    //==========================Get All JobTypes==========================//
    //=============================Method: GET===========================//
    //================Route: /api/jobtypes[/{$jobTypeId}]===============//

    public function getJobType(Request $request, Response $response, array $args): Response
    {
        $jobTypeId = $args["jobTypeId"] ?? null;
        $tokenData = $request->getAttribute("tokenData");
        $role = intval($tokenData->role);
        $userId = $tokenData->userId;


        $jobTypes = $role === 1 ? $this->getJobTypes($jobTypeId) : $this->getJobTypes($jobTypeId, $userId);

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
        $role = intval($body->role);
        $description = $body->jobTypeDescription;
        $script = $body->script;
        $hasOutputFile = $body->hasOutputFile;
        $hasFileUpload = $body->hasFileUpload;
        $arrayJobSupport = $body->arrayJobSupport;
        $outputCount = $body->outputCount;
        $arrayJobCount = $body->arrayJobCount ?? 0;
        $parameters = $body->parameters ?? [];
        $pdo = new PDO(DB_CONN);

        try {
            $getOrgStmt = $pdo->prepare("SELECT organisationId from organisationJobTypes WHERE jobTypeId = :jobTypeId");
            $getOrgStmt->bindParam(":jobTypeId", $jobTypeId);
            if (!$getOrgStmt->execute()) {
                throw new Error("Failed to get organisation associated with job type with ID $jobTypeId for user with ID $userId: " . print_r($getOrgStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $organisationId = $getOrgStmt->fetchColumn();
        $organisations = new Organisations();
        if ($role !== 1 && $organisations->_getUserRole($userId, $organisationId) === 0) {
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        }
        if (!$this->validateJobType($jobTypeId, $name, $description, $script, $hasOutputFile, $hasFileUpload, $arrayJobSupport, $arrayJobCount, $parameters)) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }


        $pdo->beginTransaction();

        try {
            $deleteParamsStmt = $pdo->prepare("DELETE from jobTypeParams WHERE jobTypeId = :jobTypeId");
            $deleteParamsStmt->bindParam(":jobTypeId", $jobTypeId);
            if (!$deleteParamsStmt->execute()) {
                throw new Error("Failed to update job type with ID $jobTypeId for user with ID $userId. Failed to delete former parameters: " . print_r($deleteParamsStmt->errorInfo(), true));
            }

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
            if (!$updateJobTypeStmt->execute()) {
                throw new Error("Failed to update job type with ID $jobTypeId for user with ID $userId: " . print_r($updateJobTypeStmt->errorInfo(), true));
            }

            if (!$pdo->commit()) {
                throw new Error("Failed to update job type with ID $jobTypeId for user with ID $userId: Failed to commit: " . print_r($pdo->errorInfo(), true));
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(200);
        }

        $response->getBody()->write("Record Updated");
        return $response->withStatus(200);
    }

    //===========================DeleteJobType===========================//
    //==========================Method: DELETE==========================//
    //=================Route: /api/jobtypes/{jobTypeId}================//
    public function delete(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $jobTypeId = $args["jobTypeId"] ?? null;
        $role = intval($tokenData->role);
        $pdo = new PDO(DB_CONN);
        try{
            $getUsrStmt = $pdo->prepare("SELECT userId FROM jobTypes WHERE jobTypeId = :jobTypeId");
            $getUsrStmt->bindParam(":jobTypeId", $jobTypeId);
            if(!$getUsrStmt->execute()){
                throw new Error("Failed to get userId for jobType with ID $jobTypeId for user with ID $userId: " . print_r($getUsrStmt->errorInfo(), true));
            }

            $getOrgStmt = $pdo->prepare("SELECT organisationId from organisationJobTypes WHERE jobTypeId = :jobTypeId");
            $getOrgStmt->bindParam(":jobTypeId", $jobTypeId);
            if(!$getOrgStmt->execute()){
                throw new Error("Failed to get organisationId associated with jobType with ID $jobTypeId for user with ID $userId" . print_r($getOrgStmt->errorInfo(), true));
            }
        }catch(Exception $e){
            Logger::error($e, $request->getRequestTarget());
            $response ->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $organisations = new Organisations();
        $ownerId = $getUsrStmt->fetchColumn();
        $organisationId = $getOrgStmt->fetchColumn();
        if($role !== 1 && $ownerId !== $userId && $organisations->_getUserRole($userId, $organisationId) !== 2){
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        }
        if (!$jobTypeId) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }


        $pdo->beginTransaction();

        try {

            $deleteParamsStmt = $pdo->prepare("DELETE FROM jobTypeParams WHERE jobTypeId = :jobTypeId");
            $deleteParamsStmt->bindParam(":jobTypeId", $jobTypeId);
            if (!$deleteParamsStmt->execute()) {
                throw new Error("Failed to delete job type with ID $jobTypeId for user with ID $userId. Failed to delete params: " . print_r($deleteParamsStmt->errorInfo(), true), $request->getRequestTarget());
            }

            $deleteJobTypeStmt = $pdo->prepare("DELETE FROM jobTypes WHERE jobTypeId = :jobTypeId");
            $deleteJobTypeStmt->bindParam(":jobTypeId", $jobTypeId);
            if (!$deleteJobTypeStmt->execute()) {
                throw new Error("Failed to delete job type with ID $jobTypeId for user with ID $userId: " . print_r($deleteJobTypeStmt->errorInfo(), true), $request->getRequestTarget());
            }

            if (!$pdo->commit()) {
                throw new Error("Failed to delete job type with ID $jobTypeId for user with ID $userId. Failed to commit: " . print_r($pdo->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $pdo->rollBack();
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $response->getBody()->write("Record Deleted");
        return $response->withStatus(200);
    }

}