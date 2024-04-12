<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

include_once __DIR__ . "/../config/Config.php";
require_once __DIR__ . "/../helpers/Logger.php";

class Organisations
{
    public function __construct()
    {
    }

    //==============================================================================//
    //===============================Helper Functions==============================//
    //============================================================================//
    private function _setRole($userId, $organisationId, $role)
    {
        $pdo = new PDO(DB_CONN);
        $setRoleStmt = $pdo->prepare("UPDATE organisationUsers SET role = :role WHERE userId = :userId and organisationId = :organisationId");
        $setRoleStmt->bindParam(":userId", $userId);
        $setRoleStmt->bindParam(":organisationId", $organisationId);
        $setRoleStmt->bindParam(":role", $role);

        if (!$setRoleStmt->execute()) {
            throw new Error("Failed to update role for user with ID $userId to $role in organisation with ID $organisationId: " . print_r($setRoleStmt->errorInfo(), true));
        }
    }

    public function _getUserRole($userId, $organisationId): int
    {
        $pdo = new PDO(DB_CONN);
        $getRoleStmt = $pdo->prepare("SELECT role FROM organisationUsers WHERE userId = :userId AND organisationId = :organisationId");
        $getRoleStmt->bindParam(":userId", $userId);
        $getRoleStmt->bindParam(":organisationId", $organisationId);

        if (!$getRoleStmt->execute()) {
            throw new Error("Failed to get role for user with ID $userId in organisation with ID $organisationId: " . print_r($getRoleStmt->errorInfo(), true));
        }

        $role = $getRoleStmt->fetchColumn();

        return $role !== null ? intval($role) : -1;
    }

    private function _getOrganisationUsers($organisationId, $userId = null, $role = null)
    {
        $query = "SELECT users.userName, users.userEmail, users.role as globalRole, users.userId, organisationUsers.role FROM organisationUsers JOIN users ON users.userId = organisationUsers.userId WHERE organisationUsers.organisationId = :organisationId";
        if ($userId) {
            $query .= " AND users.userId = :userId";
        }

        if ($role) {
            $query .= " AND organisationUsers.role = :role";
        }


        $pdo = new PDO(DB_CONN);
        $getOrgUsrStmt = $pdo->prepare($query);
        if ($userId) {
            $getOrgUsrStmt->bindParam(":userId", $userId);
        }

        if ($role) {
            $getOrgUsrStmt->bindParam(":role", $role);
        }

        $getOrgUsrStmt->bindParam(":organisationId", $organisationId);


        if (!$getOrgUsrStmt->execute()) {
            throw new Error("Failed to get users for organisation with ID $organisationId: " . print_r($getOrgUsrStmt->errorInfo(), true));
        }

        $organisations = $getOrgUsrStmt->fetchAll(PDO::FETCH_ASSOC);
        if($role ===2 ){
            error_log("ADMINS: " . print_r($organisations, true));
        }
        return $organisations;
}
    private function _getUserOrganisations($userId, $role = null)
    {


        $pdo = new PDO(DB_CONN);
        $query = "SELECT organisations.organisationName, organisations.organisationId FROM organisations JOIN organisationUsers ON organisations.organisationId = organisationUsers.organisationId WHERE organisationUsers.userId = :userId";
        if ($role) {
            $query .= " AND organisationUsers.role = :role";
        }

        $getUsrOrgsStmt = $pdo->prepare($query);
        $getUsrOrgsStmt->bindParam(":userId", $userId);

        if ($role) {
            $getUsrOrgsStmt->bindParam(":role", $role);
        }

        if (!$getUsrOrgsStmt->execute()) {
            throw new Error("Failed to get organisations containing user with ID $userId: " . print_r($getUsrOrgsStmt->errorInfo(), true));
        }

        return $getUsrOrgsStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function _getFilteredUsers(Request $request, Response $response, array $args, $filter = null): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $role = intval($tokenData->role);
        $organisationId = $args["organisationId"] ?? null;
        $body = json_decode($request->getBody());
        $userToCheck = $body->userId ?? null;

        if (!$organisationId) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        try {
            $isOrgAdmin = $this->_getUserRole($userId, $organisationId);

            if ($isOrgAdmin !== 2 && $role !== 1 && ($userId !== $userToCheck)) {
                $response->getBody()->write("Unauthorised");
                return $response->withStatus(401);
            }
            $organisationUsers = $this->_getOrganisationUsers($organisationId, $userToCheck, $filter);
            if($filter === 2){
                error_log("_ADMINS: " . print_r($organisationUsers, true));
            }
        } catch (Exception $e) {
            error_log($e);
            $response->getBody()->write("Internal Server Error");
            Logger::error($e, $request->getRequestTarget());
            return $response->withStatus(500);
        }

        $response->getBody()->write(json_encode($organisationUsers));
        return $response->withStatus(200);
    }
    //===========================================================================//
    //=================================Routes===================================//
    //=========================================================================//

