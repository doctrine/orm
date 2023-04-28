<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\Mapping\ClassMetadata;
use InvalidArgumentException;

use function array_map;
use function count;
use function func_get_arg;
use function func_num_args;
use function get_debug_type;
use function gettype;
use function implode;
use function method_exists;
use function reset;
use function spl_object_id;
use function sprintf;

/**
 * Contains exception messages for all invalid lifecycle state exceptions inside UnitOfWork
 *
 * @psalm-import-type AssociationMapping from ClassMetadata
 */
class ORMInvalidArgumentException extends InvalidArgumentException
{
    /**
     * @param object $entity
     *
     * @return ORMInvalidArgumentException
     */
    public static function scheduleInsertForManagedEntity($entity)
    {
        return new self('A managed+dirty entity ' . self::objToStr($entity) . ' can not be scheduled for insertion.');
    }

    /**
     * @param object $entity
     *
     * @return ORMInvalidArgumentException
     */
    public static function scheduleInsertForRemovedEntity($entity)
    {
        return new self('Removed entity ' . self::objToStr($entity) . ' can not be scheduled for insertion.');
    }

    /**
     * @param object $entity
     *
     * @return ORMInvalidArgumentException
     */
    public static function scheduleInsertTwice($entity)
    {
        return new self('Entity ' . self::objToStr($entity) . ' can not be scheduled for insertion twice.');
    }

    /**
     * @param string $className
     * @param object $entity
     *
     * @return ORMInvalidArgumentException
     */
    public static function entityWithoutIdentity($className, $entity)
    {
        return new self(
            "The given entity of type '" . $className . "' (" . self::objToStr($entity) . ') has no identity/no ' .
            'id values set. It cannot be added to the identity map.'
        );
    }

    /**
     * @param object $entity
     *
     * @return ORMInvalidArgumentException
     */
    public static function readOnlyRequiresManagedEntity($entity)
    {
        return new self('Only managed entities can be marked or checked as read only. But ' . self::objToStr($entity) . ' is not');
    }

    /**
     * @psalm-param non-empty-list<array{AssociationMapping, object}> $newEntitiesWithAssociations non-empty an array
     *                                                                of [array $associationMapping, object $entity] pairs
     *
     * @return ORMInvalidArgumentException
     */
    public static function newEntitiesFoundThroughRelationships($newEntitiesWithAssociations)
    {
        $errorMessages = array_map(
            static function (array $newEntityWithAssociation): string {
                [$associationMapping, $entity] = $newEntityWithAssociation;

                return self::newEntityFoundThroughRelationshipMessage($associationMapping, $entity);
            },
            $newEntitiesWithAssociations
        );

        if (count($errorMessages) === 1) {
            return new self(reset($errorMessages));
        }

        return new self(
            'Multiple non-persisted new entities were found through the given association graph:'
            . "\n\n * "
            . implode("\n * ", $errorMessages)
        );
    }

    /**
     * @param object $entry
     * @psalm-param AssociationMapping $associationMapping
     *
     * @return ORMInvalidArgumentException
     */
    public static function newEntityFoundThroughRelationship(array $associationMapping, $entry)
    {
        return new self(self::newEntityFoundThroughRelationshipMessage($associationMapping, $entry));
    }

    /**
     * @param object $entry
     * @psalm-param AssociationMapping $assoc
     *
     * @return ORMInvalidArgumentException
     */
    public static function detachedEntityFoundThroughRelationship(array $assoc, $entry)
    {
        return new self('A detached entity of type ' . $assoc['targetEntity'] . ' (' . self::objToStr($entry) . ') '
            . " was found through the relationship '" . $assoc['sourceEntity'] . '#' . $assoc['fieldName'] . "' "
            . 'during cascading a persist operation.');
    }

    /**
     * @param object $entity
     *
     * @return ORMInvalidArgumentException
     */
    public static function entityNotManaged($entity)
    {
        return new self('Entity ' . self::objToStr($entity) . ' is not managed. An entity is managed if its fetched ' .
            'from the database or registered as new through EntityManager#persist');
    }

