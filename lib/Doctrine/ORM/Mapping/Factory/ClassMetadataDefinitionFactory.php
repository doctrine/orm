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

namespace Doctrine\ORM\Mapping\Factory;

use Doctrine\ORM\Mapping\ClassMetadata;

class ClassMetadataDefinitionFactory
{
    /**
     * @var ClassMetadataResolver
     */
    private $resolver;

    /**
     * @var ClassMetadataGeneratorStrategy
     */
    private $generatorStrategy;

    /**
     * ClassMetadataDefinitionFactory constructor.
     *
     * @param ClassMetadataResolver          $resolver
     * @param ClassMetadataGeneratorStrategy $generatorStrategy
     */
    public function __construct(ClassMetadataResolver $resolver, ClassMetadataGeneratorStrategy $generatorStrategy)
    {
        $this->resolver          = $resolver;
        $this->generatorStrategy = $generatorStrategy;
    }

    /**
     * @param string             $className
     * @param ClassMetadata|null $parentMetadata
     *
     * @return ClassMetadataDefinition
     */
    public function build(string $className, ?ClassMetadata $parentMetadata) : ClassMetadataDefinition
    {
        $definition = $this->createDefinition($className, $parentMetadata);

        if (! class_exists($definition->metadataClassName, false)) {
            $metadataClassPath = $this->resolver->resolveMetadataClassPath($className);

            $this->generatorStrategy->generate($metadataClassPath, $definition);
        }

        return $definition;
    }

    /**
     * @param string             $className
     * @param ClassMetadata|null $parentMetadata
     *
     * @return ClassMetadataDefinition
     */
    private function createDefinition(string $className, ?ClassMetadata $parentMetadata) : ClassMetadataDefinition
    {
        $metadataClassName = $this->resolver->resolveMetadataClassName($className);

        return new ClassMetadataDefinition($className, $metadataClassName, $parentMetadata);
    }
}
