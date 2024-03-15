<?php


use Slim\Psr7\Response;
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
            $decoded = decodeJWT($token);
            $isAllowed = isTokenValid($decoded);

            if ($isAllowed) {
                $request = $request->withAttribute("decoded", $decoded);
                return $next($request, $response);
            }

            $response = new Response(); 
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        }catch(ExpiredException $e){
            error_log($e);
            $response = new Response();
            $response->getBody()->write("Token Expired");
            return $response->withStatus(401);
        }

    }
}