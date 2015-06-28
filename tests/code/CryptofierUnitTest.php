<?php

/**
 * Test cryptofier module cryptographical functions using:
 *  - all implementations as per CryptofierModule.list_implementations
 *  - both with and without a second-pass access key provided
 */
class CryptofierUnitTest extends SapphireTest {
    private static $config = array();

    protected $plainText = <<<PLAINTEXT
        The woods are lovely dark and deep,
        but I have promises to keep,
        and miles to go before I sleep,
        and miles to go before I sleep.

        Synbols: !@#$%^&*()_{}}{[\][;'l';/.,/.,|}{":?><
        Unicode: ā₣℞ℳ<⊅∄∝'‘’‛?§″′"'˝„°❞❝
PLAINTEXT;

    // simple token values
    protected $token = array(
        'ItemID'    => 20,
        'StartDate' => '2015-10-01',
        'EndDate'   => '2015-10-15'
    );

    /**
     * Give the IDE some type hinting/autocomplete help.
     * @return $this|PHPUnit_Framework_Assert
     */
    public function __invoke() {
        return $this;
    }

    /**
     * Tests native one-pass (server_key only) encryption/decryption for all CrytofierImplementation derived classes.
     */
    public function testBasicFunctionsOnePass() {
        // test for each service registered
        foreach ($this->listImplementations() as $className) {
            $crypto = $this->configuredService($className);

            $encrypted = $crypto->encrypt($this->plainText);

            $decrypted = $crypto->decrypt($encrypted);

            $this()->assertEquals($decrypted, $this->plainText, "That decrypted value equals plain text value using implementation '$className'");
        }
    }
    /**
     * Tests native two-pass (server_key and accessKey only) encryption/decryption for all CrytofierImplementation derived classes.
     */
    public function testBasicFunctionsTwoPass() {
        // test for each service registered
        foreach ($this->listImplementations() as $className) {
            $crypto = $this->configuredService($className);

            $accessKey = $crypto->generate_key();

            $encrypted = $crypto->encrypt($this->plainText, $accessKey);

            $decrypted = $crypto->decrypt($encrypted, $accessKey);

            $this()->assertEquals($decrypted, $this->plainText, "That decrypted value equals plain text value using implementation '$className'");
        }
    }

    /**
     * Tests one-pass (server_key only) token-oriented encryption/decryption for all CrytofierImplementation derived classes.
     */
    public function testTokenFunctionsOnePass() {
        list($itemID, $startDate, $endDate) = array_values($this->token);

        foreach ($this->listImplementations() as $className) {
            $crypto = $this->configuredService($className);

            $encrypted = $crypto->encrypt_token(array(
                    $itemID,
                    $startDate,
                    $endDate,
                )
            );
            list($id, $start, $end) = $crypto->decrypt_token($encrypted);

            $this()->assertEquals($itemID, $id, "Assert that ItemID '$itemID' = '$id' using implementation '$className'");
            $this()->assertEquals($startDate, $start, "Assert that StartDate '$startDate' = '$start' using implementation '$className'");
            $this()->assertEquals($endDate, $end, "Assert that EndDate '$endDate' = '$end' using implementation '$className'");
        }
    }

    /**
     * Tests two-pass (server_key and accessKey) token-oriented encryption/decryption for all CrytofierImplementation derived classes.
     */
    public function testTokenFunctionsTwoPass() {
        list($itemID, $startDate, $endDate) = array_values($this->token);

        foreach ($this->listImplementations() as $className) {
            $crypto = $this->configuredService($className);

            $accessKey = $crypto->generate_key();

            $encrypted = $crypto->encrypt_token(array(
                    $itemID,
                    $startDate,
                    $endDate,
                ),
                $accessKey
            );
            list($id, $start, $end) = $crypto->decrypt_token(
                $encrypted,
                $accessKey
            );

            $this()->assertEquals($itemID, $id, "Assert that ItemID '$itemID' = '$id' using implementation '$className'");
            $this()->assertEquals($startDate, $start, "Assert that StartDate '$startDate' = '$start' using implementation '$className'");
            $this()->assertEquals($endDate, $end, "Assert that EndDate '$endDate' = '$end' using implementation '$className'");
        }
    }


    public function setUpOnce() {
        parent::setUpOnce();
        $this->loadConfig();
    }

    public function setUp() {
        parent::setUp();
        Injector::nest();
    }

    public function tearDown() {
        Injector::unnest();
        parent::tearDown();
    }
    /**
     * Set class configurations according to $replace parameter and passed $config:
     *  if $replace is false then
     *      if non-empty config then merge with existing self.config (parameter values override shared)
     *      if empty $config then just the existing self.config gets loaded
     *  if $replace is true
     *      if non-empty config then use $config without merging with existing self.config
     *      if empty $config then no changes get made, including to existing self.config
     *
     * @param array $config
     * @param bool $replace
     */
    protected function loadConfig(array $config = array(), $replace = false) {
        if (!$replace) {
            $config = array_merge(
                self::$config,
                $config
            );
        }
        foreach ($config as $className => $configValues) {
            foreach ($configValues as $name => $value) {
                Config::inst()->update($className, $name, $value);
            }
        }
    }

    /**
     * Setup the Injector to use $className as the Crypto service and configure a temporary server_key.
     *
     * @param $className
     *
     * @return CryptofierImplementationInterface - configured service from injector
     */
    protected function configuredService($className) {
        /** @var CryptofierImplementationInterface $instance */
        $instance = $className::create();

        $serviceName = CryptofierModule::service_name();

        Injector::inst()->registerService(
            $instance,
            $serviceName
        );
        // generate temporary server key and assign so errors don't expose real server key.
        $serverKeyConfigName = $instance->config()->get('server_key_config_name');
        Config::inst()->update($className, $serverKeyConfigName, $instance->generate_key());

        // we don't want a singleton in this instance as we'll be switching classes regularly
        return Injector::inst()->get($serviceName);
    }

    private static function listImplementations() {
        return CryptofierModule::implementation_class_names();
    }

}