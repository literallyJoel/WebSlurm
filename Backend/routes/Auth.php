<?php

use Firebase\JWT\JWT;
use PSR\Http\Message\ResponseInterface as Response;
use PSR\Http\Message\ServerRequestInterface as Request;
use Slim\App;

include_once __DIR__ . "/../config/Config.php";
require_once __DIR__ . "/../helpers/Logger.php";

class Auth
{

    public function __construct()
    {
    }

    //===========================================================================//
    //=============================Helper Functions=============================//
    //=========================================================================//
    //Takes a users info and generates a JWT
    private function generateJWT($tokenId, $email, $name, $userId, $role, $requiresPasswordReset, $isOrgAdmin): string
    {
        try {
            $expirationTime = time() + 86400; //24 Hours
            return JWT::encode([
                'tokenId' => $tokenId,
                'exp' => $expirationTime,
                'userId' => $userId,
                'email' => $email,
                'name' => $name,
                'role' => $role,
                'requiresPasswordReset' => $requiresPasswordReset,
                'local' => true,
                'isOrgAdmin' => $isOrgAdmin
            ],
                SECRET_KEY,
                "HS256");
        } catch (Exception $e) {
            Logger::error($e, "Auth/generateJWT");
            return false;
        }
    }


    //===========================================================================//
    //=================================Routes===================================//
    //=========================================================================//

    //==============Verify Token============//
    //===============Method: POST===========//
    //=======Route: /api/auth/verify=======//
    public function verify(Request $request, Response $response): Response
    {
        //We put a middleware on here that verifies the token, so if we reach this point we can just OK it
        $response->getBody()->write("OK");
        return $response->withStatus(200);
    }

