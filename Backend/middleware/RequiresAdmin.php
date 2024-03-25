<?php

use Firebase\JWT\ExpiredException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequiresAdmin
{

public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        try{
            $token = $request->getHeaderLine("Authorization");
            $decoded = decodeJWT($token);
            
            $isAllowed = isTokenValid($decoded) && $decoded->role==="1";

            if ($isAllowed) {
                $request = $request->withAttribute("decoded", $decoded);
                return $next($request, $response);
            }

          
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        }catch(ExpiredException $e){
            error_log($e);
          
            $response->getBody()->write("Token Expired");
            return $response->withStatus(401);
        }

    }
}
