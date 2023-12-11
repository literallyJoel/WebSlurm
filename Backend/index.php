<?php

use Slim\Factory\AppFactory;

require __DIR__ . "/routes/Auth.php";
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . "/routes/Database.php";
require __DIR__ . "/routes/Users.php";
require __DIR__ . "/middleware/RequiresAuthentication.php";

//Redirect to React App if not an API route
if (!str_starts_with($_SERVER['REQUEST_URI'], "/api")) {
    $buildPath = __DIR__ . "/build";
    // Check if the build folder exists
    if (file_exists($buildPath)) {
        // Serve the index.html file from the build folder
        header('Content-Type: text/html');
        readfile($buildPath . '/index.html');
    }

} else {
    $authenticate = new requiresAuthentication();

    // Instantiate app
    $app = AppFactory::create();

    $app->addErrorMiddleware(true, true, true);

    //=========================================//
    //============Route Definitions===========//
    //=======================================//

    //======================================//
    //==============Database===============//

    //================GET=================//

    //Setup Database - eventually this'll be behind auth but for now it's easier
    $app->get("/api/db/setup", [Database::class, 'setup']);


    //======================================//
    //================Users================//


    //================POST================//
    //Create User
    $app->post("/api/users/create", [\Users::class, 'create']);

   //================PUT=================//
    //Update User
    $app->put("/api/users/update", [\Users::class, 'update'])->add(new RequiresAuthentication());

    //==============DELETE==============//
    //Delete User
    $app->delete("/api/users/delete", [\Users::class, 'delete'])->add(new RequiresAuthentication());


    //====================================//
    //===============Auth================//

    //===============POST===============//

    //Login User
    $app->post("/api/auth/login", [Auth::class, 'login']);
    //Logout User
    $app->post("/api/auth/logout", [Auth::class, 'logout'])->add(new RequiresAuthentication());
    //Verify User Token
    $app->post("/api/auth/verify", [Auth::class, 'verify'])->add(new RequiresAuthentication());
    //Verify password (used for account updates and deletions)
    $app->post("/api/auth/verifypass", [Auth::class, 'verifyPass'])->add(new RequiresAuthentication());
    //Disabled all of a users tokens
    $app->post("/api/auth/disabletokens", [Auth::class, 'disableTokens'])->add(new RequiresAuthentication());
    $app->run();    
}


