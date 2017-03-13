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

namespace Doctrine\ORM\Tools\Export\Driver;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\JoinColumnMetadata;
use Doctrine\ORM\Mapping\JoinTableMetadata;
use Doctrine\ORM\Mapping\ManyToManyAssociationMetadata;
use Doctrine\ORM\Mapping\ManyToOneAssociationMetadata;
use Doctrine\ORM\Mapping\OneToManyAssociationMetadata;
use Doctrine\ORM\Mapping\OneToOneAssociationMetadata;
use Doctrine\ORM\Mapping\ToOneAssociationMetadata;

/**
 * ClassMetadata exporter for PHP code.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class PhpExporter extends AbstractExporter
{
    /**
     * @var string
     */
    protected $extension = '.php';

    /**
     * {@inheritdoc}
     */
    public function exportClassMetadata(ClassMetadata $metadata)
    {
        $lines = [];
        $lines[] = '<?php';
        $lines[] = null;
        $lines[] = 'use Doctrine\DBAL\Types\Type;';
        $lines[] = 'use Doctrine\ORM\Mapping\ClassMetadata;';
        $lines[] = 'use Doctrine\ORM\Mapping;';
        $lines[] = null;

        if ($metadata->isMappedSuperclass) {
            $lines[] = '$metadata->isMappedSuperclass = true;';
        }

        if ($metadata->inheritanceType) {
            $lines[] = '$metadata->setInheritanceType(Mapping\InheritanceType::' . $metadata->inheritanceType . ');';
        }

        if ($metadata->customRepositoryClassName) {
            $lines[] = '$metadata->customRepositoryClassName = "' . $metadata->customRepositoryClassName . '";';
        }

        if ($metadata->table) {
            $table = $metadata->table;

            $lines[] = '$table = new Mapping\TableMetadata();';
            $lines[] = null;

            if (! empty($table->getSchema())) {
                $lines[] = '$table->setSchema("' . $table->getSchema() . '");';
            }

            $lines[] = '$table->setName("' . $table->getName() . '");';
            $lines[] = '$table->setOptions(' . $this->varExport($table->getOptions()) . ');';

            foreach ($table->getIndexes() as $index) {
                $lines[] = '$table->addIndex(' . $this->varExport($index) . ');';
            }

            foreach ($table->getUniqueConstraints() as $constraint) {
                $lines[] = '$table->addUniqueConstraint(' . $this->varExport($constraint) . ');';
            }

            $lines[] = null;
            $lines[] = '$metadata->setPrimaryTable($table);';
        }

        if ($metadata->discriminatorColumn) {
            $discrColumn = $metadata->discriminatorColumn;

            $lines[] = '$discrColumn = new Mapping\DiscriminatorColumnMetadata();';
            $lines[] = null;
            $lines[] = '$discrColumn->setColumnName("' . $discrColumn->getColumnName() . '");';
            $lines[] = '$discrColumn->setType(Type::getType("' . $discrColumn->getTypeName() . '"));';
            $lines[] = '$discrColumn->setTableName("' . $discrColumn->getTableName() . '");';

            if (! empty($discrColumn->getColumnDefinition())) {
                $lines[] = '$property->setColumnDefinition("' . $discrColumn->getColumnDefinition() . '");';
            }

            if (! empty($discrColumn->getLength())) {
                $lines[] = '$property->setLength(' . $discrColumn->getLength() . ');';
            }

            if (! empty($discrColumn->getScale())) {
                $lines[] = '$property->setScale(' . $discrColumn->getScale() . ');';
            }

            if (! empty($discrColumn->getPrecision())) {
                $lines[] = '$property->setPrecision(' . $discrColumn->getPrecision() . ');';
            }

            $lines[] = '$discrColumn->setOptions(' . $this->varExport($discrColumn->getOptions()) . ');';
            $lines[] = '$discrColumn->setNullable(' . $this->varExport($discrColumn->isNullable()) . ');';
            $lines[] = '$discrColumn->setUnique(' . $this->varExport($discrColumn->isUnique()) . ');';
            $lines[] = null;
            $lines[] = '$metadata->setDiscriminatorColumn($discrColumn);';
        }

        if ($metadata->discriminatorMap) {
            $lines[] = '$metadata->setDiscriminatorMap(' . $this->varExport($metadata->discriminatorMap) . ');';
        }

        if ($metadata->changeTrackingPolicy) {
            $lines[] = '$metadata->setChangeTrackingPolicy(Mapping\ChangeTrackingPolicy::' . $metadata->changeTrackingPolicy . ');';
        }

        if ($metadata->lifecycleCallbacks) {
            foreach ($metadata->lifecycleCallbacks as $event => $callbacks) {
                foreach ($callbacks as $callback) {
                    $lines[] = '$metadata->addLifecycleCallback("' . $callback . '", "' . $event . '");';
                }
            }
        }

        foreach ($metadata->getProperties() as $property) {
            /** @var FieldMetadata $property */
            $lines[] = sprintf(
                '$property = new Mapping\%sFieldMetadata("%s");',
                ($metadata->versionProperty === $property) ? 'Version' : '',
                $property->getName()
            );

            $lines[] = null;
            $lines[] = '$property->setColumnName("' . $property->getColumnName() . '");';
            $lines[] = '$property->setType(Type::getType("' . $property->getTypeName() . '"));';
            $lines[] = '$property->setTableName("' . $property->getTableName() . '");';

            if (! empty($property->getColumnDefinition())) {
                $lines[] = '$property->setColumnDefinition("' . $property->getColumnDefinition() . '");';
            }

            if (! empty($property->getLength())) {
                $lines[] = '$property->setLength(' . $property->getLength() . ');';
            }

            if (! empty($property->getScale())) {
                $lines[] = '$property->setScale(' . $property->getScale() . ');';
            }

            if (! empty($property->getPrecision())) {
                $lines[] = '$property->setPrecision(' . $property->getPrecision() . ');';
            }

            $lines[] = '$property->setOptions(' . $this->varExport($property->getOptions()) . ');';
            $lines[] = '$property->setPrimaryKey(' . $this->varExport($property->isPrimaryKey()) . ');';
            $lines[] = '$property->setNullable(' . $this->varExport($property->isNullable()) . ');';
            $lines[] = '$property->setUnique(' . $this->varExport($property->isUnique()) . ');';
            $lines[] = null;
            $lines[] = '$metadata->addProperty($property);';
        }

        if ( ! $metadata->isIdentifierComposite) {
            $lines[] = '$metadata->setIdGeneratorType(Mapping\GeneratorType::' . $metadata->generatorType . ');';
        }

        foreach ($metadata->associationMappings as $association) {
            $cascade = ['remove', 'persist', 'refresh', 'merge', 'detach'];

            foreach ($cascade as $key => $value) {
                if ( ! in_array($value, $association->getCascade())) {
                    unset($cascade[$key]);
                }
            }

            if (count($cascade) === 5) {
                $cascade = ['all'];
            }

            $method = null;

            if ($association instanceof OneToOneAssociationMetadata) {
                $method = 'mapOneToOne';

                $this->exportJoinColumns($association->getJoinColumns(), $lines, 'joinColumns');

                $lines[] = '$association = new Mapping\OneToOneAssociationMetadata("' . $association->getName() . '");';
                $lines[] = null;
                $lines[] = '$association->setJoinColumns($joinColumns);';
            } else if ($association instanceof ManyToOneAssociationMetadata) {
                $method = 'mapManyToOne';

                $this->exportJoinColumns($association->getJoinColumns(), $lines, 'joinColumns');

                $lines[] = '$association = new Mapping\ManyToOneAssociationMetadata("' . $association->getName() . '");';
                $lines[] = null;
                $lines[] = '$association->setJoinColumns($joinColumns);';
            } else if ($association instanceof OneToManyAssociationMetadata) {
                $method = 'mapOneToMany';

                $lines[] = '$association = new Mapping\OneToManyAssociationMetadata("' . $association->getName() . '");';
                $lines[] = null;
                $lines[] = '$association->setOrderBy(' . $this->varExport($association->getOrderBy()) . ');';
            } else if ($association instanceof ManyToManyAssociationMetadata) {
                $method = 'mapManyToMany';

                if ($association->getJoinTable()) {
                    $this->exportJoinTable($association->getJoinTable(), $lines);
                }

                $lines[] = '$association = new Mapping\ManyToManyAssociationMetadata("' . $association->getName() . '");';
                $lines[] = null;

                if ($association->getJoinTable()) {
                    $lines[] = '$association->setJoinTable($joinTable);';
                }

                if ($association->getIndexedBy()) {
                    $lines[] = '$association->setIndexedBy("' . $association->getIndexedBy() . '");';
                }

                $lines[] = '$association->setOrderBy(' . $this->varExport($association->getOrderBy()) . ');';
            }

            $lines[] = '$association->setTargetEntity("' . $association->getTargetEntity() . '");';
            $lines[] = '$association->setFetchMode("' . $association->getFetchMode() . '");';

            if ($association->getMappedBy()) {
                $lines[] = '$association->setMappedBy("' . $association->getMappedBy() . '");';
            }

            if ($association->getInversedBy()) {
                $lines[] = '$association->setInversedBy("' . $association->getInversedBy() . '");';
            }

            $lines[] = '$association->setCascade(' . $this->varExport($cascade) . ');';
            $lines[] = '$association->setOrphanRemoval(' . $this->varExport($association->isOrphanRemoval()) . ');';
            $lines[] = '$association->setPrimaryKey(' . $this->varExport($association->isPrimaryKey()) . ');';
            $lines[] = null;
            $lines[] = '$metadata->' . $method . '($association);';
        }

        return implode(PHP_EOL, $lines);
    }

    private function exportJoinTable(JoinTableMetadata $joinTable, array &$lines)
    {
        $lines[] = null;
        $lines[] = '$joinTable = new Mapping\JoinTableMetadata();';
        $lines[] = null;
        $lines[] = '$joinTable->setName("' . $joinTable->getName() . '");';

        if (! empty($joinTable->getSchema())) {
            $lines[] = '$joinTable->setSchema("' . $joinTable->getSchema() . '");';
        }

        $lines[] = '$joinTable->setOptions(' . $this->varExport($joinTable->getOptions()) . ');';

        $this->exportJoinColumns($joinTable->getJoinColumns(), $lines, 'joinColumns');

        $lines[] = null;
        $lines[] = 'foreach ($joinColumns as $joinColumn) {';
        $lines[] = '    $joinTable->addJoinColumn($joinColumn);';
        $lines[] = '}';
        $lines[] = null;

        $this->exportJoinColumns($joinTable->getInverseJoinColumns(), $lines, 'inverseJoinColumns');

        $lines[] = null;
        $lines[] = 'foreach ($inverseJoinColumns as $inverseJoinColumn) {';
        $lines[] = '    $joinTable->addInverseJoinColumn($inverseJoinColumn);';
        $lines[] = '}';
    }

    private function exportJoinColumns(array $joinColumns, array &$lines, $variableName)
    {
        $lines[] = '$' . $variableName . ' = array();';

        foreach ($joinColumns as $joinColumn) {
            /** @var JoinColumnMetadata $joinColumn */
            $lines[] = '$joinColumn = new Mapping\JoinColumnMetadata();';
            $lines[] = null;
            $lines[] = '$joinColumn->setTableName("' . $joinColumn->getTableName() . '");';
            $lines[] = '$joinColumn->setColumnName("' . $joinColumn->getColumnName() . '");';
            $lines[] = '$joinColumn->setReferencedColumnName("' . $joinColumn->getReferencedColumnName() . '");';
            $lines[] = '$joinColumn->setAliasedName("' . $joinColumn->getAliasedName() . '");';
            $lines[] = '$joinColumn->setColumnDefinition("' . $joinColumn->getColumnDefinition() . '");';
            $lines[] = '$joinColumn->setOnDelete("' . $joinColumn->getOnDelete() . '");';
            $lines[] = '$joinColumn->setOptions(' . $this->varExport($joinColumn->getOptions()) . ');';
            $lines[] = '$joinColumn->setNullable("' . $joinColumn->isNullable() . '");';
            $lines[] = '$joinColumn->setUnique("' . $joinColumn->isUnique() . '");';
            $lines[] = '$joinColumn->setPrimaryKey("' . $joinColumn->isPrimaryKey() . '");';
            $lines[] = null;
            $lines[] = '$' . $variableName . '[] = $joinColumn;';
        }
    }

    /**
     * @param mixed $var
     *
     * @return string
     */
    protected function varExport($var)
    {
        $export = var_export($var, true);
        $export = str_replace("\n", PHP_EOL . str_repeat(' ', 8), $export);
        $export = str_replace('  ', ' ', $export);
        $export = str_replace('array (', 'array(', $export);
        $export = str_replace('array( ', 'array(', $export);
        $export = str_replace(',)', ')', $export);
        $export = str_replace(', )', ')', $export);
        $export = str_replace('  ', ' ', $export);

        return $export;
    }
}
