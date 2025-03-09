<?php
if (!defined('ADR_APP_START') || ADR_APP_START !== 'XandA') {
    error_log("Access config from outside app!");
    header("Content-Type: application/json");
    die("{}");
}
ini_set('display_errors', '0');
error_reporting(0);
error_log("App started");

require_once 'lib/database_session_manager.php';
require_once 'lib/split-key.php';

/** @var array{db_host: string, db_name: string, db_user: string, db_password: string} $config */
$config = require 'config.php';
define("KEY_INDICES", $config['key_indices']);

$db = new DatabaseHandler(
    'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8',
    $config['db_user'], // Benutzername
    $config['db_password'] // Passwort
);

$db->startSession();

return $db;
