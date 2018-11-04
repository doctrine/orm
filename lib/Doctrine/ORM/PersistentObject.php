<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use BadMethodCallException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\ToManyAssociationMetadata;
use Doctrine\ORM\Mapping\ToOneAssociationMetadata;
use InvalidArgumentException;
use RuntimeException;
use function lcfirst;
use function substr;

/**
 * PersistentObject base class that implements getter/setter methods for all mapped fields and associations
 * by overriding __call.
 *
 * This class is a forward compatible implementation of the PersistentObject trait.
 *
 * Limitations:
 *
 * 1. All persistent objects have to be associated with a single EntityManager, multiple
 *    EntityManagers are not supported. You can set the EntityManager with `PersistentObject#setEntityManager()`.
 * 2. Setters and getters only work if a ClassMetadata instance was injected into the PersistentObject.
 *    This is either done on `postLoad` of an object or by accessing the global object manager.
 * 3. There are no hooks for setters/getters. Just implement the method yourself instead of relying on __call().
 * 4. Slower than handcoded implementations: An average of 7 method calls per access to a field and 11 for an association.
 * 5. Only the inverse side associations get autoset on the owning side as well. Setting objects on the owning side
 *    will not set the inverse side associations.
 *
 * @example
 *
 *  PersistentObject::setEntityManager($em);
 *
 *  class Foo extends PersistentObject
 *  {
 *      private $id;
 *  }
 *
 *  $foo = new Foo();
 *  $foo->getId(); // method exists through __call
 */
abstract class PersistentObject implements EntityManagerAware
{
    /** @var EntityManagerInterface|null */
    private static $entityManager = null;

    /** @var ClassMetadata|null */
    private $cm;

    /**
     * Sets the entity manager responsible for all persistent object base classes.
     */
    public static function setEntityManager(?EntityManagerInterface $entityManager = null)
    {
        self::$entityManager = $entityManager;
    }

    /**
     * @return EntityManagerInterface|null
     */
    public static function getEntityManager()
    {
        return self::$entityManager;
    }

    /**
     * Injects the Doctrine Object Manager.
     *
     * @throws RuntimeException
     */
    public function injectEntityManager(EntityManagerInterface $entityManager, ClassMetadata $classMetadata) : void
    {
        if ($entityManager !== self::$entityManager) {
            throw new RuntimeException(
                'Trying to use PersistentObject with different EntityManager instances. ' .
                'Was PersistentObject::setEntityManager() called?'
            );
        }

        $this->cm = $classMetadata;
    }

    /**
     * Sets a persistent fields value.
     *
     * @param string  $field
     * @param mixed[] $args
     *
     * @return object
     *
     * @throws BadMethodCallException   When no persistent field exists by that name.
     * @throws InvalidArgumentException When the wrong target object type is passed to an association.
     */
    private function set($field, $args)
    {
        $this->initializeDoctrine();

        $property = $this->cm->getProperty($field);

        if (! $property) {
            throw new BadMethodCallException("no field with name '" . $field . "' exists on '" . $this->cm->getClassName() . "'");
        }

        switch (true) {
            case $property instanceof FieldMetadata && ! $property->isPrimaryKey():
                $this->{$field} = $args[0];
                break;

            case $property instanceof ToOneAssociationMetadata:
                $targetClassName = $property->getTargetEntity();

                if ($args[0] !== null && ! ($args[0] instanceof $targetClassName)) {
                    throw new InvalidArgumentException("Expected persistent object of type '" . $targetClassName . "'");
                }

                $this->{$field} = $args[0];
                $this->completeOwningSide($property, $args[0]);
                break;
        }

        return $this;
    }

    /**
     * Gets a persistent field value.
     *
     * @param string $field
     *
     * @return mixed
     *
     * @throws BadMethodCallException When no persistent field exists by that name.
     */
    private function get($field)
    {
        $this->initializeDoctrine();

        $property = $this->cm->getProperty($field);

        if (! $property) {
            throw new BadMethodCallException("no field with name '" . $field . "' exists on '" . $this->cm->getClassName() . "'");
        }

        return $this->{$field};
    }

    /**
     * If this is an inverse side association, completes the owning side.
     *
     * @param object $targetObject
     */
    private function completeOwningSide(AssociationMetadata $property, $targetObject)
    {
        // add this object on the owning side as well, for obvious infinite recursion
        // reasons this is only done when called on the inverse side.
        if ($property->isOwningSide()) {
            return;
        }

        $mappedByField    = $property->getMappedBy();
        $targetMetadata   = self::$entityManager->getClassMetadata($property->getTargetEntity());
        $targetProperty   = $targetMetadata->getProperty($mappedByField);
        $setterMethodName = ($targetProperty instanceof ToManyAssociationMetadata ? 'add' : 'set') . $mappedByField;

        $targetObject->{$setterMethodName}($this);
    }

    /**
     * Adds an object to a collection.
     *
     * @param string  $field
     * @param mixed[] $args
     *
     * @return object
     *
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     */
    private function add($field, $args)
    {
        $this->initializeDoctrine();

        $property = $this->cm->getProperty($field);

        if (! $property) {
            throw new BadMethodCallException("no field with name '" . $field . "' exists on '" . $this->cm->getClassName() . "'");
        }

        if (! ($property instanceof ToManyAssociationMetadata)) {
            throw new BadMethodCallException('There is no method add' . $field . '() on ' . $this->cm->getClassName());
        }

        $targetClassName = $property->getTargetEntity();

        if (! ($args[0] instanceof $targetClassName)) {
            throw new InvalidArgumentException("Expected persistent object of type '" . $targetClassName . "'");
        }

        if (! ($this->{$field} instanceof Collection)) {
            $this->{$field} = new ArrayCollection($this->{$field} ?: []);
        }

        $this->{$field}->add($args[0]);

        $this->completeOwningSide($property, $args[0]);

        return $this;
    }

    /**
     * Initializes Doctrine Metadata for this class.
     *
     * @throws RuntimeException
     */
    private function initializeDoctrine()
    {
        if ($this->cm !== null) {
            return;
        }

        if (! self::$entityManager) {
            throw new RuntimeException('No runtime entity manager set. Call PersistentObject#setEntityManager().');
        }

        $this->cm = self::$entityManager->getClassMetadata(static::class);
    }

    /**
     * Magic methods.
     *
     * @param string  $method
     * @param mixed[] $args
     *
     * @return mixed
     *
     * @throws BadMethodCallException
     */
    public function __call($method, $args)
    {
        $command = substr($method, 0, 3);
        $field   = lcfirst(substr($method, 3));

        switch ($command) {
            case 'set':
                return $this->set($field, $args);

            case 'get':
                return $this->get($field);

            case 'add':
                return $this->add($field, $args);

            default:
                throw new BadMethodCallException('There is no method ' . $method . ' on ' . $this->cm->getClassName());
        }
    }
}
