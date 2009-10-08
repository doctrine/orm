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

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Common\DoctrineException,
    Doctrine\Common\Cache\ArrayCache,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\DBAL\Schema\AbstractSchemaManager,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\ORM\Mapping\MappingException,
    Doctrine\Common\Util\Inflector;

/**
 * The DatabaseDriver reverse engineers the mapping metadata from a database
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class DatabaseDriver implements Driver
{
    /** The SchemaManager. */
    private $_sm;
    
    /**
     * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading
     * docblock annotations.
     * 
     * @param AnnotationReader $reader The AnnotationReader to use.
     */
    public function __construct(AbstractSchemaManager $schemaManager)
    {
        $this->_sm = $schemaManager;
    }
    
    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadataInfo $metadata)
    {
        $tableName = $className;
        $className = Inflector::classify($tableName);

        $metadata->name = $className;
        $metadata->primaryTable['name'] = $tableName;

        $columns = $this->_sm->listTableColumns($tableName);
        try {
            $foreignKeys = $this->_sm->listTableForeignKeys($tableName);
        } catch (\Doctrine\Common\DoctrineException $e) {
            $foreignKeys = array();
        }

        $ids = array();
        $fieldMappings = array();
        foreach ($columns as $column) {
            // Skip columns that are foreign keys
            foreach ($foreignKeys as $foreignKey) {
                if ($column['name'] == $foreignKey['local']) {
                    continue(2);
                }
            }

            $fieldMapping = array();
            if ($column['primary']) {
                $fieldMapping['id'] = true;
            }

            $fieldMapping['fieldName'] = Inflector::camelize($column['name']);
            $fieldMapping['columnName'] = $column['name'];
            $fieldMapping['type'] = strtolower((string) $column['type']);
            $fieldMapping['length'] = $column['length'];
            $fieldMapping['unsigned'] = $column['unsigned'];
            $fieldMapping['fixed'] = $column['fixed'];
            $fieldMapping['notnull'] = $column['notnull'];
            $fieldMapping['default'] = $column['default'];

            if (isset($fieldMapping['id'])) {
                $ids[] = $fieldMapping;
            } else {
                $fieldMappings[] = $fieldMapping;
            }
        }

        if ($ids) {
            if (count($ids) == 1) {
                $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);
            }

            foreach ($ids as $id) {
                $metadata->mapField($id);
            }
        }

        foreach ($fieldMappings as $fieldMapping) {
            $metadata->mapField($fieldMapping);
        }

        foreach ($foreignKeys as $foreignKey) {
            $associationMapping = array();
            $associationMapping['fieldName'] = Inflector::camelize(str_replace('_id', '', $foreignKey['local']));
            $associationMapping['columnName'] = $foreignKey['local'];
            $associationMapping['targetEntity'] = Inflector::classify($foreignKey['table']);
            $associationMapping['joinColumns'][] = array(
                'name' => $foreignKey['local'],
                'referencedColumnName' => $foreignKey['foreign']
            );

            $metadata->mapManyToOne($associationMapping);
        }
    }

    /**
     * Whether the class with the specified name should have its metadata loaded.
     * This is only the case if it is either mapped as an Entity or a
     * MappedSuperclass.
     *
     * @param string $className
     * @return boolean
     */
    public function isTransient($className)
    {
        return true;
    }

    /**
     * Preloads all mapping information found in any documents within the
     * configured paths and returns a list of class names that have been preloaded.
     * 
     * @return array The list of class names that have been preloaded.
     */
    public function preload()
    {
        $tables = array();
        foreach ($this->_sm->listTables() as $table) {
            $tables[] = $table;
        }

        return $tables;
    }
}