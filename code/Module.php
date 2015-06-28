<?php

/**
 * Class for common functionality, config etc for the module.
 */
class CryptofierModule extends Object {
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
}