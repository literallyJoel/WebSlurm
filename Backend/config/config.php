<?php

$dbFile = __DIR__ . "/../data/db.db";

define('DB_PATH', $dbFile);
define('DB_CONN', "sqlite:" . $dbFile);