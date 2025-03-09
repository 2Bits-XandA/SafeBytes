<?php
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Bereich"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Zugriff verweigert';
    exit;
}
define("ADR_APP_START", "XandA");
/** @var DatabaseHandler $db */
$db = require "../../init.php";

// Überprüfen, ob die Daten im POST gesendet werden
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Erwartet JSON-Daten
    $input = file_get_contents("php://input");
    $requestData = json_decode($input, true);

    // Sicherstellen, dass sowohl `data` als auch `readOnlyFields` vorhanden sind
    if (isset($requestData['data']) && is_array($requestData['data'])) {
        try {
            foreach ($requestData['data'] as $row) {
                // Feldvalidierung: Erwartet `urlPart` und `encryptData` in der Zeile
                if (isset($row['urlPart'], $row['encryptData'])) {
                    $urlPart = $row['urlPart'];
                    $key = splitKey($urlPart, KEY_INDICES);
                    $encryptData = $row['encryptData'];

                    // Datenbankeintrag vorbereiten
                    $query = "INSERT INTO formular_eintraege (schluessel, daten, read_only, created_at) VALUES (?, ?, ?, NOW())";
                    $db->execute($query, [$key["schluessel"], $encryptData, $requestData['readOnlyFields']]);
                } else {
                    // Falsche Struktur einer Datenzeile
                    throw new Exception("Ungültige Datenstrukturen erkannt.");
                }
            }

            // Erfolgreiche Antwort
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Daten erfolgreich gespeichert."]);

        } catch (Exception $e) {
            // Fehler während des Prozesses behandeln
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
    } else {
        // Fehlende Datenstrukturen in der Anfrage
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Ungültige Anfrage: Daten fehlen."]);
    }
} else {
    // Ungültige Anfragemethode
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Nur POST-Anfragen sind erlaubt."]);
}