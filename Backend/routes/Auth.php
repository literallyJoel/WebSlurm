<?php

use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
class Auth
{
    public function __construct()
    {
    }

    private function generateJWT($email, $name, $userID, $userPrivLevel, $requiresPasswordReset): string
    {
        $tokenID = uniqid("", true);
        $secretKey = "thisShouldBeAnEnvironmentVariable";
        $expirationTime = time() + 86400; //24 hours
        return JWT::encode([
            'exp' => $expirationTime,
            'tokenID' => $tokenID,
            'userID' => $userID,
            'email' => $email,
            'name' => $name,
            'privLevel' => $userPrivLevel,
            'requiresPasswordReset' => $requiresPasswordReset,
            'local' => true
        ],
            $secretKey,
            "HS256");
    }

    public function verify(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface{
        //This function has a middleware that verifies the token, so if we get here, the token is valid.
        $response->getBody()->write("OK");
        return $response->withStatus(200);
    }
    
    public function login(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = json_decode($request->getBody());
        if ($body === null || $body->email === null || $body->password === null) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        $email = $body->email;
        $password = $body->password;
        $dbFile = __DIR__ . "/../data/db.db";
        $pdo = new PDO("sqlite:$dbFile");
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM Users WHERE userEmail = :email");
            $stmt->bindParam(":email", $email);
            $stmt->execute();
        } catch (Exception $e) {
            error_log($e->getMessage());
            $pdo->rollBack();
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
        $ok = $pdo->commit();
        if (!$ok) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            error_log($user["userName"]);
            $userPWHash = $user["userPWHash"];

            if (password_verify($password, $userPWHash)) {
                $token = $this->generateJWT($email, $user["userName"], $user["userID"], $user["privLevel"], $user["requiresPasswordReset"]);
                $response->getBody()->write(json_encode(["token" => $token]));
                return $response->withStatus(200);
            }
        }

        $response->getBody()->write("Unauthorized");
        return $response->withStatus(401);

    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response)
    {
        $decoded = $request->getAttribute("decoded") ?? null;
        if ($decoded === null) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $dbFile = __DIR__ . "/../data/db.db";
        $pdo = new PDO("sqlite:$dbFile");

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO userCancelledTokens (tokenID, userID) VALUES (:tokenID, :userID)");
            $stmt->bindParam("tokenID", $decoded->tokenID);
            $stmt->bindParam("userID", $decoded->userID);

            $stmt->execute();
        } catch (Exception $e) {
            error_log($e->getMessage());
            $pdo->rollBack();
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $success = $pdo->commit();
        if ($success) {
            $response->getBody()->write("OK");
            return $response->withStatus(200);
        }

        $response->getBody()->write("Internal Server Error");
        return $response->withStatus(500);
    }

}