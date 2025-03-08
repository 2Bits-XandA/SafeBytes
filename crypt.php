<?php

// Funktionen zur sicheren Schlüsselgenerierung
function generate_key() {
    return bin2hex(random_bytes(32)); // 64-stelliger hexadezimaler String
}

/**
 * @param $key
 * @return array{
 *      schluessel: string,
 *      masterKey: string,
 *  }
 */
    function splitKey($key)
    {
        if (strlen($key) < 56) {
            error_log("Key is not 56 - Key is " . strlen($key));
            return ["schluessel" => "", "masterKey" => ""];
        }

        $indices = [3, 14, 25, 36, 47, 0, 11, 22, 33, 44, 7, 18, 29, 40, 51, 2, 13, 24, 35, 46, 5, 16, 27, 38, 49, 9, 20, 31, 42, 53, 4, 15];
        $schluessel = '';
        $masterKey = '';

        for ($i = 0; $i < 56; $i++) {
            if (in_array($i, $indices)) {
                $schluessel .= $key[$i];
            } else {
                $masterKey .= $key[$i];
            }
        }

        return ["schluessel" => $schluessel, "masterKey" => $masterKey];
    }


// Funktion zum Verschlüsseln der Daten
function encrypt_data($data, $key) {
        if (strlen($key)<32) {
            throw new Error("Key is to short, must be >=32 but is " . strlen($key));
        }
    return openssl_encrypt(
        $data,
        "AES-256-CBC",
        $key,
        0,
        substr($key, 0, 16)
    );
}

/**
 * Entschlüsselt verschlüsselte Daten mit dem MasterKey.
 *
 * @param string $encryptedData Die verschlüsselten Daten aus dem POST-Request.
 * @param string $masterKey Ein 64-stelliger hexadezimaler Schlüssel.
 * @return string|null Gibt die entschlüsselten Daten zurück oder null bei Fehler.
 */
function decrypt_data(string $encryptedData, string $masterKey) {
    if (strlen($masterKey)<32) {
        throw new Error("Key is to short, must be >=32 but is " . strlen($masterKey));
    }

    // Entschlüsselung
    $decodedData = openssl_decrypt(
        $encryptedData,
        "AES-256-CBC",
        $masterKey, // Dieser Teil des Schlüssels wird für die Entschlüsselung verwendet
        0,
        substr($masterKey, 0, 16) // Die ersten 16 Bytes als IV
    );

    return $decodedData ?: null; // Rückgabe der entschlüsselten Daten oder null bei Fehler
}
