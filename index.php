<?php
define("ADR_APP_START", "XandA");
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
if (isset($_GET['key']) && preg_match('/^[a-fA-F0-9]{56}$/', $_GET['key'])) {
    $db->setSessionValue("schluessel", $_GET["key"]);
}

$schluessel = $db->getSessionValue("schluessel", "");

$encodedData = "";
$partialKey = "";

if ($schluessel !== '') {
    $key = splitKey($schluessel);
    $daten = $db->select("SELECT daten FROM formular_eintraege WHERE schluessel = ?", [$key['schluessel']]);
    if (count($daten) > 0 && $daten[0]['daten'] !== "") {
        $encodedData = $daten[0]['daten'];
        $partialKey = $key["masterKey"];
    }
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dateneingabe Formular</title>
    <!-- Bootstrap CSS -->
    <link href="./css/bootstrap.css" rel="stylesheet">
</head>

<body>
<div id="error-message" class="alert alert-danger" style="display: none">

</div>
<div class="container mt-5" id="key-input">
    <h1 class="text-center mb-4">Eingabe des Verschlüsselungscodes</h1>
    <form>
        <!-- Verschlüsselungscode -->
        <div class="mb-3">
            <label for="verschluesselungscode" class="form-label">8-stelliger Verschlüsselungscode</label>
            <input type="text" class="form-control" id="verschluesselungscode" placeholder="Geben Sie Ihren Code ein"
                   maxlength="8" pattern="[a-zA-Z0-9]{8}" required>
            <div class="form-text">Bitte geben Sie einen 8-stelligen alphanumerischen Code ein.</div>
        </div>

        <!-- Submit-Button -->
        <button type="submit" id="key-submit" class="btn btn-primary">Code Übermitteln</button>
    </form>
</div>
<div class="container mt-5" style="display: none"  id="main-form">
    <h1 class="text-center mb-4">Dateneingabe Formular</h1>
    <form>
        <h2 class="mb-4">Stammdaten</h2>
        <!-- Vorname -->
        <div class="mb-3">
            <label for="vorname" class="form-label">Vorname</label>
            <input type="text" class="form-control" id="vorname" placeholder="Max" required>
        </div>

        <!-- Nachname -->
        <div class="mb-3">
            <label for="nachname" class="form-label">Nachname</label>
            <input type="text" class="form-control" id="nachname" placeholder="Muster" required>
        </div>

        <!-- Strasse -->
        <div class="mb-3">
            <label for="strasse" class="form-label">Straße</label>
            <input type="text" class="form-control" id="strasse" placeholder="Mustergasse 1" required>
        </div>

        <!-- PLZ -->
        <div class="mb-3">
            <label for="plz" class="form-label">PLZ</label>
            <input type="text" class="form-control" id="plz" placeholder="12345" required>
        </div>

        <!-- Ort -->
        <div class="mb-3">
            <label for="ort" class="form-label">Ort</label>
            <input type="text" class="form-control" id="ort" placeholder="Musterstadt" required>
        </div>

        <!-- Email -->
        <div class="mb-3">
            <label for="email" class="form-label">E-Mail</label>
            <input type="email" class="form-control" id="email" placeholder="max.muster@example.com" required>
        </div>

        <!-- Handynummer -->
        <div class="mb-3">
            <label for="handynummer" class="form-label">Handynummer</label>
            <input type="tel" class="form-control" id="handynummer" placeholder="+49 123 4567890" required>
        </div>

        <h2 class="my-4">Lastschrift</h2>
        <!-- SEPA Bankverbindung -->
        <div class="mb-3">
            <label for="iban" class="form-label">SEPA IBAN</label>
            <input type="text" class="form-control" id="iban" placeholder="DE89 3704 0044 0532 0130 00" required>
        </div>

        <!-- Teilnahme am Training -->
        <div class="mb-3">
            <label class="form-label">Möchten Sie am Training teilnehmen und stimmen Sie zu, dass die Gebühr von 60 Euro
                für das Jahr 2025 mit dem Mitgliedsbeitrag eingezogen wird?</label>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="trainingTeilnahme" id="trainingJa" value="ja"
                       required>
                <label class="form-check-label" for="trainingJa">
                    Ja, ich nehme am Training teil.
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="trainingTeilnahme" id="trainingNein" value="nein"
                       required>
                <label class="form-check-label" for="trainingNein">
                    Nein, ich nehme nicht am Training teil und nutze den Platz auch nicht privat zum Training.
                </label>
            </div>
        </div>
        <!-- Submit-Button -->
        <button type="submit" class="btn btn-primary">Abschicken</button>
    </form>

    <pre><?php echo $encodedData; ?></pre>
</div>

<script type="text/javascript">
    const encrpytedData = '<?php echo $encodedData = preg_replace('/[^A-Za-z0-9+\/=]/', '', $daten[0]['daten']);; ?>';
    const partialKey = '<?php echo $partialKey; ?>';

    function showError(message, type) {
        console.log("Show Error", message, type);
        const formElement = document.querySelector("#error-message");
        formElement.innerText = message;
        formElement.className = "alert " + (type ?? "alert-danger");
        formElement.style.display = "block";
    }
    
    async function decodeData(encrpytedData, partialKey, userKey) {
        try {
            // Base64 decoding function
            const base64ToArrayBuffer = (base64) => {
                const binaryString = atob(base64);
                const bytes = new Uint8Array(binaryString.length);
                for (let i = 0; i < binaryString.length; i++) {
                    bytes[i] = binaryString.charCodeAt(i);
                }
                return bytes.buffer;
            };

            // Convert string key and IV to a Uint8Array
            const rawKey = new TextEncoder().encode(partialKey + userKey); // Key (original from PHP) with missing 8 digits filled
            const rawIV = rawKey.slice(0, 16); // First 16 bytes of key as IV
            const encryptedBytes = base64ToArrayBuffer(encrpytedData);

            // Import the key into the Crypto API
            try {
                const cryptoKey = await window.crypto.subtle.importKey(
                    "raw",
                    rawKey,
                    {name: "AES-CBC"},
                    false,
                    ["decrypt"]
                );

                const decryptedBuffer = await window.crypto.subtle.decrypt(
                    {
                        name: "AES-CBC",
                        iv: rawIV,
                    },
                    cryptoKey,
                    encryptedBytes
                );

                return new TextDecoder().decode(decryptedBuffer);
            } catch (error) {
                console.error("Decryption failed:", error);
            }

        } catch (error) {
            console.error("Error during decryption:", error);
        }
        return "";
    }
    async function handleEncrypedData(encrpytedData, partialKey, userKey) {
        if (encrpytedData === '') {
            const formElement = document.querySelector("form");
            formElement.style.display = "none"; // Versteckt das Formular

            // Eine Fehlermeldung hinzufügen
            showError("Die verschlüsselten Daten fehlen oder sind ungültig. Bitte wenden Sie sich an den Administrator.");
        } else {

            const decodedData = await decodeData(encrpytedData, partialKey, userKey);
            if (decodedData !== "") {
                showError("Die entschlüsselten Daten: " + decodedData, "alert-success");

                try {
                    const data = JSON.parse(decodedData);

                    // Felder einzeln ausfüllen
                    document.getElementById("vorname").value = data.vorname || "";
                    document.getElementById("nachname").value = data.nachname || "";
                    document.getElementById("strasse").value = data.strasse || "";
                    document.getElementById("plz").value = data.plz || "";
                    document.getElementById("ort").value = data.ort || "";
                    document.getElementById("email").value = data.email || "";
                    document.getElementById("handynummer").value = data.handynummer || "";
                    document.getElementById("iban").value = data.iban || "";

                    // Blendet das Hauptformular ein
                    const mainForm = document.getElementById("main-form");
                    const keyInput = document.getElementById("key-input");

                    if (mainForm && keyInput) {
                        mainForm.style.display = "block";
                        keyInput.style.display = "none";
                    }
                } catch (error) {
                    console.error("Fehler beim Füllen des Formulars: ", error);
                    showError("Die entschlüsselten Daten enthalten ein ungültiges Format. Bitte wenden Sie sich an den Administrator.");
                }

            } else {
               showError("Die verschlüsselten Daten konnten nicht dekodiert werden.");
            }
        }
    }


    document.addEventListener("DOMContentLoaded", function () {
        const codeInput = document.getElementById("verschluesselungscode");
        const submitButton = document.getElementById("key-submit");

        submitButton.addEventListener("click", function (e) {
            e.preventDefault();
            const userKey = codeInput.value;
            handleEncrypedData(encrpytedData, partialKey, userKey).catch((e) => console.log("Error in OnClick", e));
            return false;
        });


        document.getElementById("main-form").addEventListener("submit", function (e) {
            e.preventDefault(); // Verhindert das normale Absenden des Formulars

            const jsonData = JSON.stringify({
                vorname: document.getElementById("vorname").value || "",
                nachname: document.getElementById("nachname").value || "",
                strasse: document.getElementById("strasse").value || "",
                plz: document.getElementById("plz").value || "",
                ort: document.getElementById("ort").value || "",
                email: document.getElementById("email").value || "",
                handynummer: document.getElementById("handynummer").value || "",
                iban: document.getElementById("iban").value || "",
                trainingTeilnahme: document.querySelector('input[name="trainingTeilnahme"]:checked')?.value || ""
            });

            console.log("Erstelltes JSON: ", jsonData);
            showError("JSON: " + jsonData, "alert-success");

            // Optional: Hier kannst du das JSON z. B. per AJAX an einen Server senden
            // fetch("/endpoint", {
            //     method: "POST",
            //     headers: {
            //         "Content-Type": "application/json",
            //     },
            //     body: jsonData,
            // }).then(response => console.log(response)).catch(error => console.error(error));
        });
    });

</script>
<!-- Bootstrap Bundle with Popper -->
<script src="./js/bootstrap.bundle.js"></script>
</body>

</html>