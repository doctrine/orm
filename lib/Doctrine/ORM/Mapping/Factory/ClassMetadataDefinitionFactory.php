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

namespace Doctrine\ORM\Mapping\Factory;

use Doctrine\ORM\Mapping\Factory\AbstractClassMetadataFactory;

class ClassMetadataDefinitionFactory
{
    /**
     * @var ClassMetadataResolver
     */
    private $resolver;

    /**
     * @var ClassMetadataGenerator
     */
    private $generator;

    /**
     * @var int
     */
    private $autoGenerate;

    public function __construct(ClassMetadataResolver $resolver, ClassMetadataGenerator $generator, int $autoGenerate)
    {
        $this->resolver     = $resolver;
        $this->generator    = $generator;
        $this->autoGenerate = $autoGenerate;
    }

    /**
     * @param string $className
     *
     * @return ClassMetadataDefinition
     */
    public function build(string $className) : ClassMetadataDefinition
    {
        $metadataClassName = $this->resolver->resolveMetadataClassName($className);
        $definition        = new ClassMetadataDefinition($className, $metadataClassName);

        if (! class_exists($metadataClassName, false)) {
            $metadataClassPath = $this->resolver->resolveMetadataClassPath($className);

            switch ($this->autoGenerate) {
                case AbstractClassMetadataFactory::AUTOGENERATE_FILE_NOT_EXISTS:
                    if (! file_exists($metadataClassPath)) {
                        $this->generator->generate($metadataClassPath, $definition);
                    }

                    break;

                case AbstractClassMetadataFactory::AUTOGENERATE_ALWAYS:
                    $this->generator->generate($metadataClassPath, $definition);
                    break;

                case AbstractClassMetadataFactory::AUTOGENERATE_NEVER:
                    // Do nothing
                    break;
            }

            require $metadataClassPath;
        }

        return $definition;
    }
}