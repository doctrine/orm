<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Reflection\ReflectionService;
use Doctrine\ORM\Reflection\StaticReflectionService;

/**
 * The DisconnectedClassMetadataFactory is used to create ClassMetadata objects
 * that do not require the entity class actually exist. This allows us to
 * load some mapping information and use it to do things like generate code
 * from the mapping information.
 */
class DisconnectedClassMetadataFactory extends ClassMetadataFactory
{
    public function getReflectionService() : ReflectionService
    {
        if ($this->reflectionService === null) {
            $this->reflectionService = new StaticReflectionService();
        }

        return $this->reflectionService;
    }
}
