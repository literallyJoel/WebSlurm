<?php
use Firebase\JWT\JWT;
use Slim\Http\Response;
include_once __DIR__ . "/../../config/Config.php";
function isTokenValid($tokenData): bool
{
    $valid = true;
    $valid = $valid && isset($tokenData->exp);

    
    if(!$valid){
        error_log("Failed on exp set");
        return false;
    }

    $valid = $valid && time() < $tokenData->exp;
    if(!$valid){
        error_log("Failed on exp");
        return false;
    }
    $valid = $valid && !(isTokenCancelled($tokenData));
    if(!$valid){
        error_log("Failed on cancelled");
        return false;
    }
    return $valid;
}

function isTokenCancelled($tokenData): bool
{
    $dbFile = __DIR__ . "/../../data/db.db";
    $pdo = new PDO("sqlite:$dbFile");
    $userID = $tokenData->userId;
    $tokenID = $tokenData->tokenId ?? null;

    if ($tokenID) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT * FROM userTokens WHERE userID = :userID AND tokenID = :tokenID");
        $stmt->bindParam(":userID", $userID);
        $stmt->bindParam(":tokenID", $tokenID);
        $ok = $stmt->execute();
        if (!$ok) {
            return true;
        }

        $token = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($token) {
            return false;
        }

        return true;
    }

    return true;
}

function decodeJWT($token)
{
    if (empty($token) || strtolower($token) === "bearer") {
        $resp = new Response();
        $resp->getBody()->write("Unauthorized");
        return $resp->withStatus(401);
    }

    
    $token = substr($token, 7);
    

    $key = new \Firebase\JWT\Key(SECRET_KEY, "HS256");
    return JWT::decode($token, $key);
}