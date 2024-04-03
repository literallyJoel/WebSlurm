<?php


use Firebase\JWT\ExpiredException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

include __DIR__ . "/helpers/AuthHelper.php";

class RequiresAuthentication
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        try{
            $token = $request->getHeaderLine("Authorization");
            $tokenData = decodeJWT($token);
            $isAllowed = isTokenValid($tokenData);

            if ($isAllowed) {
                $request = $request->withAttribute("tokenData", $tokenData);
                return $next($request, $response);
            }

          
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        }catch(ExpiredException $e){   
            $response->getBody()->write("Token Expired");
            return $response->withStatus(401);
        }

    }
}