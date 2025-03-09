async function encryptData(data, partialKey, userKey) {
    try {
        // Convert string key and IV to a Uint8Array
        const rawKey = new TextEncoder().encode(partialKey + userKey); // Key (original from PHP) with missing 8 digits filled
        const rawIV = rawKey.slice(0, 16); // First 16 bytes of key as IV

        // Import the key into the Crypto API
        const cryptoKey = await window.crypto.subtle.importKey(
            "raw",
            rawKey,
            {name: "AES-CBC"},
            false,
            ["encrypt"]
        );

        // Encode the data to be encrypted
        const encodedData = new TextEncoder().encode(data);

        // Encrypt the data
        const encryptedBuffer = await window.crypto.subtle.encrypt(
            {
                name: "AES-CBC",
                iv: rawIV,
            },
            cryptoKey,
            encodedData
        );

        // Convert encrypted buffer to Base64 string
        const encryptedBytes = new Uint8Array(encryptedBuffer);
        return btoa(String.fromCharCode(...encryptedBytes));
    } catch (error) {
        console.error("Encryption failed:", error);
    }
    return "";
}

async function decryptData(encrpytedData, partialKey, userKey) {
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