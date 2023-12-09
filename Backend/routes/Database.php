<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Database
{
    public function __construct()
    {
    }


    public function setup(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $dbFile = __DIR__ . "/../data/db.db";
            if (!file_exists($dbFile)) {
                if(!file_exists(__DIR__ . "/../data")){
                    mkdir(__DIR__ . "/../data", 0775, true);
                }
                $db = new SQLite3($dbFile);

                // Create the users table
                $db->exec('CREATE TABLE IF NOT EXISTS users (
                userID TEXT PRIMARY KEY NOT NULL,
                userName TEXT,
                userEmail TEXT,
                userPWHash TEXT,
                privLevel INTEGER NOT NULL,
                requiresPasswordReset BOOLEAN NOT NULL
            )');

                // Create the slurmCommands table
                $db->exec('CREATE TABLE IF NOT EXISTS slurmCommands (
                commandID INTEGER PRIMARY KEY NOT NULL,
                commandName TEXT NOT NULL,
                userID UUID,
                FOREIGN KEY(userID) REFERENCES users(userID)
            )');

                // Create the slurmCommandParams table
                $db->exec('CREATE TABLE IF NOT EXISTS slurmCommandParams (
                paramID INTEGER PRIMARY KEY NOT NULL,
                paramName TEXT NOT NULL,
                paramType INTEGER NOT NULL,
                commandID INTEGER,
                FOREIGN KEY(commandID) REFERENCES slurmCommands(commandID)
            )');

                // Create the Jobs table
                $db->exec('CREATE TABLE IF NOT EXISTS Jobs (
                jobID TEXT PRIMARY KEY NOT NULL,
                jobComplete BOOLEAN NOT NULL,
                commandID INTEGER,
                FOREIGN KEY(commandID) REFERENCES slurmCommands(commandID)
            )');
                // Create cancelled tokens table.
                $db->exec('CREATE TABLE IF NOT EXISTS userCancelledTokens (
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
