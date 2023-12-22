<?php

use Firebase\JWT\ExpiredException;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;
include __DIR__ . "/helpers/AuthHelper.php";
class RequiresAdmin
{



    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $token = $request->getHeaderLine("Authorization");
            $decoded = decodeJWT($token);
            $isAllowed = isTokenValid($decoded);

            $role = $decoded->role;

            if ($isAllowed && $role === 1) {
                $request = $request->withAttribute("decoded", $decoded);
                return $handler->handle($request);
            }

            $response = new Response();
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        } catch (ExpiredException $e) {
            $response = new Response();
            $response->getBody()->write("Token Expired");
            return $response->withStatus(401);
        }
    }
}