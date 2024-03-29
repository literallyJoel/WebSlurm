<?php
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    include_once __DIR__ . "/../config/Config.php";
    require_once __DIR__ . "/../helpers/Logger.php";
    
    class Database
{
    public function __construct()
    {
    }


    public function setup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {

            if (!file_exists(DB_PATH)) {
                if (!file_exists(__DIR__ . "/../data")) {
                    mkdir(__DIR__ . "/../data", 0775, true);
                }
                $db = new SQLite3(DB_PATH);

                $query = 'create table organisations
    (
        organisationID integer not null
            constraint organisations_pk
                primary key autoincrement,
        name           text    not null,
        description    text
    );

    create table users
    (
        userID                TEXT    not null
            primary key,
        userName              TEXT,
        userEmail             TEXT,
        userPWHash            TEXT,
        role                  INTEGER not null,
        requiresPasswordReset BOOLEAN not null
    );

    create table fileIDs
    (
        fileID TEXT not null
            constraint fileIDs_pk
                primary key,
        userID TEXT not null
            constraint fileIDs_users_userID_fk
                references users
    );

    create table jobTypes
    (
        jobTypeID       INTEGER            not null
            primary key,
        jobName         TEXT               not null,
        script          TEXT               not null,
        userID          TEXT
            references users,
        jobDescription  text    default "" not null,
        hasOutputFile   boolean default false,
        outputCount     integer default 0,
        hasFileUpload   boolean,
        arrayJobSupport boolean,
        arrayJobCount   number
    );

    create table Jobs
    (
        jobID           INTEGER            not null
            primary key autoincrement,
        jobComplete     INTEGER            not null,
        slurmID         INTEGER            not null,
        jobTypeID       INTEGER            not null
            references jobTypes,
        jobCompleteTime integer,
        jobStartTime    integer            not null,
        userID          TEXT               not null
            constraint Jobs_users_userID_fk
                references users,
        jobName         TEXT default "old" not null,
        fileID          TEXT
            constraint Jobs_fileIDs_fileID_fk
                references fileIDs
    );

    create table JobParameters
    (
        jobID integer not null
            constraint JobParameters_Jobs_jobID_fk
                references Jobs,
        key   TEXT    not null,
        value TEXT    not null,
        constraint JobParameters_pk
            primary key (jobID, key)
    );

    create table jobTypeOrganisations
    (
        organisationID integer not null
            constraint jobTypeOrganisations_organisations_organisationID_fk
                references organisations,
        jobTypeID      integer not null
            constraint jobTypeOrganisations_jobTypes_jobTypeID_fk
                references jobTypes,
        constraint jobTypeOrganisations_pk
            primary key (jobTypeID, organisationID)
    );

    create table jobTypeParams
    (
        paramID      INTEGER not null
            primary key,
        paramName    TEXT    not null,
        paramType    INTEGER not null,
        defaultValue TEXT,
        jobTypeID    INTEGER
            references jobTypes
    );

    create table userOrganisations
    (
        userID         integer           not null,
        organisationID integer           not null,
        role           integer default 0 not null,
        constraint userOrganisations_pk
            primary key (userID, organisationID),
        constraint userOrganisations_users_organisationID_userID_fk
            foreign key (organisationID, userID) references users (organisationID, userID)
    );

    create table userTokens
    (
        tokenID TEXT not null
            primary key,
        userID  TEXT
            references users
    );

';
                // Create the database
                $db->exec($query);

                /*
                This exists as a default user. It has no email or password so cannot be accessed,
                and has standard user permissions in any case. Used so when a user is removed,
                the commands they created can remain.
                */
                $db->exec("INSERT INTO users (userID, userName, role, requiresPasswordReset) VALUES ('default', 'default', 0, false);");

                $db->close();
            }

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
