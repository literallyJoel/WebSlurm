<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

include_once __DIR__ . "/../config/Config.php";
require_once __DIR__ . "/../helpers/Logger.php";

class Users
{
    public function __construct()
    {
    }

    //==============================================================================//
    //===============================Helper Functions==============================//
    //============================================================================//

    //Gets the number of users
    private function getUserCount(): int
    {
        $pdo = new PDO(DB_CONN);

        try {
            $userCountStmt = $pdo->prepare("SELECT COUNT(*) FROM Users");
            $ok = $userCountStmt->execute();
            if (!$ok) {
                throw new Error("PDO Error: " . print_r($userCountStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, "Users/getUserCount");
            return false;
        }

        return $userCountStmt->fetchColumn();
    }

    //Validates on user creation and update
    private function validateUser($name, $email, $_role, $password, $generatedPass, $isUpdate = false): bool
    {

        $args = func_get_args();
        $argNames = ['name', 'email', 'role', 'password', 'generatedPass', 'isUpdate'];

        // Null check, excluding password when generatedPass is true or isUpdate is true
        foreach ($args as $key => $value) {
            if ($argNames[$key] === 'password' && ($generatedPass || $isUpdate)) {
                continue; // Skip password check
            }
            if ($value === null) {
                Logger::debug("Failed Null Check", "Users/ValidateUser");
                return false;
            }
        }
        $role = intval($_role);
        //Email Check
        if (!preg_match("/[^\s@]+@[^\s@]+\.[^\s@]+/", $email)) {
            Logger::debug("Failed Email Check", "Users/ValidateUser");
            return false;
        }

        //Password check - its hashed on the client, so we just need to make sure it's present
        if (!$generatedPass && !$isUpdate && strlen($password) === 0) {
            Logger::debug("Failed on password check", "Users/ValidateUser");
            return false;
        }

        if ($role !== 1 && $role !== 0) {
            error_log("Failed on role");
            error_log("Provided: " . $role);
            error_log("Type: " . gettype($role));
            return false;
        }

        return true;
    }

    //Generates a random password
    private function generatePassword(): string
    {
        try {
            $characters =
                "23456789abcdefghijkmnpqrstuvwxyzABCDEFGHIJKMNPQRSTUVWXYZ@!~#!$%^&*()";
            $charactersLength = strlen($characters);
            $randomString = "";
            for ($i = 0; $i < 10; $i++) {
                $randomString .= $characters[random_int(0, $charactersLength - 1)];
            }

            return $randomString;
        } catch (Exception $e) {
            Logger::error($e, "Users/generatePassword");
            return false;

        }
    }

    //Sends an email containing a temporary password to the provided email address
    private function sendPasswordEmail($name, $email, $password)
    {
        $mailTemplate = file_get_contents(TEMP_PASS_EMAIL_TEMPLATE);
        $mailTemplate = str_replace('$name', $name, $mailTemplate);
        $mailTemplate = str_replace('$password', $password, $mailTemplate);

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
        if (mail($email, TEMP_PASS_EMAIL_SUBJECT, $mailTemplate, $headers)) {
            Logger::debug("Temporary Password email sent", "Users/sendPasswordEmail");
        } else {
            Logger::error("Failed to send temporary password email", "Users/sendPasswordEmail");
        }
    }


    //Gets users, either all or with the provided ID
    private function getUsers($userId = null)
    {
        $pdo = new PDO(DB_CONN);
        try {
            $query = "SELECT userID, userName, userEmail, Role FROM Users WHERE userId != 'default'";
            if ($userId) {
                $query .= " AND userId = :userId";
            }

            $getUsersStmt = $pdo->prepare($query);
            if ($userId) {
                $getUsersStmt->bindParam("userId", $userId);
            }

            if (!$getUsersStmt->execute()) {
                throw new Error("PDO Error: " . print_r($getUsersStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, "Users/getUsers");
            return false;
        }

        return $getUsersStmt->fetchAll(PDO::FETCH_ASSOC);
    }



    //===========================================================================//
    //=================================Routes===================================//
    //=========================================================================//

    //===============================Count================================//
    //============================Method: GET============================//
    //======================Route: /api/users/count=====================//
    public function getCount(Request $request, Response $response)
    {
        $userCount = $this->getUserCount();
        if (!$userCount) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $response->getBody()->write(json_encode(["count" => $userCount]));
        return $response->withStatus(200);
    }
    //=================================Get================================//
    //==============================Method: GET==========================//
    //====================Route: /api/users[/{userId}]==================//
    public function getUser(Request $request, Response $response, array $args): Response
    {
        $userId = $args["userId"] ?? null;

        $users = $this->getUsers($userId);

        if (!$users) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $response->getBody()->write(json_encode($users));
        return $response->withStatus(200);
    }

    //=============================Create===============================//
    //==========================Method: POST============================//
    //=======================Route: /api/users/=========================//
    public function create(Request $request, Response $response): Response
    {
        $body = json_decode($request->getBody());
        error_log(print_r($body, true));
        $name = $body->name;
        $email = $body->email;
        $role = $body->role;
        $generatePass = $body->generatePass ?? false;
        $password = $body->password ?? "";

        if (!$this->validateUser($name, $email, $role, $password, $generatePass)) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        $userId = uniqid("", true);

        if ($generatePass) {
            $generatedPassword = $this->generatePassword();
            $this->sendPasswordEmail($name, $email, $generatedPassword);
            //The frontend always does a SHA512 has before sending so we do that here
            $generatedPassword = hash("sha512", $generatedPassword);
            $userPWHash = password_hash($generatedPassword, PASSWORD_BCRYPT);
        } else {
            $userPWHash = password_hash($password, PASSWORD_BCRYPT);
        }

        $pdo = new PDO(DB_CONN);
        try {
            $createUserStmt = $pdo->prepare("INSERT INTO users (userId, userName, userEmail, userPWHash, role, requiresPasswordReset) VALUES (:userId, :userName, :userEmail, :userPWHash, :role, :requiresPasswordReset)");
            $createUserStmt->bindParam(":userId", $userId);
            $createUserStmt->bindParam(":userName", $name);
            $createUserStmt->bindParam(":userEmail", $email);
            $createUserStmt->bindParam(":userPWHash", $userPWHash);
            $createUserStmt->bindParam(":role", $role);
            $createUserStmt->bindParam(":requiresPasswordReset", $generatePass);

            if (!$createUserStmt->execute()) {
                throw new Error("PDO Error: " . print_r($createUserStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $userId = $pdo->lastInsertId();
        Logger::debug("User created with ID $userId", $request->getRequestTarget());
        if ($generatePass) {
            $response->getBody()->write(json_encode(["userId" => $userId, "generatedPass" => $generatedPassword]));
        } else {
            $response->getBody()->write(json_encode(["userId" => $userId]));
        }

        return $response->withStatus(200);
    }

    //===============================Update===============================//
    //=============================Method: PUT===========================//
    //=========================Route: /api/users[/{userId}]========================//
    public function update(Request $request, Response $response, array $args): Response
    {
        $body = json_decode($request->getBody());
        $name = $body->name;
        $email = $body->email;
        $password = $body->password ?? null;
        $role = $body->role;
        $userId = $args["userId"] ?? null;
        $requiresPasswordReset = $body->requiresPasswordReset ?? false;
        $tokenData = $request->getAttribute("tokenData");

        error_log("Provided:\nName $name\nEmail $email\nPass $password\nRole $role\nuserId $userId\nReset? $requiresPasswordReset");
        if ($userId !== null) {
            if (($tokenData->role !== 1 && $tokenData->userId !== $userId) || ($tokenData->userId !== $userId && $password !== null)) {
                Logger::warning("Unauthorised attempt by user with ID $tokenData->userID to update user with ID $userId", $request->getRequestTarget());
                $response->getBody()->write("Bad Request");
                return $response->withStatus(400);
            }
        } else {
            $userId = $tokenData->userId;
        }

        if (!$this->validateUser($name, $email, $role, $password, false, true)) {
            error_log("Validation Error");
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        $pdo = new PDO(DB_CONN);
        $pdo->beginTransaction();
        try {
            $updateUserStmt = $pdo->prepare("UPDATE users SET userName = :userName, userEmail = :userEmail, requiresPasswordReset = :requiresPasswordReset, role = :role WHERE userId = :userId");
            $updateUserStmt->bindParam(":userEmail", $email);
            $updateUserStmt->bindParam(":userName", $name);
            $updateUserStmt->bindParam(":requiresPasswordReset", $requiresPasswordReset);
            $updateUserStmt->bindParam(":role", $role);
            $updateUserStmt->bindParam("userId", $userId);

            if (!$updateUserStmt->execute()) {
                throw new Error("PDO Error: " . print_r($updateUserStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            $pdo->rollBack();
            return $response->withStatus(500);
        }

        try {
            if ($password) {
                $updatePwdStmt = $pdo->prepare("UPDATE users SET userPWHash = :userPWHash WHERE userId = :userId");
                $updatePwdStmt->bindParam(":userId", $userId);
                $password = password_hash($password, PASSWORD_BCRYPT);
                $updatePwdStmt->bindParam(":userPWHash", $password);
                if (!$updatePwdStmt->execute()) {
                    throw new Error("PDO Exception: " . print_r($updatePwdStmt->errorInfo(), true));
                }
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Serveer Error");
            $pdo->rollBack();
            return $response->withStatus(500);
        }

        $pdo->commit();

        Logger::debug("User with ID $tokenData->userId updated user with ID $userId", $request->getRequestTarget());
        $response->getBody()->write("OK");
        return $response->withStatus(200);
    }


    //===============================Delete===============================//
    //============================Method: DELETE=========================//
    //====================Route: /api/users[/{userId}]==================//
    public function delete(Request $request, Response $response, array $args): Response
    {
        $userId = $args["userId"] ?? null;
        $tokenData = $request->getAttribute("tokenData");
        $role = $tokenData->role;
        if ($userId !== null) {
            if (intval($role) !== 1 && $tokenData->userId !== $userId) {
                Logger::warning("Unauthorised attempt by user with ID $tokenData->userID to delete user with ID $userId", $request->getRequestTarget());
                $response->getBody()->write("Bad Request");
                return $response->withStatus(400);
            }
        } else {
            $userId = $tokenData->userId;
        }

        $pdo = new PDO(DB_CONN);
        try {
            $pdo->beginTransaction();
            $deleteUserStmt = $pdo->prepare("DELETE FROM users WHERE userId = :userId");
            $deleteTokensStmt = $pdo->prepare("DELETE FROM userTokens WHERE userId = :userId");
            $deleteFileIdStmt = $pdo->prepare("DELETE FROM fileIds WHERE userId = :userId");
            $cleanupJobTypesStmt = $pdo->prepare("UPDATE jobTypes SET userId = 'default' WHERE userId = :userId");

            $deleteUserStmt->bindParam(":userId", $userId);
            $deleteTokensStmt->bindParam(":userId", $userId);
            $deleteFileIdStmt->bindParam(":userId", $userId);
            $cleanupJobTypesStmt->bindParam(":userId", $userId);

            if (!$deleteFileIdStmt->execute()) {
                throw new Error("PDO Error: " . print_r($deleteTokensStmt->errorInfo(), true));
            }
            if (!$deleteTokensStmt->execute()) {
                throw new Error("PDO Error: " . print_r($deleteTokensStmt->errorInfo(), true));
            }
            if (!$cleanupJobTypesStmt->execute()) {
                throw new Error("PDO Error: " . print_r($cleanupJobTypesStmt->errorInfo(), true));
            }
            if (!$deleteUserStmt->execute()) {
                throw new Error("PDO Error: " . print_r($deleteUserStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $pdo->rollBack();
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }


        if (!$pdo->commit()) {
            Logger::error("PDO Error: " . print_r($pdo->errorInfo(), true), $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $response->getBody()->write("Record Deleted");
        return $response->withStatus(200);
    }


}