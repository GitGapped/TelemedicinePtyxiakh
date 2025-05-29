<?php
// Encryption key - store this securely and never expose it
// In production, this should be stored in environment variables or a secure key management system
define('ENCRYPTION_KEY', 'your-32-byte-encryption-key-here');

/**
 * Encrypt a message using AES-256-GCM
 * @param string $message The message to encrypt
 * @return array Array containing encrypted message and authentication tag
 */
function encryptMessage($message) {
    // Generate a random initialization vector
    $iv = random_bytes(12);
    
    // Encrypt the message
    $encrypted = openssl_encrypt(
        $message,
        'aes-256-gcm',
        ENCRYPTION_KEY,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );
    
    // Combine IV, encrypted message, and authentication tag
    $combined = $iv . $tag . $encrypted;
    
    // Return base64 encoded result for safe storage
    return base64_encode($combined);
}

/**
 * Decrypt a message using AES-256-GCM
 * @param string $encryptedMessage The encrypted message to decrypt
 * @return string|false The decrypted message or false if decryption fails
 */
function decryptMessage($encryptedMessage) {
    // Decode the base64 string
    $decoded = base64_decode($encryptedMessage);
    if ($decoded === false) {
        return false;
    }
    
    // Extract IV (first 12 bytes), tag (next 16 bytes), and encrypted message
    $iv = substr($decoded, 0, 12);
    $tag = substr($decoded, 12, 16);
    $encrypted = substr($decoded, 28);
    
    // Decrypt the message
    $decrypted = openssl_decrypt(
        $encrypted,
        'aes-256-gcm',
        ENCRYPTION_KEY,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );
    
    return $decrypted;
} 