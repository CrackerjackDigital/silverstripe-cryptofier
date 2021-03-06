Two-way Cryptography Wrapper Module for SilverStripe
----------------------------------------------------

__AS WITH ALL THINGS SECURITY RELATED PLEASE READ THIS DOCUMENTATION AND THINK CAREFULLY BEFORE INSTALLING AND USING__

This module provides common developer interface and user friendly output to cryptography modules at the moment:

-   defuse (https://github.com/defuse/php-encryption)
-   Zend (https://github.com/zendframework/zend-crypt).

The implementation is chosen by default to be _defuse_ if php >= 5.4 or _Zend_ if php 5.3 (lesser versions are beneath
requirements for SilverStripe 3.1 anyway).

Please contact me via github if you have any questions and/or see any problems with or enhancements/pull requests
for the module.

(Apologies for shouting up there...)

Example problem
===============

John wants to be able to send out a 'private' link to his site which shows a page which does a search for an Event
by either Event ID or a Start Date and End Date (or all three as events may repeat).

He creates a protected page (via normal SilverStrip Login process) on the site with a form which allows him to
choose an Event, Start Date and End Date using nice dropdowns and calendars etc. He is the only one who needs access
to this page.

When this form is submitted it comes back with an easily copyable link and access key:

Link:       http://coolstuff.com/search/239810923801294-130as-09sda-0as9d9ad-s9as-d9a-s0d9a-s0d
AccessKey:  0c0ac758dfc390392a9e7e7c0827ed59

He can then distribute this info to anyone he feels would be interested by email, text etc

When a person clicks the link they are taken to the site and prompted to enter the access key. If a valid
access key is entered then they are taken to the search page and results matching the criteria which John originally
specified above are displayed.

Cryptofier provides functionality for generating the encrypted link url segment and the access key in a user-friendly
manner.

In this case it may be overkill but later on John may want to incorporate email addresses and other sensitive
information into the link, and so it will need to be encrypted rather than just reversibly encoded (e.g. base64_encode)
or one-way hashed (in which case you can't get the information back).

Caution
=======

The idea is that when we are using keys on the web we pretty much always want them to be 'friendly' to be
passed on URL's etc, copied and pasted, sent by email and maybe even typed into forms, so the interface
will only ever give you (and take) a 'friendly' key which you can use for these purposes.

The process of making the key 'friendly' is easily reversible (e.g. a bin2hex/hex2bin combination), __the key is in no
way protected by it, so don't ever use/present the returned key thinking it is in itself encrypted some way,
it's just been made more useable__.

If you stick to the API (publically accessible functions on the Implementations used below) then encryption/decryption
always uses a private server side key in its calls.

__The server key should never be publicly disclosed, e.g. in your public github repo source code__.

We can then consider the 'primary' encryption to be using the server key and any subsequent encryption to be using
a second pass key more as an 'access key' rather than a pure encryption key.

Without the second-pass key the encrypted value is still 'safe' as only the server should be able to decrypt it.

Lack of a server key should cause an exception so you should not be able to do anything with the module
until you have one configured, even so:

__Double check you have set the server keys for all implementations immediately after install__.

To set the server keys copy the 'cryptofier.yml' file from the module 'install/' directory to your application
_config folder and update the two entries 'server_key' to valid server keys you can generate via calls to the
respective implementations generate_key method. Then run /dev/build?flush=1 to update config.

Usage
=====

Api provides the following functionality (by example):

Encrypting and decrypting a value using the server key only:

		$service = Injector::inst()->get('CryptofierService');

		$encrypted = $service
			->encrypt(
				"The woods are lovely dark and deep"
			);

		$decrypted = $service
			->decrypt(
				$encrypted
			);

Encrypting and decrypting using an access key:

-   Initially be the administrator:

		$service = Injector::inst()->get('CryptofierService');

		$accessKey = $service->generate_key();

		$encrypted = $service
			->encrypt(
				"But I have promises to keep",
				$accessKey
			);

		// save encrypted data somewhere

		echo "Access Key: $accessKey";

		// now access key could be provided to users who can then retype into a form

-   Later by the user entering access key in a form:

		$accessKey = Controller::curr()->getRequest()->postVar('AccessKey');

		// load encrypted data from somewhere

		$decrypted = $service
			->decrypt(
				$encrypted,
				$accessKey
			);

		// use decrypted data


Encrypting/decrypting some more structured info for use as e.g. a url segment

		$service = Injector::inst()->get('CryptofierService');

		$encrypted = $service->encrypt_token(array(
			'ID' => 10,
			'Title' => 'And miles to go before I sleep',
			'StartDate' => '2015-10-10',
			'EndDate' => '2015-10-20'
		));

		$somePage = <get a page which can handle the 'link' action and do something with it).

		$link = Controller::join_links($somePage->Link('token'), $encrypted);

		// output link or send by email etc

	Some time later in the page controller class:

		// allowed actions, url handlers etc e.g.
		private static $url_handlers = array(
			'token/$Token!' => 'token'
		);

		public function index() {
			// code that enforces a login to see the page content (or set via CMS security options on page)
		}

		public function token(SS_HttpRequest $request) {
			$accessToken = $request->postVar('AccessToken');

			if (!$accessToken && !Member::currentUserID()) {
				// show a form to get the access token, the form should post back to current url via 'setFormAction'
			} else {

				$encrypted = $request->param('Token');

				try {
					list($id, $title, $startDate, $endDate) = $service->decrypt_token($encrypted, $accessToken);

					// do something with data, maybe output something, set session info etc
					// maybe redirect to same page without 'token' action

				} catch (CryptofierException $e) {
					// handle cryptofier exception without compromising info

					$this->httpError(401, 'No way buddy');

				} catch (Exception $e) {

					$this->httpError(500, 'Fail');

				}
			}

		}

Of course we can use two-pass with an access token as per above by passing an access key as second argument. In this
case you would need to show a Form initially where the access key could be entered and then use the entered key from
POST data to decrypt the token on the url.

Setting the Implementation to use (override default choice by PHP_VERSION)
==========================================================================

Say we want to use Zend always then in application config.yml:

	Injector:
		CryptofierService: CryptofierZendImplementation

Generating Keys
===============

Keys (e.g. for use as server keys) can be generated from the command line root of your server:

		framework/sake CryptofierGenerateKeys.php

Tests
=====

dev/tests/CrytofierUnitTest should be all greens or stop what you're doing and check out the problem!


Requirements
============

	{
        "php":                          ">=5.3.2",
        "composer/installers":          "*",
        "silverstripe/framework":       "3.1.*",
        "defuse/php-encryption":        ">=1.2.1",
        "zendframework/zend-crypt":     "2.5.1"
    }

Installation
============

1.  Install cryptofier:

		composer require crackerjackdigital/cryptofier

	or if not in packagist yet then mangle composer.json as follows:

		"require": {
			// ... whatever is there
			"crackerjackdigital/crytofier": "dev-master"
		},
		"repositories": [
			// ... whatever is there
			{
				"type": "vcs",
	            "url": "https://github.com/crackerjackdigital/silverstripe-cryptofier.git"
			}
		]

	as always be carefull with commas, brackets etc as easy to bork your composer and get mystifying error messages.


2.  Read this README again.

3.	Copy cryptofier/install/cryptofier.yml to your app config folder and update:

	__set your server keys for all implementations by calling generate_key on each implementation or by generating
	keys using framework/sake CryptofierGenerateKeys__

4.  dev/build?flush=1

5.  Checkout the docs! [@docs/html/index.html]

6.  Try it out using examples above as basis


Disclaimer
==========

I am in no way a crptography expert and do not even attempt to 'roll-my-own' cryptography here but leverage
other modules as listed above in what I think is a sensible and SilverStripe compliant way.

If you spot any problems at all, or have suggestions, improvements, bug fixes etc then please do contact me
at: https://github.com/wakes or via https://github.com/CrackerjackDigital.

Have fun!

