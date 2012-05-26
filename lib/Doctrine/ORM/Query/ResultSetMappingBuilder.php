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

namespace Doctrine\ORM\Query;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * A ResultSetMappingBuilder uses the EntityManager to automatically populate entity fields
 *
 * @author Michael Ridgway <mcridgway@gmail.com>
 * @since 2.1
 */
class ResultSetMappingBuilder extends ResultSetMapping
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param EntityManager
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Adds a root entity and all of its fields to the result set.
     *
     * @param string $class The class name of the root entity.
     * @param string $alias The unique alias to use for the root entity.
     * @param array $renamedColumns Columns that have been renamed (tableColumnName => queryColumnName)
     */
    public function addRootEntityFromClassMetadata($class, $alias, $renamedColumns = array())
    {
        $this->addEntityResult($class, $alias);
        $this->addAllClassFields($class, $alias, $renamedColumns);
    }

    /**
     * Adds a joined entity and all of its fields to the result set.
     *
     * @param string $class The class name of the joined entity.
     * @param string $alias The unique alias to use for the joined entity.
     * @param string $parentAlias The alias of the entity result that is the parent of this joined result.
     * @param object $relation The association field that connects the parent entity result with the joined entity result.
     * @param array $renamedColumns Columns that have been renamed (tableColumnName => queryColumnName)
     */
    public function addJoinedEntityFromClassMetadata($class, $alias, $parentAlias, $relation, $renamedColumns = array())
    {
        $this->addJoinedEntityResult($class, $alias, $parentAlias, $relation);
        $this->addAllClassFields($class, $alias, $renamedColumns);
    }

    /**
     * Adds all fields of the given class to the result set mapping (columns and meta fields)
     */
    protected function addAllClassFields($class, $alias, $renamedColumns = array())
    {
        $classMetadata = $this->em->getClassMetadata($class);
        if ($classMetadata->isInheritanceTypeSingleTable() || $classMetadata->isInheritanceTypeJoined()) {
            throw new \InvalidArgumentException('ResultSetMapping builder does not currently support inheritance.');
        }
        $platform = $this->em->getConnection()->getDatabasePlatform();
        foreach ($classMetadata->getColumnNames() as $columnName) {
            $propertyName = $classMetadata->getFieldName($columnName);
            if (isset($renamedColumns[$columnName])) {
                $columnName = $renamedColumns[$columnName];
            }
            $columnName = $platform->getSQLResultCasing($columnName);
            if (isset($this->fieldMappings[$columnName])) {
                throw new \InvalidArgumentException("The column '$columnName' conflicts with another column in the mapper.");
            }
            $this->addFieldResult($alias, $columnName, $propertyName);
        }
        foreach ($classMetadata->associationMappings as $associationMapping) {
            if ($associationMapping['isOwningSide'] && $associationMapping['type'] & ClassMetadataInfo::TO_ONE) {
                foreach ($associationMapping['joinColumns'] as $joinColumn) {
                    $columnName = $joinColumn['name'];
                    $renamedColumnName = isset($renamedColumns[$columnName]) ? $renamedColumns[$columnName] : $columnName;
                    $renamedColumnName = $platform->getSQLResultCasing($renamedColumnName);
                    if (isset($this->metaMappings[$renamedColumnName])) {
                        throw new \InvalidArgumentException("The column '$renamedColumnName' conflicts with another column in the mapper.");
                    }
                    $this->addMetaResult($alias, $renamedColumnName, $columnName);
                }
            }
        }
    }


    /**
     * Adds the mappings of the results of native SQL queries to the result set.
     *
     * @param   ClassMetadataInfo $class
     * @param   array $queryMapping
     * @return  ResultSetMappingBuilder
     */
    public function addNamedNativeQueryMapping(ClassMetadataInfo $class, array $queryMapping)
    {
        if (isset($queryMapping['resultClass'])) {
            return $this->addNamedNativeQueryResultClassMapping($class, $queryMapping['resultClass']);
        }

        return $this->addNamedNativeQueryResultSetMapping($class, $queryMapping['resultSetMapping']);
    }

    /**
     * Adds the class mapping of the results of native SQL queries to the result set.
     *
     * @param   ClassMetadataInfo $class
     * @param   string $resultClassName
     * @return  ResultSetMappingBuilder
     */
    public function addNamedNativeQueryResultClassMapping(ClassMetadataInfo $class, $resultClassName)
    {

        $classMetadata  = $this->em->getClassMetadata($resultClassName);
        $shortName      = $classMetadata->reflClass->getShortName();
        $alias          = strtolower($shortName[0]).'0';

        $this->addEntityResult($class->name, $alias);

        if ($classMetadata->discriminatorColumn) {
            $discriminatorColumn = $classMetadata->discriminatorColumn;
            $this->setDiscriminatorColumn($alias, $discriminatorColumn['name']);
            $this->addMetaResult($alias, $discriminatorColumn['name'], $discriminatorColumn['fieldName']);
        }

        foreach ($classMetadata->getColumnNames() as $key => $columnName) {
            $propertyName   = $classMetadata->getFieldName($columnName);
            $this->addFieldResult($alias, $columnName, $propertyName);
        }

        foreach ($classMetadata->associationMappings as $associationMapping) {
            if ($associationMapping['isOwningSide'] && $associationMapping['type'] & ClassMetadataInfo::TO_ONE) {
                foreach ($associationMapping['joinColumns'] as $joinColumn) {
                    $columnName = $joinColumn['name'];
                    $this->addMetaResult($alias, $columnName, $columnName, $classMetadata->isIdentifier($columnName));
                }
            }
        }

        return $this;
    }

    /**
     * Adds the result set mapping of the results of native SQL queries to the result set.
     *
     * @param   ClassMetadataInfo $class
     * @param   string $resultSetMappingName
     * @return  ResultSetMappingBuilder
     */
    public function addNamedNativeQueryResultSetMapping(ClassMetadataInfo $class, $resultSetMappingName)
    {
        $counter        = 0;
        $resultMapping  = $class->getSqlResultSetMapping($resultSetMappingName);
        $rooShortName   = $class->reflClass->getShortName();
        $rootAlias      = strtolower($rooShortName[0]) . $counter;


        if (isset($resultMapping['entities'])) {
            foreach ($resultMapping['entities'] as $key => $entityMapping) {
                $classMetadata  = $this->em->getClassMetadata($entityMapping['entityClass']);

                if ($class->reflClass->name == $classMetadata->reflClass->name) {
                    $this->addEntityResult($classMetadata->name, $rootAlias);
                    $this->addNamedNativeQueryEntityResultMapping($classMetadata, $entityMapping, $rootAlias);
                } else {
                    $shortName      = $classMetadata->reflClass->getShortName();
                    $joinAlias      = strtolower($shortName[0]) . ++ $counter;
                    $associations   = $class->getAssociationsByTargetClass($classMetadata->name);

                    foreach ($associations as $relation => $mapping) {
                        $this->addJoinedEntityResult($mapping['targetEntity'], $joinAlias, $rootAlias, $relation);
                        $this->addNamedNativeQueryEntityResultMapping($classMetadata, $entityMapping, $joinAlias);
                    }
                }

            }
        }

        if (isset($resultMapping['columns'])) {
            foreach ($resultMapping['columns'] as $entityMapping) {
                $this->addScalarResult($entityMapping['name'], $entityMapping['name']);
            }
        }

        return $this;
    }

    /**
     * Adds the entity result mapping of the results of native SQL queries to the result set.
     * 
     * @param ClassMetadataInfo $classMetadata
     * @param array $entityMapping
     * @param string $alias
     * @return ResultSetMappingBuilder
     */
    public function addNamedNativeQueryEntityResultMapping(ClassMetadataInfo $classMetadata, array $entityMapping, $alias)
    {
        if (isset($entityMapping['discriminatorColumn']) && $entityMapping['discriminatorColumn']) {
            $discriminatorColumn = $entityMapping['discriminatorColumn'];
            $this->setDiscriminatorColumn($alias, $discriminatorColumn);
            $this->addMetaResult($alias, $discriminatorColumn, $discriminatorColumn);
        }

        if (isset($entityMapping['fields']) && !empty($entityMapping['fields'])) {
            foreach ($entityMapping['fields'] as $field) {
                $fieldName = $field['name'];
                $relation  = null;

                if(strpos($fieldName, '.')){
                    list($relation, $fieldName) = explode('.', $fieldName);
                }

                if (isset($classMetadata->associationMappings[$relation])) {
                    if($relation) {
                        $associationMapping = $classMetadata->associationMappings[$relation];
                        $joinAlias          = $alias.$relation;
                        $parentAlias        = $alias;

                        $this->addJoinedEntityResult($associationMapping['targetEntity'], $joinAlias, $parentAlias, $relation);
                        $this->addFieldResult($joinAlias, $field['column'], $fieldName);
                    }else {
                        $this->addFieldResult($alias, $field['column'], $fieldName, $classMetadata->name);
                    }
                } else {
                    if(!isset($classMetadata->fieldMappings[$fieldName])) {
                        throw new \InvalidArgumentException("Entity '".$classMetadata->name."' has no field '".$fieldName."'. ");
                    }
                    $this->addFieldResult($alias, $field['column'], $fieldName, $classMetadata->name);
                }
            }

        } else {
            foreach ($classMetadata->getColumnNames() as $columnName) {
                $propertyName   = $classMetadata->getFieldName($columnName);
                $this->addFieldResult($alias, $columnName, $propertyName);
            }
        }

        return $this;
    }
}
