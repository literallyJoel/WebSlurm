<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . "/routes/Auth.php";
require_once __DIR__ . "/routes/Database.php";
require_once __DIR__ . "/routes/Users.php";
require_once __DIR__ . "/routes/Organisations.php";
require_once __DIR__ . "/middleware/RequiresAuthentication.php";
require_once __DIR__ . "/middleware/RequiresAdmin.php";

//!TEMP
header('Access-Control-Expose-Headers: Location, Upload-Offset, Upload-Length');
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS');
    header('Access-Control-Allow-Headers: token, Content-Type, upload-length, tus-resumable, upload-metadata, upload-offset, authorization');
    header('Access-Control-Max-Age: 1728000');
    header('Content-Length: 0');
    header('Content-Type: text/plain');
    die();
}


if (!str_starts_with($_SERVER['REQUEST_URI'], "/~sgjvivia/api") && !str_starts_with($_SERVER['REQUEST_URI'], "/~sgjvivia/files")) {

    // Check if the build folder exists

    //favicon
    if ($_SERVER['REQUEST_URI'] === "/favicon") {
        header('Content-Type: image/x-icon');
        readfile(__DIR__ . '/assets/jdvlogo.ico');
        die();
    }


    if (str_starts_with($_SERVER['REQUEST_URI'], "/assets")) {
        error_log("ASSET REDIRECT");
        $assetPath = __DIR__ . '/dist' . $_SERVER['REQUEST_URI'];
        if (file_exists($assetPath)) {
            readfile($assetPath);
            die();
        }
    }
    // Serve the index.html file from the build folder

    readfile(__DIR__ . '/assets/index.html');

    die();
} else if ((str_starts_with($_SERVER['REQUEST_URI'], "/~sgjvivia/api")) || (str_starts_with($_SERVER['REQUEST_URI'], "/~sgjvivia/files"))) {
    error_log("API CALL");
    $authenticate = new requiresAuthentication();
    // Instantiate app
    $config = ['settings' => ['addContentLengthHeader' => false, 'displayErrorDetails' => true]];
    $app = new \Slim\App($config);


    //=========================================//
    //============Route Definitions===========//
    //=======================================//

    //======================================//
    //==============Database===============//

    //================GET=================//
    $container = $app->getContainer();

    $container['DB'] = function ($container) {
        return new Database();
    };

//Setup Database - eventually this'll be behind auth but for now it's easier
    $app->get("/api/db/setup", "DB:setup");


//=======================================//
//=============Organisations============//

//=================GET=================//
    $container['Organisations'] = function ($container) {
        return new Organisations();
    };
//Get all organisations
    $app->get("/api/organisations", "Organisations:getAll")->add(new RequiresAdmin());
//Get organisation by ID
    $app->get("/api/organisations/{organisationID}", "Organisations:getOrg")->add(new RequiresAdmin());
//Get all users in an organisation
    $app->get("/api/organisations/{organisationID}/users", "Organisations:getUsers")->add(new RequiresAdmin());
//Get all job types in an organisation
    $app->get("/api/organisations/{organisationID}/jobtypes", "Organisations:getJobTypes")->add(new RequiresAdmin());
//Check if user is in organisation
    $app->get("/api/organisations/{organisationID}/ismember/{userId}", "Organisations:isUserInOrg")->add(new RequiresAuthentication());
//=================POST================//
//Create Organisation
    $app->post("/api/organisations/create", "Organisations:create")->add(new RequiresAdmin());
//Add user to organisation
    $app->post("/api/organisations/{organisationID}/user/{userId}", "Organisations:addUserToOrg")->add(new RequiresAdmin());
//Add job type to organisation
    $app->post("/api/organisations/{organisationID}/addjobtype", "Organisations:addJobTypeToOrg")->add(new RequiresAdmin());
//=================PUT================//
//Update Organisation
    $app->put("/api/organisations/{organisationID}", "Organisations:update")->add(new RequiresAdmin());
//================DELETE================//
//Delete Organisation
    $app->delete("/api/organisations/{organisationID}", "Organisations:delete")->add(new RequiresAdmin());
//Remove user from organisation
    $app->delete("/api/organisations/{organisationID}/user/{userId}", "Organisations:removeUserFromOrg")->add(new RequiresAdmin());
//Remove job type from organisation
    $app->delete("/api/organisations/{organisationID}/jobtype/{jobTypeID}", "Organisations:removeJobTypeFromOrg")->add(new RequiresAdmin());
//======================================//
//================Users================//


    $container['Users'] = function ($container) {
        return new Users();
    };

//================GET=================//
//Returns whether a user has been created, so if the setup screen should be shown
    $app->get("/api/users/shouldsetup", "Users:getShouldSetup");
//Returns the number of users - admin only (excludes default)
    $app->get("/api/users/count", "Users:getCount")->add(new RequiresAdmin());
//Get all users - admin only
    $app->get("/api/users", "Users:getAll")->add(new RequiresAdmin());
//================POST================//
//Create User
    $app->post("/api/users/create", "Users:create");

//================PUT=================//
//Update User
    $app->put("/api/users/update", "Users:update")->add(new RequiresAuthentication());

//==============DELETE==============//
//Delete User
    $app->delete("/api/users/delete", "Users:delete")->add(new RequiresAuthentication());


//====================================//
//===============Auth================//

//===============POST===============//
    $container['Auth'] = function ($container) {
        return new Auth();
    };
//Login User
    $app->post("/api/auth/login", "Auth:login");
//Logout User
    $app->post("/api/auth/logout", "Auth:logout")->add(new RequiresAuthentication());
//Verify User Token
    $app->post("/api/auth/verify", "Auth:verify")->add(new RequiresAuthentication());
//Verify password (used for account updates and deletions)
    $app->post("/api/auth/verifypass", "Auth:verifyPass")->add(new RequiresAuthentication());
//Disabled all of a users tokens
    $app->post("/api/auth/disabletokens", "Auth:disableTokens")->add(new RequiresAuthentication());

//====================================//
//=============Job Types=============//
    $container['JobTypes'] = function ($container) {
        return new JobTypes();
    };
//===============POST===============//
//Create Job Type
    $app->post("/api/jobtypes/create", "JobTypes:create")->add(new RequiresAdmin());

//================GET===============//
    $app->get("/api/jobtypes", "JobTypes:getAll")->add(new RequiresAuthentication());
    $app->get("/api/jobtypes/{jobTypeID}", "JobTypes:getById")->add(new RequiresAuthentication());


//===============PUT===============//
    $app->put("/api/jobtypes/{jobTypeID}", "JobTypes:updateById")->add(new RequiresAdmin());

//==============DELETE============//
    $app->delete("/api/jobtypes/{jobTypeID}", "JobTypes:deleteById")->add(new RequiresAdmin());


//====================================//
//===============Jobs================//
    $container['Jobs'] = function ($container) {
        return new Jobs();
    };

//===============POST===============//
    $app->post("/api/jobs/create", "Jobs:create")->add(new RequiresAuthentication());

//===============GET==============//
    $app->get("/api/jobtest", "Jobs:jobTest")->add(new RequiresAuthentication());
    $app->get("/api/jobs/complete", "Jobs:getComplete")->add(new RequiresAuthentication());
    $app->get("/api/jobs/running", "Jobs:getRunning")->add(new RequiresAuthentication());
    $app->get("/api/jobs/failed", "Jobs:getFailed")->add(new RequiresAuthentication());
    $app->get("/api/jobs/fileid", "Jobs:generateFileID")->add(new RequiresAuthentication());
    $app->get("/api/jobs/output", "Jobs:getJobOutput")->add(new RequiresAuthentication());
    $app->get("/api/jobs", "Jobs:getAll")->add(new RequiresAuthentication());
    $app->any('/api/jobs/upload[/{id}]', "Jobs:handleFileUpload")->add(new RequiresAuthentication());
    $app->any('/files/[{id}]', "Jobs:handleFileUpload")->add(new RequiresAuthentication());
    $app->get("/api/jobs/{jobID}/download/in", "Jobs:downloadInputFile")->add(new RequiresAuthentication());
    $app->get("/api/jobs/{jobID}/download/out", "Jobs:downloadOutputFile")->add(new RequiresAuthentication());
    $app->get("/api/jobs/{jobID}/download/out/{file}", "Jobs:downloadMultiOut")->add(new RequiresAuthentication());
    $app->get("/api/jobs/{jobID}/parameters", "Jobs:getParameters")->add(new RequiresAuthentication());
    $app->get("/api/jobs/{jobID}", "Jobs:getJob")->add(new RequiresAuthentication());
    $app->get("/api/jobs/{jobID}/zipinfo", "Jobs:getZipData")->add(new RequiresAuthentication());
    $app->get("/api/jobs/{jobID}/extracted/{file}", "Jobs:getExtractedFile")->add(new RequiresAuthentication());
    $app->get("/api/jobs/{jobID}/download/zip", "Jobs:downloadZip")->add(new RequiresAuthentication());
    $app->post("/api/jobs/{jobId}/markcomplete", "Jobs:markComplete");
}
try {
    $app->run();
} catch (Exception $e){
    Logger::error($e, "index");
    header('Content-Type: application/json');
    http_response_code(500);
    echo "Internal Server Error";
    die();
}




?>