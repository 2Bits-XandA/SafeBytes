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
    return openssl_encrypt(
        $data,
        "AES-256-CBC",
        $key,
        0,
        substr($key, 0, 16)
    );
}
