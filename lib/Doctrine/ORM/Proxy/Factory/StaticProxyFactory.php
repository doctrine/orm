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

use Doctrine\Common\Persistence\ObjectManagerAware;
use Doctrine\ORM\Configuration\ProxyConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;

/**
 * Static factory for proxy objects.
 *
 * @package Doctrine\ORM\Proxy\Factory
 * @since 3.0
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class StaticProxyFactory implements ProxyFactory
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var ProxyGenerator
     */
    protected $generator;

    /**
     * @var ProxyDefinitionFactory
     */
    protected $definitionFactory;

    /**
     * @var array<string, ProxyDefinition>
     */
    private $definitions = [];

    /**
     * ProxyFactory constructor.
     *
     * @param ProxyConfiguration $configuration
     */
    public function __construct(EntityManagerInterface $entityManager, ProxyConfiguration $configuration)
    {
        $resolver          = $configuration->getResolver();
        $autoGenerate      = $configuration->getAutoGenerate();
        $generator         = new ProxyGenerator($configuration->getDirectory(), $configuration->getNamespace());
        $definitionFactory = new ProxyDefinitionFactory($entityManager, $resolver, $generator, $autoGenerate);

        $generator->setPlaceholder('baseProxyInterface', Proxy::class);

        $this->entityManager     = $entityManager;
        $this->generator         = $generator;
        $this->definitionFactory = $definitionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function generateProxyClasses(array $classMetadataList) : int
    {
        $generated = 0;

        foreach ($classMetadataList as $classMetadata) {
            if ($classMetadata->isMappedSuperclass || $classMetadata->getReflectionClass()->isAbstract()) {
                continue;
            }

            $this->definitionFactory->build($classMetadata);

            $generated++;
        }

        return $generated;
    }

    /**
     * {@inheritdoc}
     */
    public function getProxy(string $className, array $identifier) : Proxy
    {
        $proxyDefinition    = $this->getOrCreateProxyDefinition($className);
        $proxyInstance      = $this->createProxyInstance($proxyDefinition);
        $proxyClassMetadata = $proxyDefinition->entityClassMetadata;

        $proxyClassMetadata->assignIdentifier($proxyInstance, $identifier);

        return $proxyInstance;
    }

    /**
     * @param ProxyDefinition $definition
     *
     * @return Proxy
     */
    protected function createProxyInstance(ProxyDefinition $definition) : Proxy
    {
        /** @var Proxy $classMetadata */
        $proxyClassName = $definition->proxyClassName;
        $proxyInstance  = new $proxyClassName($definition);

        return $proxyInstance;
    }

    /**
     * Create a proxy definition for the given class name.
     *
     * @param string $className
     *
     * @return ProxyDefinition
     */
    private function getOrCreateProxyDefinition(string $className) : ProxyDefinition
    {
        if (! isset($this->definitions[$className])) {
            $classMetadata = $this->entityManager->getClassMetadata($className);

            $this->definitions[$className] = $this->definitionFactory->build($classMetadata);
        }

        return $this->definitions[$className];
    }
}