    //=============================Get Organisation============================//
    //==============================Method: GET===============================//
    //===============Route: /api/organisations/{organisationId}==============//
    public function getOrganisation(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $role = intval($tokenData->role);
        $organisationId = $args["organisationId"] ?? null;

        if (!$organisationId) {
            if ($role !== 1) {
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }
        } else {
            if (!$this->_getOrganisationUsers($organisationId, $userId)) {
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(500);
            }
        }

        try {
            $query = "SELECT * FROM organisations";
            if ($organisationId) {
                $query .= " WHERE organisationId = :organisationId";
            }



            $pdo = new PDO(DB_CONN);
            $getOrgStmt = $pdo->prepare($query);

            if ($organisationId) {
                $getOrgStmt->bindParam(":organisationId", $organisationId);
            }

            if (!$getOrgStmt->execute()) {
                throw new Error("Failed to get organisations for user with ID $userId: " . print_r($getOrgStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $organisations = $getOrgStmt->fetchAll(PDO::FETCH_ASSOC);
        $response->getBody()->write(json_encode($organisations));
        return $response->withStatus(200);
    }
    //===========================Create Organisation===========================//
    //============================Method: POST================================//
    //=======================Route: /api/organisations/======================//
    public function createOrganisation(Request $request, Response $response): Response
    {
        $body = json_decode($request->getBody());
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $adminId = $body->adminId ?? null;
        $organisationName = $body->organisationName ?? null;

        if (!$adminId || !$organisationName) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        $pdo = new PDO(DB_CONN);
        $pdo->beginTransaction();

        try {
            $createOrgStmt = $pdo->prepare("INSERT INTO organisations (organisationName) VALUES (:organisationName)");
            $createOrgStmt->bindParam(":organisationName", $organisationName);

            if (!$createOrgStmt->execute()) {
                throw new Error("Failed to create organisation for user with ID $userId: " . print_r($createOrgStmt->errorInfo(), true));
            }

            $organisationId = $pdo->lastInsertId();
            $addAdminStmt = $pdo->prepare("INSERT INTO organisationUsers (organisationId, userId, role) VALUES (:organisationId, :userId, 2)");
            $addAdminStmt->bindParam(":organisationId", $organisationId);
            $addAdminStmt->bindParam(":userId", $userId);

            if (!$addAdminStmt->execute()) {
                throw new Error("Failed to create organisation for user with ID $userId. (Failed to add user with ID $adminId as administrator): " . print_r($addAdminStmt->errorInfo(), true));
            }

            if (!$pdo->commit()) {
                throw new Error("Failed to create organisation for user with ID $userId. (Failed to commit transaction): " . print_r($pdo->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        Logger::debug("Organisation with ID $organisationId created for user with ID $userId and has admin with ID $adminId", $request->getRequestTarget());
        $response->getBody()->write("Record Created");
        return $response->withStatus(201);
    }

    //===========================Delete Organisation===========================//
    //===========================Method: DELETE===============================//
    //===============Route: /api/organisations/{organisationId}==============//
    public function deleteOrganisation(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $organisationId = $args["organisationId"] ?? null;

        if (!$organisationId) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        $pdo = new PDO(DB_CONN);
        $pdo->beginTransaction();
        try {
            $cleanupUsrStmt = $pdo->prepare("DELETE FROM organisationUsers WHERE organisationId = :organisationId");
            $cleanupJobTypesStmt = $pdo->prepare("DELETE FROM organisationJobTypes WHERE organisationId = :organisationId");
            $cleanupJobsStmt = $pdo->prepare("DELETE FROM organisationJobs WHERE organisationId = :organisationId");
            $deleteOrgStmt = $pdo->prepare("DELETE FROM organisations WHERE organisationId = :organisationId");

            $cleanupUsrStmt->bindParam(":organisationId", $organisationId);
            $cleanupJobTypesStmt->bindParam(":organisationId", $organisationId);
            $cleanupJobsStmt->bindParam(":organisationId", $organisationId);
            $deleteOrgStmt->bindParam(":organisationId", $organisationId);

            if (!$cleanupUsrStmt->execute()) {
                throw new Error("Failed to delete organisation with ID $organisationId for user with ID $userId (cleanupUsr): " . print_r($cleanupUsrStmt->errorInfo(), true));
            }
            if (!$cleanupJobTypesStmt->execute()) {
                throw new Error("Failed to delete organisation with ID $organisationId for user with ID $userId (cleanupJobTypes): " . print_r($cleanupJobTypesStmt->errorInfo(), true));
            }
            if (!$cleanupJobsStmt->execute()) {
                throw new Error("Failed to delete organisation with ID $organisationId for user with ID $userId (cleanupJobs): " . print_r($cleanupJobsStmt->errorInfo(), true));
            }

            if (!$deleteOrgStmt->execute()) {
                throw new Error("Failed to delete organisation with ID $organisationId for user with ID $userId  " . print_r($deleteOrgStmt->errorInfo(), true));
            }

            if (!$pdo->commit()) {
                throw new Error("Failed to delete organisation with ID $organisationId for user with ID $userId (failed to commit transaction): " . print_r($pdo->errorInfo(), true));
            }
        } catch (Exception $e) {
            $pdo->rollback();
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        Logger::debug("Organisation with ID $organisationId delted by user with ID $userId", $request->getRequestTarget());
        $response->getBody()->write("Record Deleted");
        return $response->withStatus(200);
    }

    //===========================Update Organisation===========================//
    //============================Method: PATCH===============================//
    //===============Route: /api/organisations/{organisationId}==============//
    public function updateOrganisation(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;

        $organisationId = $args["organisationId"] ?? null;
        $body = json_decode($request->getBody());
        $organisationName = $body->organisationName ?? null;

        if (!$organisationId | !$organisationName) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        try {
            $pdo = new PDO(DB_CONN);
            $updateOrgStmt = $pdo->prepare("UPDATE organisations SET organisationName = :organisationName WHERE organisationId = :organisationId");
            $updateOrgStmt->bindParam(":organisationName", $organisationName);
            $updateOrgStmt->bindParam(":organisationId", $organisationId);

            if (!$updateOrgStmt->execute()) {
                throw new Error("Failed to update organisation with ID $organisationId for user with ID $userId");
            }
        } catch (Exception $e) {
            $response->getBody()->write("Internal Server Error");
            Logger::error($e, $request->getRequestTarget());
            return $response->withStatus(500);
        }

        $response->getBody()->write("Record Updated");
        return $response->withStatus(200);
    }
    //============================Set user role===============================//
    //============================Method: PATCH==============================//
    //===========Route: /api/organisations/{organisationId}/users/==========//
    public function setRole(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $organisationId = $args["organisationId"];
        $body = json_decode($request->getBody());
        $userToAdd = $body->userId;
        $role = $body->role;

        try {
            if ($this->_getUserRole($userId, $organisationId) !== 2) {
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }

            $this->_setRole($userToAdd, $organisationId, $role);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
        $response->getBody()->write("Record Updated");
        return $response->withStatus(200);
    }
    //--This is a post request because PHP gets upset about . characters in the URL, so I need to put the userId in the body.
    //========================Get Organisation Users==========================//
    //=============================Method: GET===============================//
    //=========Route: /api/organisations/{organisationId}/users/get]========//
    public function getOrganisationUsers(Request $request, Response $response, array $args): Response
    {
        return $this->_getFilteredUsers($request, $response, $args);
    }
    //--This is a post request because PHP gets upset about . characters in the URL, so I need to put the userId in the body.
    //========================Get Organisation Admins=========================//
    //=============================Method: POST===============================//
    //============Route: /api/organisations/{organisationId}/admins==========//
    public function getOrganisationAdmins(Request $request, Response $response, array $args): Response
    {
        return $this->_getFilteredUsers($request, $response, $args, 2);
    }
    //--This is a post request because PHP gets upset about . characters in the URL, so I need to put the userId in the body.
    //====================Get Organisation Moderators=========================//
    //=============================Method: POST===============================//
    //==========Route: /api/organisations/{organisationId}/moderators=========//
    public function getOrganisationModerators(Request $request, Response $response, array $args): Response
    {
        return $this->_getFilteredUsers($request, $response, $args, 1);
    }

    //--This is a post request because PHP gets upset about . characters in the URL, so I need to put the userId in the body.
    //========================Get User Organisations===========================//
    //=============================Method: POST ==============================//
    //===========Route: /api/organisations/users/getorganisations============//
    public function getUserOrganisations(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $body = json_decode($request->getBody());
        $userToCheck = $body->userId ?? null;
        $role = intval($tokenData->role);
        $roleToCheck = $args["role"] ?? null;

        try {
            if ($userToCheck && $userToCheck !== $userId && $role !== 1) {
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(500);
            } else {
                $userToCheck = $userId;
            }
            $organisations = $this->_getUserOrganisations($userToCheck, $roleToCheck);
        } catch (Exception $e) {
            $response->getBody()->write("Internal Server Error");
            Logger::error($e, $request->getRequestTarget());
            return $response->withStatus(500);
        }


        $response->getBody()->write(json_encode($organisations));
        return $response->withStatus(200);
    }

//--This is a post request because PHP gets upset about . characters in the URL, so I need to put the userId in the body.
//=====================Remove User From Organisation==================//
//============================Method: POST===========================//
//======Route: /api/organisations/{organisationId}/users/remove=====//
    public
    function removeUserFromOrganisation(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $role = intval($tokenData->role);
        $organisationId = $args["organisationId"] ?? null;
        $body = json_decode($request->getBody());
        $userToRemove = $body->userId ?? null;
        try {
            if (!$organisationId || !$userId) {
                $response->getBody()->write("Bad Request");
                return $response->withStatus(500);
            }

            if ($role !== 1 && $this->_getUserRole($userId, $organisationId) !== 2) {
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }


            $pdo = new PDO(DB_CONN);
            $deleteUsrStmt = $pdo->prepare("DELETE FROM organisationUsers WHERE organisationId = :organisationId AND userId = :userId");
            $deleteUsrStmt->bindParam(":organisationId", $organisationId);
            $deleteUsrStmt->bindParam(":userId", $userToRemove);

            if (!$deleteUsrStmt->execute()) {
                throw new Error("Failed to delete user with ID $userToRemove from organisation with ID $organisationId for user with ID $userId: " . print_r($deleteUsrStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $response->getBody()->write("OK");
        return $response->withStatus(200);
    }

//=======================Add User to Organisation=====================//
//============================Method: POST===========================//
//=========Route: /api/organisations/{organisationId}/users/=========//
    public
    function addUserToOrganisation(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $role = intval($tokenData->role);
        $organisationId = $args["organisationId"] ?? null;
        $body = json_decode($request->getBody());
        $newUserEmail = $body->userEmail ?? null;
        $newUserRole = intval($body->role) ?? null;
        try {
            if (!$organisationId || !$newUserEmail || $newUserRole === null) {
                $response->getBody()->write("Bad Request");
                return $response->withStatus(400);
            }
            if ($role !== 1 && $this->_getUserRole($userId, $organisationId) !== 2) {
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }

            $pdo = new PDO(DB_CONN);
            $getUsrStmt = $pdo->prepare("SELECT userId FROM users WHERE userEmail = :userEmail");
            $getUsrStmt->bindParam(":userEmail", $newUserEmail);
            if (!$getUsrStmt->execute()) {
                throw new Error("Failed to fetch user from email address while adding to organisation with ID $organisationId for user with ID $userId: " . print_r($getUsrStmt->errorInfo(), true));
            }

            $newUserId = $getUsrStmt->fetchColumn();

            if (!$newUserId) {
                //We send an OK response when not found to make it harder to use this endpoint to find valid user emails
                $response->getBody()->write("OK");
                return $response->withStatus(200);
            }

            $addUserToOrgStmt = $pdo->prepare("INSERT INTO organisationUsers (userId, organisationId, role) VALUES (:userId, :organisationId, :role)");
            $addUserToOrgStmt->bindParam(":userId", $newUserId);
            $addUserToOrgStmt->bindParam(":organisationId", $organisationId);
            $addUserToOrgStmt->bindParam(":role", $newUserRole);

            if (!$addUserToOrgStmt->execute()) {
                throw new Error("Failed to add user with ID $newUserId to organisation with ID $organisationId for user with ID $userId: " . print_r($addUserToOrgStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            $response->getBody()->write("Internal Server Error");
            Logger::error($e, $request->getRequestTarget());
            return $response->withStatus(500);
        }

        $response->getBody()->write("OK");
        return $response->withStatus(200);
    }

    //=======================Get Organisation JobTypes====================//
    //============================Method: GET============================//
    //========Route: /api/organisations/{organisationId}/jobtypes=======//
    public function getOrganisationJobTypes(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $organisationId = $args["organisationId"];
        $role = intval($tokenData->role);
        if (!$this->_getOrganisationUsers($organisationId, $userId) && $role !== 1) {
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        }

        $pdo = new PDO(DB_CONN);
        try {
            $getJobTypesStmt = $pdo->prepare("SELECT jobTypes.*, users.userName AS createdByName, users.userId AS createdBy
FROM jobTypes 
JOIN organisationJobTypes ON jobTypes.jobTypeId = organisationJobTypes.jobTypeId 
LEFT JOIN users ON jobTypes.userId = users.userId
WHERE organisationJobTypes.organisationId = :organisationId
");
            $getJobTypesStmt->bindParam(":organisationId", $organisationId);
            if (!$getJobTypesStmt->execute()) {
                throw new Error("Failed to get job types belonging to organisation with ID $organisationId: " . print_r($getJobTypesStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $jobTypes = $getJobTypesStmt->fetchAll(PDO::FETCH_ASSOC);
        $response->getBody()->write(json_encode($jobTypes));
        return $response->withStatus(200);
    }
}