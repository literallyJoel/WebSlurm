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

include_once __DIR__ . "/../config/Config.php";
require_once __DIR__ . "/../helpers/Logger.php";
class Organisations
{
    public function __construct()
    {

    }

    private function checkAccess(string $orgId, string $userId, int $role, int $checkType = 0): bool
    {
        $pdo = new PDO(DB_CONN);
        if ($role === 1) {
            return true;
        }
        if ($checkType < 0 || $checkType > 2) {
            return false;
        }

        $stmt = $pdo->prepare("SELECT * FROM userOrganisations WHERE userID = :userID AND organisationID = :organisationID AND role > :role");
        $stmt->bindParam(":userID", $userId);
        $requiredRole = $checkType - 1;
        $stmt->bindParam(":role", $requiredRole);
        $stmt->bindParam(":organisationID", $orgId);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return !!$results;
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = json_decode($request->getBody());
        $name = $body->name ?? null;
        $description = $body->description ?? null;
        $owner = $body->owner ?? null;

        if (!isset($name) || !isset($description) || !isset($owner)) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }
        $pdo = new PDO(DB_CONN);
        try {
            $stmt = $pdo->prepare("INSERT INTO organisations (name, description) VALUES (:name, :description)");
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":description", $description);
            $stmt->execute();
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO userOrganisations (userId, organisationId, role) VALUES (:userID, :orgID, 2)");
            $stmt->bindParam(":userId", $owner);
            $lastInsertId = $pdo->lastInsertId();
            $stmt->bindParam(":orgID", $lastInsertId);
            $stmt->execute();
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $stmt = $pdo->prepare("DELETE FROM organisations WHERE organisationID = :orgID");
            $stmt->bindParam(":orgID", $lastInsertId);
            $stmt->execute();
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        Logger::info("Organisation created", $request->getRequestTarget());
        $response->getBody()->write(json_encode(["orgID" => $lastInsertId]));
        return $response->withStatus(201);
    }


    public function getAll(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $decodedToken = $request->getAttribute("decoded");
        $userID = $decodedToken->userID;
        $pdo = new PDO(DB_CONN);
        $role = $decodedToken->role;

        if ($role === 1) {
            $stmt = $pdo->prepare("SELECT o.organisationID, o.organisationName, u.userID, u.userName
                                        FROM organisations o
                                        JOIN userOrganisations uo ON o.organisationID = uo.organisationId
                                        JOIN users u ON uo.userID = u.userID
                                        WHERE uo.role = 3;
                                        ");
        } else {
            $stmt = $pdo->prepare("SELECT o.organisationID, o.organisationName, u.userID, u.userName
                                        FROM organisations o
                                        JOIN userOrganisations uo ON o.organisationID = uo.organisationId
                                        JOIN users u ON uo.userID = u.userID
                                        WHERE uo.role = 3 AND u.userID = :userID
                                        ");
            $stmt->bindParam(":userID", $userID);
        }

        try {
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response->getBody()->write(json_encode($results));
            Logger::info("Organisations retrieved for user with ID $userID", $request->getRequestTarget());
            return $response->withStatus(200);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

    public function isUserInOrg(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $decoded = $request->getAttribute("decoded");
        $userID = $decoded->userID;
        $orgID = $args["orgID"];
        $role = $decoded->role;

        if (!$this->checkAccess($orgID, $userID, $role)) {
            $response->getBody()->write("Unauthorized");
            Logger::warning("Unauthorized attempt to check if user is in organisation $orgID by user with ID $userID", $request->getRequestTarget());
            return $response->withStatus(401);
        }

        $pdo = new PDO(DB_CONN);

        try {
            $stmt = $pdo->prepare("SELECT * FROM userOrganisations WHERE userID = :userID AND organisationID = :orgID");
            $stmt->bindParam(":userID", $userID);
            $stmt->bindParam(":orgID", $orgID);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::info($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $response->getBody()->write(json_encode(["inOrg" => !!$results]));

        return $response->withStatus(200);
    }

    public function getOrg(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $orgID = $args["orgID"];
        $pdo = new PDO(DB_CONN);
        $decoded = $request->getAttribute("decoded");
        $userID = $decoded->userID;
        $role = $decoded->role;

        if ($role === 1) {
            try {
                $stmt = $pdo->prepare("SELECT o.organisationID, o.organisationName, u.userID, u.userName
                                            FROM organisations o
                                            JOIN userOrganisations uo ON o.organisationID = uo.organisationId
                                            JOIN users u ON uo.userID = u.userID
                                            WHERE uo.role = 3 AND o.organisationID = :orgID;
                                            ");
                $stmt->bindParam(":orgID", $orgID);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response->getBody()->write(json_encode($results));
                return $response->withStatus(200);
            } catch (Exception $e) {
                Logger::error($e, $request->getRequestTarget());
                $response->getBody()->write("Internal Server Error");
                return $response->withStatus(500);
            }
        }
        try {
            $stmt = $pdo->prepare("SELECT o.organisationID, o.organisationName, u.userID, u.userName
                                            FROM organisations o
                                            JOIN userOrganisations uo ON o.organisationID = uo.organisationId
                                            JOIN users u ON uo.userID = u.userID
                                            WHERE uo.role = 3 AND u.userID = :userID AND o.organisationID = :orgID;
                                            ");
            $stmt->bindParam(":orgID", $orgID);
            $stmt->bindParam(":userID", $userID);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response->getBody()->write(json_encode($results));
            Logger::info("Organisation with ID $orgID retrieved for user with ID $userID", $request->getRequestTarget());
            return $response->withStatus(200);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

    public function addUserToOrg(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $decoded = $request->getAttribute("decoded");
        $role = $decoded->role;
        $body = json_decode($request->getBody());
        $userID = $body->userID;
        $orgID = $body->orgID;
        $pdo = new PDO(DB_CONN);

        if (!$this->checkAccess($orgID, $userID, $role, 1)) {
            $response->getBody()->write("Unauthorized");
            Logger::warning("Unauthorized attempt to add user with ID $userID to organisation with ID $orgID by user with ID [$decoded->userID]", $request->getRequestTarget());
            return $response->withStatus(401);
        }

        $stmt = $pdo->prepare("SELECT * FROM userOrganisations WHERE userID = :userID AND organisationID = :orgID");
        $stmt->bindParam(":userID", $userID);
        $stmt->bindParam(":orgID", $orgID);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($results) {
            $response->getBody()->write("User already in organisation");
            return $response->withStatus(400);
        }

        $stmt = $pdo->prepare("INSERT INTO userOrganisations (userID, organisationID) VALUES (:userID, :orgID)");
        $stmt->bindParam(":userID", $userID);
        $stmt->bindParam(":orgID", $orgID);
        try {
            $stmt->execute();
            $response->getBody()->write("ok");
            Logger::info("User with ID $userID added to organisation with ID $orgID by user with ID [$decoded->userID]", $request->getRequestTarget());
            return $response->withStatus(200);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

    public function removeUserFromOrg(ServerRequestInterface $request, ResponseInterface $response)
    {
        $decoded = $request->getAttribute("decoded");
        $role = $decoded->role;
        $body = json_decode($request->getBody());
        $userID = $body->userID;
        $orgID = $body->orgID;

        $pdo = new PDO(DB_CONN);

        if (!$this->checkAccess($orgID, $userID, $role, 1)) {
            Logger::warning("Unauthorized attempt to remove user with ID $userID from organisation with ID $orgID by user with ID [$decoded->userID]", $request->getRequestTarget());
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        }


        $stmt = $pdo->prepare("DELETE FROM userOrganisations WHERE userID = :userID AND organisationID = :orgID");
        $stmt->bindParam(":userID", $userID);
        $stmt->bindParam(":orgID", $orgID);
        try {
            $stmt->execute();
            $response->getBody()->write("ok");
            Logger::info("User with ID $userID removed from organisation with ID $orgID by user with ID [$decoded->userID]", $request->getRequestTarget());
            return $response->withStatus(200);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }


    public function delete(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $decoded = $request->getAttribute("decoded");
        $userID = $decoded->userID;
        $pdo = new PDO(DB_CONN);
        $body = json_decode($request->getBody());
        $orgID = $body->orgID;
        $role = $decoded->role;

        if (!$this->checkAccess($orgID, $userID, $role, 2)) {
            Logger::warning("Unauthorized attempt to delete organisation with ID $orgID by user with ID $userID", $request->getRequestTarget());
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM userOrganisations WHERE organisationID = :orgID");
            $stmt->bindParam(":orgID", $orgID);
            $stmt->execute();
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM organisations WHERE organisationID = :orgID");
            $stmt->bindParam(":orgID", $orgID);
            $stmt->execute();
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
        try {
            $stmt->execute();
            $response->getBody()->write("ok");
            Logger::info("Organisation with ID $orgID deleted by user with ID $userID", $request->getRequestTarget());
            return $response->withStatus(200);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

    public function addJobTypeToOrg(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $decoded = $request->getAttribute("decoded");
        $userID = $decoded->userID;
        $role = $decoded->role;
        $pdo = new PDO(DB_CONN);
        $body = json_decode($request->getBody());
        $orgID = $body->orgID;
        $jobTypeID = $body->jobTypeID;

        if (!$this->checkAccess($orgID, $userID, $role)) {
            $response->getBody()->write("Unauthorized");
            Logger::warning("Unauthorized attempt to add job type with ID $jobTypeID to organisation with ID $orgID by user with ID $userID", $request->getRequestTarget());
            return $response->withStatus(401);
        }

        $stmt = $pdo->prepare("INSERT INTO organisationJobTypes (organisationID, jobTypeID) VALUES (:organisationID, :jobTypeID)");
        $stmt->bindParam(":organisationID", $orgID);
        $stmt->bindParam(":jobTypeID", $jobTypeID);
        try {
            $stmt->execute();
            $response->getBody()->write("ok");
            Logger::info("Job type with ID $jobTypeID added to organisation with ID $orgID by user with ID $userID", $request->getRequestTarget());
            return $response->withStatus(200);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

    }

    public function removeJobTypeFromOrg(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $decoded = $request->getAttribute("decoded");
        $userID = $decoded->userID;
        $role = $decoded->role;
        $pdo = new PDO(DB_CONN);
        $body = json_decode($request->getBody());
        $orgID = $body->orgID;
        $jobTypeID = $body->jobTypeID;

        if (!$this->checkAccess($orgID, $userID, $role)) {
            $response->getBody()->write("Unauthorized");
            Logger::warning("Unauthorized attempt to remove job type with ID $jobTypeID from organisation with ID $orgID by user with ID $userID", $request->getRequestTarget());
            return $response->withStatus(401);
        }

        $stmt = $pdo->prepare("DELETE FROM organisationJobTypes WHERE organisationID = :organisationID AND jobTypeID = :jobTypeID");
        $stmt->bindParam(":organisationID", $orgID);
        $stmt->bindParam(":jobTypeID", $jobTypeID);
        try {
            $stmt->execute();
            $response->getBody()->write("ok");
            Logger::info("Job type with ID $jobTypeID removed from organisation with ID $orgID by user with ID $userID", $request->getRequestTarget());
            return $response->withStatus(200);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

    public function getUsers(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $decoded = $request->getAttribute("decoded");
        $userId = $decoded->userID;
        $role = $decoded->role;
        $orgID = $args["orgID"];


        $pdo = new PDO(DB_CONN);
        if (!$this->checkAccess($orgID, $userId, $role)) {
            $response->getBody()->write("Unauthorized");
            Logger::warning("Unauthorized attempt to get users in organisation with ID $orgID by user with ID $userId", $request->getRequestTarget());
            return $response->withStatus(401);
        }
        $stmt = $pdo->prepare("SELECT users.userName, user.userEmail from Users JOIN userOrganisations ON users.userId = userOrganisation.userId WHERE userOrganisation.organisationId = :orgID");
        $stmt->bindParam(":orgID", $orgID);
        try {
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response->getBody()->write(json_encode($results));
            Logger::info("Users in organisation with ID $orgID retrieved by user with ID $userId", $request->getRequestTarget());
            return $response->withStatus(200);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }


        public
        function getJobTypes(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
        {
            $orgId = $args["orgId"];
            $decoded = $request->getAttribute("decoded");
            $userId = $decoded->userID;
            $role = $decoded->role;
            if (!$this->checkAccess($orgId, $userId, $role)) {
                Logger::warning("Unauthorized attempt to get job types for organisation with ID $orgId by user with ID $userId", $request->getRequestTarget());
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }

            $pdo = new PDO(DB_CONN);
            $stmt = $pdo->prepare("SELECT jobTypes.jobTypeID, jobTypes.jobTypeName FROM jobTypes JOIN organisationJobTypes ON jobTypes.jobTypeID = organisationJobTypes.jobTypeID WHERE organisationJobTypes.organisationID = :orgID");
            $stmt->bindParam(":orgID", $orgId);
            try {
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response->getBody()->write(json_encode($results));
                Logger::info("Job types for organisation with ID $orgId retrieved by user with ID $userId", $request->getRequestTarget());
                return $response->withStatus(200);
            } catch (Exception $e) {
                Logger::error($e, $request->getRequestTarget());
                $response->getBody()->write("Internal Server Error");
                return $response->withStatus(500);
            }

        }

        public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface{
            $decoded = $request->getAttribute("decoded");
            $userID = $decoded->userID;
            $role = $decoded->role;
            $body = json_decode($request->getBody());
            $orgID = $body->orgID;
            $name = $body->name;
            $description = $body->description;
            if(!$this->checkAccess($orgID, $userID, $role, 1)){
                Logger::warning("Unauthorized attempt to update organisation with ID $orgID by user with ID $userID", $request->getRequestTarget());
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }
            $pdo = new PDO(DB_CONN);
            $stmt = $pdo->prepare("UPDATE organisations SET name = :name, description = :description WHERE organisationID = :orgID");
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":orgID", $orgID);
            try{
                $stmt->execute();
                $response->getBody()->write("ok");
                Logger::info("Organisation with ID $orgID updated by user with ID $userID", $request->getRequestTarget());
                return $response->withStatus(200);
            }catch(Exception $e){
                Logger::error($e, $request->getRequestTarget());
                $response->getBody()->write("Internal Server Error");
                return $response->withStatus(500);
            }
        }
}
