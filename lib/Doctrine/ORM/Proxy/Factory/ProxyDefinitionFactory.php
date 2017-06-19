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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

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
     * @var ProxyGenerator
     */
    private $generator;

    /**
     * @var int
     */
    private $autoGenerate;

    /**
     * ProxyDefinitionFactory constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ProxyResolver          $resolver
     * @param ProxyGenerator         $generator
     * @param int                    $autoGenerate
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ProxyResolver $resolver,
        ProxyGenerator $generator,
        int $autoGenerate
    )
    {
        $this->entityManager = $entityManager;
        $this->resolver = $resolver;
        $this->generator = $generator;
        $this->autoGenerate = $autoGenerate;
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
            $proxyClassPath = $this->resolver->resolveProxyClassPath($classMetadata->getName());

            switch ($this->autoGenerate) {
                case ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS:
                    if (! file_exists($proxyClassPath)) {
                        $this->generator->generate($proxyClassPath, $definition);
                    }

                    require $proxyClassPath;

                    break;

                case ProxyFactory::AUTOGENERATE_ALWAYS:
                    $this->generator->generate($proxyClassPath, $definition);

                    require $proxyClassPath;

                    break;

                case ProxyFactory::AUTOGENERATE_EVAL:
                    $this->generator->generate(null, $definition);

                    break;

                case ProxyFactory::AUTOGENERATE_NEVER:
                    require $proxyClassPath;

                    break;
            }
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
        $entityPersister = $unitOfWork->getEntityPersister($classMetadata->getName());
        $proxyClassName  = $this->resolver->resolveProxyClassName($classMetadata->getName());

        return new ProxyDefinition($classMetadata, $entityPersister, $proxyClassName);
    }
}
