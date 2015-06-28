<?php

/**
 * Base class for Crypto implementations, provides some common functionality.
 */
abstract class CryptofierImplementation extends Object
    implements CryptofierImplementationInterface {

    const TokenDelimiter = '|'; // something not likely to be in data/time or other token fields.

    // the name used by config to lookup the server key (may be override by config.server_key_config_name).
    const ServerKeyConfigName = 'server_key';

    // by default we use whatever is in self.ServerKeyConfigName as the config variable name
    private static $server_key_config_name = self::ServerKeyConfigName;


    /**
     * Add server key to the config.
     * @see CryptofierCryptoInterface
     * @return array
     */
    public static function get_extra_config() {
        $serverKeyConfigName = (string)static::config()->get('server_key_config_name');

        return array(
            $serverKeyConfigName => static::config()->get($serverKeyConfigName)
        );
    }

    /**
     * Return the native value from a 'friendly' value returned by the 'friendly' function.
     *
     * @param string $friendlyValue - value generated by the 'friendly' function.
     *
     * @return mixed - unfriendly value usefull directly to crypto libraries etc
     */
    abstract protected function unfriendly($friendlyValue);

    /**
     * @see CryptofierCryptoInterface
     * Get the private server key which should be kept private from config.crypto_server_key
     * (really config.(config.crypto_server_key_name)). This should returned as the 'friendly' version of
     * a key generated by self.generate_key.
     * @throws CryptofierException
     * @return string - friendly
     * @api
     */
    final public function server_key() {
        $serverKey = static::config()->get(
            (string)static::config()->get('server_key_config_name')
        );
        if (empty($serverKey)) {
            throw new CryptofierException("No server key (not going to do anything without it)");
        }
    }


    /**
     * Returns encrypted plain text value first by server key then optionally using supplied key.
     *
     * @param $plainTextValue
     * @param $friendlyKeySecondPass - 'friendly' version of self.generate_key output
     *
     * @return string - maybe unfriendly
     * @throws CryptofierException
     * @api
     */
    public function encrypt($plainTextValue, $friendlyKeySecondPass = null) {
        $encrypted = $this->encrypt_native(
            $plainTextValue,
            $this->server_key()
        );
        if ($friendlyKeySecondPass) {
            // second pass
            $encrypted = $this->encrypt_native(
                $encrypted,
                $friendlyKeySecondPass
            );
        }

        return $encrypted;
    }


    /**
     * @param $encryptedValue
     * @param $friendlyKeySecondPass - 'friendly' version of self.generate_key output
     *
     * @return string - maybe unfriendly
     * @throws CryptofierException
     * @api
     */
    public function decrypt($encryptedValue, $friendlyKeySecondPass = null) {
        if ($friendlyKeySecondPass) {
            $encryptedValue = $this->decrypt_native(
                $encryptedValue,
                $friendlyKeySecondPass
            );
        }

        return $this->decrypt_native(
            $encryptedValue,
            $this->server_key()
        );
    }

    /**
     * @see CryptofierCryptoInterface
     *
     * @param $values    - values to encrypt into token
     * @param $accessKey - optional second-pass key, see decrypt method for details
     *
     * @return string - friendly (tokens should always be friendly)
     * @throws CryptofierException
     * @api
     */
    public function encrypt_token(array $values, $accessKey = null) {
        try {
            $token = implode(
                $this->config()->get('token_delimiter'),
                array_values($values)
            );

            return $this->friendly($this->encrypt($token, $accessKey));

        } catch (Exception $e) {
            throw new CryptofierException("Failed to " . __METHOD__);
        }
    }

    /**
     * @see CryptofierCryptoInterface
     *
     * @param $friendlyToken
     * @param null|string $accessKey
     *
     * @return array - decrypted token values in same order they where encrypted
     * @throws CryptofierException
     * @api
     */
    public function decrypt_token($friendlyToken, $accessKey = null) {
        try {
            // these should come out in order they where encrypted
            $values = explode(
                $this->config()->get('token_delimiter'),
                $this->decrypt(
                    $this->unfriendly($friendlyToken),
                    $accessKey
                )
            );

            return $values;
        } catch (Exception $e) {
            throw new CryptofierException("Failed to " . __METHOD__);
        }
    }

    /**
     * Native single pass encryption of value using key.
     *
     * NB: Shouldn't be called directly as doesn't use the server key!
     *
     * @param $value
     * @param $friendlyKey - key generated by generate_key method
     *
     * @return string
     * @api
     */
    abstract protected function encrypt_native($value, $friendlyKey);

    /**
     * Native single pass decryption of value using key.
     *
     * NB: Shouldn't be called directly as doesn't use the server key!
     *
     * @param $encryptedValue
     * @param $friendlyKey - key generated by generate_key method
     *
     * @return string - encrypted value, maybe unfriendly
     * @api
     */
    abstract protected function decrypt_native($encryptedValue, $friendlyKey);

}