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
    protected $_extension = '.php';

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
            $lines[] = '$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_' . $this->_getInheritanceTypeString($metadata->inheritanceType) . ');';
        }

        if ($metadata->customRepositoryClassName) {
            $lines[] = '$metadata->customRepositoryClassName = "' . $metadata->customRepositoryClassName . '";';
        }

        if ($metadata->table) {
            $lines[] = '$metadata->setPrimaryTable(' . $this->_varExport($metadata->table) . ');';
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

            $lines[] = '$discrColumn->setOptions(' . $this->_varExport($discrColumn->getOptions()) . ');';
            $lines[] = '$discrColumn->setNullable(' . $this->_varExport($discrColumn->isNullable()) . ');';
            $lines[] = '$discrColumn->setUnique(' . $this->_varExport($discrColumn->isUnique()) . ');';
            $lines[] = null;
            $lines[] = '$metadata->setDiscriminatorColumn($discrColumn);';
        }

        if ($metadata->discriminatorMap) {
            $lines[] = '$metadata->setDiscriminatorMap(' . $this->_varExport($metadata->discriminatorMap) . ');';
        }

        if ($metadata->changeTrackingPolicy) {
            $lines[] = '$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_' . $this->_getChangeTrackingPolicyString($metadata->changeTrackingPolicy) . ');';
        }

        if ($metadata->lifecycleCallbacks) {
            foreach ($metadata->lifecycleCallbacks as $event => $callbacks) {
                foreach ($callbacks as $callback) {
                    $lines[] = "\$metadata->addLifecycleCallback('$callback', '$event');";
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

            $lines[] = '$property->setOptions(' . $this->_varExport($property->getOptions()) . ');';
            $lines[] = '$property->setPrimaryKey(' . $this->_varExport($property->isPrimaryKey()) . ');';
            $lines[] = '$property->setNullable(' . $this->_varExport($property->isNullable()) . ');';
            $lines[] = '$property->setUnique(' . $this->_varExport($property->isUnique()) . ');';
            $lines[] = null;
            $lines[] = '$metadata->addProperty($property);';
        }

        if ( ! $metadata->isIdentifierComposite && $generatorType = $this->_getIdGeneratorTypeString($metadata->generatorType)) {
            $lines[] = '$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_' . $generatorType . ');';
        }

        foreach ($metadata->associationMappings as $associationMapping) {
            $cascade = ['remove', 'persist', 'refresh', 'merge', 'detach'];
            foreach ($cascade as $key => $value) {
                if ( ! in_array($value, $associationMapping['cascade'])) {
                    unset($cascade[$key]);
                }
            }

            if (count($cascade) === 5) {
                $cascade = ['all'];
            }

            switch (true) {
                case ($associationMapping['type'] & ClassMetadata::TO_ONE):
                    $method = $associationMapping['type'] === ClassMetadata::ONE_TO_ONE
                        ? 'mapOneToOne'
                        : 'mapManyToOne';

                    $this->exportJoinColumns($associationMapping['joinColumns'] ?? [], $lines, 'joinColumns');

                    $lines[] = null;
                    $lines[] = '$metadata->' . $method . '(array(';
                    $lines[] = '    "fieldName"     => "' . $associationMapping['fieldName'] . '",';
                    $lines[] = '    "targetEntity"  => "' . $associationMapping['targetEntity'] . '",';
                    $lines[] = '    "fetch"         => "' . $associationMapping['fetch'] . '",';
                    $lines[] = '    "mappedBy"      => "' . $associationMapping['mappedBy'] . '",';
                    $lines[] = '    "inversedBy"    => "' . $associationMapping['inversedBy'] . '",';
                    $lines[] = '    "joinColumns"   => $joinColumns,';
                    $lines[] = '    "cascade"       => ' . $this->_varExport($cascade) . ',';
                    $lines[] = '    "orphanRemoval" => ' . $this->_varExport($associationMapping['orphanRemoval']) . ',';
                    $lines[] = '));';
                    break;

                case ($associationMapping['type'] & ClassMetadata::TO_MANY):
                    if ($associationMapping['type'] === ClassMetadata::MANY_TO_MANY) {
                        $method = 'mapManyToMany';

                        $this->exportJoinTable($associationMapping['joinTable'], $lines, 'joinTable');
                    } else {
                        $method = 'mapOneToMany';
                    }

                    $lines[] = null;
                    $lines[] = '$metadata->' . $method . '(array(';
                    $lines[] = '    "fieldName"     => "' . $associationMapping['fieldName'] . '",';
                    $lines[] = '    "targetEntity"  => "' . $associationMapping['targetEntity'] . '",';
                    $lines[] = '    "fetch"         => "' . $associationMapping['fetch'] . '",';
                    $lines[] = '    "mappedBy"      => "' . $associationMapping['mappedBy'] . '",';
                    $lines[] = '    "inversedBy"    => "' . $associationMapping['inversedBy'] . '",';
                    $lines[] = '    "orderBy"       => ' . $this->_varExport($associationMapping['orderBy']) . ',';

                    if ($associationMapping['type'] === ClassMetadata::MANY_TO_MANY) {
                        $lines[] = '    "joinTable"     => $joinTable,';
                    }

                    $lines[] = '    "cascade"       => ' . $this->_varExport($cascade) . ',';
                    $lines[] = '    "orphanRemoval" => ' . $this->_varExport($associationMapping['orphanRemoval']) . ',';
                    $lines[] = '));';
                    break;
            }
        }

        return implode("\n", $lines);
    }

    private function exportJoinTable(array $joinTable, array &$lines, $variableName)
    {
        $this->exportJoinColumns($joinTable['joinColumns'], $lines, 'joinColumns');

        $lines[] = null;

        $this->exportJoinColumns($joinTable['inverseJoinColumns'], $lines, 'inverseJoinColumns');

        $lines[] = null;
        $lines[] = '$' . $variableName . ' = array(';
        $lines[] = '    "name"               => "' . $joinTable['name'] . '",';
        $lines[] = '    "joinColumns"        => $joinColumns,';
        $lines[] = '    "inverseJoinColumns" => $inverseJoinColumns,';
        $lines[] = ');';
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
            $lines[] = '$joinColumn->setOptions(' . $this->_varExport($joinColumn->getOptions()) . ');';
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
    protected function _varExport($var)
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
