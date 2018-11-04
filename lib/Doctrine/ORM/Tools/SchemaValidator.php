<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\JoinColumnMetadata;
use Doctrine\ORM\Mapping\ManyToManyAssociationMetadata;
use Doctrine\ORM\Mapping\ManyToOneAssociationMetadata;
use Doctrine\ORM\Mapping\OneToManyAssociationMetadata;
use Doctrine\ORM\Mapping\OneToOneAssociationMetadata;
use Doctrine\ORM\Mapping\ToManyAssociationMetadata;
use Doctrine\ORM\Mapping\ToOneAssociationMetadata;
use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function class_exists;
use function class_parents;
use function count;
use function implode;
use function in_array;
use function sprintf;

/**
 * Performs strict validation of the mapping schema
 */
class SchemaValidator
{
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Checks the internal consistency of all mapping files.
     *
     * There are several checks that can't be done at runtime or are too expensive, which can be verified
     * with this command. For example:
     *
     * 1. Check if a relation with "mappedBy" is actually connected to that specified field.
     * 2. Check if "mappedBy" and "inversedBy" are consistent to each other.
     * 3. Check if "referencedColumnName" attributes are really pointing to primary key columns.
     *
     * @return string[]
     */
    public function validateMapping()
    {
        $errors  = [];
        $cmf     = $this->em->getMetadataFactory();
        $classes = $cmf->getAllMetadata();

        foreach ($classes as $class) {
            $ce = $this->validateClass($class);

            if ($ce) {
                $errors[$class->getClassName()] = $ce;
            }
        }

        return $errors;
    }

    /**
     * Validates a single class of the current.
     *
     * @return string[]
     */
    public function validateClass(ClassMetadata $class)
    {
        $ce = [];

        foreach ($class->getDeclaredPropertiesIterator() as $fieldName => $association) {
            if (! ($association instanceof AssociationMetadata)) {
                continue;
            }

            $ce = array_merge($ce, $this->validateAssociation($class, $association));
        }

        foreach ($class->getSubClasses() as $subClass) {
            if (! in_array($class->getClassName(), class_parents($subClass), true)) {
                $message = "According to the discriminator map class, '%s' has to be a child of '%s', but these entities are not related through inheritance.";

                $ce[] = sprintf($message, $subClass, $class->getClassName());
            }
        }

        return $ce;
    }

