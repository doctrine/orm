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

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping;

class ClassMetadataExporter implements Exporter
{
    const VARIABLE = '$this';

    /**
     * {@inheritdoc}
     */
    public function export($value, int $indentationLevel = 0) : string
    {
        /** @var Mapping\ClassMetadata $value */
        $reflectionClass = $value->getReflectionClass();
        $namespace       = $reflectionClass->getNamespaceName();
        $lines           = [];

        $lines[] = '<?php';
        $lines[] = null;

        if ($namespace) {
            $lines[] = 'namespace ' . $namespace . ';';
            $lines[] = null;
        }

        $shortClassName    = $reflectionClass->getShortName();
        $extendedClassName = ($value instanceof Mapping\MappedSuperClassMetadata)
            ? 'MappedSuperClassMetadata'
            : 'ClassMetadata'
        ;

        $lines[] = 'use Doctrine\DBAL\Types\Type;';
        $lines[] = 'use Doctrine\ORM\Mapping;';
        $lines[] = null;
        $lines[] = sprintf('class %sClassMetadata extends Mapping\%s', $shortClassName, $extendedClassName);
        $lines[] = '{';
        $lines[] = $this->exportClassBody($value, $indentationLevel + 1);
        $lines[] = '}';
        $lines[] = null;

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param Mapping\ClassMetadata $metadata
     * @param int                   $indentationLevel
     *
     * @return string
     */
    private function exportClassBody(Mapping\ClassMetadata $metadata, int $indentationLevel) : string
    {
        $lines = [];

        $lines[] = $this->exportConstructor($metadata, $indentationLevel);
        $lines[] = null;
        $lines[] = $this->exportInitialize($metadata, $indentationLevel);

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param Mapping\ClassMetadata $metadata
     * @param int                   $indentationLevel
     *
     * @return string
     */
    private function exportConstructor(Mapping\ClassMetadata $metadata, int $indentationLevel) : string
    {
        $indentation     = str_repeat(self::INDENTATION, $indentationLevel);
        $bodyIndentation = str_repeat(self::INDENTATION, $indentationLevel + 1);
        $lines           = [];

        $lines[] = $indentation . 'public function __construct(?ClassMetadata $parent = null)';
        $lines[] = $indentation . '{';
        $lines[] = $bodyIndentation . 'parent::__construct("' . $metadata->getClassName() . '", $parent);';
        $lines[] = $indentation . '}';

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param Mapping\ClassMetadata $metadata
     * @param int                   $indentationLevel
     *
     * @return string
     */
    private function exportInitialize(Mapping\ClassMetadata $metadata, int $indentationLevel) : string
    {
        $indentation     = str_repeat(self::INDENTATION, $indentationLevel);
        $bodyIndentation = str_repeat(self::INDENTATION, $indentationLevel + 1);
        $objectReference = $bodyIndentation . static::VARIABLE;
        $lines           = [];

        $lines[] = $indentation . 'protected function initialize()';
        $lines[] = $indentation . '{';

        if ($metadata->getCustomRepositoryClassName()) {
            $lines[] = $objectReference . '->setCustomRepositoryClassName("' . $metadata->getCustomRepositoryClassName() . '");';
            $lines[] = null;
        }

        if ($metadata->changeTrackingPolicy) {
            $lines[] = $objectReference . '->setChangeTrackingPolicy(Mapping\ChangeTrackingPolicy::' . strtoupper($metadata->changeTrackingPolicy) . ');';
            $lines[] = null;
        }

        if ($metadata->inheritanceType) {
            $lines[] = $objectReference . '->setInheritanceType(Mapping\InheritanceType::' . strtoupper($metadata->inheritanceType) . ');';
            $lines[] = null;
        }

        if ($metadata->table) {
            $lines[] = $bodyIndentation . '// Table';
            $lines[] = $this->exportTableMetadata($metadata->table, $indentationLevel + 1);
            $lines[] = null;
        }

        if ($metadata->discriminatorColumn) {
            $lines[] = $bodyIndentation . '// Discriminator mapping';
            $lines[] = $this->exportDiscriminatorMetadata($metadata, $indentationLevel + 1);
            $lines[] = null;
        }

        if ($metadata->lifecycleCallbacks) {
            $lines[] = $bodyIndentation . '// Lifecycle callbacks';

            foreach ($metadata->lifecycleCallbacks as $event => $callbacks) {
                foreach ($callbacks as $callback) {
                    $lines[] = $objectReference . '->addLifecycleCallback("' . $callback . '", "' . $event . '");';
                }
            }

            $lines[] = null;
        }

        foreach ($metadata->getProperties() as $name => $property) {
            $lines[] = $bodyIndentation . '// Property: ' . $name;
            $lines[] = $this->exportProperty($property, $indentationLevel + 1);
            $lines[] = null;
        }

        $lines[] = $indentation . '}';

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param Mapping\ClassMetadata $metadata
     * @param int                   $indentationLevel
     *
     * @return string
     */
    private function exportDiscriminatorMetadata(Mapping\ClassMetadata $metadata, int $indentationLevel) : string
    {
        $variableExporter      = new VariableExporter();
        $discriminatorExporter = new DiscriminatorColumnMetadataExporter();
        $indentation           = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference       = $indentation . static::VARIABLE;
        $lines                 = [];

        $lines[] = $discriminatorExporter->export($metadata->discriminatorColumn, $indentationLevel);
        $lines[] = null;
        $lines[] = $objectReference . '->setDiscriminatorColumn(' . $discriminatorExporter::VARIABLE . ');';

        if ($metadata->discriminatorMap) {
            $discriminatorMap = $variableExporter->export($metadata->discriminatorMap, $indentationLevel + 1);

            $lines[] = $objectReference . '->setDiscriminatorMap(' . $discriminatorMap . ');';
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param Mapping\TableMetadata $table
     * @param int                   $indentationLevel
     *
     * @return string
     */
    private function exportTableMetadata(Mapping\TableMetadata $table, int $indentationLevel) : string
    {
        $tableExporter   = new TableMetadataExporter();
        $indentation     = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference = $indentation . static::VARIABLE;
        $lines           = [];

        $lines[] = $tableExporter->export($table, $indentationLevel);
        $lines[] = null;
        $lines[] = $objectReference . '->setTable(' . $tableExporter::VARIABLE . ');';

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param Mapping\Property $property
     * @param int              $indentationLevel
     *
     * @return string
     */
    private function exportProperty(Mapping\Property $property, int $indentationLevel) : string
    {
        $indentation     = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference = $indentation . static::VARIABLE;
        $lines           = [];

        switch (true) {
            case ($property instanceof Mapping\VersionFieldMetadata):
                $propertyExporter = new VersionFieldMetadataExporter();
                break;

            case ($property instanceof Mapping\FieldMetadata):
                $propertyExporter = new FieldMetadataExporter();
                break;

            case ($property instanceof Mapping\OneToOneAssociationMetadata):
                $propertyExporter = new OneToOneAssociationMetadataExporter();
                break;

            case ($property instanceof Mapping\OneToManyAssociationMetadata):
                $propertyExporter = new OneToManyAssociationMetadataExporter();
                break;

            case ($property instanceof Mapping\ManyToOneAssociationMetadata):
                $propertyExporter = new ManyToOneAssociationMetadataExporter();
                break;

            case ($property instanceof Mapping\ManyToManyAssociationMetadata):
                $propertyExporter = new ManyToManyAssociationMetadataExporter();
                break;

            default:
                $propertyExporter = new TransientMetadataExporter();
                break;
        }

        $lines[] = $propertyExporter->export($property, $indentationLevel);
        $lines[] = null;
        $lines[] = $objectReference . '->addProperty(' . $propertyExporter::VARIABLE . ');';

        return implode(PHP_EOL, $lines);
    }
}
