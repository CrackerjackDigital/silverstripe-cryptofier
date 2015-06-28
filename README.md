Two-way Cryptography Module for SilverStripe
--------------------------------------------

This module provides common developer interface and user friendly output to cryptography modules:

-   defuse (https://github.com/defuse/php-encryption)
-   Zend (https://github.com/zendframework/zend-crypt).

The implementation is chosen by default to be defuse if php >= 5.4 or Zend if php 5.3 (lesser versions are beneath
requirements for SilverStripe 3.1 anyway).

Requirements
============

-	silverstripe/framework >= 3.1


Installation
============

1.  Install cryptofier:

		composer require crackerjackdigital/cryptofier

2.  Copy cryptofier/install/cryptofier.yml to your app config folder and update
	-   set server keys for Defuse (and Zend) implementations. (run sake /cryptofier/genkeys to get keys)
	-   optionally set override for Injector-created implementation

3.  dev/build

4.  Checkout the docs!