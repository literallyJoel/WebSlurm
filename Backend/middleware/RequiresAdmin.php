<?php

use Firebase\JWT\ExpiredException;
use PSR\HTTP\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

include_once __DIR__ . "/helpers/AuthHelper.php";
require_once __DIR__ . "/../helpers/Logger.php";
class RequiresAdmin
{
    public function __invoke(Request $request, Response $response, $next)
    {
        try {
            $token = $request->getHeaderLine("Authorization");
            $tokenData = decodeJWT($token);
            $isValid = isTokenValid($tokenData);

            if ($isValid && $tokenData->role === "1") {
                $request = $request->withAttribute("tokenData", $tokenData);
                return $next($request, $response);
            }

            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        } catch (ExpiredException $e) {
            $response->getBody()->write("Token Expired");
            return $response->withStatus(401);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget() . "|| RequiresAuthentication");
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }
}