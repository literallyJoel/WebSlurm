<?php

use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response;
use Firebase\JWT\ExpiredException;
include __DIR__ . "/helpers/AuthHelper.php";

class RequiresAuthentication
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        try{
            $token = $request->getHeaderLine("Authorization");
            $decoded = decodeJWT($token);
            error_log("Decoded: " . print_r($decoded));
            $isAllowed = isTokenValid($decoded);

            if ($isAllowed) {
                $request = $request->withAttribute("decoded", $decoded);
                return $handler->handle($request);
            }

            $response = new Response(); 
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        }catch(ExpiredException $e){
            $response = new Response();
            $response->getBody()->write("Token Expired");
            return $response->withStatus(401);
        }

    }
}