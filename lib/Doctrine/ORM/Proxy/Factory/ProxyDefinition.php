<?php

declare(strict_types = 1);

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

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
        $identifier    = $classMetadata->getIdentifierValues($proxy);
        $original      = $this->entityPersister->loadById($identifier, $proxy);

        if (null === $original) {
            $proxy->__setInitialized(false);

            throw EntityNotFoundException::fromClassNameAndIdentifier(
                $classMetadata->getName(), $identifier
            );

            // @todo guilhermeblanco Move the flattening identifier to Persisters
            /*$identifierFlattener = new IdentifierFlattener(
                $this->entityManager->getUnitOfWork(),
                $this->entityManager->getMetadataFactory()
            );

            throw EntityNotFoundException::fromClassNameAndIdentifier(
                $classMetadata->getName(),
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
        $identifier    = $classMetadata->getIdentifierValues($proxy);
        $original      = $this->entityPersister->loadById($identifier);

        if (null === $original) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(
                $classMetadata->getName(), $identifier
            );

            // @todo guilhermeblanco Move the flattening identifier to Persisters
            /*$identifierFlattener = new IdentifierFlattener(
                $this->entityManager->getUnitOfWork(),
                $this->entityManager->getMetadataFactory()
            );

            throw EntityNotFoundException::fromClassNameAndIdentifier(
                $classMetadata->getName(),
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
