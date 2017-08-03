<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

use Doctrine\ORM\Reflection\RuntimeReflectionService;

/**
 * RuntimeClassMetadataFactory is the ClassMetadata object creation factory that runs at
 * execution time, consuming pre-exising entity classes.
 *
 * @package Doctrine\ORM\Mapping\Factory
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class RuntimeClassMetadataFactory extends AbstractClassMetadataFactory
{
    /** @var RuntimeReflectionService */
    private $reflectionService;

    /**
     * @return RuntimeReflectionService
     */
    protected function getReflectionService() : RuntimeReflectionService
    {
        if (! $this->reflectionService) {
            $this->reflectionService = new RuntimeReflectionService();
        }

        return $this->reflectionService;
    }
}
