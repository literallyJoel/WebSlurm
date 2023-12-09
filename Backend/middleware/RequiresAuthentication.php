<?php

use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response;
include __DIR__ . "/helpers/AuthHelper.php";

class RequiresAuthentication
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $token = $request->getHeaderLine("Authorization");
        error_log("Token: " . $token);
        $decoded = decodeJWT($token);
        $isAllowed = isTokenValid($decoded);

        if ($isAllowed) {
            $request = $request->withAttribute("decoded", $decoded);
            return $handler->handle($request);
        }

        $response = new Response(); 
        $response->getBody()->write("Unauthorized");
        return $response->withStatus(401);
    }
}