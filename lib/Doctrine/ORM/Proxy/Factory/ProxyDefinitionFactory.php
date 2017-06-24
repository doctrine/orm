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

declare(strict_types=1);

namespace Doctrine\ORM\Proxy\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Proxy\Factory\Strategy\ProxyGeneratorStrategy;

class ProxyDefinitionFactory
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ProxyResolver
     */
    private $resolver;

    /**
     * @var ProxyGeneratorStrategy
     */
    private $generatorStrategy;

    /**
     * @var int
     */
    private $autoGenerate;

    /**
     * ProxyDefinitionFactory constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ProxyResolver          $resolver
     * @param ProxyGeneratorStrategy $generatorStrategy
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ProxyResolver $resolver,
        ProxyGeneratorStrategy $generatorStrategy
    )
    {
        $this->entityManager     = $entityManager;
        $this->resolver          = $resolver;
        $this->generatorStrategy = $generatorStrategy;
    }

    /**
     * @param ClassMetadata $classMetadata
     *
     * @return ProxyDefinition
     */
    public function build(ClassMetadata $classMetadata) : ProxyDefinition
    {
        $definition = $this->createDefinition($classMetadata);

        if (! class_exists($definition->proxyClassName, false)) {
            $proxyClassPath = $this->resolver->resolveProxyClassPath($classMetadata->getClassName());

            $this->generatorStrategy->generate($proxyClassPath, $definition);
        }

        return $definition;
    }

    /**
     * @param ClassMetadata $classMetadata
     *
     * @return ProxyDefinition
     */
    private function createDefinition(ClassMetadata $classMetadata) : ProxyDefinition
    {
        $unitOfWork      = $this->entityManager->getUnitOfWork();
        $entityPersister = $unitOfWork->getEntityPersister($classMetadata->getClassName());
        $proxyClassName  = $this->resolver->resolveProxyClassName($classMetadata->getClassName());

        return new ProxyDefinition($classMetadata, $entityPersister, $proxyClassName);
    }
}
