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

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

class NewAnnotationDriver implements MappingDriver
{
    static protected $entityAnnotationClasses = [
        Annotation\Entity::class           => 1,
        Annotation\MappedSuperclass::class => 2,
    ];

    /**
     * The Annotation reader.
     *
     * @var AnnotationReader
     */
    protected $reader;

    /**
     * The file locator.
     *
     * @var FileLocator
     */
    protected $locator;

    /**
     * Cache for AnnotationDriver#getAllClassNames().
     *
     * @var array|null
     */
    private $classNames;

    /**
     * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading docblock annotations.
     *
     * @param AnnotationReader  $reader  The AnnotationReader to use, duck-typed.
     * @param FileLocator       $locator A FileLocator or one/multiple paths where mapping documents can be found.
     */
    public function __construct(AnnotationReader $reader, FileLocator $locator)
    {
        $this->reader  = $reader;
        $this->locator = $locator;
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        // IMPORTANT: We're handling $metadata as "parent" metadata here, while building the $className ClassMetadata.
        $reflectionClass  = new \ReflectionClass($className);
        $classAnnotations = $this->getClassAnnotations($reflectionClass);
        $classBuilder     = null;

        // Evaluate Entity annotation
        switch (true) {
            case isset($classAnnotations[Annotation\Entity::class]):
                $entityAnnot  = $classAnnotations[Annotation\Entity::class];
                $classBuilder = new ClassMetadataBuilder();

                if ($entityAnnot->repositoryClass !== null) {
                    $builder->withCustomRepositoryClass($entityAnnot->repositoryClass);
                }

                if ($entityAnnot->readOnly) {
                    $builder->asReadOnly();
                }

                break;

            case isset($classAnnotations[Annotation\MappedSuperclass::class]):
                $mappedSuperclassAnnot = $classAnnotations[Annotation\MappedSuperclass::class];

                $builder->withCustomRepositoryClass($mappedSuperclassAnnot->repositoryClass);
                $builder->asMappedSuperClass();
                break;

            case isset($classAnnotations[Annotation\Embeddable::class]):
                $builder->asEmbeddable();
                break;

            default:
                throw Mapping\MappingException::classIsNotAValidEntityOrMappedSuperClass($className);
        }

        return $classBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllClassNames()
    {
        if ($this->classNames !== null) {
            return $this->classNames;
        }

        $classNames = array_filter(
            $this->locator->getAllClassNames(null),
            function ($className) {
                return ! $this->isTransient($className);
            }
        );

        $this->classNames = $classNames;

        return $classNames;
    }

    /**
     * {@inheritdoc}
     */
    public function isTransient($className)
    {
        $reflectionClass  = new \ReflectionClass($className);
        $classAnnotations = $this->reader->getClassAnnotations($reflectionClass);

        foreach ($classAnnotations as $annotation) {
            if (isset(self::$entityAnnotationClasses[get_class($annotation)])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     *
     * @return array
     */
    private function getClassAnnotations(\ReflectionClass $reflectionClass)
    {
        $classAnnotations = $this->reader->getClassAnnotations($reflectionClass);

        foreach ($classAnnotations as $key => $annot) {
            if (! is_numeric($key)) {
                continue;
            }

            $classAnnotations[get_class($annot)] = $annot;
        }

        return $classAnnotations;
    }

    /**
     * @param \ReflectionProperty $reflectionProperty
     *
     * @return array
     */
    private function getPropertyAnnotations(\ReflectionProperty $reflectionProperty)
    {
        $propertyAnnotations = $this->reader->getPropertyAnnotations($reflectionProperty);

        foreach ($propertyAnnotations as $key => $annot) {
            if (! is_numeric($key)) {
                continue;
            }

            $propertyAnnotations[get_class($annot)] = $annot;
        }

        return$propertyAnnotations;
    }
}