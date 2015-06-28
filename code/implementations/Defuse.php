<?php
use \Defuse\Crypto\Crypto as Crypto;

final class CryptofierDefuseImplementation extends CryptofierImplementation {

    // server key used by encrypt, decrypt methods to always encrypt with this on first pass
    // should be in 'friendly' format
    private static $server_key = '';

    // what we use to join/split fields in the token, should be very unlikely to be in a field value.
    private static $token_delimiter = self::TokenDelimiter;

    /**
     * Generate a key and return in 'friendly' format.
     *
     * @param null $unused - not used in this implementation
     *
     * @return string - friendly
     */
    public function generate_key($unused = null) {
        $crypto = new Crypto();

        return $this->friendly(
            $crypto->createNewRandomKey()
        );
    }

    /**
     * Use Defuse\Crypto\Crypto.binToHex to make 'friendly' value for urls, cut-and-paste, typeable etc
     *
     * @param $unfriendlyValue
     *
     * @return string - friendly
     */
    public function friendly($unfriendlyValue) {
        return Crypto::binToHex($unfriendlyValue);
    }

    /**
     * Uses Defuse\Crypto\Crypto.hexToBin to convert a self.friendly value back to raw value
     *
     * @param $friendlyValue - value returned from 'friendly' function
     *
     * @return string - maybe unfriendly value
     */
    protected function unfriendly($friendlyValue) {
        return $friendlyValue ? Crypto::hexToBin($friendlyValue) : null;
    }

    /**
     * @see CryptofierCryptoInterface
     *
     * NB: Shouldn't be called externally/by derived classes as doesn't use the server key!
     *
     * @param $value
     * @param $friendlyKey
     *
     * @return string - maybe unfriendly
     * @throws CryptofierException
     */
    final protected function encrypt_native($value, $friendlyKey) {
        try {
            return Crypto::encrypt(
                $value,
                $this->unfriendly($friendlyKey)
            );
        } catch (Exception $e) {
            throw new CryptofierException("Failed to " . __METHOD__);
        }

    }

    /**
     * @see CryptofierCryptoInterface
     *
     * NB: Shouldn't be called externally/by derived classes as doesn't use the server key!
     *
     * @param $value
     * @param $friendlyKey
     *
     * @return string - maybe unfriendly
     * @throws CryptofierException
     */
    final protected function decrypt_native($value, $friendlyKey) {
        try {
            return Crypto::decrypt(
                $value,
                $this->unfriendly($friendlyKey)
            );
        } catch (Exception $e) {
            throw new CryptofierException("Failed to " . __METHOD__);
        }
    }

}