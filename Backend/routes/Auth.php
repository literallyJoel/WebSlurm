<?php

use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface;
require_once __DIR__ . "/../config/config.php";
use Psr\Http\Message\ServerRequestInterface;
class Auth
{
    public function __construct()
    {
    }

    private function generateJWT($tokenID, $email, $name, $userID, $userPrivLevel, $requiresPasswordReset): string
    {
       
        $secretKey = "thisShouldBeAnEnvironmentVariable";
        $expirationTime = time() + 86400; //24 hours
        return JWT::encode([
            'tokenID' => $tokenID,
            'exp' => $expirationTime,
            'userID' => $userID,
            'email' => $email,
            'name' => $name,
            'role' => $userPrivLevel,
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

    public function disableAllUserTokens(ServerRequestInterface $request, ResponseInterface $response):ResponseInterface{
        $decoded = $request->getAttribute("decoded") ?? null;
        $body = json_decode($request->getBody());
        $providedID = $body->userID ?? null;
        $userID = $decoded->userID ?? null;

        if(!is_null($providedID)){
            if($decoded->role !== 1){
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }
        }



        
        $pdo = new PDO(DB_CONN);

        try{
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM userTokens WHERE userID = :userID");
            $stmt->bindParam(":userID", is_null($providedID) ? $userID : $providedID);
            $stmt->execute();
        }catch(Exception $e){
            error_log($e->getMessage());
            $pdo->rollBack();
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $success = $pdo->commit();
        if($success){
            $response->getBody()->write("OK");
            return $response->withStatus(200);
        }

        $response->getBody()->write("Internal Server Error");
        return $response->withStatus(500);
    }
    
    public function verifyPass(ServerREquestInterface $request, ResponseInterface $response):ResponseInterface{
        $decoded = $request->getAttribute("decoded") ?? null;
        $body = json_decode($request->getBody());
        if(is_null($decoded)){
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $password = $body->password;
        
        $pdo = new PDO(DB_CONN);
        try{
            $stmt = $pdo->prepare("SELECT * from Users WHERE userID = :userID");
            $stmt->bindParam(":userID", $decoded->userID);
            $ok = $stmt->execute();
        }catch(Exception $e){
            error_log($e->getMessage());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        if(!$ok){
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if($user){
            $userPWHash = $user["userPwHash"];
            if(password_verify($password, $userPWHash)){
                $response->getBody()->write(json_encode(["ok" => true]));
                return $response->withStatus(200);
            }
        }

        $response->getBody()->write(json_encode(["ok" => false]));
        return $response->withStatus(401);
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
        
        $pdo = new PDO(DB_CONN);
        try {
            $stmt = $pdo->prepare("SELECT * FROM Users WHERE userEmail = :email");
            $stmt->bindParam(":email", $email);
            $ok = $stmt->execute();
        } catch (Exception $e) {
            error_log($e->getMessage());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
      
        if (!$ok) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $userPWHash = $user["userPWHash"];

            if (password_verify($password, $userPWHash)) {
                $tokenID = uniqid("", true);
                $token = $this->generateJWT($tokenID, $email, $user["userName"], $user["userID"], $user["role"], $user["requiresPasswordReset"]);
            
            try{
                $pdo->beginTransaction();
                $newTokenStmt = $pdo->prepare("INSERT INTO userTokens (tokenID, userID) VALUES (:tokenID, :userID)");
                $newTokenStmt->bindParam(":tokenID", $tokenID);
                $newTokenStmt->bindParam(":userID", $user["userID"]);
                $newTokenStmt->execute();
            }catch(Exception $e){
                $pdo->rollBack();
                error_log($e->getMessage());
            }
                $pdo->commit();
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

        
        $pdo = new PDO(DB_CONN);

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM userTokens WHERE tokenID = :tokenID AND userID = :userID");
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