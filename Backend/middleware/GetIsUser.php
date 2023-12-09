<?php

include __DIR__ . "/helpers/AuthHelper.php";
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class GetIsUser
{




    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): \Psr\Http\Message\ResponseInterface
    {
        $token = $request->getHeaderLine("Authorization");
        $decoded = decodeJWT($token);
        $isAllowed = isTokenValid($decoded);
        $body = json_decode($request->getBody());

        if ($body === null || $body["userID"] === null) {
            $response = new Response();
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        }

        if ($isAllowed && $body["userID"] === $decoded->userID) {
            $request = $request->withAttribute("decoded", $decoded);
            return $handler->handle($request);
        }

        $response = new Response();
        $response->getBody()->write("Unauthorized");
        return $response->withStatus(401);

    }
}