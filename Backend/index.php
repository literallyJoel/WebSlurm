<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . "/routes/Auth.php";
require_once __DIR__ . "/routes/Database.php";
require_once __DIR__ . "/routes/Users.php";
require_once __DIR__ . "/routes/Organisations.php";
require_once __DIR__ . "/middleware/RequiresAuthentication.php";
require_once __DIR__ . "/middleware/RequiresAdmin.php";
require_once __DIR__ . "/routes/Files.php";

$config = ['settings' => ['addContentLengthHeader' => false, 'displayErrorDetails' => true]];
$app = new \Slim\App($config);

//!TEMP
header('Access-Control-Expose-Headers: Location, Upload-Offset, Upload-Length');
header('Access-Control-Allow-Origin: *');

if($_SERVER["REQUEST_METHOD"] == "OPTIONS"){
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
$container['Organisations'] = function ($container) {
    return new Organisations();
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
$container['Jobs'] = function ($container){
    return new Jobs();
};

$container['Files'] = function ($container){
    return new Files();
};

$app->group("/api", function(App $app){
    //==============================//
    //===========Database===========//
    //==============================//
    //Setup Database
    $app->get("/db/setup", "DB:setup");


    //==============================//
    //========Organisations=========//
    //==============================//
    $app->group("/organisations", function(App $app){
        //=================GET================//
        //Get organisation by ID
        $app->get("/{organisationID}", "Organisations:getOrg")->add(new RequiresAdmin());
        //Get all users in an organisation
        $app->get("/{organisationID}/users", "Organisations:getUsers")->add(new RequiresAdmin());
        //Get all job types in an organisation
        $app->get("/{organisationID}/jobtypes", "Organisations:getJobTypes")->add(new RequiresAdmin());
        //Check if user is in organisation
        $app->get("/{organisationID}/ismember/{userId}", "Organisations:isUserInOrg")->add(new RequiresAuthentication());

        //=================POST================//
        //Create Organisation
        $app->post("/create", "Organisations:create")->add(new RequiresAdmin());
        //Add user to organisation
        $app->post("/{organisationID}/user/{userId}", "Organisations:addUserToOrg")->add(new RequiresAdmin());
        //Add job type to organisation
        $app->post("/{organisationID}/addjobtype", "Organisations:addJobTypeToOrg")->add(new RequiresAdmin());

        //=================PUT================//
        //Update Organisation
        $app->put("/{organisationID}", "Organisations:update")->add(new RequiresAdmin());

        //================DELETE================//
        //Delete Organisation
        $app->delete("/{organisationID}", "Organisations:delete")->add(new RequiresAdmin());
        //Remove user from organisation
        $app->delete("/{organisationID}/user/{userId}", "Organisations:removeUserFromOrg")->add(new RequiresAdmin());
        //Remove job type from organisation
        $app->delete("/{organisationID}/jobtype/{jobTypeID}", "Organisations:removeJobTypeFromOrg")->add(new RequiresAdmin());
    });
    //==============================//
    //============Files=============//
    //==============================//
    $app->group("/files", function (App $app){
        //Download output file
        $app->get("/out/download/{jobId}[/{fileId}]", "Files:downloadOutputFile")->add(new RequiresAuthentication);
        //Get output metadata
        $app->get("/out/meta/{jobId}[/{fileName}", "Files:getOutputMetadata")->add(new RequiresAuthentication);
        //Download input file
        $app->get("/in/download/{jobId}[/{arrayId}]", "Files:downloadInputFile")->add(new RequiresAuthentication());
        //Get input metadata
        $app->get("/in/meta/{jobId}[/{arrayId}]", "Files:getInputMetadata")->add(new RequiresAuthentication());
        //Upload a file
        $app->any('/upload[/{id}]', "Jobs:handleFileUpload")->add(new RequiresAuthentication());
    });
    //==============================//
    //============Users=============//
    //==============================//
    $app->group("/users", function(App $app){
        //================GET================/
        //Returns whether a user has been created, so if the setup screen should be shown
        $app->get("/shouldsetup", "Users:getShouldSetup");
        //Returns the number of users - admin only (excludes default)
        $app->get("/count", "Users:getCount")->add(new RequiresAdmin());
        //Get all users - admin only
        $app->get("", "Users:getAll")->add(new RequiresAdmin());

        //================POST================//
        //Create User
        //TODO: create custom route for creating first user so we can add auth to this
        $app->post("/create", "Users:create");

        //================PUT=================//
        //Update User
        $app->put("/update", "Users:update")->add(new RequiresAuthentication());

        //==============DELETE==============//
        //Delete User
        $app->delete("/delete", "Users:delete")->add(new RequiresAuthentication());
    });

    //==============================//
    //=============Auth=============//
    //==============================//
    $app->group("/auth", function(App $app){
        //Login User
        $app->post("/login", "Auth:login");
        //Logout User
        $app->post("/logout", "Auth:logout")->add(new RequiresAuthentication());
        //Verify User Token
        $app->post("/verify", "Auth:verify")->add(new RequiresAuthentication());
        //Verify password (used for account updates and deletions)
        $app->post("/verifypass", "Auth:verifyPass")->add(new RequiresAuthentication());
        //Disabled all of a users tokens
        $app->post("/disabletokens", "Auth:disableAllUserTokens")->add(new RequiresAuthentication());
        //Refresh user token
        $app->post("/refresh", "Auth:refreshToken")->add(new RequiresAuthentication());
    });

    //==============================//
    //==========Job Types===========//
    //==============================//
    $app->group("/jobtypes", function(App $app){
        //===============POST===============//
        //Create Job Type
        $app->post("/create", "JobTypes:create")->add(new RequiresAdmin());

        //================GET===============//
        //Get all job types
        $app->get("", "JobTypes:getAll")->add(new RequiresAuthentication());
        //Get Job type with ID
        $app->get("/{jobTypeID}", "JobTypes:getById")->add(new RequiresAuthentication());

        //===============PUT===============//
        //Update Job Type with ID
        $app->put("/{jobTypeID}", "JobTypes:updateById")->add(new RequiresAdmin());

        //==============DELETE============//
        //Delete Job Type with ID
        $app->delete("/{jobTypeID}", "JobTypes:deleteById")->add(new RequiresAdmin());
    });

    //==============================//
    //=============Jobs=============//
    //==============================//
    $app->group("/jobs", function(App $app){
        //===============POST===============//
        //Create new job type
        $app->post("/create", "Jobs:create")->add(new RequiresAuthentication());

        //===============GET==============//
        //Get complete jobs
        $app->get("/complete", "Jobs:getComplete")->add(new RequiresAuthentication());
        //Get running jobs
        $app->get("/running", "Jobs:getRunning")->add(new RequiresAuthentication());
        //Get failed jobs
        $app->get("/failed", "Jobs:getFailed")->add(new RequiresAuthentication());
        //Gets a file ID for a new file
        $app->get("/fileid", "Jobs:generateFileID")->add(new RequiresAuthentication());
        //Get job output
        $app->get("/output", "Jobs:getJobOutput")->add(new RequiresAuthentication());
        //Get all jobs
        $app->get("", "Jobs:getAll")->add(new RequiresAuthentication());
        //Download jobs input file
        $app->get("/{jobID}/download/in", "Jobs:downloadInputFile")->add(new RequiresAuthentication());
        //Download jobs output file
        $app->get("/{jobID}/download/out", "Jobs:downloadOutputFile")->add(new RequiresAuthentication());
        //Download jobs output file if there are multiple
        $app->get("/{jobID}/download/out/{file}", "Jobs:downloadMultiOut")->add(new RequiresAuthentication());
        //Get job parameters
        $app->get("/{jobID}/parameters", "Jobs:getParameters")->add(new RequiresAuthentication());
        //Get a job with the given ID
        $app->get("/{jobID}", "Jobs:getJob")->add(new RequiresAuthentication());
        //Get the metadata of the files in a jobs ZIP output
        $app->get("/{jobID}/zipinfo", "Jobs:getZipData")->add(new RequiresAuthentication());
        $app->get("/{jobID}/extracted/{file}", "Jobs:getExtractedFile")->add(new RequiresAuthentication());
        //Download a ZIP of all the files in a jobs output
        $app->get("/{jobID}/download/zip", "Jobs:downloadZip")->add(new RequiresAuthentication());
        //Mark a job as complete
        $app->post("/{jobId}/markcomplete", "Jobs:markComplete");
    });
});

// $app->options("/[{path:.*}]", function(ServerRequestInterface $request, ResponseInterface $response, $args){
//    $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');
//    $response = $response->withAddedHeader('Access-Control-Allow-Methods', 'POST, GET, DELETE, PUT, PATCH, OPTIONS');
//     $response = $response->withAddedHeader('Access-Control-Allow-Headers', 'token, Content-Type, upload-length, tus-resumable, upload-metadata, upload-offset, authorization');
//     $response = $response->withAddedHeader('Access-Control-Max-Age', '1728000');
//     $response = $response->withAddedHeader('Content-Length', '0');
//     $response = $response->withAddedHeader('Content-Type', 'text/plain');
// });


//Serve the favicon
$app->get("/favicon", function(ServerRequestInterface $request, ResponseInterface $response){
    $response = $response->withHeader('Content-Type', 'image/x-icon');
    $file = __DIR__ . '/assets/jdvlogo.ico';
    $response->getBody()->write(file_get_contents($file));
    return $response->withHeader("Content-Type", "image/x-icon")->withAddedHeader('Content-Length', filesize($file));
});

//Serve the assets
$app->get("/assets/[{file:.*}]", function(ServerRequestInterface $request, ResponseInterface $response, $args){
    $file = __DIR__ . '/dist/assets/' . $args['file'];
    if(file_exists($file)){
        $response->getBody()->write(file_get_contents($file));
        return $response->withHeader("Content-Type", mime_content_type($file))->withAddedHeader('Content-Length', filesize($file));
    } else {
        return $response->withStatus(404);
    }
});

//Serve the index.html file
$app->get("/[{path:.*}]", function(ServerRequestInterface $request, ResponseInterface $response, $args){
    $file = __DIR__ . '/assets/index.html';
    $response->getBody()->write(file_get_contents($file));
    return $response;
});

//Run the app
try {
    $app->run();
} catch (Exception $e){
    Logger::error($e, "index");
    header('Content-Type: application/json');
    http_response_code(500);
    echo "Internal Server Error";
    die();
}





