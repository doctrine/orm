<?php
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

namespace Doctrine\ORM\Proxy;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\Common\Proxy\ProxyGenerator;

/**
 * This factory is used to create proxy objects for entities at runtime.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 * @author Marco Pivetta <ocramius@gmail.com>
 * @since 2.0
 */
class ProxyFactory
{
    /**
     * @var EntityManager The EntityManager this factory is bound to.
     */
    private $em;

    /**
     * @var \Doctrine\ORM\UnitOfWork The UnitOfWork this factory uses to retrieve persisters
     */
    private $uow;

    /**
     * @var ProxyGenerator the proxy generator responsible for creating the proxy classes/files.
     */
    private $proxyGenerator;

    /**
     * @var bool Whether to automatically (re)generate proxy classes.
     */
    private $autoGenerate;

    /**
     * @var string
     */
    private $proxyNs;

    /**
     * @var string
     */
    private $proxyDir;

    /**
     * @var array definitions (indexed by requested class name) for the proxy classes.
     *            Each element is an array containing following items:
     *            "fqcn"             - FQCN of the proxy class
     *            "initializer"      - Closure to be used as proxy __initializer__
     *            "cloner"           - Closure to be used as proxy __cloner__
     *            "identifierFields" - list of field names for the identifiers
     *            "reflectionFields" - ReflectionProperties for the fields
     */
    private $definitions = array();

    /**
     * Initializes a new instance of the <tt>ProxyFactory</tt> class that is
     * connected to the given <tt>EntityManager</tt>.
     *
     * @param EntityManager $em           The EntityManager the new factory works for.
     * @param string        $proxyDir     The directory to use for the proxy classes. It must exist.
     * @param string        $proxyNs      The namespace to use for the proxy classes.
     * @param boolean       $autoGenerate Whether to automatically generate proxy classes.
     */
    public function __construct(EntityManager $em, $proxyDir, $proxyNs, $autoGenerate = false)
    {
        $this->em           = $em;
        $this->uow          = $em->getUnitOfWork();
        $this->proxyDir     = $proxyDir;
        $this->proxyNs      = $proxyNs;
        $this->autoGenerate = $autoGenerate;
    }

    /**
     * Gets a reference proxy instance for the entity of the given type and identified by
     * the given identifier.
     *
     * @param  string $className
     * @param  mixed  $identifier
     * @return object
     */
    public function getProxy($className, $identifier)
    {
        if ( ! isset($this->definitions[$className])) {
            $this->initProxyDefinitions($className);
        }

        $definition         = $this->definitions[$className];
        $fqcn               = $definition['fqcn'];
        $identifierFields   = $definition['identifierFields'];
        /* @var $reflectionFields \ReflectionProperty[] */
        $reflectionFields   = $definition['reflectionFields'];
        $proxy              = new $fqcn($definition['initializer'], $definition['cloner']);

        foreach ($identifierFields as $idField) {
            $reflectionFields[$idField]->setValue($proxy, $identifier[$idField]);
        }

        return $proxy;
    }

    /**
     * Generates proxy classes for all given classes.
     *
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata[] $classes The classes (ClassMetadata instances)
     *                                                                      for which to generate proxies.
     * @param string $proxyDir The target directory of the proxy classes. If not specified, the
     *                      directory configured on the Configuration of the EntityManager used
     *                      by this factory is used.
     * @return int Number of generated proxies.
     */
    public function generateProxyClasses(array $classes, $proxyDir = null)
    {
        $generated = 0;

        foreach ($classes as $class) {
            /* @var $class \Doctrine\ORM\Mapping\ClassMetadataInfo */
            if ($class->isMappedSuperclass || $class->getReflectionClass()->isAbstract()) {
                continue;
            }

            $generator = $this->getProxyGenerator();

            $proxyFileName = $generator->getProxyFileName($class->getName(), $proxyDir);
            $generator->generateProxyClass($class, $proxyFileName);
            $generated += 1;
        }

        return $generated;
    }

    /**
     * @param ProxyGenerator $proxyGenerator
     */
    public function setProxyGenerator(ProxyGenerator $proxyGenerator)
    {
        $this->proxyGenerator = $proxyGenerator;
    }

    /**
     * @return ProxyGenerator
     */
    public function getProxyGenerator()
    {
        if (null === $this->proxyGenerator) {
            $this->proxyGenerator = new ProxyGenerator($this->proxyDir, $this->proxyNs);
            $this->proxyGenerator->setPlaceholder('<baseProxyInterface>', 'Doctrine\ORM\Proxy\Proxy');
        }

        return $this->proxyGenerator;
    }

    /**
     * @param string $className
     */
    private function initProxyDefinitions($className)
    {
        $fqcn = ClassUtils::generateProxyClassName($className, $this->proxyNs);
        $classMetadata = $this->em->getClassMetadata($className);

        if ( ! class_exists($fqcn, false)) {
            $generator = $this->getProxyGenerator();
            $fileName = $generator->getProxyFileName($className);

            if ($this->autoGenerate) {
                $generator->generateProxyClass($classMetadata);
            }

            require $fileName;
        }

        $entityPersister = $this->uow->getEntityPersister($className);

        if ($classMetadata->getReflectionClass()->hasMethod('__wakeup')) {
            $initializer = function (Proxy $proxy) use ($entityPersister, $classMetadata) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                if ($proxy->__isInitialized()) {
                    return;
                }

                $properties = $proxy->__getLazyProperties();

                foreach ($properties as $propertyName => $property) {
                    if (!isset($proxy->$propertyName)) {
                        $proxy->$propertyName = $properties[$propertyName];
                    }
                }

                $proxy->__setInitialized(true);
                $proxy->__wakeup();

                if (null === $entityPersister->load($classMetadata->getIdentifierValues($proxy), $proxy)) {
                    throw new EntityNotFoundException();
                }
            };
        } else {
            $initializer = function (Proxy $proxy) use ($entityPersister, $classMetadata) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                if ($proxy->__isInitialized()) {
                    return;
                }

                $properties = $proxy->__getLazyProperties();

                foreach ($properties as $propertyName => $property) {
                    if (!isset($proxy->$propertyName)) {
                        $proxy->$propertyName = $properties[$propertyName];
                    }
                }

                $proxy->__setInitialized(true);

                if (null === $entityPersister->load($classMetadata->getIdentifierValues($proxy), $proxy)) {
                    throw new EntityNotFoundException();
                }
            };
        }

        $cloner = function (Proxy $proxy) use ($entityPersister, $classMetadata) {
            if ($proxy->__isInitialized()) {
                return;
            }

            $proxy->__setInitialized(true);
            $proxy->__setInitializer(null);
            $class = $entityPersister->getClassMetadata();
            $original = $entityPersister->load($classMetadata->getIdentifierValues($proxy));

            if (null === $original) {
                throw new EntityNotFoundException();
            }

            foreach ($class->getReflectionClass()->getProperties() as $reflectionProperty) {
                $propertyName = $reflectionProperty->getName();

                if ($class->hasField($propertyName) || $class->hasAssociation($propertyName)) {
                    $reflectionProperty->setAccessible(true);
                    $reflectionProperty->setValue($proxy, $reflectionProperty->getValue($original));
                }
            }
        };

        $this->definitions[$className] = array(
            'fqcn'             => $fqcn,
            'initializer'      => $initializer,
            'cloner'           => $cloner,
            'identifierFields' => $classMetadata->getIdentifierFieldNames(),
            'reflectionFields' => $classMetadata->getReflectionProperties(),
        );
    }
}
