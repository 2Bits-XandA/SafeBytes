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
    <title>Admin-Bereich</title>
    <!-- Bootstrap CSS -->
    <link href="../css/bootstrap.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1 class="text-center">Admin-Bereich</h1>

        <!-- Bereich zum Hochladen einer Datei -->
        <div class="mt-5">
            <h2>CSV-Upload</h2>
            <form id="upload-form">
                <div class="mb-3">
                    <label for="file-input" class="form-label">CSV-Datei hochladen</label>
                    <input type="file" class="form-control" id="file-input" accept=".csv">
                </div>
                <button type="submit" class="btn btn-primary">Hochladen</button>
            </form>
        </div>

        <!-- Input field to select the key for the column holding the Mail address -->
        <div class="mt-4">
            <h3>E-Mail Schlüssel auswählen</h3>
            <div>
                <label for="email-key-select" class="form-label">Spalte mit E-Mail-Adresse:</label>
                <select class="form-select" id="email-key-select">
                    <!-- Options will be generated dynamically using JavaScript -->
                </select>
            </div>
        </div>


        <!-- Input field to select columns that should be read-only -->
        <div class="mt-4">
            <h3>Schreibgeschützte Felder auswählen</h3>
            <div id="readonly-fields-container">
                <!-- Dynamische Checkboxes werden hier mit JavaScript eingefügt -->
            </div>
        </div>
        
        <!-- Tabelle für die Einträge -->
        <div class="mt-4" style="max-width: 100%; overflow-x: auto;">
            <h2>Einträge</h2>
            <table class="table table-striped  table-responsive" style="max-width: 100%;">
                <thead id="data-table-header">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Datum</th>
                    </tr>
                </thead>
                <tbody id="data-table" style="max-width: 100%;">
                    <!-- Daten werden hier mit JavaScript eingefügt -->
                </tbody>
            </table>
        </div>

        
        <!-- Speichern Button -->
        <div class="mt-4 text-center">
            <button id="save-button" class="btn btn-success">Speichern</button>
        </div>

    </div>

    <!-- Bootstrap & JavaScript -->
    <script src="../js/bootstrap.bundle.js"></script>
    <script src="../js/papaparse.min.js"></script>
    <script src="../crypt.js"></script>
    <script>
        const indices = <?php echo json_encode(KEY_INDICES); ?>;
        const currentRows = [];

        const csvOptions = {
            delimiter: ",",
            linebreak: "\n"
        };

        function rebuildMailSelect() {
            if (currentRows.length > 0) {
                // Optionen für das <select> Element einfügen
                const emailKeySelect = document.getElementById('email-key-select');
                emailKeySelect.innerHTML = ""; // Vorherige Optionen entfernen

                const sampleRow = currentRows[0];

                // Optionen basierend auf den Schlüsseln der Datensätze erstellen
                Object.keys(sampleRow).forEach(key => {
                    if (!key.startsWith("_") && !key.startsWith("sb_")) { // Interne Schlüssel überspringen
                        const option = document.createElement("option");
                        option.value = key; // Wert der Option
                        option.textContent = key.replace(/_/g, " ").replace(/\b\w/g, char => char.toUpperCase()); // Label der Option
                        emailKeySelect.appendChild(option);
                    }
                });

                // Eine Option als Vorauswahl festlegen, die möglicherweise mit "email" übereinstimmt
                const possibleEmailColumn = Object.keys(sampleRow).find(key => key.toLowerCase().includes("mail"));
                if (possibleEmailColumn) {
                    emailKeySelect.value = possibleEmailColumn;
                }
            } else {
                console.log("No data to rebuild mail select from.");
            }
        }

        function rebuildReadOnlyArea() {
            if (currentRows.length === 0) {
                    console.log("No data to rebuild from.");
                return;
            }

            const sampleRow = currentRows[0];
            // Checkboxes für schreibgeschützte Felder einfügen
            const readonlyFieldsContainer = document.getElementById('readonly-fields-container');
            readonlyFieldsContainer.innerHTML = ""; // Vorherige Checkboxes entfernen

            // Optionen basierend auf den Schlüsseln der Datensätze erstellen
            Object.keys(sampleRow).forEach(key => {
                if (!key.startsWith("_") && !key.startsWith("sb_")) { // Interne Schlüssel überspringen
                    const checkboxDiv = document.createElement("div");
                    checkboxDiv.className = "form-check";

                    const checkbox = document.createElement("input");
                    checkbox.type = "checkbox";
                    checkbox.className = "form-check-input";
                    checkbox.id = `readonly-${key}`;
                    checkbox.value = key;

                    const label = document.createElement("label");
                    label.className = "form-check-label";
                    label.htmlFor = `readonly-${key}`;
                    label.textContent = key.replace(/_/g, " ").replace(/\b\w/g, char => char.toUpperCase());

                    checkboxDiv.appendChild(checkbox);
                    checkboxDiv.appendChild(label);

                    readonlyFieldsContainer.appendChild(checkboxDiv);
                }
            });
        }

        function rebuildTable() {
            const tableBody = document.getElementById("data-table");
            const tableHeader = document.getElementById("data-table-header");

            // Tabelle zurücksetzen
            tableBody.innerHTML = "";

            // Tabellen-Header hinzufügen


            if (currentRows.length > 0 && tableHeader) {
                tableHeader.innerHTML = "";
                const headerRow = document.createElement("tr");
                const headerLeading = document.createElement("th");
                headerLeading.textContent = "#";
                headerRow.appendChild(headerLeading);
                Object.keys(currentRows[0]).forEach(key => {
                    if (!key.startsWith("_")) {
                        const th = document.createElement("th");
                        th.textContent = key.replace(/_/g, " ").replace(/\b\w/g, char => char.toUpperCase());
                        headerRow.appendChild(th);
                    }
                });
                tableHeader.appendChild(headerRow);
            } else {
                console.log("No data to rebuild table select from.");
            }

            // Jede Zeile in die Tabelle einfügen
            currentRows.forEach((row, index) => {
                const tr = document.createElement("tr");
                const th = document.createElement("th");
                th.scope = "row";
                th.textContent = index + 1;
                tr.appendChild(th);

                Object.entries(row).forEach(([key, value]) => {
                    if (!key.startsWith("_")) {
                        const td = document.createElement("td");
                        td.className = "cell-" + key;
                        try {
                            td.textContent = String(value).trim();
                        } catch (e) {
                            console.error("Error Setting Value", e, value, key, row.index);
                            td.textContent = "Errored Value";
                        }
                        tr.appendChild(td);
                    }
                });

                tableBody.appendChild(tr);
            });
        }

        function rebuildPage() {
            console.log("Rebuilding page with " + currentRows.length + " rows.")
            rebuildMailSelect();
            rebuildReadOnlyArea();
            rebuildTable();
        }

        function randomKey() {
            const allowedChars = "abcdefghklmnpqrstuvwxyz23456789ABCDEFGHKLMNPRSTUVWXYZoOQ01ijIJ~.-_";
            return Array.from({length: 56}, () => allowedChars.charAt(Math.floor(Math.random() * allowedChars.length))).join('') +
                Array.from({length: 8}, () => allowedChars.charAt(Math.floor(Math.random() * 31))).join('');
        }
        
        async function generateEncryptedData(jsonString) {
            const myRandomKey = randomKey();
            const urlPart = myRandomKey.substring(0, 56);
            const extra = myRandomKey.substring(56);
            const key = splitKey(urlPart, indices);
            const myEncryptedData = await encryptData(jsonString, key.masterKey, extra);

            return {
                sb_urlPart: urlPart,
                sb_extra: extra,
                _encryptedData: myEncryptedData,
            }
        }

        function buildStoreRequest() {
            if (currentRows.length === 0) {
                console.log("No data to rebuild from.");
                return;
            }
            const storeRequest = {
                readOnlyFields: Array.from(document.querySelectorAll('input[type="checkbox"]:checked')).map(checkbox => checkbox.value).join(","),
                data: currentRows.map(row => {
                    return {
                        urlPart: row.sb_urlPart,
                        encryptData: row._encryptedData,
                    }
                })
            };
            console.log("Store Request", storeRequest);
            return storeRequest;
        }

        function exportCsv() {
            const dataToExport = currentRows.map((row, index) => {
                return {
                    index,
                    urlPart: row.sb_urlPart,
                    userKey: row.sb_extra,
                }
            });

            const csv = Papa.unparse(dataToExport, csvOptions);
            console.log("CSV", csv);
            const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = "export_crypt_keys.csv";
            a.click();
            URL.revokeObjectURL(url);
        }

        // JavaScript: Datei einlesen und Tabelle befüllen
        document.getElementById('upload-form').addEventListener('submit', function(event) {
            event.preventDefault();

            const fileInput = document.getElementById('file-input');
            const file = fileInput.files[0];

            if (!file) {
                alert('Bitte wählen Sie eine Datei aus.');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                currentRows.length = 0;
                const data = e.target.result;
                const papaData = Papa.parse(data);
                if (papaData.errors.length > 0) {
                    console.error("Errors while parsing CSV:", papaData.errors);
                    alert("Fehler beim Parsen der CSV-Datei!");
                    return;
                }
                const rows = papaData.data;
                if (papaData.meta) {
                    csvOptions.delimiter = papaData.meta.delimiter;
                    csvOptions.linebreak = papaData.meta.linebreak;
                }
                // Erste Zeile als Header (Schlüssel)
                const headers = rows.shift().map(header => {
                    return header.trim().toLowerCase().replace(/\s+/g, "_").replace(/^[^a-z]+/, "");
                });

                // Jede Zeile in die Tabelle einfügen
                const promises = rows.filter((row) => row.length >= 3).map(async (row, index) => {
                    const record = {};
                    headers.forEach((key, i) => {
                        if (key && key !== "") {
                            record[key] = (row[i] || "").trim();
                        }
                    });
                    const encryptedData = await generateEncryptedData(JSON.stringify(record));
                    currentRows.push({ ...record, ...encryptedData});
                });
                Promise.all(promises).then(() => {
                    rebuildPage();
                }).catch(error => {
                    console.error(error);
                })
            };
            reader.readAsText(file);
        });


        document.getElementById('save-button').addEventListener('click', function () {
            const storeRequest = buildStoreRequest();

            if (!storeRequest) {
                console.log("Store request could not be built. No data available.");
                return;
            }

            const isAdminPath = window.location.pathname.endsWith('admin.php') || window.location.pathname.endsWith('/');
            const storeUrl = isAdminPath ? './store.php' : './admin/store.php';

            fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(storeRequest),
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    exportCsv();
                    currentRows.length = 0;
                    rebuildPage();
                    alert('Daten erfolgreich gespeichert!');
                })
                .catch(error => {
                    console.error('Error while sending data:', error);
                    alert('Fehler beim Speichern der Daten!');
                });
        });
    </script>
</body>
</html>