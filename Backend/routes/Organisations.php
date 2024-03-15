<?php


use DI\Container;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\StreamFactory;
use SpazzMarticus\Tus\Factories\FilenameFactoryInterface;
use SpazzMarticus\Tus\Factories\OriginalFilenameFactory;
use SpazzMarticus\Tus\Providers\LocationProviderInterface;
use SpazzMarticus\Tus\Providers\PathLocationProvider;
use SpazzMarticus\Tus\TusServer;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\EventDispatcher\EventDispatcher;

require_once __DIR__ . "/../config/config.php";

class Organisations{
    public function __construct()
    {
        
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface{
        $decodedToken = $request->getAttribute("decoded");
        $role = $decodedToken->role;

        if(($role !== 1)){
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        }   

        $body = json_decode($request->getBody());
        $name = $body->name ?? null;
        $description = $body->description ?? null;

        try{
            $pdo = new PDO(DB_CONN);
            $stmt = $pdo->prepare("INSERT INTO organisations (name, description) VALUES (:name, :description)");
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":description", $description);
            $stmt->execute();
        }catch(Exception $e){
            error_log($e);
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
        
    }
    

    public function getAll(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface{
        $decodedToken = $request->getAttribute("decoded");
        $userID = $decodedToken->userID;
        $pdo = new PDO(DB_CONN);
        $role = $decodedToken->role;

        if($role === 1){
            $stmt = $pdo->prepare("SELECT * FROM organisations");
        }else{
            $stmt = $pdo->prepare("SELECT * FROM organisations JOIN userOrganisations ON organisations.organisationID = userOrganisations.organisationID WHERE userOrganisations.userID = :userID");
            $stmt->bindParam(":userID", $userID);
        }
        
        try{
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response->getBody()->write(json_encode($results));
            return $response->withStatus(200);
        }catch(Exception $e){
            error_log($e);
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

    public function isUserInOrg(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface{
        $decoded = $request->getAttribute("decoded");
        $userID = $decoded->userID;
        $orgID = $args["orgID"];

        $pdo = new PDO(DB_CONN);

        try{
            $stmt = $pdo->prepare("SELECT * FROM userOrganisations WHERE userID = :userID AND organisationID = :orgID");
            $stmt->bindParam(":userID", $userID);
            $stmt->bindParam(":orgID", $orgID);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch(Exception $e){
            error_log($e);
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
       
        $response->getBody()->write(json_encode(["inOrg" => !!$results]));

        return $response->withStatus(200);
    }

    public function getOrg(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface{
        $orgID = $args["orgID"];
        $pdo = new PDO(DB_CONN);
        $decoded = $request->getAttribute("decoded");
        $userID = $decoded->userID;
        $role = $decoded->role;

        if($role ===1){
            try{
                $stmt = $pdo->prepare("SELECT * FROM organisations WHERE organisationID = :orgID");
                $stmt->bindParam(":orgID", $orgID);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response->getBody()->write(json_encode($results));
                return $response->withStatus(200);
            }catch(Exception $e){
                error_log($e);
                $response->getBody()->write("Internal Server Error");
                return $response->withStatus(500);
            }
        }
        try{
            $stmt = $pdo->prepare("SELECT * FROM organisations JOIN userOrganisations ON organisations.organisationID = userOrganisations.organisationID WHERE organisations.organisationID = :orgID AND userOrganisations.userID = :userID");
            $stmt->bindParam(":orgID", $orgID);
            $stmt->bindParam(":userID", $userID);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response->getBody()->write(json_encode($results));
            return $response->withStatus(200);
        }catch(Exception $e){
            error_log($e);
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

    public function addUserToOrg(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface{
        $decoded = $request->getAttribute("decoded");
        $role = $decoded->role;
        $body = json_decode($request->getBody());
        $userID = $body->userID;
        $orgID = $body->orgID;
        $pdo = new PDO(DB_CONN);
        
        if($role !== 1){
            $stmt = $pdo->prepare("SELECT * FROM userOrganisations WHERE userID = :userID AND organisationID = :organisationID AND role = 1");
            $stmt->bindParam(":userID", $userID);
            $stmt->bindParam(":organisationID", $orgID);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if(!$results){
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }
        }

     
        $stmt = $pdo->prepare("INSERT INTO userOrganisations (userID, organisationID) VALUES (:userID, :orgID)");
        $stmt->bindParam(":userID", $userID);
        $stmt->bindParam(":orgID", $orgID);
        try{
            $stmt->execute();
            $response->getBody()->write("ok");
            return $response->withStatus(200);
        }catch(Exception $e){
            error_log($e);
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

    public function removeUserFromOrg(ServerRequestInterface $request, ResponseInterface $response){
        $decoded = $request->getAttribute("decoded");
        $role = $decoded->role;
        $body = json_decode($request->getBody());
        $userID = $body->userID;
        $orgID = $body->orgID;

        $pdo = new PDO(DB_CONN);

        if($role !== 1){
            $stmt = $pdo->prepare("SELECT * FROM userOrganisations WHERE userID = :userID AND organisationID = :organisationID AND role = 1");
            $stmt->bindParam(":userID", $userID);
            $stmt->bindParam(":organisationID", $orgID);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if(!$results){
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }
        }
       
        $stmt = $pdo->prepare("DELETE FROM userOrganisations WHERE userID = :userID AND organisationID = :orgID");
        $stmt->bindParam(":userID", $userID);
        $stmt->bindParam(":orgID", $orgID);
        try{
            $stmt->execute();
            $response->getBody()->write("ok");
            return $response->withStatus(200);
        }catch(Exception $e){
            error_log($e);
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }



    public function delete(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface{
        $decoded = $request->getAttribute("decoded");
        $userID = $decoded->userID;
        $pdo = new PDO(DB_CONN);
        $body = json_decode($request->getBody());
        $orgID = $body->orgID;
        $role = $decoded->role;

         if($role !== 1){
            $stmt = $pdo->prepare("SELECT * FROM userOrganisations WHERE userID = :userID AND organisationID = :organisationID AND role = 1");
            $stmt->bindParam(":userID", $userID);
            $stmt->bindParam(":organisationID", $orgID);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if(!$results){
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }
        }


        
        try{
            $stmt = $pdo->prepare("DELETE FROM userOrganisations WHERE organisationID = :orgID");
            $stmt->bindParam(":orgID", $orgID);
            $stmt->execute();
        }catch(Exception $e){
            error_log($e);
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        try{
            $stmt = $pdo->prepare("DELETE FROM organisations WHERE organisationID = :orgID");
            $stmt->bindParam(":orgID", $orgID);
            $stmt->execute();
        }catch(Exception $e){
            error_log($e);
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
        try{
            $stmt->execute();
            $response->getBody()->write("ok");
            return $response->withStatus(200);
        }catch(Exception $e){
            error_log($e);
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

    public function addJobTypeToOrg(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface{
        $decoded = $request->getAttribute("decoded");
        $userID = $decoded->userID;
        $role = $decoded->role;
        $pdo = new PDO(DB_CONN);
        $body = json_decode($request->getBody());
        $orgID = $body->orgID;
        $jobTypeID = $body->jobTypeID;

        if($role !== 1){
            $stmt = $pdo->prepare("SELECT * FROM userOrganisations WHERE userID = :userID AND organisationID = :organisationID AND role = 1");
            $stmt->bindParam(":userID", $userID);
            $stmt->bindParam(":organisationID", $orgID);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if(!$results){
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }
        }

        $stmt = $pdo->prepare("INSERT INTO organisationJobTypes (organisationID, jobTypeID) VALUES (:organisationID, :jobTypeID)");
        $stmt->bindParam(":organisationID", $orgID);
        $stmt->bindParam(":jobTypeID", $jobTypeID);
        try{
            $stmt->execute();
            $response->getBody()->write("ok");
            return $response->withStatus(200);
        }catch(Exception $e){
            error_log($e);
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

    }

    public function removeJobTypeFromOrg(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface{
        $decoded = $request->getAttribute("decoded");
        $userID = $decoded->userID;
        $role = $decoded->role;
        $pdo = new PDO(DB_CONN);
        $body = json_decode($request->getBody());
        $orgID = $body->orgID;
        $jobTypeID = $body->jobTypeID;

        if($role !== 1){
            $stmt = $pdo->prepare("SELECT * FROM userOrganisations WHERE userID = :userID AND organisationID = :organisationID AND role = 1");
            $stmt->bindParam(":userID", $userID);
            $stmt->bindParam(":organisationID", $orgID);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if(!$results){
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM organisationJobTypes WHERE organisationID = :organisationID AND jobTypeID = :jobTypeID");
        $stmt->bindParam(":organisationID", $orgID);
        $stmt->bindParam(":jobTypeID", $jobTypeID);
        try{
            $stmt->execute();
            $response->getBody()->write("ok");
            return $response->withStatus(200);
        }catch(Exception $e){
            error_log($e);
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }
}
