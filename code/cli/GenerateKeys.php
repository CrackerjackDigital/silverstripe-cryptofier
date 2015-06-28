<?php

class CryptofierGenerateKeys extends CliController {
    /**
     * For each class found with the CryptofierImplementationInterface
     * output a key from generate_key method. This is already 'friendly'
     * and good to put in yml files as server_key, in forms, on urls etc
     */
    public function process() {
        $implementations = CryptofierModule::implementation_class_names();

        /** @var CryptofierImplementation $impl */

        foreach ($implementations as $className) {
            echo "Generating key for '$className'" . PHP_EOL;
            echo singleton($className)->generate_key() . PHP_EOL;
            echo PHP_EOL;
        }
    }
}