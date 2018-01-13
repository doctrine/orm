<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

spl_autoload_register(function ($class) : void {
    $deprecatedClasses = [
        ClassMetadataInfo::class => [ClassMetadata::class, '2.7', '3.0'],
    ];

    if (array_key_exists($class, $deprecatedClasses)) {
        $deprecationMetadata = $deprecatedClasses[$class];
        @trigger_error(sprintf(
            'Class %s is deprecated in favor of class %s since %s, will be removed in %s.',
            $class,
            ...$deprecationMetadata
        ), E_USER_DEPRECATED);
        class_alias($deprecationMetadata[0], $class);
    }
});
