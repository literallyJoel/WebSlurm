<?php

use Firebase\JWT\JWT;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response;
include __DIR__ . "/helpers/AuthHelper.php";
class RequiresAdmin
{



    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): \Psr\Http\Message\ResponseInterface
    {
        $token = $request->getHeaderLine("Authorization");
        $decoded = decodeJWT($token);
        $isAllowed = isTokenValid($decoded);

        $privLevel = $decoded->privLevel;

        if ($isAllowed && $privLevel === 1) {
            $request = $request->withAttribute("decoded", $decoded);
            return $handler->handle($request);
        }

        $response = new Response();
        $response->getBody()->write("Unauthorized");
        return $response->withStatus(401);
    }
}