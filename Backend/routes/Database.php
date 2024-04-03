<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

include_once __DIR__ . "/../config/Config.php";
require_once __DIR__ . "/../helpers/Logger.php";

class Database
{
    public function __construct()
    {
    }

    //===========================================================================//
    //=================================Routes===================================//
    //=========================================================================//

    //=============Setup Database==========//
    //=============Method: GET=============//
    //========Route: /api/db/setup========//
    public function setup(Request $request, Response $response): Response
    {
        try {
            if (file_exists(DB_PATH)) {
                //Some things will create a .db text file if the database doesn't exist, so we'll check
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file(DB_PATH);
                if ($mime === "application/x-sqlite3") {
                    $response->getBody()->write("DB Already Created");
                    return $response->withStatus(204);
                }
            }

            $db = new SQLite3(DB_PATH);
            error_log("YOO: " . TABLE_CREATE_QUERY);
            $db->exec(TABLE_CREATE_QUERY);

            /*
            This exists as a default user. It has no email or password so cannot be accessed,
            and has standard user permissions in any case. Used so when a user is removed,
            the commands they created can remain.
            */
            $ok = $db->exec("INSERT INTO users (userId, userName, role, requiresPasswordReset) VALUES ('default', 'Deleted User', 0, false)");
            if (!$ok) {
                throw new Error("Error creating database: " . print_r($db->lastErrorMsg(), true));
            }
            $db->close();


            Logger::info("Database Created", $request->getRequestTarget());
            $response->getBody()->write("Database Created");
            return $response->withStatus(201);
        } catch (Exception $e) {
            Logger::error($e, $request->getRequestTarget());
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }
    }
}