<?php
define("ADR_APP_START", "XandA");
/** @var DatabaseHandler $db */
$db = require "../init.php";

/* SQL: CREATE TABLE updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schluessel VARCHAR(255) NOT NULL,
    encryptedData TEXT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at DATETIME NOT NULL
);
*/

// JSON-Body auslesen
$input = file_get_contents('php://input');
$schluessel = $db->getSessionValue("schluessel", "");
// Überprüfen, ob die benötigten Felder vorhanden sind
if (!isset($input) || $input === false || $schluessel === "") {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage: Parameter fehlen']);
    exit();
}

$keys = splitKey($schluessel, KEY_INDICES);
// IP-Adresse des Absenders abrufen
$ipAddress = $_SERVER['REMOTE_ADDR'];

// Aktuelle Uhrzeit im Format 'YYYY-MM-DD HH:MM:SS'
$createdAt = date('Y-m-d H:i:s');
try {
    // SQL-Statement vorbereiten
    $count = $db->execute(
        'INSERT INTO updates (schluessel, encryptedData, ip_address, created_at) VALUES (?, ?, ?, ?)',
        [$keys['schluessel'], $input, $ipAddress, $createdAt]
    );

    if ($count > 0) {
        // Erfolgsmeldung senden
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Daten erfolgreich gespeichert']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Daten konnten nicht gespeichert werden.']);
    }
} catch (\PDOException $e) {
    // Fehler ausgeben, falls das INSERT fehlschlägt
    http_response_code(505);
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler']);
}
