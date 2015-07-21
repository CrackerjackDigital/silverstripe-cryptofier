<?php

/**
 * Class for common functionality, config etc for the module.
 */
class CryptofierModule extends Object implements CryptofierImplementationInterface {
    const ServiceName                 = 'CryptofierService';
    const ImplementationBaseClassName = 'CryptofierImplementation';

    // can be reset if an (unlikely) conflict
    private static $service_name = self::ServiceName;

    public static function service_name() {
        return self::config()->get('service_name');
    }
    /**
     * Return a list of classes which descend from the self.ImplementationBaseClassName class as they
     * are the wrapped native crypto implementations.
     *
     * @return array of implementation class names excluding the base class
     */
    public static function implementation_class_names() {
        $implementationClassName = self::ImplementationBaseClassName;

        // get all classes descended from implementation class, filtering out the Implementation class name itself.
        $implementations = array_filter(
            array_values(
                ClassInfo::subclassesFor($implementationClassName)
            ),
            function($item) use ($implementationClassName) {
                return $item !== $implementationClassName;
            }
        );
        return array_values($implementations);
    }

    /**
     * @return CryptofierImplementationInterface
     */
    private static function implementation() {
        return Injector::inst()->get(self::ServiceName);
    }

    /**
     * Return the private server key used as part of the two-step encryption process. This should be
     * returned 'friendly'.
     * @return string - friendly
     * @api
     */
    public function server_key() {
        return self::implementation()->server_key();
    }

    /**
     * Generate a new access key. It should be 'friendly' so needs to be passed through
     * self.unfriendly before being handed to the native crypto api.
     *
     * @param null $init - anything needed to init or augment the generated key, e.g. a randomiser
     *
     * @return string - friendly
     * @api
     */
    public function generate_key($init = null) {
        return self::implementation()->generate_key($init);
    }

    /**
     * Return a value which is safe to use in url's and copy-paste operations, at a pinch typeable so
     * probably ANSI. This could be a cypher text or a system generated key which is a byte string for example.
     *
     * @param $unfriendlyValue - value likely to break urls or be otherwise unfriendly to humans/UI.
     *
     * @return string - friendly value
     * @api
     */
    public function friendly($unfriendlyValue) {
        return self::implementation()->friendly($unfriendlyValue);
    }


    /**
     * Encrypt value using the server key, and optionally as a second pass using $friendlyKeySecondPass if provided.
     * The optional friendly key should be 'unfriendlied' before use.
     *
     * @param string $plainTextValue
     * @param string|null $friendlyKeySecondPass - should have been generated using self.generate_key
     *
     * @return string - friendly encrypted $plainTextValue
     * @api
     */
    public function encrypt($plainTextValue, $friendlyKeySecondPass = null) {
        return self::implementation()->encrypt($plainTextValue, $friendlyKeySecondPass);
    }

    /**
     * Decrypt value using server key, optionally using provided $friendleyKey which should be same as when the
     * values was encrypted. The optional friendly key should be 'unfriendlied' before use.
     *
     * @param string $friendlyEncryptedValue     - friendly encrypted value
     * @param string|null $friendlyKeySecondPass - should have been generated using self.generate_key
     *
     * @return string - decrypted value, maybe unfriendly
     * @api
     */
    public function decrypt($friendlyEncryptedValue, $friendlyKeySecondPass = null) {
        return self::implementation()->decrypt($friendlyEncryptedValue, $friendlyKeySecondPass);
    }

    /**
     * Return an encoded version of passed parameters which can be passed on link into the system booking pages.
     * The return token is friendly ready to pass on url or give to person. The optional friendly key should be
     * 'unfriendlied' before use.
     *
     * @param $values    - values to encrypt into token
     * @param $accessKey - optional second-pass key, see decrypt method for details
     *
     * @return null|string - friendly encrypted value
     * @api
     */
    public function encrypt_token(array $values, $accessKey = null) {
        return self::implementation()->encrypt_token($values, $accessKey);
    }

    /**
     * Return array of decrypted values from the token. The optional friendly key should be
     * 'unfriendlied' before use.
     *
     * @param $friendlyToken - link to decrypt into parts, will be hex2bin converted before passing to Crypto
     * @param $accessKey     - optional second-pass key, see decrypt method for details
     *
     * @return array - decrypted token values in same order they where encrypted
     * @throws Exception
     * @api
     */
    public function decrypt_token($friendlyToken, $accessKey = null) {
        return self::implementation()->decrypt_token($friendlyToken, $accessKey);
    }

}