    //==============Disable Tokens===========//
    //===============Method: POST===========//
    //====Route: /api/auth/disabletokens====//
    public function disableUserTokens(Request $request, Response $response): Response
    {
        //Grab the token data
        $tokenData = $request->getAttribute("tokenData");
        $body = json_decode($request->getBody());
        $providedId = $body->userId ?? null;
        $userId = $tokenData->userId;


        //Check if the user is modifying a different user, and that they have permission to do so
        if ($providedId) {
            if ($tokenData->role !== 1) {
                Logger::warning("Unauthorized attempt to disable user tokens for user with ID $providedId by user with ID $userId", $request->getRequestTarget());
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }
        }
        $pdo = new PDO(DB_CONN);
        //Delete the selected users tokens from the active token table
        try {

            $disableTokensStatement = $pdo->prepare("DELETE FROM userTokens WHERE userId = :userId");
            $userIdParam = $providedId ?? $userId;
            $disableTokensStatement->bindParam("userId", $userIdParam);
            $ok = $disableTokensStatement->execute();
            if (!$ok) {
                throw new Error("Error disabling user tokens " . print_r($pdo->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        Logger::debug("Disabled all tokens for user with ID $userId", $request->getRequestTarget());
        $response->getBody()->write("OK");
        return $response->withStatus(200);


    }

    //============Verify Password===========//
    //============Method: POST============//
    //====Route: /api/auth/verifypass====//

    public function verifyPass(Request $request, Response $response): Response
    {

        //Grab the token data
        $tokenData = $request->getAttribute("tokenData");
        $body = json_decode($request->getBody());
        $password = $body->password;
        $pdo = new PDO(DB_CONN);

        //Grab the user
        try {

            $getUserStmt = $pdo->prepare("SELECT * FROM Users WHERE userId = :userId");
            $getUserStmt->bindParam(":userId", $tokenData->userId);
            $ok = $getUserStmt->execute();
            if (!$ok) {
                throw new Error("PDO Error: " . print_r($getUserStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $user = $getUserStmt->fetch(PDO::FETCH_ASSOC);

        //Check the provided password matches the stored one
        if ($user) {
            $userPWHash = $user['userPWHash'];
            if (password_verify($password, $userPWHash)) {
                Logger::debug("Password verified for user with ID $tokenData->userId", $request->getRequestTarget());
                $response->getBody()->write(json_encode(["ok" => true]));
                return $response->withStatus(200);
            }
        }


        Logger::warning("Password verification failed for user with ID $tokenData->userId", $request->getRequestTarget());
        $response->getBody()->write(json_encode(["ok" => false]));
        return $response->withStatus(200);
    }

    //================Login=================//
    //===========Method: POST=============//
    //========Route: /api/auth/login=======//

    public function login(Request $request, Response $response): Response
    {
        $body = json_decode($request->getBody());

        //Bad request if email or password not present
        if (!$body || !$body->email || !$body->password) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        $email = $body->email;
        $password = $body->password;

        $pdo = new PDO(DB_CONN);
        //Grab the user with the specified email
        try {
            $getUserStmt = $pdo->prepare("SELECT Users.* FROM Users WHERE userEmail = :email");
            $getUserStmt->bindParam(":email", $email);
            $ok = $getUserStmt->execute();
            if (!$ok) {
                throw new Error("PDO Error: " . print_r($getUserStmt->errorInfo(), true));
            }

            $user = $getUserStmt->fetch(PDO::FETCH_ASSOC);


            //If the user exists, check if the password matches the provided one
            if ($user) {
                $getIsOrgAdminStmt = $pdo->prepare("SELECT COUNT(*) FROM organisationUsers WHERE userId = :userId AND role = 2");
                $getIsOrgAdminStmt->bindParam(":userId", $user["userId"]);
                if (!$getIsOrgAdminStmt->execute()) {
                    throw new Error("Failed to fetch is user is org admin: " . print_r($getIsOrgAdminStmt->errorInfo(), true));
                }

                $orgAdmin = $getIsOrgAdminStmt->fetchAll(PDO::FETCH_ASSOC);
                $isOrgAdmin = !!$orgAdmin;
                $userPWHash = $user["userPWHash"];
                if (password_verify($password, $userPWHash)) {
                    //If it does generate a new token and sent it to the client
                    $tokenId = uniqid("", true);
                    $token = $this->generateJWT($tokenId, $email, $user["userName"], $user["userId"], $user["role"], $user["requiresPasswordReset"], $isOrgAdmin);


                    $newTokenStmt = $pdo->prepare("INSERT INTO userTokens (tokenId, userId) VALUES (:tokenId, :userId)");
                    $newTokenStmt->bindParam(":tokenId", $tokenId);
                    $newTokenStmt->bindParam(":userId", $user["userId"]);
                    $ok = $newTokenStmt->execute();
                    if (!$ok) {
                        throw new Error("PDO Error: " . print_r($newTokenStmt->errorInfo(), true));
                    }

                    Logger::debug("User with ID {$user["userId"]} logged in.", $request->getRequestTarget());
                    $response->getBody()->write(json_encode(["token" => $token]));
                    return $response->withStatus(200);
                }
            }

            //Otherwise 401
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }


    }


    //================Logout================//
    //==============Method: POST============//
    //========Route: /api/auth/logout======//

    public function logout(Request $request, Response $response): Response
    {

        //Grab the token data
        $tokenData = $request->getAttribute("tokenData");

        $pdo = new PDO(DB_CONN);
        //Remove the token from the database
        try {
            $deleteTokenStmt = $pdo->prepare("DELETE FROM userTokens WHERE tokenId = :tokenId AND userId = :userId");
            $deleteTokenStmt->bindParam(":tokenId", $tokenData->tokenId);
            $deleteTokenStmt->bindParam(":userId", $tokenData->userId);
            $ok = $deleteTokenStmt->execute();
            if (!$ok) {
                throw new Error("PDO Exception: " . print_r($deleteTokenStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }


        Logger::debug("User with ID $tokenData->userId logged out.", $request->getRequestTarget());
        $response->getBody()->write("OK");
        return $response->withStatus(200);
    }

    //===============Refresh================//
    //=============Method: POST=============//
    //========Route: /api/auth/refresh=====//
    public function refreshToken(Request $request, Response $response): Response
    {
        //Grab the token data
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $tokenId = $tokenData->tokenId;
        //Generate ID for new token
        $newTokenId = uniqid("", true);

        $pdo = new PDO(DB_CONN);
        try {
            $pdo->beginTransaction();

            //Grab the user, so we can get their most up-to-date information
            $getUserStmt = $pdo->prepare("SELECT * FROM users WHERE userId = :userId");
            $getUserStmt->bindParam(":userId", $userId);
            $getUserStmt->execute();

            $user = $getUserStmt->fetch(PDO::FETCH_ASSOC);
            //Generate a new token
            $token = $this->generateJWT($newTokenId, $user["userEmail"], $user["userName"], $user["userId"], $user["role"], $user["requiresPasswordReset"]);

            //Insert the new token into the database
            $insertTokenStmt = $pdo->prepare("INSERT INTO userTokens (tokenId, userId) VALUES (:tokenId, :userId)");
            $insertTokenStmt->bindParam(":tokenId", $newTokenId);
            $insertTokenStmt->bindParam(":userId", $userId);
            $insertTokenStmt->execute();

            //Remove the old token from the database
            $deleteOldTokenStmt = $pdo->prepare("DELETE FROM userTokens WHERE tokenId = :tokenId AND userId = :userId");
            $deleteOldTokenStmt->bindParam(":tokenId", $tokenId);
            $deleteOldTokenStmt->bindParam(":userId", $userId);
            $deleteOldTokenStmt->execute();
            $pdo->commit();
        } catch (Exception $e) {
            //Rollback if anything goes wrong
            $pdo->rollBack();
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        //Send a new token to the client
        Logger::debug("User with ID $userId refreshed token", $request->getRequestTarget());
        $response->getBody()->write(json_encode(["token" => $token]));
        return $response->withStatus(200);
    }
}

