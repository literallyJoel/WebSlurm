<?php
use Firebase\JWT\JWT;
use Slim\Psr7\Response;
function isTokenValid($decoded): bool
{
    $valid = true;
    $valid = $valid && isset($decoded->exp);
    error_log("exp set: " . $valid);
    error_log(time());
    error_log($decoded->exp);
    $valid = $valid && time() < $decoded->exp;
    error_log("exp past: " . $valid);
    $valid = $valid && !(isTokenCancelled($decoded));
    error_log("is cancelled: " . $valid); 
    return $valid;
}

function isTokenCancelled($decoded): bool
{
    $dbFile = __DIR__ . "/../../data/db.db";
    $pdo = new PDO("sqlite:$dbFile");
    $userID = $decoded->userID;
    $tokenID = $decoded->tokenID ?? null;

    if ($tokenID) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT * FROM userCancelledTokens WHERE userID = :userID AND tokenID = :tokenID");
        $stmt->bindParam(":userID", $userID);
        $stmt->bindParam(":tokenID", $tokenID);
        $ok = $stmt->execute();
        if (!$ok) {
            return true;
        }

        $token = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($token) {
            return true;
        }

        return false;
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
    error_log("Token: " . $token);
    
    $key = new \Firebase\JWT\Key("thisShouldBeAnEnvironmentVariable", "HS256");

    return JWT::decode($token, $key);
}