<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
require_once __DIR__ . "/../config/config.php";
class Database
{
    public function __construct()
    {
    }


    public function setup(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            
            if (!file_exists(DB_PATH)) {
                if(!file_exists(__DIR__ . "/../data")){
                    mkdir(__DIR__ . "/../data", 0775, true);
                }
                $db = new SQLite3(DB_PATH);

                // Create the users table
                $db->exec('CREATE TABLE IF NOT EXISTS users (
                userID TEXT PRIMARY KEY NOT NULL,
                userName TEXT,
                userEmail TEXT,
                userPWHash TEXT,
                role INTEGER NOT NULL,
                requiresPasswordReset BOOLEAN NOT NULL
            )');

                /*
                This exists as a default user. It has no email or password so cannot be accessed,
                and has standard user permissions in any case. Used so when a user is removed,
                the commands they created can remain.
                */
                $db->exec('INSERT INTO users (userID, userName, role) VALUES ("default", "default", 0');

                // Create the slurmCommands table
                $db->exec('CREATE TABLE IF NOT EXISTS jobTypes(
                jobTypeID INTEGER PRIMARY KEY NOT NULL,
                jobName TEXT NOT NULL,
                jobDescription TEXT NOT NULL,
                script TEXT NOT NULL,
                userID TEXT,
                fileUploadCount INTEGER DEFAULT 0,
                FOREIGN KEY(userID) REFERENCES users(userID)
            )');

                // Create the slurmCommandParams table
                $db->exec('CREATE TABLE IF NOT EXISTS jobTypeParams(
                paramID INTEGER PRIMARY KEY NOT NULL,
                paramName TEXT NOT NULL,
                paramType INTEGER NOT NULL,
                defaultValue TEXT,
                jobTypeID INTEGER,
                jobCompleteTime INTEGER,
                jobStartTime INTEGER NOT NULL,
                FOREIGN KEY(jobTypeID) REFERENCES jobTypes(jobTypeID)
            )');

                //Create File IDs table
                $db->exec('CREATE TABLE IF NOT EXISTS fileIDs(
                    fileID TEXT PRIMARY KEY NOT NULL,
                    userID TEXT NOT NULL,
                    FOREIGN KEY(userID) REFERENCES users(userID)');

                // Create the Jobs table
                $db->exec('CREATE TABLE IF NOT EXISTS Jobs (
                jobID INTEGER PRIMARY KEY NOT NULL,
                jobComplete INTEGER NOT NULL,
                slurmID INTEGER NOT NULL,
                commandID INTEGER,
                userID TEXT NOT NULL,
                jobName TEXT NOT NULL,
                fileID TEXT,
                FOREIGN KEY(jobTypeID) REFERENCES jobTypes(jobTypeID),
                FOREIGN KEY(userID) REFERENCES users(userID),
                FOREIGN KEY(fileID) REFERENCES fileIDs(fileID)
            )');
                

                //Create the all tokens table. This is so we can disable tokens if needed.
                $db->exec('CREATE TABLE IF NOT EXISTS userTokens(
                        tokenID TEXT PRIMARY KEY NOT NULL,
                        userID TEXT,
                        FOREIGN KEY(userID) REFERENCES users(userID)
             )');

                $db->close();
            }

            $response->getBody()->write("Database Created");
            return $response->withStatus(201);
        } catch (Exception $e) {
            error_log($e);
            $response->getBody()->write("Internal Server Error");
            return $response->withStatus(500);
        }

    }

}
