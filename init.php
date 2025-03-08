<?php
if (!defined('ADR_APP_START') || ADR_APP_START !== 'XandA') {
    error_log("Access config from outside app!");
    header("Content-Type: application/json");
    die("{}");
}

require_once 'database_session_manager.php';
require_once 'crypt.php';

/** @var array{db_host: string, db_name: string, db_user: string, db_password: string} $config */
$config = require 'config.php';

$db = new DatabaseHandler(
    'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8',
    $config['db_user'], // Benutzername
    $config['db_password'] // Passwort
);

$db->startSession();

return $db;
