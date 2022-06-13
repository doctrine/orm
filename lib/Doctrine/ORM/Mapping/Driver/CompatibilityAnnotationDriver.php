<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Persistence\Mapping\Driver\AnnotationDriver as PersistenceAnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

use function class_exists;

if (! class_exists(PersistenceAnnotationDriver::class)) {
    /**
     * @internal This class will be removed in ORM 3.0.
     */
    abstract class CompatibilityAnnotationDriver implements MappingDriver
    {
    }
} else {
    /**
     * @internal This class will be removed in ORM 3.0.
     */
    abstract class CompatibilityAnnotationDriver extends PersistenceAnnotationDriver
    {
    }
}
