<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools;

use Doctrine\ORM\Reflection\ReflectionService;
use Doctrine\ORM\Reflection\StaticReflectionService;
use Doctrine\ORM\Mapping\ClassMetadataFactory;

/**
 * The DisconnectedClassMetadataFactory is used to create ClassMetadata objects
 * that do not require the entity class actually exist. This allows us to
 * load some mapping information and use it to do things like generate code
 * from the mapping information.
 *
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class DisconnectedClassMetadataFactory extends ClassMetadataFactory
{
    /**
     * @return ReflectionService
     */
    public function getReflectionService() : ReflectionService
    {
        if ($this->reflectionService === null) {
            $this->reflectionService = new StaticReflectionService();
        }

        return $this->reflectionService;
    }
}