    /**
     * @return string[]
     */
    private function validateAssociation(ClassMetadata $class, AssociationMetadata $association)
    {
        $metadataFactory = $this->em->getMetadataFactory();
        $fieldName       = $association->getName();
        $targetEntity    = $association->getTargetEntity();

        if (! class_exists($targetEntity) || $metadataFactory->isTransient($targetEntity)) {
            $message = "The target entity '%s' specified on %s#%s is unknown or not an entity.";

            return [sprintf($message, $targetEntity, $class->getClassName(), $fieldName)];
        }

        $mappedBy   = $association->getMappedBy();
        $inversedBy = $association->getInversedBy();

        $ce = [];

        if ($mappedBy && $inversedBy) {
            $message = 'The association %s#%s cannot be defined as both inverse and owning.';

            $ce[] = sprintf($message, $class, $fieldName);
        }

        /** @var ClassMetadata $targetMetadata */
        $targetMetadata    = $metadataFactory->getMetadataFor($targetEntity);
        $containsForeignId = array_filter($targetMetadata->identifier, static function ($identifier) use ($targetMetadata) {
            $targetProperty = $targetMetadata->getProperty($identifier);

            return $targetProperty instanceof AssociationMetadata;
        });

        if ($association->isPrimaryKey() && count($containsForeignId)) {
            $message = "Cannot map association %s#%s as identifier, because the target entity '%s' also maps an association as identifier.";

            $ce[] = sprintf($message, $class->getClassName(), $fieldName, $targetEntity);
        }

        if ($mappedBy) {
            /** @var AssociationMetadata $targetAssociation */
            $targetAssociation = $targetMetadata->getProperty($mappedBy);

            if (! $targetAssociation) {
                $message = 'The association %s#%s refers to the owning side property %s#%s which does not exist.';

                $ce[] = sprintf($message, $class->getClassName(), $fieldName, $targetEntity, $mappedBy);
            } elseif ($targetAssociation instanceof FieldMetadata) {
                $message = 'The association %s#%s refers to the owning side property %s#%s which is not defined as association, but as field.';

                $ce[] = sprintf($message, $class->getClassName(), $fieldName, $targetEntity, $mappedBy);
            } elseif ($targetAssociation->getInversedBy() === null) {
                $message = 'The property %s#%s is on the inverse side of a bi-directional relationship, but the '
                    . "specified mappedBy association on the target-entity %s#%s does not contain the required 'inversedBy=\"%s\"' attribute.";

                $ce[] = sprintf($message, $class->getClassName(), $fieldName, $targetEntity, $mappedBy, $fieldName);
            } elseif ($targetAssociation->getInversedBy() !== $fieldName) {
                $message = 'The mapping between %s#%s and %s#%s are inconsistent with each other.';

                $ce[] = sprintf($message, $class->getClassName(), $fieldName, $targetEntity, $mappedBy);
            }
        }

        if ($inversedBy) {
            /** @var AssociationMetadata $targetAssociation */
            $targetAssociation = $targetMetadata->getProperty($inversedBy);

            if (! $targetAssociation) {
                $message = 'The association %s#%s refers to the inverse side property %s#%s which does not exist.';

                $ce[] = sprintf($message, $class->getClassName(), $fieldName, $targetEntity, $inversedBy);
            } elseif ($targetAssociation instanceof FieldMetadata) {
                $message = 'The association %s#%s refers to the inverse side property %s#%s which is not defined as association, but as field.';

                $ce[] = sprintf($message, $class->getClassName(), $fieldName, $targetEntity, $inversedBy);
            } elseif ($targetAssociation->getMappedBy() === null) {
                $message = 'The property %s#%s is on the owning side of a bi-directional relationship, but the '
                    . "specified mappedBy association on the target-entity %s#%s does not contain the required 'inversedBy=\"%s\"' attribute.";

                $ce[] = sprintf($message, $class->getClassName(), $fieldName, $targetEntity, $mappedBy, $fieldName);
            } elseif ($targetAssociation->getMappedBy() !== $fieldName) {
                $message = 'The mapping between %s#%s and %s#%s are inconsistent with each other.';

                $ce[] = sprintf($message, $class->getClassName(), $fieldName, $targetEntity, $inversedBy);
            }

            // Verify inverse side/owning side match each other
            if ($targetAssociation) {
                if ($association instanceof OneToOneAssociationMetadata && ! $targetAssociation instanceof OneToOneAssociationMetadata) {
                    $message = 'If association %s#%s is one-to-one, then the inversed side %s#%s has to be one-to-one as well.';

                    $ce[] = sprintf($message, $class->getClassName(), $fieldName, $targetMetadata->getClassName(), $inversedBy);
                } elseif ($association instanceof ManyToOneAssociationMetadata && ! $targetAssociation instanceof OneToManyAssociationMetadata) {
                    $message = 'If association %s#%s is many-to-one, then the inversed side %s#%s has to be one-to-many.';

                    $ce[] = sprintf($message, $class->getClassName(), $fieldName, $targetMetadata->getClassName(), $inversedBy);
                } elseif ($association instanceof ManyToManyAssociationMetadata && ! $targetAssociation instanceof ManyToManyAssociationMetadata) {
                    $message = 'If association %s#%s is many-to-many, then the inversed side %s#%s has to be many-to-many as well.';

                    $ce[] = sprintf($message, $class->getClassName(), $fieldName, $targetMetadata->getClassName(), $inversedBy);
                }
            }
        }

        if ($association->isOwningSide()) {
            if ($association instanceof ManyToManyAssociationMetadata) {
                $classIdentifierColumns  = array_keys($class->getIdentifierColumns($this->em));
                $targetIdentifierColumns = array_keys($targetMetadata->getIdentifierColumns($this->em));
                $joinTable               = $association->getJoinTable();

                foreach ($joinTable->getJoinColumns() as $joinColumn) {
                    if (! in_array($joinColumn->getReferencedColumnName(), $classIdentifierColumns, true)) {
                        $message = "The referenced column name '%s' has to be a primary key column on the target entity class '%s'.";

                        $ce[] = sprintf($message, $joinColumn->getReferencedColumnName(), $class->getClassName());
                        break;
                    }
                }

                foreach ($joinTable->getInverseJoinColumns() as $inverseJoinColumn) {
                    if (! in_array($inverseJoinColumn->getReferencedColumnName(), $targetIdentifierColumns, true)) {
                        $message = "The referenced column name '%s' has to be a primary key column on the target entity class '%s'.";

                        $ce[] = sprintf($message, $inverseJoinColumn->getReferencedColumnName(), $targetMetadata->getClassName());
                        break;
                    }
                }

                if (count($targetIdentifierColumns) !== count($joinTable->getInverseJoinColumns())) {
                    $columnNames = array_map(
                        static function (JoinColumnMetadata $joinColumn) {
                            return $joinColumn->getReferencedColumnName();
                        },
                        $joinTable->getInverseJoinColumns()
                    );

                    $columnString = implode("', '", array_diff($targetIdentifierColumns, $columnNames));
                    $message      = "The inverse join columns of the many-to-many table '%s' have to contain to ALL "
                        . "identifier columns of the target entity '%s', however '%s' are missing.";

                    $ce[] = sprintf($message, $joinTable->getName(), $targetMetadata->getClassName(), $columnString);
                }

                if (count($classIdentifierColumns) !== count($joinTable->getJoinColumns())) {
                    $columnNames = array_map(
                        static function (JoinColumnMetadata $joinColumn) {
                            return $joinColumn->getReferencedColumnName();
                        },
                        $joinTable->getJoinColumns()
                    );

                    $columnString = implode("', '", array_diff($classIdentifierColumns, $columnNames));
                    $message      = "The join columns of the many-to-many table '%s' have to contain to ALL "
                        . "identifier columns of the source entity '%s', however '%s' are missing.";

                    $ce[] = sprintf($message, $joinTable->getName(), $class->getClassName(), $columnString);
                }
            } elseif ($association instanceof ToOneAssociationMetadata) {
                $identifierColumns = array_keys($targetMetadata->getIdentifierColumns($this->em));
                $joinColumns       = $association->getJoinColumns();

                foreach ($joinColumns as $joinColumn) {
                    if (! in_array($joinColumn->getReferencedColumnName(), $identifierColumns, true)) {
                        $message = "The referenced column name '%s' has to be a primary key column on the target entity class '%s'.";

                        $ce[] = sprintf($message, $joinColumn->getReferencedColumnName(), $targetMetadata->getClassName());
                    }
                }

                if (count($identifierColumns) !== count($joinColumns)) {
                    $ids = [];

                    foreach ($joinColumns as $joinColumn) {
                        $ids[] = $joinColumn->getColumnName();
                    }

                    $columnString = implode("', '", array_diff($identifierColumns, $ids));
                    $message      = "The join columns of the association '%s' have to match to ALL "
                        . "identifier columns of the target entity '%s', however '%s' are missing.";

                    $ce[] = sprintf($message, $fieldName, $targetMetadata->getClassName(), $columnString);
                }
            }
        }

        if ($association instanceof ToManyAssociationMetadata && $association->getOrderBy()) {
            foreach ($association->getOrderBy() as $orderField => $orientation) {
                $targetProperty = $targetMetadata->getProperty($orderField);

                if ($targetProperty instanceof FieldMetadata) {
                    continue;
                }

                if (! ($targetProperty instanceof AssociationMetadata)) {
                    $message = "The association %s#%s is ordered by a property '%s' that is non-existing field on the target entity '%s'.";

                    $ce[] = sprintf($message, $class->getClassName(), $fieldName, $orderField, $targetMetadata->getClassName());
                    continue;
                }

                if ($targetProperty instanceof ToManyAssociationMetadata) {
                    $message = "The association %s#%s is ordered by a property '%s' on '%s' that is a collection-valued association.";

                    $ce[] = sprintf($message, $class->getClassName(), $fieldName, $orderField, $targetMetadata->getClassName());
                    continue;
                }

                if ($targetProperty instanceof AssociationMetadata && ! $targetProperty->isOwningSide()) {
                    $message = "The association %s#%s is ordered by a property '%s' on '%s' that is the inverse side of an association.";

                    $ce[] = sprintf($message, $class->getClassName(), $fieldName, $orderField, $targetMetadata->getClassName());
                    continue;
                }
            }
        }

        return $ce;
    }

    /**
     * Checks if the Database Schema is in sync with the current metadata state.
     *
     * @return bool
     */
    public function schemaInSyncWithMetadata()
    {
        $schemaTool  = new SchemaTool($this->em);
        $allMetadata = $this->em->getMetadataFactory()->getAllMetadata();

        return count($schemaTool->getUpdateSchemaSql($allMetadata, true)) === 0;
    }
}
