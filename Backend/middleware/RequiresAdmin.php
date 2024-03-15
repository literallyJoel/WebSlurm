<?php

use Firebase\JWT\ExpiredException;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class RequiresAdmin
{



    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next): ResponseInterface
    {
        try {
            $token = $request->getHeaderLine("Authorization");
            $decoded = decodeJWT($token);
            $isAllowed = isTokenValid($decoded);

            $role = $decoded->role;

            if ($isAllowed && $role === 1) {
                $request = $request->withAttribute("decoded", $decoded);
                return $next($request, $response);
            }

            
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        } catch (ExpiredException $e) {
            $response->getBody()->write("Token Expired");
            return $response->withStatus(401);
        }
    }
}