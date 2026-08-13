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
    public $hashAlgorithm = 'sha256';

    public $validateEncryptionKey = false;

    public $encryptionKey = null;

    const CIPHER = 'aes-256-cbc';

    public function init()
    {
        if (!empty($this->encryptionKey)) {
            $this->setEncryptionKey($this->encryptionKey);
        }

        parent::init();
    }

    protected function validateEncryptionKey($key)
    {
        if (!is_string($key) || $key === '') {
            throw new CException(
                Yii::t('yii', 'Encryption key cannot be empty.')
            );
        }

        return true;
    }

    public function getValidationKey()
    {
        try {
            $key = parent::getValidationKey();

            if (!empty($key)) {
                return $key;
            }
        } catch (CException $e) {
        }

        $key = Yii::app()->getGlobalState(
            self::STATE_VALIDATION_KEY
        );

        if (!empty($key)) {
            $this->setValidationKey($key);
            return $key;
        }

        $key = $this->generateRandomString(32);

        Yii::app()->setGlobalState(
            self::STATE_VALIDATION_KEY,
            $key
        );

        $this->setValidationKey($key);

        return $key;
    }

    public function getEncryptionKey()
    {
        if (!empty($this->encryptionKey)) {
            return $this->encryptionKey;
        }

        try {
            $key = parent::getEncryptionKey();

            if (!empty($key)) {
                return $key;
            }
        } catch (CException $e) {
        }

        $key = Yii::app()->getGlobalState(
            self::STATE_ENCRYPTION_KEY
        );

        if (!empty($key)) {
            $this->setEncryptionKey($key);
            return $key;
        }

        $key = $this->generateRandomString(32);

        Yii::app()->setGlobalState(
            self::STATE_ENCRYPTION_KEY,
            $key
        );

        $this->setEncryptionKey($key);

        return $key;
    }

    protected function getCipherKey($key)
    {
        return hash('sha256', $key, true);
    }

    public function encrypt($data, $key = null)
    {
        if ($key === null) {
            $key = $this->getEncryptionKey();
        }

        if (!is_string($data)) {
            $data = serialize($data);
        }

        $key = $this->getCipherKey($key);
        $ivLength = openssl_cipher_iv_length(self::CIPHER);

        if ($ivLength === false || $ivLength <= 0) {
            throw new CException(
                'Unable to determine cipher IV length.'
            );
        }

        try {
            $iv = random_bytes($ivLength);
        } catch (\Throwable $e) {
            throw new CException(
                'Unable to generate secure encryption IV.'
            );
        }

        $encrypted = openssl_encrypt(
            $data,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new CException(
                'Unable to encrypt data.'
            );
        }

        return $iv . $encrypted;
    }

    public function decrypt($data, $key = null)
    {
        if ($key === null) {
            $key = $this->getEncryptionKey();
        }

        if (!is_string($data) || $data === '') {
            return false;
        }

        $key = $this->getCipherKey($key);
        $ivLength = openssl_cipher_iv_length(self::CIPHER);

        if ($ivLength === false || $ivLength <= 0) {
            return false;
        }

        if (strlen($data) <= $ivLength) {
            return false;
        }

        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        if (strlen($iv) !== $ivLength || $encrypted === '') {
            return false;
        }

        $decrypted = openssl_decrypt(
            $encrypted,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return $decrypted === false ? false : $decrypted;
    }

    public function computeHMAC(
        $data,
        $key = null,
        $hashAlgorithm = null
    ) {
        if ($key === null) {
            $key = $this->getValidationKey();
        }

        if ($hashAlgorithm === null) {
            $hashAlgorithm = $this->hashAlgorithm;
        }

        if (function_exists('hash_hmac')) {
            return hash_hmac(
                $hashAlgorithm,
                $data,
                $key,
                false
            );
        }

        return parent::computeHMAC(
            $data,
            $key,
            $hashAlgorithm
        );
    }

    public function hashData($data, $key = null)
    {
        return $this->computeHMAC($data, $key) . $data;
    }

    public function validateData($data, $key = null)
    {
        if (!is_string($data)) {
            return false;
        }

        $hmacLength = strlen(
            hash_hmac(
                $this->hashAlgorithm,
                '',
                $key ?: $this->getValidationKey()
            )
        );

        if (strlen($data) <= $hmacLength) {
            return false;
        }

        $providedHmac = substr($data, 0, $hmacLength);
        $payload = substr($data, $hmacLength);

        $calculatedHmac = $this->computeHMAC($payload, $key);

        if (function_exists('hash_equals')) {
            return hash_equals(
                $providedHmac,
                $calculatedHmac
            ) ? $payload : false;
        }

        return $this->compareString(
            $providedHmac,
            $calculatedHmac
        ) ? $payload : false;
    }

}