    /**
     * @param object $entity
     * @param string $operation
     *
     * @return ORMInvalidArgumentException
     */
    public static function entityHasNoIdentity($entity, $operation)
    {
        return new self('Entity has no identity, therefore ' . $operation . ' cannot be performed. ' . self::objToStr($entity));
    }

    /**
     * @param object $entity
     * @param string $operation
     *
     * @return ORMInvalidArgumentException
     */
    public static function entityIsRemoved($entity, $operation)
    {
        return new self('Entity is removed, therefore ' . $operation . ' cannot be performed. ' . self::objToStr($entity));
    }

    /**
     * @param object $entity
     * @param string $operation
     *
     * @return ORMInvalidArgumentException
     */
    public static function detachedEntityCannot($entity, $operation)
    {
        return new self('Detached entity ' . self::objToStr($entity) . ' cannot be ' . $operation);
    }

    /**
     * @param string $context
     * @param mixed  $given
     * @param int    $parameterIndex
     *
     * @return ORMInvalidArgumentException
     */
    public static function invalidObject($context, $given, $parameterIndex = 1)
    {
        return new self($context . ' expects parameter ' . $parameterIndex .
            ' to be an entity object, ' . gettype($given) . ' given.');
    }

    /** @return ORMInvalidArgumentException */
    public static function invalidCompositeIdentifier()
    {
        return new self('Binding an entity with a composite primary key to a query is not supported. ' .
            'You should split the parameter into the explicit fields and bind them separately.');
    }

    /** @return ORMInvalidArgumentException */
    public static function invalidIdentifierBindingEntity(/* string $class */)
    {
        if (func_num_args() === 0) {
            Deprecation::trigger(
                'doctrine/orm',
                'https://github.com/doctrine/orm/pull/9642',
                'Omitting the class name in the exception method %s is deprecated.',
                __METHOD__
            );

            return new self('Binding entities to query parameters only allowed for entities that have an identifier.');
        }

        return new self(sprintf(
            <<<'EXCEPTION'
Binding entities to query parameters only allowed for entities that have an identifier.
Class "%s" does not have an identifier.
EXCEPTION
            ,
            func_get_arg(0)
        ));
    }

    /**
     * @param AssociationMapping $assoc
     * @param mixed              $actualValue
     *
     * @return self
     */
    public static function invalidAssociation(ClassMetadata $targetClass, $assoc, $actualValue)
    {
        $expectedType = $targetClass->getName();

        return new self(sprintf(
            'Expected value of type "%s" for association field "%s#$%s", got "%s" instead.',
            $expectedType,
            $assoc['sourceEntity'],
            $assoc['fieldName'],
            get_debug_type($actualValue)
        ));
    }

    /**
     * Used when a given entityName hasn't the good type
     *
     * @deprecated This method will be removed in 3.0.
     *
     * @param mixed $entityName The given entity (which shouldn't be a string)
     *
     * @return self
     */
    public static function invalidEntityName($entityName)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9471',
            '%s() is deprecated',
            __METHOD__
        );

        return new self(sprintf('Entity name must be a string, %s given', get_debug_type($entityName)));
    }

    /**
     * Helper method to show an object as string.
     *
     * @param object $obj
     */
    private static function objToStr($obj): string
    {
        return method_exists($obj, '__toString') ? (string) $obj : get_debug_type($obj) . '@' . spl_object_id($obj);
    }

    /**
     * @param object $entity
     * @psalm-param AssociationMapping $associationMapping
     */
    private static function newEntityFoundThroughRelationshipMessage(array $associationMapping, $entity): string
    {
        return 'A new entity was found through the relationship \''
            . $associationMapping['sourceEntity'] . '#' . $associationMapping['fieldName'] . '\' that was not'
            . ' configured to cascade persist operations for entity: ' . self::objToStr($entity) . '.'
            . ' To solve this issue: Either explicitly call EntityManager#persist()'
            . ' on this unknown entity or configure cascade persist'
            . ' this association in the mapping for example @ManyToOne(..,cascade={"persist"}).'
            . (method_exists($entity, '__toString')
                ? ''
                : ' If you cannot find out which entity causes the problem implement \''
                . $associationMapping['targetEntity'] . '#__toString()\' to get a clue.'
            );
    }
}
