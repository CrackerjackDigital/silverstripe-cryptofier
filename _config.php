<?php
// defined these classes depending on php version so can be used in config files
if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
    define('CryptofierPHPVersion54', PHP_VERSION);
} else {
    // shouldn't be lower than 5.3 when running silverstripe
    define('CryptofierPHPVersion53', PHP_VERSION);
}
