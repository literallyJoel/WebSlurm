<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . "/middleware/RequiresAuthentication.php";
require_once __DIR__ . "/middleware/RequiresAdmin.php";

$config = ['settings' => ['addContentLengthHeader' => false, 'displayErrorDetails' => true]];
$app = new App($config);

//!TEMP
header('Access-Control-Expose-Headers: Location, Upload-Offset, Upload-Length');
header('Access-Control-Allow-Origin: *');

if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS');
    header('Access-Control-Allow-Headers: token, Content-Type, upload-length, tus-resumable, upload-metadata, upload-offset, authorization');
    header('Access-Control-Max-Age: 1728000');
    header('Content-Length: 0');
    header('Content-Type: text/plain');
    die();
}

$container = $app->getContainer();
$container['DB'] = function ($container) {
    return new Database();
};
$container['Users'] = function ($container) {
    return new Users();
};
$container['Auth'] = function ($container) {
    return new Auth();
};
$container['JobTypes'] = function ($container) {
    return new JobTypes();
};
$container['Jobs'] = function ($container) {
    return new Jobs();
};

$container['Files'] = function ($container) {
    return new Files();
};

$container["Organisations"] = function ($container) {
    return new Organisations();
};

$container["Setup"] = function ($container) {
    return new Setup();
};

$app->group("/api", function (App $app) {

    $app->get("/db/setup", "DB:setup");

    $app->group("/setup", function (App $app) {
        $app->get("/shouldsetup", "Setup:getShouldSetup");
        //Creates the initial user and org. Returns 404 if the setup is already completed.
        $app->post("/createinitial", "Setup:createInitial");
    });

    $app->group("/auth", function (App $app) {
        $app->post("/verify", "Auth:verify")->add(new RequiresAuthentication());
        $app->post("/disabletokens", "Auth:disableUserTokens")->add(new RequiresAuthentication());
        $app->post("/verifypass", "Auth:verifyPass")->add(new RequiresAuthentication());
        $app->post("/login", "Auth:login");
        $app->post("/logout", "Auth:logout")->add(new RequiresAuthentication());
        $app->post("/refresh", "Auth:refreshToken")->add(new RequiresAuthentication());
    });

    $app->group("/files", function (App $app) {
        $app->get("/input/download/{jobId}/{filePath}", "Files:downloadInputFile")->add(new RequiresAuthentication());
        $app->get("/input/tree/{jobId}", "Files:getInputTree")->add(new RequiresAuthentication());
        $app->get("/output/download/{jobId}/{filePath}", "Files:downloadOutputFile")->add(new RequiresAuthentication());
        $app->get("/output/tree/{jobId}", "Files:getOutputTree")->add(new RequiresAuthentication);
        $app->get("/new", "Files:generateFileId")->add(new RequiresAuthentication());

        $app->any("/upload[/{id}]", "Files:handleFileUpload")->add(new RequiresAuthentication());
    });

    $app->group("/jobs", function (App $app) {
        $app->post("[/]", "Jobs:create")->add(new RequiresAuthentication());
        $app->post("/{jobId}/markcomplete", "Jobs:markComplete");

        $app->delete("[/]", "Jobs:delete")->add(new RequiresAuthentication());


        $app->get("/running", "Jobs:getRunning")->add(new RequiresAuthentication());
        $app->get("/failed", "Jobs:getFailed")->add(new RequiresAuthentication());
        $app->get("/complete", "Jobs:getComplete")->add(new RequiresAuthentication());
        $app->get("[/{jobId}]", "Jobs:getJob")->add(new RequiresAuthentication());
        $app->get("/{jobId}/parameters", "Jobs:getParameters")->add(new RequiresAuthentication());
    });

    $app->group("/jobtypes", function (App $app) {
        $app->post("[/]", "JobTypes:createJobType")->add(new RequiresAdmin());

        $app->get("[/{jobTypeId}]", "JobTypes:getJobType")->add(new RequiresAuthentication());

        $app->put("[/{jobTypeId}]", "JobTypes:update")->add(new RequiresAdmin());

        $app->delete("/{jobTypeId}", "JobTypes:delete")->add(new RequiresAdmin());
    });

    $app->group("/users", function (App $app) {
        $app->get("/count", "Users:getCount")->add(new RequiresAdmin());
        $app->get("[/{userId}]", "Users:getUser")->add(new RequiresAuthentication());

        $app->post("[/]", "Users:create")->add(new RequiresAdmin());


        $app->put("[/{userId}]", "Users:update")->add(new RequiresAuthentication());

        $app->delete("[/{userId}]", "Users:delete")->add(new RequiresAuthentication());
    });

    $app->group("/organisations", function (App $app) {
        $app->get("/{organisationId}", "Organisations:getOrganisation")->add(new RequiresAuthentication());
        $app->get("/{organisationId}/users[/{userId}]", "Organisations:getOrganisationUsers")->add(new RequiresAuthentication());
        $app->get("/{organisationId}/admins[/{userId}]", "Organisations:getOrganisationAdmins")->add(new RequiresAuthentication());
        $app->get("/{organisationId}/moderators[/{userId}]", "Organisations:getOrganisationModerators")->add(new RequiresAuthentication());

        $app->post("[/]", "Organisations:createOrganisation")->add(new RequiresAuthentication());
        $app->post("/users/getorganisations[/{role}]", "Organisations:getUserOrganisations")->add(new RequiresAuthentication());
        $app->post("/{organisationId}/users/remove", "Organisations:removeUserFromOrganisation")->add(new RequiresAuthentication());
        $app->post("/{organisationId}/users", "Organisations:addUserToOrganisation")->add(new RequiresAuthentication());

        $app->delete("/{organisationId}", "Organisations:deleteOrganisation")->add(new RequiresAdmin());

        $app->patch("/{organisationId}", "Organisations:updateOrganisation")->add(new RequiresAdmin());
        $app->patch("/{organisationId}/users/{userId}/{role}", "Organisations:setUserRole")->add(new RequiresAuthentication());
    });


});

$app->get("/favicon", function (Request $request, Response $response): Response {
    $file = __DIR__ . "/assets/jdvlogo.ico";
    $response->getBody()->write(file_get_contents($file));
    return $response->withAddedHeader("Content-Type", "image/x-icon")->withAddedHeader("Content-Length", filesize($file));
});

$app->get("/assets/[{file:.*}]", function (Request $request, Response $response, array $args): Response {
    $file = __DIR__ . "/assets/" . $args['file'];

    if (file_exists($file)) {
        $response->getBody()->write(file_get_contents("file"));
        return $response->withHeader("Content-Type", mime_content_type($file))->withAddedHeader("Content-Length", filesize($file));
    } else {
        return $response->withStatus(404);
    }
});

$app->get("/[{path:.*}]", function (Request $request, Response $response): Response {
    $file = __DIR__ . "/assets/index.html";
    $response->getBody()->write(file_get_contents($file));
    return $response;
});

try {
    $app->run();
} catch (Exception $e) {
    Logger::error($e, "index");
    http_response_code(500);
    echo "Internal Server Error";
    die;
}