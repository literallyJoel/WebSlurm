<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../config/Config.php";
require_once __DIR__ . "/../helpers/Logger.php";
require_once __DIR__ . "/Users.php";
require_once __DIR__ . "/Organisations.php";

class Setup
{
    //===========================================================================//
    //=============================Helper Functions=============================//
    //=========================================================================//
    private function getUserCount(): int
    {

        $pdo = new PDO(DB_CONN);
        $getUsrCountStmt = $pdo->prepare("SELECT COUNT(*) FROM users");
        if (!$getUsrCountStmt->execute()) {
            throw new Error("Failed to get user count: " . print_r($getUsrCountStmt->errorInfo(), true));
        }

        $userCount = $getUsrCountStmt->fetchColumn();

        return intval($userCount);
    }

    private function getOrganisationCount(): int
    {

        $pdo = new PDO(DB_CONN);
        $getOrgCountStmt = $pdo->prepare("SELECT COUNT(*) FROM organisations");
        if (!$getOrgCountStmt->execute()) {
            throw new Error("Failed to get organisation count: " . print_r($getOrgCountStmt->errorInfo(), true));
        }

        $orgCount = $getOrgCountStmt->fetchColumn();

        return intval($orgCount);
    }

    private function validateSetup($userName, $email, $password, $orgName): bool
    {
        // Null check
        foreach (func_get_args() as $arg) {
            if ($arg === null || $arg === "") {
                Logger::debug("Failed Null Check", "Setup/validateUser");
                return false;
            }
        }

        //Email Check
        if (!preg_match("/[^\s@]+@[^\s@]+\.[^\s@]+/", $email)) {
            Logger::debug("Failed Email Check", "Users/ValidateUser");
            return false;
        }


        return true;
    }

    //===========================================================================//
    //=================================Routes===================================//
    //=========================================================================//

    //==========================Get Should Setup===========================//
    //============================Method: GET=============================//
    //===================Route: /api/setup/shouldsetup===================//
    public function getShouldSetup(Request $request, Response $response): Response
    {
        try {
            $userCount = $this->getUserCount();
            $orgCount = $this->getOrganisationCount();

            $response->getBody()->write(json_encode(["shouldSetup" => ($userCount === 1 || $orgCount === 0)]));
            return $response->withStatus(200);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

    }

    public function createInitial(Request $request, Response $response): Response
    {
        $body = json_decode($request->getBody());
        $userName = $body->userName ?? null;
        $email = $body->email ?? null;
        $password = $body->password ?? null;
        $organisationName = $body->organisationName ?? null;
        try {
            $userCount = $this->getUserCount();
            $orgCount = $this->getOrganisationCount();
            if ($userCount !== 1 && $orgCount !== 0) {
                return $response->withStatus(404);
            }

            if (!$this->validateSetup($userName, $email, $password, $organisationName)) {
                $response->getBody()->write("Bad Request");
                return $response->withStatus(400);
            }

            $pdo = new PDO(DB_CONN);
            $pdo->beginTransaction();
            try {
                $userId = uniqid("", true);
                $userPWHash = password_hash($password, PASSWORD_BCRYPT);
                $createUsrStmt = $pdo->prepare("INSERT INTO users (userId, userName, userEmail, userPWHash, role, requiresPasswordReset) VALUES (:userId, :userName, :userEmail, :userPWHash, 1, false)");
                $createUsrStmt->bindParam(":userId", $userId);
                $createUsrStmt->bindParam(":userName", $userName);
                $createUsrStmt->bindParam(":userEmail", $email);
                $createUsrStmt->bindParam(":userPWHash", $userPWHash);

                if (!$createUsrStmt->execute()) {
                    throw new Error("Failed to create user: " . print_r($createUsrStmt->errorInfo(), true));
                }

                $createOrgStmt = $pdo->prepare("INSERT INTO organisations (organisationName) VALUES (:organisationName)");
                $createOrgStmt->bindParam(":organisationName", $organisationName);
                if (!$createOrgStmt->execute()) {
                    throw new Error("Failed to create organisation: " . print_r($createOrgStmt->errorInfo(), true));
                }
                $orgId = $pdo->lastInsertId();
                $addUserToOrgStmt = $pdo->prepare("INSERT INTO organisationUsers (organisationId, userId, role) VALUES (:organisationId, :userId, 2)");
                $addUserToOrgStmt->bindParam(":organisationId", $orgId);
                $addUserToOrgStmt->bindParam(":userId", $userId);

                if (!$addUserToOrgStmt->execute()) {
                    throw new Error("Failed to add user to organisation: " . print_r($addUserToOrgStmt->errorInfo(), true));
                }

                if (!$pdo->commit()) {
                    throw new Error("Failed to commit setup transaction: ", print_r($pdo->errorInfo(), true));
                }
            } catch (Exception $e) {
                $pdo->rollback();
                throw $e;
            }

            $response->getBody()->write(json_encode(["userId" => $userId, "organisationId" => $orgId]));
            return $response->withStatus(200);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

    }

}