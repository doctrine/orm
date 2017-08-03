<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

use Doctrine\ORM\Reflection\StaticReflectionService;

/**
 * StaticClassMetadataFactory is the ClassMetadata object creation factory that sits behind
 * the front-door, allowing to generate entity classes in case they do not exist yet.
 *
 * @package Doctrine\ORM\Mapping\Factory
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class StaticClassMetadataFactory extends AbstractClassMetadataFactory
{
    /** @var StaticReflectionService */
    private $reflectionService;

    /**
     * @return StaticReflectionService
     */
    protected function getReflectionService() : StaticReflectionService
    {
        if (! $this->reflectionService) {
            $this->reflectionService = new StaticReflectionService();
        }

        return $this->reflectionService;
    }
}
