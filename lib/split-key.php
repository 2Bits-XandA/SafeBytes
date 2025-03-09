<?php
define("SPLIT_KEY_CHARS", '/^[abcdefghklmnpqrstuvwxyz23456789ABCDEFGHKLMNPRSTUVWXYZoOQ01ijIJ~\.\-_]+$/');

/**
 * @param $key
 * @return false
 */
function isPossibleKey($key): bool
{
    return is_string($key) && strlen($key) >= 56 && preg_match(SPLIT_KEY_CHARS, $key) !== false;
}

/**
 * @param $key
 * @param $indices int[]
 * @return array{
 *      schluessel: string,
 *      masterKey: string,
 *  }
 */
function splitKey(string $key, array $indices)
{
    if (strlen($key) < 56) {
        error_log("Key is not 56 - Key is " . strlen($key));
        return ["schluessel" => "", "masterKey" => ""];
    }
    $indices = array_unique($indices);
    if (count($indices) !== 32) {
        error_log("Indices is not 32 - Indices is " . count($indices));
    }

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