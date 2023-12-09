<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

require __DIR__ . "/../helpers/Validator.php";

class Users
{
    public function __construct()
    {
    }

    private function generatePass()
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
        $privLevel = $body->role ?? null;
        $generatePass = $body->generatePass ?? null;
        $password = $body->password ?? null;

        //Generate a user ID
        $userID = uniqid("", true);

        $validator = new Validator();
        if (!$validator->validateAccountCreation($body, $email, $name, $password, $generatePass, $privLevel)) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        if ($generatePass) {
            $generatedPassword = $this->generatePass();
            $userPWHash = password_hash($generatedPassword, PASSWORD_BCRYPT);
        } else {
            $userPWHash = password_hash($password, PASSWORD_BCRYPT);
        }

        $dbFile = __DIR__ . "/../data/db.db";
        $pdo = new PDO("sqlite:$dbFile");

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO users (userID, userName, userEmail, userPWHash, privLevel, requiresPasswordReset)
            VALUES (:userID, :userName, :userEmail, :userPwHash, :privLevel, :requiresPasswordReset)");

            $stmt->bindParam(":userID", $userID);
            $stmt->bindParam(":userName", $name);
            $stmt->bindParam(":userEmail", $email);
            $stmt->bindParam(":userPwHash", $userPWHash);
            $stmt->bindParam(":privLevel", $privLevel);
            $stmt->bindParam(":requiresPasswordReset", $generatePass);

            $stmt->execute();
        } catch (Exception $e) {
            error_log($e->getMessage());
            $pdo->rollBack();
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $success = $pdo->commit();

        if ($success) {
            if ($generatePass) {
                $response->getBody()->write(json_encode(["userID" => $userID, "generatedPass" => $generatedPassword]));
            } else {
                $response->getBody()->write(json_encode(["userID" => $userID]));
            }

            return $response->withStatus(201);
        } else {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

    

}