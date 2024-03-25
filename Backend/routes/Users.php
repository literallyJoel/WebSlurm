<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function PHPSTORM_META\type;

require_once __DIR__ . "/../helpers/Validator.php";
include_once __DIR__ . "/../config/Config.php";
require_once __DIR__ . "/../helpers/Logger.php";

class Users
{
    public function __construct()
    {
    }

    private function generatePass(): string
    {
        $characters =
            "23456789abcdefghijkmnpqrstuvwxyzABCDEFGHIJKMNPQRSTUVWXYZ@!~#!$%^&*()";
        $charactersLength = strlen($characters);
        $randomString = "";
        for ($i = 0; $i < 10; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = json_decode($request->getBody());
        $name = $body->name ?? null;
        $email = $body->email ?? null;
        $role = $body->role ?? null;
        $generatePass = $body->generatePass ?? null;
        $password = $body->password ?? null;

        //Generate a user ID
        $userID = uniqid("", true);

        $validator = new Validator();
        if (!$validator->validateAccountCreation($body, $email, $name, $password, $generatePass, $role)) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        if ($generatePass) {
            $generatedPassword = $this->generatePass();
            $userPWHash = password_hash($generatedPassword, PASSWORD_BCRYPT);
        } else {
            $userPWHash = password_hash($password, PASSWORD_BCRYPT);
        }


        $pdo = new PDO(DB_CONN);

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO users (userID, userName, userEmail, userPWHash, role, requiresPasswordReset)
            VALUES (:userID, :userName, :userEmail, :userPwHash, :role, :requiresPasswordReset)");

            $stmt->bindParam(":userID", $userID);
            $stmt->bindParam(":userName", $name);
            $stmt->bindParam(":userEmail", $email);
            $stmt->bindParam(":userPwHash", $userPWHash);
            $stmt->bindParam(":role", $role);
            $stmt->bindParam(":requiresPasswordReset", $generatePass);

            $stmt->execute();
        } catch (Exception $e) {

            Logger::error($e, $request->getRequestTarget());
            $pdo->rollBack();
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $success = $pdo->commit();

        if ($success) {
            Logger::info("User created with ID: {$pdo->lastInsertId()}", $request->getRequestTarget());
            if ($generatePass) {
                $response->getBody()->write(json_encode(["userID" => $userID, "generatedPass" => $generatedPassword]));
            } else {
                $response->getBody()->write(json_encode(["userID" => $userID]));
            }

            return $response->withStatus(201);
        } else {
            Logger::error("Failed to commit transaction. Err: {$pdo->errorInfo()}", $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }


    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = json_decode($request->getBody());
        $name = $body->name ?? null;
        $email = $body->email ?? null;
        $password = $body->pass ?? null;
        $role = $body->role ?? null;
        $userID = $body->userID ?? null;
        $decodedToken = $request->getAttribute("decoded") ?? null;

        if ($userID !== null) {
            if ($decodedToken->role !== 1 && $decodedToken->userID !== $userID) {
                Logger::warning("Unauthorised attempt by user with ID {$decodedToken->userID} to update user with ID $userID", $request->getRequestTarget());
                $response->getBody()->write("Bad Request");
                return $response->withStatus(400);
            }
        }

        if ($decodedToken === null) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $validator = new Validator();

        if (!$validator->validateAccountUpdate($body, $email, $name, $password)) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }


        $pdo = new PDO(DB_CONN);

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE users SET " .
                (!is_null($email) ? "userEmail = :userEmail, " : "") .
                (!is_null($role) ? "role = :role, " : "") .
                (!is_null($name) ? "userName = :userName" : "") .
                (!is_null($password) ? "userPwHash = :userPwHash" : "") .
                " WHERE userID = :userID");

            //Bind parameters
            if (!is_null($email)) {
                $stmt->bindParam(':userEmail', $userEmail);
            }

            if (!is_null($role)) {
                $stmt->bindParam(':role', $role);
            }

            if (!is_null($name)) {
                $stmt->bindParam(':userName', $userName);
            }

            if (!is_null($password)) {
                $userPwHash = password_hash($password, PASSWORD_BCRYPT);
                $stmt->bindParam("userPwHash", $userPwHash);
            }

            if(!is_null($userID)){
                $stmt->bindParam("userID", $userID);
            }else{
                $stmt->bindParam("userID", $decodedToken->userID);
            }
            //Execute the statement
            $stmt->execute();

        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $pdo->rollBack();
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $success = $pdo->commit();

        if ($success) {
            Logger::info("User with ID {$decodedToken->userID} updated with ID: $userID", $request->getRequestTarget());
            $response->getBody()->write(json_encode(["userID" => $userID, "message" => "Successfully updated user with ID: $userID", "OK" => true]));
            return $response->withStatus(201);
        } else {
            Logger::error("Failed to commit transaction. Err: {$pdo->errorInfo()}", $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args){
        $body = json_decode($request->getBody());
        $userID = $body->userID ?? null;
        $decodedToken = $request->getAttribute("decoded") ?? null;

        if ($userID !== null) {
            if ($decodedToken->role !== 1 && $decodedToken->userID !== $userID) {
                Logger::warning("Unauthorised attempt by user with ID {$decodedToken->userID} to delete user with ID $userID", $request->getRequestTarget());
                $response->getBody()->write("Bad Request");
                return $response->withStatus(400);
            }
        }

        if ($decodedToken === null) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }


        $pdo = new PDO(DB_CONN);

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM users WHERE userID = :userID");
            $cleanupTokens = $pdo->prepare("DELETE FROM userCancelledTokens WHERE userID = :userID");
            $cleanupCommands = $pdo->prepare("UPDATE slurmCommands SET userID = '0' WHERE userID = :userID");

            if(is_null($userID)){
                $userID = $decodedToken->userID;
            }

            $stmt->bindParam("userID", $userID);
            $cleanupTokens->bindParam("userID", $userID);
            $cleanupCommands->bindParam("userID", $userID);


            //Execute the statement
            $cleanupCommands->execute();
            $cleanupTokens->execute();
            $stmt->execute();

        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $pdo->rollBack();
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $success = $pdo->commit();

        if ($success) {
            Logger::info("User with ID {$decodedToken->userID} deleted user with ID: $userID", $request->getRequestTarget());
            $response->getBody()->write(json_encode(["userID" => $userID, "message" => "Successfully deleted user with ID: $userID", "OK" => true]));
            return $response->withStatus(201);
        } else {
            Logger::error("Failed to commit transaction. Err: {$pdo->errorInfo()}", $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

    public function getShouldSetup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface{
        $pdo = new PDO(DB_CONN);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        $count = $stmt->fetchColumn();

        if($count === "1"){
            $response->getBody()->write(json_encode(["shouldSetup" => true]));
            return $response->withStatus(200);
        }else{
            $response->getBody()->write(json_encode(["shouldSetup" => false]));
            return $response->withStatus(200);
        }   
    }

    public function getAll(ServerRequestInterface $request, ResponseInterface $response):ResponseInterface{
       try {
           $decoded = $request->getAttribute("decoded");
           $userId = $decoded->userID;
           $pdo = new PDO(DB_CONN);
           $stmt = $pdo->prepare("SELECT userID, userName, userEmail, role FROM users WHERE userID != 'default'");
           $stmt->execute();
           $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
           Logger::info("User with ID $userId retrieved all users", $request->getRequestTarget());
           $response->getBody()->write(json_encode($users));
           return $response->withStatus(200);
       } catch (Exception $e) {
           Logger::error($e, $request->getRequestTarget());
           $response->getBody()->write("Internal Server Error");
           return $response->withStatus(500);
       }
    }

    public function getCount(ServerRequestInterface $request, ResponseInterface $response):ResponseInterface{
        try {
            $decoded = $request->getAttribute("decoded");
            $userId = $decoded->userID;
            $pdo = new PDO(DB_CONN);
            $stmt = $pdo->prepare("SELECT COUNT (*) FROM USERS WHERE userID != 'default'");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            $response->getBody()->write(json_encode(["count" => $count]));
            Logger::info("User count retrieved by user with ID $userId", $request->getRequestTarget());
            return $response->withStatus(200);
        }catch(Exception $e){
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }
}   
