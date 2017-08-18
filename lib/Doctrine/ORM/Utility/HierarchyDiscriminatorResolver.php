<?php

namespace Doctrine\ORM\Utility;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class HierarchyDiscriminatorResolver
 * @package Doctrine\ORM\Utility
 * @internal This class exists only to avoid code duplication, do not reuse it externally
 */
class HierarchyDiscriminatorResolver
{
    public static function resolveDiscriminatorsForClass(
        ClassMetadata $rootClassMetadata,
        EntityManagerInterface $entityManager
    ) {
        $hierarchyClasses = $rootClassMetadata->subClasses;
        $hierarchyClasses[] = $rootClassMetadata->name;

        $discriminators = [];
        foreach ($hierarchyClasses as $class) {
            $currentMetadata = $entityManager->getClassMetadata($class);
            $currentDiscriminator = $currentMetadata->discriminatorValue;

            if (null !== $currentDiscriminator) {
                $discriminators[$currentDiscriminator] = null;
            }
        }

        return $discriminators;
    }
}
