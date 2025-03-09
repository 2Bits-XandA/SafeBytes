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
    if (is_array($requestData)) {
        try {
            $schluesselArray = [];
            foreach ($requestData as $row) {
                // Feldvalidierung: Erwartet `urlPart` und `encryptData` in der Zeile
                if (isset($row['urlPart']) && isPossibleKey($row['urlPart'])) {
                    $key = splitKey($row['urlPart'], KEY_INDICES);
                    array_push($schluesselArray, $key["schluessel"]);
                } else {
                    error_log("Not a valid key: " . var_export($row) . "");
                }
            }
            if (count($schluesselArray) > 0) {
                // Platzhalter für die Anzahl der Schlüssel erstellen
                $platzhalter = implode(',', array_fill(0, count($schluesselArray), '?'));

                // SQL-Query vorbereiten
                $sql = "
                    SELECT t1.schluessel ,t1.encryptedData ,t1.ip_address ,t1.created_at
                    FROM updates AS t1
                    INNER JOIN (
                        SELECT schluessel, MAX(created_at) AS max_timestamp
                        FROM updates
                        WHERE schluessel IN ($platzhalter)
                        GROUP BY schluessel
                    ) AS t2
                    ON t1.schluessel = t2.schluessel AND t1.created_at = t2.max_timestamp;
                ";

                $result = $db->select($sql, $schluesselArray);
                // Erfolgreiche Antwort
                http_response_code(200);
                header('Content-Type: application/json');
                echo json_encode( $result);
                exit();
            } else {
                // Ungültige Anfragemethode
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Keine Keys gefunden"]);
                exit();
            }

        } catch (Exception $e) {
            // Fehler während des Prozesses behandeln
            http_response_code(500);
            error_log($e->getMessage());
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            exit();
        }
    } else {
        // Fehlende Datenstrukturen in der Anfrage
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Ungültige Anfrage: Daten fehlen."]);
        exit();
    }
}
// Ungültige Anfragemethode
http_response_code(405);
echo json_encode(["success" => false, "message" => "Nur POST-Anfragen sind erlaubt."]);
