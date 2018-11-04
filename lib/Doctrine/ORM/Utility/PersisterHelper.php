<?php

declare(strict_types=1);

namespace Doctrine\ORM\Utility;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\ManyToManyAssociationMetadata;
use Doctrine\ORM\Mapping\ToOneAssociationMetadata;
use RuntimeException;
use function sprintf;

/**
 * The PersisterHelper contains logic to infer binding types which is used in
 * several persisters.
 *
 * @internal do not use in your own codebase: no BC compliance on this class
 */
class PersisterHelper
{
    /**
     * @param string $columnName
     *
     * @return Type
     *
     * @throws RuntimeException
     */
    public static function getTypeOfColumn($columnName, ClassMetadata $class, EntityManagerInterface $em)
    {
        if (isset($class->fieldNames[$columnName])) {
            $fieldName = $class->fieldNames[$columnName];
            $property  = $class->getProperty($fieldName);

            switch (true) {
                case $property instanceof FieldMetadata:
                    return $property->getType();

                // Optimization: Do not loop through all properties later since we can recognize to-one owning scenario
                case $property instanceof ToOneAssociationMetadata:
                    // We know this is the owning side of a to-one because we found columns in the class (join columns)
                    foreach ($property->getJoinColumns() as $joinColumn) {
                        if ($joinColumn->getColumnName() !== $columnName) {
                            continue;
                        }

                        $targetClass = $em->getClassMetadata($property->getTargetEntity());

                        return self::getTypeOfColumn($joinColumn->getReferencedColumnName(), $targetClass, $em);
                    }

                    break;
            }
        }

        // iterate over association mappings
        foreach ($class->getDeclaredPropertiesIterator() as $association) {
            if (! ($association instanceof AssociationMetadata)) {
                continue;
            }

            // resolve join columns over to-one or to-many
            $targetClass = $em->getClassMetadata($association->getTargetEntity());

            if (! $association->isOwningSide()) {
                $association = $targetClass->getProperty($association->getMappedBy());
                $targetClass = $em->getClassMetadata($association->getTargetEntity());
            }

            $joinColumns = $association instanceof ManyToManyAssociationMetadata
                ? $association->getJoinTable()->getInverseJoinColumns()
                : $association->getJoinColumns();

            foreach ($joinColumns as $joinColumn) {
                if ($joinColumn->getColumnName() !== $columnName) {
                    continue;
                }

                return self::getTypeOfColumn($joinColumn->getReferencedColumnName(), $targetClass, $em);
            }
        }

        throw new RuntimeException(sprintf(
            'Could not resolve type of column "%s" of class "%s"',
            $columnName,
            $class->getClassName()
        ));
    }
}
