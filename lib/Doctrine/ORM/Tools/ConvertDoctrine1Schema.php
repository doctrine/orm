<?php
/*
 *  $Id$
 *
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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools;

use Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\ORM\Tools\Export\Driver\AbstractExporter,
    Doctrine\Common\Util\Inflector;

/**
 * Class to help with converting Doctrine 1 schema files to Doctrine 2 mapping files
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class ConvertDoctrine1Schema
{
    private $_legacyTypeMap = array(
        // TODO: This list may need to be updated
        'clob' => 'text',
        'timestamp' => 'datetime',
        'enum' => 'string'
    );

    /**
     * Constructor passes the directory or array of directories
     * to convert the Doctrine 1 schema files from
     *
     * @param array $from
     * @author Jonathan Wage
     */
    public function __construct($from)
    {
        $this->_from = (array) $from;
    }

    /**
     * Get an array of ClassMetadataInfo instances from the passed
     * Doctrine 1 schema
     *
     * @return array $metadatas  An array of ClassMetadataInfo instances
     */
    public function getMetadata()
    {
        $schema = array();
        foreach ($this->_from as $path) {
            if (is_dir($path)) {
                $files = glob($path . '/*.yml');
                foreach ($files as $file) {
                    $schema = array_merge($schema, (array) \Symfony\Component\Yaml\Yaml::parse($file));
                }
            } else {
                $schema = array_merge($schema, (array) \Symfony\Component\Yaml\Yaml::parse($path));
            }
        }

        $metadatas = array();
        foreach ($schema as $className => $mappingInformation) {
            $metadatas[] = $this->_convertToClassMetadataInfo($className, $mappingInformation);
        }

        return $metadatas;
    }

    private function _convertToClassMetadataInfo($className, $mappingInformation)
    {
        $metadata = new ClassMetadataInfo($className);

        $this->_convertTableName($className, $mappingInformation, $metadata);
        $this->_convertColumns($className, $mappingInformation, $metadata);
        $this->_convertIndexes($className, $mappingInformation, $metadata);
        $this->_convertRelations($className, $mappingInformation, $metadata);

        return $metadata;
    }

    private function _convertTableName($className, array $model, ClassMetadataInfo $metadata)
    {
        if (isset($model['tableName']) && $model['tableName']) {
            $e = explode('.', $model['tableName']);
            if (count($e) > 1) {
                $metadata->table['schema'] = $e[0];
                $metadata->table['name'] = $e[1];
            } else {
                $metadata->table['name'] = $e[0];
            }
        }
    }

    private function _convertColumns($className, array $model, ClassMetadataInfo $metadata)
    {
        $id = false;

        if (isset($model['columns']) && $model['columns']) {
            foreach ($model['columns'] as $name => $column) {
                $fieldMapping = $this->_convertColumn($className, $name, $column, $metadata);

                if (isset($fieldMapping['id']) && $fieldMapping['id']) {
                    $id = true;
                }
            }
        }

        if ( ! $id) {
            $fieldMapping = array(
                'fieldName' => 'id',
                'columnName' => 'id',
                'type' => 'integer',
                'id' => true
            );
            $metadata->mapField($fieldMapping);
            $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);
        }
    }

    private function _convertColumn($className, $name, $column, ClassMetadataInfo $metadata)
    {
        if (is_string($column)) {
            $string = $column;
            $column = array();
            $column['type'] = $string;
        }
        if ( ! isset($column['name'])) {
            $column['name'] = $name;
        }
        // check if a column alias was used (column_name as field_name)
        if (preg_match("/(\w+)\sas\s(\w+)/i", $column['name'], $matches)) {
            $name = $matches[1];
            $column['name'] = $name;
            $column['alias'] = $matches[2];
        }
        if (preg_match("/([a-zA-Z]+)\(([0-9]+)\)/", $column['type'], $matches)) {
            $column['type'] = $matches[1];
            $column['length'] = $matches[2];
        }
        $column['type'] = strtolower($column['type']);
        // check if legacy column type (1.x) needs to be mapped to a 2.0 one
        if (isset($this->_legacyTypeMap[$column['type']])) {
            $column['type'] = $this->_legacyTypeMap[$column['type']];
        }
        if ( ! \Doctrine\DBAL\Types\Type::hasType($column['type'])) {
            throw ToolsException::couldNotMapDoctrine1Type($column['type']);
        }

        $fieldMapping = array();
        if (isset($column['primary'])) {
            $fieldMapping['id'] = true;
        }
        $fieldMapping['fieldName'] = isset($column['alias']) ? $column['alias'] : $name;
        $fieldMapping['columnName'] = $column['name'];
        $fieldMapping['type'] = $column['type'];
        if (isset($column['length'])) {
            $fieldMapping['length'] = $column['length'];
        }
        $allowed = array('precision', 'scale', 'unique', 'options', 'notnull', 'version');
        foreach ($column as $key => $value) {
            if (in_array($key, $allowed)) {
                $fieldMapping[$key] = $value;
            }
        }

        $metadata->mapField($fieldMapping);

        if (isset($column['autoincrement'])) {
            $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);
        } else if (isset($column['sequence'])) {
            $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_SEQUENCE);
            $definition = array(
                'sequenceName' => is_array($column['sequence']) ? $column['sequence']['name']:$column['sequence']
            );
            if (isset($column['sequence']['size'])) {
                $definition['allocationSize'] = $column['sequence']['size'];
            }
            if (isset($column['sequence']['value'])) {
                $definition['initialValue'] = $column['sequence']['value'];
            }
            $metadata->setSequenceGeneratorDefinition($definition);
        }
        return $fieldMapping;
    }

    private function _convertIndexes($className, array $model, ClassMetadataInfo $metadata)
    {
        if (isset($model['indexes']) && $model['indexes']) {
            foreach ($model['indexes'] as $name => $index) {
                $type = (isset($index['type']) && $index['type'] == 'unique')
                    ? 'uniqueConstraints' : 'indexes';

                $metadata->table[$type][$name] = array(
                    'columns' => $index['fields']
                );
            }
        }
    }

    private function _convertRelations($className, array $model, ClassMetadataInfo $metadata)
    {
        if (isset($model['relations']) && $model['relations']) {
            foreach ($model['relations'] as $name => $relation) {
                if ( ! isset($relation['alias'])) {
                    $relation['alias'] = $name;
                }
                if ( ! isset($relation['class'])) {
                    $relation['class'] = $name;
                }
                if ( ! isset($relation['local'])) {
                    $relation['local'] = Inflector::tableize($relation['class']);
                }
                if ( ! isset($relation['foreign'])) {
                    $relation['foreign'] = 'id';
                }
                if ( ! isset($relation['foreignAlias'])) {
                    $relation['foreignAlias'] = $className;
                }

                if (isset($relation['refClass'])) {
                    $type = 'many';
                    $foreignType = 'many';
                    $joinColumns = array();
                } else {
                    $type = isset($relation['type']) ? $relation['type'] : 'one';
                    $foreignType = isset($relation['foreignType']) ? $relation['foreignType'] : 'many';
                    $joinColumns = array(
                        array(
                            'name' => $relation['local'],
                            'referencedColumnName' => $relation['foreign'],
                            'onDelete' => isset($relation['onDelete']) ? $relation['onDelete'] : null,
                            'onUpdate' => isset($relation['onUpdate']) ? $relation['onUpdate'] : null,
                        )
                    );
                }

                if ($type == 'one' && $foreignType == 'one') {
                    $method = 'mapOneToOne';
                } else if ($type == 'many' && $foreignType == 'many') {
                    $method = 'mapManyToMany';
                } else {
                    $method = 'mapOneToMany';
                }

                $associationMapping = array();
                $associationMapping['fieldName'] = $relation['alias'];
                $associationMapping['targetEntity'] = $relation['class'];
                $associationMapping['mappedBy'] = $relation['foreignAlias'];
                $associationMapping['joinColumns'] = $joinColumns;

                $metadata->$method($associationMapping);
            }
        }
    }
}
