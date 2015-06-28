<?php
use Zend\Crypt\BlockCipher as BlockCipher;
use Zend\Crypt\Key\Derivation\Scrypt as KeyGen;
use Zend\Math\Rand;

class CryptofierZendImplementation extends CryptofierImplementation {

    // server key used by encrypt, decrypt methods to always encrypt with this on first pass
    // should be in 'friendly' format
    private static $server_key = '';

    // what we use to split fields in the token, should be very unlikely to be in a field value.
    private static $token_delimiter = self::TokenDelimiter;

    /**
     * Generate a key and return in 'friendly' format.
     *
     * @param string $salt - optional 32 byte salt (if not supplied one will be generated)..
     *
     * @return string - friendly
     */
    public function generate_key($salt = null) {
        $salt = mb_strlen($salt) === 32 ? $salt : Rand::getBytes(32, true);
        $pass = Rand::getBytes(32, true);

        return $this->friendly(
            KeyGen::calc($pass, $salt, 2048, 2, 1, 32)
        );
    }

    /**
     * Use bin2hex to make 'friendly' value for urls, cut-and-paste, typeable etc
     *
     * @param $unfriendlyValue
     *
     * @return string - 'friendly' value
     */
    public function friendly($unfriendlyValue) {
        return bin2hex($unfriendlyValue);
    }

    /**
     * Use hex2bin to make 'unfriendly' version of value from the 'friendly' function.
     *
     * @param $friendlyValue
     *
     * @return string - unfriendly
     */
    protected function unfriendly($friendlyValue) {
        return hex2bin($friendlyValue);
    }


    /**
     * @see CryptofierCryptoInterface
     *
     * @param $value
     * @param $friendlyKey
     *
     * @return string - maybe unfriendly
     * @throws CryptofierException
     */
    public function encrypt_native($value, $friendlyKey) {
        try {
            $cipher = BlockCipher::factory('mcrypt', array('algo' => 'aes'));
            $cipher->setKey(
                $this->unfriendly($friendlyKey)
            );
            return $cipher->encrypt($value);

        } catch (Exception $e) {
            throw new CryptofierException("Failed to " . __METHOD__);
        }
    }

    /**
     * @see CryptofierCryptoInterface
     *
     * @param $value
     * @param $friendlyKey
     *
     * @return string - maybe unfriendly
     * @throws CryptofierException
     */
    public function decrypt_native($value, $friendlyKey) {
        try {
            $cipher = BlockCipher::factory('mcrypt', array('algo' => 'aes'));
            $cipher->setKey(
                $this->unfriendly($friendlyKey)
            );
            return $cipher->decrypt($value);

        } catch (Exception $e) {
            throw new CryptofierException("Failed to " . __METHOD__);
        }

    }

}