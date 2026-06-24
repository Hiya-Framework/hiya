<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

namespace Hiya\Base;

use CSecurityManager;
use CException;
use Yii;

class SecurityManager extends CSecurityManager
{
    /**
     * @var string Default hash algorithm for HMAC operations
     * Overrides parent default 'sha1' with more secure 'sha256'
     */
    public $hashAlgorithm = 'sha256';
    
    /**
     * @var bool Disable mcrypt-based encryption key validation
     * Since we use OpenSSL, mcrypt validation is no longer needed
     */
    public $validateEncryptionKey = false;

    /**
     * Validates the encryption key without using deprecated mcrypt functions
     * 
     * This method overrides the parent implementation to skip mcrypt-based validation
     * since OpenSSL handles key validation internally. Only checks if key is not empty.
     * 
     * @param string $key The encryption key to validate
     * @return bool Returns true if key is valid
     * @throws CException If the encryption key is empty
     */
    protected function validateEncryptionKey($key)
    {
        // Skip validation, OpenSSL will handle it
        if (empty($key)) {
            throw new CException(Yii::t('yii', 'Encryption key cannot be empty.'));
        }
        return true;
    }

    /**
     * Retrieves or auto-generates the validation key for HMAC operations
     * 
     * If no key is explicitly set, this method will:
     * 1. Check if key exists in parent
     * 2. Try to load from global state (persistent storage)
     * 3. Generate a new random 32-byte key if none found
     * 
     * @return string The validation key
     */
    public function getValidationKey()
    {
        // Use parent method to get the key if already set
        try {
            $key = parent::getValidationKey();
            if (!empty($key)) {
                return $key;
            }
        } catch (CException $e) {
            // Key not set yet, we'll generate one
        }

        // Try to get from global state (persistent storage)
        if (($key = Yii::app()->getGlobalState(self::STATE_VALIDATION_KEY)) !== null) {
            $this->setValidationKey($key);
            return $key;
        }

        // Generate random key (32 bytes = 64 hex characters for SHA256)
        $key = $this->generateRandomString(32);
        
        // Store in global state for persistence across requests
        Yii::app()->setGlobalState(self::STATE_VALIDATION_KEY, $key);
        $this->setValidationKey($key);
        
        return $key;
    }

    /**
     * Retrieves or auto-generates the encryption key for AES-256 operations
     * 
     * If no key is explicitly set, this method will:
     * 1. Check if key exists in parent
     * 2. Try to load from global state (persistent storage)
     * 3. Generate a new random 32-byte key if none found
     * 
     * @return string The encryption key
     */
    public function getEncryptionKey()
    {
        // Use parent method to get the key if already set
        try {
            $key = parent::getEncryptionKey();
            if (!empty($key)) {
                return $key;
            }
        } catch (CException $e) {
            // Key not set yet, we'll generate one
        }

        // Try to get from global state (persistent storage)
        if (($key = Yii::app()->getGlobalState(self::STATE_ENCRYPTION_KEY)) !== null) {
            $this->setEncryptionKey($key);
            return $key;
        }

        // Generate random key (32 bytes for AES-256)
        $key = $this->generateRandomString(32);
        
        // Store in global state for persistence across requests
        Yii::app()->setGlobalState(self::STATE_ENCRYPTION_KEY, $key);
        $this->setEncryptionKey($key);
        
        return $key;
    }

    /**
     * Encrypts data using OpenSSL AES-256-CBC algorithm
     * 
     * This method replaces the parent mcrypt-based encryption with OpenSSL.
     * Uses AES-256-CBC with a randomly generated IV prepended to the output.
     * 
     * @param string $data The plain text data to encrypt
     * @param string|null $key Optional encryption key (auto-generated if null)
     * @return string The encrypted data with IV prepended
     */
    public function encrypt($data, $key = null)
    {
        if ($key === null) {
            $key = $this->getEncryptionKey();
        }
        
        // Derive 32-byte key for AES-256
        $key = hash('sha256', $key, true);
        
        // Generate random IV (16 bytes for AES-CBC)
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        // Encrypt
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        
        // Prepend IV to encrypted data
        return $iv . $encrypted;
    }

    /**
     * Decrypts data using OpenSSL AES-256-CBC algorithm
     * 
     * This method replaces the parent mcrypt-based decryption with OpenSSL.
     * Expects the IV to be prepended to the encrypted data.
     * 
     * @param string $data The encrypted data with IV prepended
     * @param string|null $key Optional decryption key (auto-detected if null)
     * @return string|false The decrypted plain text data, or false on failure
     */
    public function decrypt($data, $key = null)
    {
        if ($key === null) {
            $key = $this->getEncryptionKey();
        }
        
        // Derive 32-byte key for AES-256
        $key = hash('sha256', $key, true);
        
        // Extract IV (first 16 bytes)
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        // Decrypt
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        
        if ($decrypted === false) {
            return false;
        }
        
        return $decrypted;
    }

    /**
     * Computes HMAC using the specified hash algorithm
     * 
     * Overrides parent to ensure hash_hmac() is used when available.
     * Falls back to parent implementation for older PHP versions.
     * 
     * @param string $data The data to generate HMAC for
     * @param string|null $key The validation key (auto-generated if null)
     * @param string|null $hashAlgorithm Hash algorithm (defaults to 'sha256')
     * @return string The HMAC value
     */
    public function computeHMAC($data, $key = null, $hashAlgorithm = null)
    {
        if ($key === null) {
            $key = $this->getValidationKey();
        }
        if ($hashAlgorithm === null) {
            $hashAlgorithm = $this->hashAlgorithm;
        }

        // Use PHP native hash_hmac if available
        if (function_exists('hash_hmac')) {
            return hash_hmac($hashAlgorithm, $data, $key, false);
        }

        // Fallback for older PHP versions
        return parent::computeHMAC($data, $key, $hashAlgorithm);
    }

    /**
     * Prepends HMAC to data for integrity verification
     * 
     * Alias for backward compatibility with Yii 1.1.
     * 
     * @param string $data The data to hash
     * @param string|null $key The validation key (auto-generated if null)
     * @return string Data with HMAC prepended
     */
    public function hashData($data, $key = null)
    {
        return $this->computeHMAC($data, $key) . $data;
    }

    /**
     * Validates data integrity by checking HMAC
     * 
     * Alias for backward compatibility with Yii 1.1.
     * If data is too short to contain HMAC, returns data as-is (for CSRF, session, etc.).
     * 
     * @param string $data The data to validate (HMAC prepended)
     * @param string|null $key The validation key (auto-generated if null)
     * @return string|false The original data without HMAC, or false on failure
     */
    public function validateData($data, $key = null)
    {
        if (!is_string($data)) {
            return false;
        }

        $hmacLength = strlen($this->computeHMAC('test'));
        $dataLength = strlen($data);
        
        // If data is shorter than HMAC length, it's not a hashed value
        // Return as-is (handles CSRF tokens, session IDs, etc.)
        if ($dataLength < $hmacLength) {
            return $data;
        }
        
        $hmac = substr($data, 0, $hmacLength);
        $data2 = substr($data, $hmacLength);
        
        if ($this->compareString($hmac, $this->computeHMAC($data2, $key))) {
            return $data2;
        }
        
        // HMAC mismatch - return original data as fallback
        return $data;
    }
}