<?php

use DI\Container;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;
use Slim\Factory\AppFactory;
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

require __DIR__ . "/routes/Auth.php";
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . "/routes/Database.php";
require __DIR__ . "/routes/Users.php";
require __DIR__ . "/middleware/RequiresAuthentication.php";
require __DIR__ . "/middleware/RequiresAdmin.php";


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

    //====================================//
    //=============Job Types=============//

    //===============POST===============//
    //Create Job Type
    $app->post("/api/jobtypes/create", [JobTypes::class, 'create'])->add(new RequiresAdmin());

    //================GET===============//
    $app->get("/api/jobtypes", [JobTypes::class, 'getAll'])->add(new RequiresAuthentication());
    $app->get("/api/jobtypes/{jobTypeID}", [JobTypes::class, 'getById'])->add(new RequiresAuthentication());


    //===============PUT===============//
    $app->put("/api/jobtypes/{jobTypeID}", [JobTypes::class, 'updateById'])->add(new RequiresAdmin());

    //==============DELETE============//
    $app->delete("/api/jobtypes/{jobTypeID}", [JobTypes::class, 'deleteById'])->add(new RequiresAdmin());


    //====================================//
    //===============Jobs================//

    //===============POST===============//
    $app->post("/api/jobs/create", [Jobs::class, 'create'])->add(new RequiresAuthentication());

    //===============GET==============//
    $app->get("/api/jobtest", [Jobs::class, 'jobTest'])->add(new RequiresAuthentication());
    $app->get("/api/jobs/complete", [Jobs::class, 'getComplete'])->add(new RequiresAuthentication());
    $app->get("/api/jobs/running", [Jobs::class, 'getRunning'])->add(new RequiresAuthentication());
    $app->get("/api/jobs/failed", [Jobs::class, 'getFailed'])->add(new RequiresAuthentication());
    $app->get("/api/jobs/fileid", [Jobs::class, 'generateFileID'])->add(new RequiresAuthentication());
    $app->get("/api/jobs/output", [Jobs::class, 'getJobOutput'])->add(new RequiresAuthentication());
    $app->get("/api/jobs", [Jobs::class, 'getAll'])->add(new RequiresAuthentication());
    $app->any('/api/jobs/upload[/{id}]', function (ServerRequestInterface $request, ResponseInterface $response) {
        //Grab the users information from their decoded token
        $decodedToken = $request->getAttribute("decoded");
        //Grab the user ID to store with the job type
        $userID = $decodedToken->userID;


        $path = __DIR__ . "/usr/in/$userID/";
        if (!file_exists($path)) {
            mkdir($path, 0775, true);
        }

        $container = new Container();

        $container->set(ResponseFactoryInterface::class, new ResponseFactory());
        $container->set(StreamFactoryInterface::class, new StreamFactory());
        $container->set(SimpleCacheInterface::class, new Psr16Cache(new FilesystemAdapter()));
        $container->set(EventDispatcherInterface::class, new EventDispatcher());
        $container->set(FilenameFactoryInterface::class, function () use ($path) {
            return new OriginalFilenameFactory($path);
        });

        $container->set(LocationProviderInterface::class, new PathLocationProvider);
        $tus = new TusServer(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(SimpleCacheInterface::class),
            $container->get(EventDispatcherInterface::class),
            $container->get(FilenameFactoryInterface::class),
            $container->get(LocationProviderInterface::class),
        );


        $tus->setMaxSize(10737419264 * 1.5);
        return $tus->handle($request);
    })->add(new RequiresAuthentication());
    $app->get("/api/jobs/{jobID}/parameters", [Jobs::class, 'getParameters'])->add(new RequiresAuthentication());
    $app->get("/api/jobs/{jobID}", [Jobs::class, 'getJob'])->add(new RequiresAuthentication());


    $app->run();
}


