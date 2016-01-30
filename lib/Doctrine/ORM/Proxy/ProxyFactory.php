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

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Doctrine\Common\Proxy\Exception\OutOfBoundsException;
use Doctrine\Common\Proxy\ProxyDefinition;
use Doctrine\Common\Proxy\ProxyGenerator;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\EntityNotFoundException;
use ProxyManager\Generator\ClassGenerator;
use ProxyManager\GeneratorStrategy\EvaluatingGeneratorStrategy;
use ProxyManager\Proxy\GhostObjectInterface;
use ProxyManager\ProxyGenerator\LazyLoadingGhostGenerator;
use ProxyManager\ProxyGenerator\Util\Properties;
use ReflectionClass;
use ReflectionProperty;

/**
 * This factory is used to create proxy objects for entities at runtime.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 * @author Marco Pivetta  <ocramius@gmail.com>
 * @since 2.0
 */
class ProxyFactory extends AbstractProxyFactory
{
    /**
     * @var EntityManagerInterface The EntityManager this factory is bound to.
     */
    private $em;

    /**
     * @var \Doctrine\ORM\UnitOfWork The UnitOfWork this factory uses to retrieve persisters
     */
    private $uow;

    /**
     * @var string
     */
    private $proxyNs;

    /**
     * The IdentifierFlattener used for manipulating identifiers
     *
     * @var \Doctrine\ORM\Utility\IdentifierFlattener
     */
    private $identifierFlattener;

    /**
     * Initializes a new instance of the <tt>ProxyFactory</tt> class that is
     * connected to the given <tt>EntityManager</tt>.
     *
     * @param EntityManagerInterface $em           The EntityManager the new factory works for.
     * @param string                 $proxyDir     The directory to use for the proxy classes. It must exist.
     * @param string                 $proxyNs      The namespace to use for the proxy classes.
     * @param boolean|int            $autoGenerate The strategy for automatically generating proxy classes. Possible
     *                                             values are constants of Doctrine\Common\Proxy\AbstractProxyFactory.
     */
    public function __construct(EntityManagerInterface $em, $proxyDir, $proxyNs, $autoGenerate = AbstractProxyFactory::AUTOGENERATE_NEVER)
    {
        $proxyGenerator = new ProxyGenerator($proxyDir, $proxyNs);

        $proxyGenerator->setPlaceholder('baseProxyInterface', 'Doctrine\ORM\Proxy\Proxy');
        parent::__construct($proxyGenerator, $em->getMetadataFactory(), $autoGenerate);

        $this->em      = $em;
        $this->uow     = $em->getUnitOfWork();
        $this->proxyNs = $proxyNs;
    }

    /**
     * {@inheritDoc}
     *
     * @return GhostObjectInterface
     */
    public function getProxy($className, array $identifier)
    {
        $definition = $this->createProxyDefinition($className);
        $fqcn       = $definition->proxyClassName;

        if (! class_exists($fqcn, false)) {
            $generatorStrategy    = new EvaluatingGeneratorStrategy();
            $proxyGenerator       = new ClassGenerator();
            $skippedProperties    = array_filter(
                Properties::fromReflectionClass(new ReflectionClass($className))->getInstanceProperties(),
                function (ReflectionProperty $property) use ($definition) {
                    return ! in_array(
                            $property->getName(),
                            array_map(
                                function (ReflectionProperty $property) {
                                    return $property->getName();
                                },
                                $definition->reflectionFields
                            )
                        )
                        || in_array($property->getName(), $definition->identifierFields);
                }
            );

            $proxyGenerator->setName($fqcn);

            (new LazyLoadingGhostGenerator())->generate(
                $this->em->getClassMetadata($className)->getReflectionClass(),
                $proxyGenerator,
                [
                    'skippedProperties' => array_map([$this, 'getInternalReflectionPropertyName'], $skippedProperties)
                ]
            );

            $generatorStrategy->generate($proxyGenerator);
        }

        $proxy = $fqcn::staticProxyConstructor($definition->initializer/*, $definition->cloner*/);

        foreach ($definition->identifierFields as $idField) {
            if (! isset($identifier[$idField])) {
                throw OutOfBoundsException::missingPrimaryKeyValue($className, $idField);
            }

            $definition->reflectionFields[$idField]->setValue($proxy, $identifier[$idField]);
        }

        return $proxy;
    }

    /**
     * Reset initialization/cloning logic for an un-initialized proxy
     *
     * @param GhostObjectInterface $proxy
     *
     * @return GhostObjectInterface
     *
     * @throws \Doctrine\Common\Proxy\Exception\InvalidArgumentException
     */
    public function resetUninitializedProxy(GhostObjectInterface $proxy)
    {
        if ($proxy->isProxyInitialized()) {
            throw InvalidArgumentException::unitializedProxyExpected($proxy);
        }

        $className  = ClassUtils::getClass($proxy);
        $definition = $this->createProxyDefinition($className);

        $proxy->setProxyInitializer($definition->initializer);

        return $proxy;
    }

    /**
     * {@inheritDoc}
     */
    protected function skipClass(ClassMetadata $metadata)
    {
        /* @var $metadata \Doctrine\ORM\Mapping\ClassMetadataInfo */
        return $metadata->isMappedSuperclass || $metadata->getReflectionClass()->isAbstract();
    }

    /**
     * {@inheritDoc}
     */
    protected function createProxyDefinition($className)
    {
        $classMetadata   = $this->em->getClassMetadata($className);
        $entityPersister = $this->uow->getEntityPersister($className);

        return new ProxyDefinition(
            ClassUtils::generateProxyClassName($className, $this->proxyNs),
            $classMetadata->getIdentifierFieldNames(),
            $classMetadata->getReflectionProperties(),
            $this->createInitializer($classMetadata, $entityPersister),
            function () {
            }
        );
    }

    /**
     * Creates a closure capable of initializing a proxy
     *
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata
     * @param \Doctrine\ORM\Persisters\Entity\EntityPersister    $entityPersister
     *
     * @return \Closure
     *
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    private function createInitializer(ClassMetadata $classMetadata, EntityPersister $entityPersister)
    {
        return function (
            GhostObjectInterface $proxy,
            $method,
            $parameters,
            & $initializer
        ) use (
            $entityPersister,
            $classMetadata
        ) {
            $initializerBkp = $initializer;
            $initializer    = null;

            if (! $initializerBkp) {
                return;
            }

            if (null === $entityPersister->loadById($classMetadata->getIdentifierValues($proxy), $proxy)) {
                $initializer = $initializerBkp;

                throw new EntityNotFoundException($classMetadata->getName());
            }
        };
    }

    /**
     * @param ReflectionProperty $reflectionProperty
     *
     * @return string
     */
    private function getInternalReflectionPropertyName(ReflectionProperty $reflectionProperty)
    {
        if ($reflectionProperty->isProtected()) {
            return "\0*\0" . $reflectionProperty->getName();
        }

        if ($reflectionProperty->isPrivate()) {
            return "\0" . $reflectionProperty->getDeclaringClass()->getName()
            . "\0" . $reflectionProperty->getName();
        }

        return $reflectionProperty->getName();
    }
}
