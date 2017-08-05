<?php


declare(strict_types=1);

namespace Doctrine\ORM\Proxy\Factory;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Property;
use Doctrine\ORM\Mapping\TransientMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\Proxy\Proxy;

/**
 * Definition structure how to create a proxy.
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class ProxyDefinition
{
    /**
     * @var ClassMetadata
     */
    public $entityClassMetadata;

    /**
     * @var EntityPersister
     */
    public $entityPersister;

    /**
     * @var string
     */
    public $proxyClassName;

    /**
     * @var array|null
     */
    private $lazyPropertyList;

    /**
     * @param ClassMetadata   $entityClassMetadata
     * @param EntityPersister $entityPersister
     * @param string          $proxyClassName
     */
    public function __construct(
        ClassMetadata $entityClassMetadata,
        EntityPersister $entityPersister,
        string $proxyClassName
    )
    {
        $this->entityClassMetadata = $entityClassMetadata;
        $this->entityPersister     = $entityPersister;
        $this->proxyClassName      = $proxyClassName;
    }

    /**
     * Method responsible for loading properties in the proxy object.
     * This should accept 3 parameters:
     *  - $proxy: the proxy object to be initialized
     *  - $method: the method that triggered the initialization process
     *  - $parameters: an array of ordered parameters that were passed to that method
     *
     * @param Proxy $proxy
     *
     * @throws EntityNotFoundException
     */
    public function initializer(Proxy $proxy) : void
    {
        if ($proxy->__isInitialized()) {
            return;
        }

        $proxy->__setInitialized(true);

        $lazyPropertyList   = $this->getLazyPublicPropertyList();
        $existingProperties = get_object_vars($proxy); // Do not override properties manually set.

        foreach ($lazyPropertyList as $propertyName => $defaultValue) {
            if (! array_key_exists($propertyName, $existingProperties)) {
                $proxy->$propertyName = $defaultValue;
            }
        }

        if (method_exists($proxy, '__wakeup')) {
            $proxy->__wakeup();
        }

        $classMetadata = $this->entityClassMetadata;
        $identifier    = $this->entityPersister->getIdentifier($proxy);
        $original      = $this->entityPersister->loadById($identifier, $proxy);

        if (null === $original) {
            $proxy->__setInitialized(false);

            throw EntityNotFoundException::fromClassNameAndIdentifier(
                $classMetadata->getClassName(), $identifier
            );

            // @todo guilhermeblanco Move the flattening identifier to Persisters
            /*$identifierFlattener = new IdentifierFlattener(
                $this->entityManager->getUnitOfWork(),
                $this->entityManager->getMetadataFactory()
            );

            throw EntityNotFoundException::fromClassNameAndIdentifier(
                $classMetadata->getClassName(),
                $identifierFlattener->flattenIdentifier($classMetadata, $identifier)
            );*/
        }
    }

    /**
     * Method responsible of loading properties that need to be copied in the cloned object.
     * This should accept a single parameter, which is the cloned proxy instance itself.
     *
     * @param Proxy $proxy
     *
     * @throws EntityNotFoundException
     */
    public function cloner(Proxy $proxy) : void
    {
        if ($proxy->__isInitialized()) {
            return;
        }

        $proxy->__setInitialized(true);

        $classMetadata = $this->entityClassMetadata;
        $identifier    = $this->entityPersister->getIdentifier($proxy);
        $original      = $this->entityPersister->loadById($identifier);

        if (null === $original) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(
                $classMetadata->getClassName(), $identifier
            );

            // @todo guilhermeblanco Move the flattening identifier to Persisters
            /*$identifierFlattener = new IdentifierFlattener(
                $this->entityManager->getUnitOfWork(),
                $this->entityManager->getMetadataFactory()
            );

            throw EntityNotFoundException::fromClassNameAndIdentifier(
                $classMetadata->getClassName(),
                $identifierFlattener->flattenIdentifier($classMetadata, $identifier)
            );*/
        }

        foreach ($classMetadata->getProperties() as $property) {
            /** @var Property $property */
            $property->setValue($proxy, $property->getValue($original));
        }
    }

    /**
     * Generates the list of public properties to be lazy loaded, with their default values.
     *
     * @return array<string, mixed>
     */
    public function getLazyPublicPropertyList() : array
    {
        if (null !== $this->lazyPropertyList) {
            return $this->lazyPropertyList;
        }

        $reflectionClass     = $this->entityClassMetadata->getReflectionClass();
        $defaultPropertyList = $reflectionClass->getDefaultProperties();
        $lazyPropertyList    = [];

        foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
            if ($reflectionProperty->isStatic()) {
                continue;
            }

            $propertyName = $reflectionProperty->getName();
            $property     = $this->entityClassMetadata->getProperty($propertyName);

            if (! $property || $property instanceof TransientMetadata || $property->isPrimaryKey()) {
                continue;
            }

            $lazyPropertyList[$propertyName] = $defaultPropertyList[$propertyName];
        }

        return $this->lazyPropertyList = $lazyPropertyList;
    }
}
