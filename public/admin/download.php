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
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV-Analyse und Fetch</title>
    <link href="../css/bootstrap.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1 class="text-center">Download & Fetch</h1>

        <!-- Bereich zum Hochladen einer Datei -->
        <div class="mt-5">
            <h2>CSV-Datei hochladen</h2>
            <form id="upload-form">
                <div class="mb-3">
                    <label for="file-input" class="form-label">CSV-Datei auswählen</label>
                    <input type="file" class="form-control" id="file-input" accept=".csv">
                </div>
                <button type="submit" class="btn btn-primary">Analysieren & Anfrage senden</button>
            </form>
        </div>

        <!-- Fortschritt oder Ergebnis -->
        <div class="mt-3" id="result-output">
            <h3>Ergebnis:</h3>
            <pre id="console-log" style="background: #f8f9fa; padding: 10px; border: 1px solid #ccc;"></pre>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../js/papaparse.min.js"></script>
    <script src="../crypt.js"></script>
    <script>
        const indices = <?php echo json_encode(KEY_INDICES); ?>;
        const currentRows = [];

        const form = document.getElementById('upload-form');
        const fileInput = document.getElementById('file-input');
        const consoleLog = document.getElementById('console-log');

        const csvOptions = {
            delimiter: ",",
            linebreak: "\n"
        };

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const file = fileInput.files[0];
            
            if (!file) {
                alert('Bitte wählen Sie eine CSV-Datei aus.');
                return;
            }

            // Parse die CSV-Datei
            Papa.parse(file, {
                header: true,
                skipEmptyLines: true,
                complete: function (results) {
                    consoleLog.innerText = 'CSV-Datei erfolgreich gelesen. Sende Anfragen...';
                    if (results.meta) {
                        csvOptions.delimiter = results.meta.delimiter;
                        csvOptions.linebreak = results.meta.linebreak;
                    }
                    const data = results.data.map(row => row.urlPart).filter(urlPart => urlPart);
                    console.log('URL-Teile:', data);
                    if (data.length > 0) {
                        fetchData(data).then((loaded) => {
                            console.log('Loaded:', loaded);
                            const decryptPromises = results.data.map(async (row, index) => {
                                const keys = splitKey(row.urlPart, indices);
                                const updates = loaded.find(item => item.schluessel === keys.schluessel);
                                if (updates) {
                                    currentRows.push({
                                        ...row,
                                        ...keys,
                                        ...updates,
                                        decrypted: JSON.parse(await decryptData(updates.encryptedData, keys.masterKey, row.userKey)),
                                    });
                                } else {
                                    console.log("Nothing Found", keys.schluessel);
                                }
                            });
                            return Promise.all(decryptPromises);
                        }).then(() => {
                            console.log('Decrypted:', currentRows);
                            consoleLog.innerText = 'Anfragen erfolgreich gesendet.';
                            consoleLog.innerText += '\n' + JSON.stringify(currentRows, null, 2);


                            const csv = Papa.unparse(currentRows.map((row) => row.decrypted), csvOptions);
                            console.log("CSV", csv);
                            const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
                            const url = URL.createObjectURL(blob);
                            const a = document.createElement("a");
                            a.href = url;
                            a.download = "decrypted_updates.csv";
                            a.click();
                            URL.revokeObjectURL(url);

                        }).catch(err => {
                            consoleLog.innerText = `Fehler beim Senden der Anfragen: ${err.message}`;
                        });
                    } else {
                        consoleLog.innerText = 'Keine gültigen URL-Teile gefunden.';
                    }
                },
                error: function (err) {
                    consoleLog.innerText = `Fehler beim Lesen der CSV: ${err.message}`;
                }
            });
        });

        async function fetchData(urlParts) {
            try {
                // Sende URL-Teile an read.php
                const response = await fetch('read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(urlParts.map(part => ({ urlPart: part }))),
                });

                if (!response.ok) {
                    throw new Error(`Fehler: ${response.status}`);
                }

                const result = await response.json();
                console.log('Fetch-Ergebnis:', result);
                consoleLog.innerText += '\nAnfrage erfolgreich. Resultate in der Konsole prüfen.';
                return result;
            } catch (error) {
                console.error('Fetch-Fehler:', error);
                consoleLog.innerText += `\nFehler beim Senden der Anfragen: ${error.message}`;
            }
            return [];
        }
    </script>
</body>
</html>