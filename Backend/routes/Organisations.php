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

    //Gets either all organisations or if an id is provided, that org
    private function getOrganisations($organisationId = null)
    {
        $query = "SELECT * FROM organisations";
        if ($organisationId) {
            $query .= " WHERE organisationId = :organisationId";
        }

        try {
            $pdo = new PDO(DB_CONN);
            $getOrgStmt = $pdo->prepare($query);
            if ($organisationId) {
                $getOrgStmt->bindParam(":organisationId", $organisationId);
            }

            $ok = $getOrgStmt->execute();
            if (!$ok) {
                throw new Error("PDO Error: " . print_r($getOrgStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, "organisations/getOrganisations");
            return null;
        }

        return $getOrgStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function setUserRole($userId, $organisationId, $role)
    {
        $pdo = new PDO(DB_CONN);
        $setUsrRoleStmt = $pdo->prepare("UPDATE organisationUsers SET role = :role WHERE userId = :userId AND organisationId = :organisationId");
        $setUsrRoleStmt->bindParam(":userId", $userId);
        $setUsrRoleStmt->bindParam(":organisationId", $organisationId);
        $setUsrRoleStmt->bindParam(":role", $role);

        if (!$setUsrRoleStmt->execute()) {
            throw new Error("Failed to update role to $role for user with ID $userId in Organisation with ID $organisationId: " . print_r($setUsrRoleStmt->errorInfo(), true));
        }
    }

    private function getUserRole($userId, $organisationId)
    {
        $pdo = new PDO(DB_CONN);
        try {
            $getUserRoleStmt = $pdo->prepare("SELECT role FROM organisationUsers WHERE organisationId = :organisationId AND userId = :userId");
            $getUserRoleStmt->bindParam(":organisationId", $organisationId);
            $getUserRoleStmt->bindParam(":userId", $userId);
            if (!$getUserRoleStmt->execute()) {
                throw new Error("Failed to delete org (getting user): " . print_r($pdo->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, "organisations/getUserRole");
            return null;
        }

        return $getUserRoleStmt->fetchColumn();

    }

    private function isUserAdmin($userId, $organisationId): bool
    {
        $role = $this->getUserRole($userId, $organisationId);

        return intval($role) === 2;
    }

    private function isUserModerator($userId, $organisationId)
    {
        $role = $this->getUserRole($userId, $organisationId);
        return intval($role) === 1;
    }

    private function getOrganisationUsers($organisationId, $userId = null, $role = null)
    {
        $query = "SELECT users.userName, users.userEmail, users.role as globalRole, users.userID, organisationUsers.role  FROM organisationUsers JOIN users ON users.userId = organisationUsers.userId WHERE organisationUsers.organisationId = :organisationId";
        if ($userId) {
            $query .= " AND organisationUsers.userId = :userId";
        }

        if ($role) {
            $query .= " AND organisationUsers.role = :role";
        }

        try {
            $pdo = new PDO(DB_CONN);
            $getOrgUsersStmt = $pdo->prepare($query);

            $getOrgUsersStmt->bindParam(":organisationId", $organisationId);
            if ($userId) {
                $getOrgUsersStmt->bindParam(":userId", $userId);
            }

            if ($role) {
                $getOrgUsersStmt->bindParam(":role", $role);
            }

            $ok = $getOrgUsersStmt->execute();
            if (!$ok) {
                throw new Error("PDO Error: ", $pdo->errorInfo());
            }
        } catch (Exception $e) {
            Logger::error($e, "organisations/getOrganisationUsers");
            return null;
        }

        return $getOrgUsersStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getUserOrgs($userId, $role = null)
    {
        $pdo = new PDO(DB_CONN);
        $query = "SELECT organisations.organisationName, organisations.organisationId FROM organisations JOIN organisationUsers ON organisations.organisationId = organisationUsers.organisationId WHERE organisationUsers.userId = :userId";
        if ($role) {
            $query .= " AND organisationUsers.role = :role";
        }
        $getOrgsStmt = $pdo->prepare($query);
        $getOrgsStmt->bindParam(":userId", $userId);
        if ($role) {
            $getOrgsStmt->bindParam(":role", $role);
        }
        if (!$getOrgsStmt->execute()) {
            throw new Error("Failed to get organisations where user with $userId is a member: " . print_r($getOrgsStmt->errorInfo(), true));
        }
        $orgs = $getOrgsStmt->fetchAll(PDO::FETCH_ASSOC);
        return $orgs;
    }

    private function handleRoleRequest(Request $request, Response $response, array $args, int $role): Response
    {
        $body = json_decode($request->getBody());
        $tokenData = $request->getAttribute("tokenData");
        $currentUserId = $tokenData->userId;
        $selectedUserId = $body->userId ?? null;
        $organisationId = $args["organisationId"] ?? null;


        if (!$selectedUserId || !$organisationId) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }
        if (!$this->isUserAdmin($currentUserId, $organisationId)) {
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        }

        try {
            $this->setUserRole($selectedUserId, $organisationId, $role);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $response->getBody()->write("Record Updated");
        return $response->withStatus(200);

    }

    private function getIsUserRole($userId, $role): bool
    {
        $pdo = new PDO(DB_CONN);

        $getIsRoleStmt = $pdo->prepare("SELECT * FROM organisationUsers WHERE userId = :userId AND $role = :role");
        $getIsRoleStmt->bindParam(":userId", $userId);
        $getIsRoleStmt->bindParam(":role", $role);

        if (!$getIsRoleStmt->execute()) {
            throw new Error("Failed to get roles for user with ID $userId: " . print_r($getIsRoleStmt->bindParam(), true));
        }

        $usr = $getIsRoleStmt->fetchAll();

        return !(!$usr || count($usr) === 0);
    }
    //===========================================================================//
    //=================================Routes===================================//
    //=========================================================================//

    //=============================Make User Admin============================//
    //=============================Method: PUT===============================//
    //====Route: /api/organisations/{organisationId}/users/admin/{userId}====//
    public function makeUserAdmin(Request $request, Response $response, array $args): Response
    {
        return $this->handleRoleRequest($request, $response, $args, 2);
    }

    //=========================Make User Moderator============================//
    //=============================Method: PUT===============================//
    //==Route: /api/organisations/{organisationId}/users/moderator/{userId}=//
    public function makeUserModerator(Request $request, Response $response, array $args): Response
    {
        return $this->handleRoleRequest($request, $response, $args, 1);
    }
    //============================Make User User=============================//
    //=============================Method: PUT===============================//
    //====Route: /api/organisations/{organisationId}/users/user/{userId}====//
    public function makeUserUser(Request $request, Response $response, array $args): Response
    {
        return $this->handleRoleRequest($request, $response, $args, 0);
    }
    //=============================Get Admin Orgs=============================//
    //=============================Method: GET===============================//
    //====================Route: /api/organisations/admin===================//
    public function getAdminOrgs(Request $request, Response $response): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;

        try {
            $organisations = $this->getUserOrgs($userId, 2);
        } catch (Exception $e) {
            $response->getBody()->write("Internal Server Error");
            Logger::error($e, $request->getRequestTarget());
            return $response->withStatus(500);
        }

        $response->getBody()->write(json_encode($organisations));
        return $response->withStatus(200);
    }
    //=============================Get User Orgs============================//
    //===========================Method: GET===============================//
    //==============Route: /api/organisations/users[/{userId}]=============//
    public function getUserMemberships(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $globalRole = $tokenData->role;
        $userId = $tokenData->userId;
        $requestedId = $args["userId"] ?? null;

        if ($requestedId) {
            if ($requestedId !== $userId && intval($globalRole) !== 1) {
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }
        }

        try {
            $orgs = $this->getUserOrgs(!!$requestedId ? $requestedId : $userId);
        } catch (Exception $e) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(200);
        }
        $response->getBody()->write(json_encode($orgs));
        return $response->withStatus(200);
    }
    //==============================Get Org=================================//
    //============================Method: GET==============================//
    //===========Route: /api/organisations[/{organisationId}]=============//
    public function getOrganisation(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $globalRole = $tokenData->role;
        $organisationId = $args["organisationId"] ?? null;

        if (!$organisationId) {
            if (intval($globalRole) !== 1) {

                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }
        } else {
            $temp = $this->getOrganisationUsers($organisationId, $userId);
            error_log(print_r($temp, true));
            if (!$this->getOrganisationUsers($organisationId, $userId)) {
                $response->getBody()->write("Unauthorized");
                return $response->withStatus(401);
            }
        }

        $orgs = $this->getOrganisations($organisationId);
        if ($orgs === null) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $response->getBody()->write(json_encode($orgs));
        return $response->withStatus(200);
    }
    //=============================Create Org=============================//
    //============================Method: POST===========================//
    //====================Route: /api/organisations/====================//
    public function create(Request $request, Response $response): Response
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
                throw new error ("Failed to create organisation: " . print_r($createOrgStmt->errorInfo(), true));
            }

            $organisationId = $pdo->lastInsertId();
            $addAdminStmt = $pdo->prepare("INSERT INTO organisationUsers (organisationId, userId, role) VALUES (:organisationId, :userId, 2)");
            $addAdminStmt->bindParam(":organisationId", $organisationId);
            $addAdminStmt->bindParam(":userId", $userId);
            if (!$addAdminStmt->execute()) {
                throw new Error("Failed to create organisation (adding admin): " . print_r($addAdminStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $pdo->rollBack();
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        if (!$pdo->commit()) {
            Logger::error("Failed to create organisation: " . print_r($pdo->errorInfo(), true), $request->getRequestTarget());
            $pdo->rollBack();
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $orgId = $pdo->lastInsertId();
        Logger::debug("Organisation with ID $orgId created by user with ID $userId and added admin with ID $adminId", $request->getRequestTarget());
        $response->getBody()->write("Record Created");
        return $response->withStatus(201);

    }



    //=============================Delete Org=============================//
    //===========================Method: DELETE==========================//
    //============Route: /api/organisations/{organisationId}============//
    public function deleteOrganisation(Request $request, Response $response, array $args)
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
            $cleanupUsrStmt = $pdo->prepare("DELETE FROM userOrganisations WHERE organisationId = :organisationId");
            $cleanupJobTypesStmt = $pdo->prepare("DELETE FROM organisationJobTypes WHERE organisationId = :organisationId");
            $cleanupJobsStmt = $pdo->prepare("DELETE FROM organisationJobs WHERE organisationID = :organisationId");
            $deleteOrgStmt = $pdo->prepare("DELETE FROM organisations WHERE organisationId = :organisationId");

            if (!$cleanupUsrStmt->execute()) {
                throw new Error("Failed to delete organisation (cleanupUsr): " . print_r($cleanupUsrStmt->errorInfo(), true));
            }
            if (!$cleanupJobTypesStmt->execute()) {
                throw new Error("Failed to delete organisation (cleanupJobTypes): " . print_r($cleanupJobTypesStmt->errorInfo(), true));
            }
            if (!$cleanupJobsStmt->execute()) {
                throw new Error("Failed to delete organisation (cleanupJobs): " . print_r($cleanupJobsStmt->errorInfo(), true));
            }

            if (!$deleteOrgStmt->execute()) {
                throw new Error("Failed to delete organisation: " . print_r($deleteOrgStmt->errorInfo(), true));
            }
        } catch (Exception $e) {
            $pdo->rollback();
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        if (!$pdo->commit()) {
            $response->getBody()->write("Internal Server Error");
            Logger::error("Failed to delete organisation with Id $organisationId:" . print_r($pdo->errorInfo(), true), $request->getRequestTarget());
            return $response->withStatus(500);
        }

        Logger::debug("Organisation with ID $organisationId deleted by user with ID $userId", $request->getRequestTarget());
        $response->getBody()->write("Record Deleted");
        return $response->withStatus(200);
    }

    //=============================Update Org=============================//
    //=============================Method: PUT===========================//
    //====================Route: /api/organisations/====================//
    public function updateOrganisation(Request $request, Response $response): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $body = json_decode($request->getBody());
        $organisationId = $body->organisationId ?? null;
        $organisationName = $body->organisationName ?? null;

        if (!$organisationId || !$organisationName) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        try {
            $pdo = new PDO(DB_CONN);

            $updateOrgStmt = $pdo->prepare("UPDATE organisations set organisationName = :organisationName WHERE organisationId = :organisationId");
            $updateOrgStmt->bindParam(":organisationName", $organisationName);
            $updateOrgStmt->bindParam(":organisationId", $organisationId);

            if (!$updateOrgStmt->execute()) {
                throw new Error("Failed to update organisation with id $organisationId: " . print_r($updateOrgStmt->errorInfo(), true));
            }

            Logger::debug("Successfully updated organisation with ID $organisationId for user with ID $userId", $request->getRequestTarget());
            $response->getBody()->write("Record Updated");
            return $response->withStatus(200);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

    //==============================Add User==============================//
    //============================Method: POST===========================//
    //==================Route: /api/organisations/user==================//
    public function addUser(Request $request, Response $response): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $body = json_decode($request->getBody());
        $organisationId = $body->organisationId ?? null;
        $userToAdd = $body->userEmail ?? null;
        $newUserRole = $body->role ?? null;

        if (!$organisationId || !$userToAdd || (intval($newUserRole) !== 0 && intval($newUserRole) !== 1 && intval($newUserRole) !== 2)) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        $globalRole = $tokenData->role;

        if (!$this->isUserAdmin($userId, $organisationId) && intval($globalRole) !== 1) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        try {
            $pdo = new PDO(DB_CONN);
            $getUsrStmt = $pdo->prepare("SELECT * from Users where userEmail = :userEmail");
            $getUsrStmt->bindParam(":userEmail", $userToAdd);
            if (!$getUsrStmt->execute()) {
                throw new Error("Failed to get user from email: " . print_r($getUsrStmt->errorInfo(), true));
            }

            $toAddId = $getUsrStmt->fetchColumn();


            if (!$toAddId) {
                //We OK because we don't want this endpoint to reveal valid emails
                $response->getBody()->write("Record Created");
                return $response->withStatus(200);
            }

            $addUserStmt = $pdo->prepare("INSERT INTO organisationUsers (organisationId, userId, role) VALUES (:organisationId, :userId, :role)");
            $addUserStmt->bindParam(":organisationId", $organisationId);
            $addUserStmt->bindParam(":userId", $toAddId);
            $addUserStmt->bindParam(":role", $newUserRole);

            if (!$addUserStmt->execute()) {
                throw new Error("Failed to add user with ID $toAddId to org with ID $organisationId: " . print_r($pdo->errorInfo(), true));
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        Logger::debug("User with ID $userId added User with ID $toAddId to organisation with ID $organisationId", $request->getRequestTarget());
        $response->getBody()->write("Record Created");
        return $response->withStatus(200);
    }

    //=============================Delete User============================//
    //============================Method: DELETE=========================//
    //======Route: /api/organisations/{organisationId}/{userId}=========//
    public function deleteUser(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $role = $tokenData->role;
        $organisationId = $args["organisationId"] ?? null;
        $userToDelete = $args["userId"] ?? null;

        if (!$organisationId || $userToDelete) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(500);
        }

        if (!$this->isUserAdmin($userId, $organisationId) || intval($role) !== 1) {
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        }

        try {
            $pdo = new PDO(DB_CONN);
            $removeUserStmt = $pdo->prepare("DELETE FROM organisationUsers WHERE organisationId = :organisationId AND userId = :userId");
            $removeUserStmt->bindParam(":organisationId", $organisationId);
            $removeUserStmt->bindParam(":userId", $userToDelete);
            if (!$removeUserStmt->execute()) {
                throw new Error("Failed to remove user with ID $userToDelete from Organisation with ID $organisationId");
            }
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        Logger::debug("User with ID $userId removed user with ID $userToDelete from organisation with ID $organisationId", $request->getRequestTarget());
        $response->getBody()->write("Record Deleted");
        return $response->withStatus(200);
    }

    //==============================Get Users==============================//
    //=============================Method: GET============================//
    //====Route: /api/organisations/{organisationId}/users[/{userId}]====//
    public function getUser(Request $request, Response $response, array $args): Response
    {

        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $organisationId = $args["organisationId"] ?? null;
        $userToFind = $args["userId"] ?? null;

        if (!$this->isUserAdmin($userId, $organisationId)) {
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        }
        if (!$organisationId) {
            $response->getBody()->write("Bad Request");
            return $response->withStatus(400);
        }

        $user = $this->getOrganisationUsers($organisationId, $userToFind);

        if ($user === null) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $response->getBody()->write(json_encode($user));
        return $response->withStatus(200);
    }

    //==============================Get Admins=============================//
    //=============================Method: GET============================//
    //======Route: /api/organisations/users/{organisationId}/admins======//
    public function getAdmins(Request $request, Response $response, array $args)
    {
        $tokenData = $request->getAttribute("tokenData");
        $userId = $tokenData->userId;
        $organisationId = $args["organisationId"];

        if (!$this->getOrganisationUsers($organisationId, $userId)) {
            $response->getBody()->write("Unauthorized");
            return $response->withStatus(401);
        }


        $admins = $this->getOrganisationUsers($organisationId, null, 2);

        if ($admins === null) {
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

        $response->getBody()->write(json_encode($admins));
        return $response->withStatus(200);
    }

    //=========================Is User a Moderator============================//
    //=============================Method: GET===============================//
    //==Route: /api/organisations/isModerator[/${userId}][/{organisationId}]==//
    public function isUserModerator(Request $request, Response $response, array $args): Response
    {
        $tokenData = $request->getAttribute("tokenData");
        $role = $tokenData->role;
        $userId = $tokenData->userId;
        $userToCheck = $args["userId"] ?? null;
        $organisationId = $args["organisationId"] ?? null;

        if ($userToCheck) {
            if ($userToCheck !== $userId) {
                if ($organisationId) {
                    if (!$this->isUserModerator($userId, $organisationId)) {
                        $response->getBody()->write("Unauthorized");
                        return $response->withStatus(500);
                    }
                } else if (intval($role) !== 1) {
                    $response->getBody()->write("Unauthorized");
                    return $response->withStatus(500);
                }
            }
        } else {
            $userToCheck = $userId;
        }

        try {
            if ($organisationId) {
                $isModerator = $this->isUserModerator($userToCheck, $organisationId);
            } else {
                $isModerator = $this->getIsUserRole($userToCheck, 2);
            }
            $response->getBody()->write(json_encode(["isModerator" => $isModerator]));
            return $response->withStatus(200);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }

}