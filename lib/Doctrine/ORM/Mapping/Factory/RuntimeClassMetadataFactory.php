<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

use Doctrine\ORM\Reflection\RuntimeReflectionService;

/**
 * RuntimeClassMetadataFactory is the ClassMetadata object creation factory that runs at
 * execution time, consuming pre-exising entity classes.
 */
class RuntimeClassMetadataFactory extends AbstractClassMetadataFactory
{
    /** @var RuntimeReflectionService */
    private $reflectionService;

    protected function getReflectionService() : RuntimeReflectionService
    {
        if (! $this->reflectionService) {
            $this->reflectionService = new RuntimeReflectionService();
        }

        return $this->reflectionService;
    }
}
