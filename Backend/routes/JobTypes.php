<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

require __DIR__ . "/../helpers/Validator.php";

class JobTypes
{
    public function __construct()
    {

    }


    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $decodedToken = $request->getAttribute("decodedToken");
        $userID = $decodedToken->userID;
        $body = json_decode($request->getBody());
        $name = $body->name ?? null;
        $parameters = $body->parameters ?? null;

        $validator = new Validator();

        if (!$validator->validateJobTypeCreation($body, $name, $parameters)) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        $dbFile = __DIR__ . "/../data/db.db";
        $pdo = new PDO("sqlite:$dbFile");
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO jobTypes (jobName, userID) VALUES (:jobName, :userID)");
            $stmt->bindParam(":jobName", $name);
            $stmt->bindParam(":userID", $userID);
            $stmt->execute();


            $jobTypeID = $pdo->lastInsertId();

            foreach ($parameters as $param) {
                $stmt = $pdo->prepare("INSERT INTO jobTypeParams (paramName, paramType, defaultValue, jobTypeID) VALUES (:paramName, :paramType, :defaultValue, :jobTypeID)");
                $stmt->bindParam(":paramName", $param->name);
                $stmt->bindParam(":paramType", $param->type);
                $stmt->bindParam(":jobTypeID", $jobTypeID);
                $defaultVal = strval($param->defaultValue);
                $stmt->bindParam(":defaultValue", $defaultVal);
                $stmt->execute();
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log($e);
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
        $ok = $pdo->commit();
        if ($ok) {
            $response->getBody()->write(json_encode(["jobTypeID" => $jobTypeID]));
            return $response->withStatus(200);
        } else {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

    }
}