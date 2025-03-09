<?php
define("ADR_APP_START", "XandA");
/** @var DatabaseHandler $db */
$db = require "../init.php";

if (isset($_GET['key'])) {
    $db->setSessionValue("schluessel", isPossibleKey($_GET['key']) ? $_GET["key"]: "");
}


$schluessel = $db->getSessionValue("schluessel", "");

$encodedData = "";
$partialKey = "";

if ($schluessel !== '') {
    $key = splitKey($schluessel, KEY_INDICES);
    $daten = $db->select("SELECT daten FROM formular_eintraege WHERE schluessel = ?", [$key['schluessel']]);
    if (count($daten) > 0 && $daten[0]['daten'] !== "") {
        $encodedData = $daten[0]['daten'];
        $partialKey = $key["masterKey"];
    }
} // TODO should we use gibberish data if nothing is found?
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

<script src="crypt.js" type="text/javascript"></script>
<script type="text/javascript">
    const encrpytedData = '<?php echo preg_replace('/[^A-Za-z0-9+\/=]/', '', $encodedData);; ?>';
    const partialKey = '<?php echo $partialKey; ?>';

    function showError(message, type) {
        console.log("Show Error", message, type);
        const formElement = document.querySelector("#error-message");
        formElement.innerText = message;
        formElement.className = "alert " + (type ?? "alert-danger");
        formElement.style.display = "block";
    }

    async function processDecryption(encrpytedData, partialKey, userKey) {
        if (encrpytedData === '') {
            const formElement = document.querySelector("form");
            formElement.style.display = "none"; // Versteckt das Formular

            // Eine Fehlermeldung hinzufügen
            showError("Die verschlüsselten Daten fehlen oder sind ungültig. Bitte wenden Sie sich an den Administrator.");
        } else {

            const decodedData = await decryptData(encrpytedData, partialKey, userKey);
            if (decodedData !== "") {
                showError("Daten erfolgreich entschlüsselt", "alert-success");

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

    let userKey = undefined;

    document.addEventListener("DOMContentLoaded", function () {
        // TODO for debugging this is useful - should it be removed?
        if (encrpytedData === '') {
            showError("Keine Daten vorhanden. Bitte wenden Sie sich an den Administrator.");
        }

        const codeInput = document.getElementById("verschluesselungscode");
        const submitButton = document.getElementById("key-submit");

        submitButton.addEventListener("click", function (e) {
            e.preventDefault();
            userKey = codeInput.value;
            processDecryption(encrpytedData, partialKey, userKey).catch((e) => console.log("Error in OnClick", e));
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

            showError("Daten erfolgreich verschlüsselt für den Versand", "alert-success");
            encryptData(jsonData, partialKey, userKey)
                .then((encrypted) => {
                    return fetch("./update.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "text/plain",
                        },
                        body: encrypted,
                    })
                })
                .then(response => console.log(response))
                .catch(error => console.error(error));
        });
    });

</script>
<!-- Bootstrap Bundle with Popper -->
<script src="./js/bootstrap.bundle.js"></script>
</body>
</html>