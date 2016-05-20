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

use Symfony\Component\Yaml\Yaml;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * ClassMetadata exporter for Doctrine YAML mapping files.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class YamlExporter extends AbstractExporter
{
    /**
     * @var string
     */
    protected $_extension = '.dcm.yml';

    /**
     * {@inheritdoc}
     */
    public function exportClassMetadata(ClassMetadata $metadata)
    {
        $array = [];

        if ($metadata->isMappedSuperclass) {
            $array['type'] = 'mappedSuperclass';
        } else {
            $array['type'] = 'entity';
        }

        $array['table'] = $metadata->table['name'];

        if (isset($metadata->table['schema'])) {
            $array['schema'] = $metadata->table['schema'];
        }

        $inheritanceType = $metadata->inheritanceType;

        if ($inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE) {
            $array['inheritanceType'] = $this->_getInheritanceTypeString($inheritanceType);
        }

        if ($column = $metadata->discriminatorColumn) {
            $array['discriminatorColumn'] = $column;
        }

        if ($map = $metadata->discriminatorMap) {
            $array['discriminatorMap'] = $map;
        }

        if ($metadata->changeTrackingPolicy !== ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT) {
            $array['changeTrackingPolicy'] = $this->_getChangeTrackingPolicyString($metadata->changeTrackingPolicy);
        }

        if (isset($metadata->table['indexes'])) {
            $array['indexes'] = $metadata->table['indexes'];
        }

        if ($metadata->customRepositoryClassName) {
            $array['repositoryClass'] = $metadata->customRepositoryClassName;
        }

        if (isset($metadata->table['uniqueConstraints'])) {
            $array['uniqueConstraints'] = $metadata->table['uniqueConstraints'];
        }

        if (isset($metadata->table['options'])) {
            $array['options'] = $metadata->table['options'];
        }

        $properties = $metadata->getProperties();
        $mappings   = [];
        $ids        = [];

        foreach ($properties as $name => $property) {
            $mapping = [
                'column' => $property->getColumnName(),
                'type'   => $property->getTypeName(),
            ];

            if ($mapping['column'] === $name) {
                unset($mapping['column']);
            }

            if (isset($mapping['id']) && $mapping['id']) {
                $ids[$name] = $mapping;

                unset($mappings[$name]);

                continue;
            }

            $mappings[$name] = $mapping;
        }

        if ( ! $metadata->isIdentifierComposite && $idGeneratorType = $this->_getIdGeneratorTypeString($metadata->generatorType)) {
            $ids[$metadata->getSingleIdentifierFieldName()]['generator']['strategy'] = $idGeneratorType;
        }

        $array['id'] = $ids;

        if ($mappings) {
            if ( ! isset($array['fields'])) {
                $array['fields'] = [];
            }

            $array['fields'] = array_merge($array['fields'], $mappings);
        }

        foreach ($metadata->associationMappings as $name => $associationMapping) {
            $cascade = [];

            if ($associationMapping['isCascadeRemove']) {
                $cascade[] = 'remove';
            }

            if ($associationMapping['isCascadePersist']) {
                $cascade[] = 'persist';
            }

            if ($associationMapping['isCascadeRefresh']) {
                $cascade[] = 'refresh';
            }

            if ($associationMapping['isCascadeMerge']) {
                $cascade[] = 'merge';
            }

            if ($associationMapping['isCascadeDetach']) {
                $cascade[] = 'detach';
            }
            if (count($cascade) === 5) {
                $cascade = ['all'];
            }

            $associationMappingArray = [
                'targetEntity' => $associationMapping['targetEntity'],
                'cascade'     => $cascade,
            ];

            if (isset($associationMapping['fetch'])) {
                $associationMappingArray['fetch'] = $this->_getFetchModeString($associationMapping['fetch']);
            }

            if (isset($mapping['id']) && $mapping['id'] === true) {
                $array['id'][$name]['associationKey'] = true;
            }

            if ($associationMapping['type'] & ClassMetadata::TO_ONE) {
                $joinColumns = $associationMapping['isOwningSide'] ? $associationMapping['joinColumns'] : [];
                $newJoinColumns = [];

                foreach ($joinColumns as $joinColumn) {
                    $newJoinColumns[$joinColumn['name']]['referencedColumnName'] = $joinColumn['referencedColumnName'];

                    if (isset($joinColumn['onDelete'])) {
                        $newJoinColumns[$joinColumn['name']]['onDelete'] = $joinColumn['onDelete'];
                    }
                }

                $oneToOneMappingArray = [
                    'mappedBy'      => $associationMapping['mappedBy'],
                    'inversedBy'    => $associationMapping['inversedBy'],
                    'joinColumns'   => $newJoinColumns,
                    'orphanRemoval' => $associationMapping['orphanRemoval'],
                ];

                $associationMappingArray = array_merge($associationMappingArray, $oneToOneMappingArray);

                if ($associationMapping['type'] & ClassMetadata::ONE_TO_ONE) {
                    $array['oneToOne'][$name] = $associationMappingArray;
                } else {
                    $array['manyToOne'][$name] = $associationMappingArray;
                }
            } elseif ($associationMapping['type'] == ClassMetadata::ONE_TO_MANY) {
                $oneToManyMappingArray = [
                    'mappedBy'      => $associationMapping['mappedBy'],
                    'inversedBy'    => $associationMapping['inversedBy'],
                    'orphanRemoval' => $associationMapping['orphanRemoval'],
                    'orderBy'       => isset($associationMapping['orderBy']) ? $associationMapping['orderBy'] : null
                ];

                $associationMappingArray = array_merge($associationMappingArray, $oneToManyMappingArray);
                $array['oneToMany'][$name] = $associationMappingArray;
            } elseif ($associationMapping['type'] == ClassMetadata::MANY_TO_MANY) {
                $manyToManyMappingArray = [
                    'mappedBy'   => $associationMapping['mappedBy'],
                    'inversedBy' => $associationMapping['inversedBy'],
                    'joinTable'  => isset($associationMapping['joinTable']) ? $associationMapping['joinTable'] : null,
                    'orderBy'    => isset($associationMapping['orderBy']) ? $associationMapping['orderBy'] : null
                ];

                $associationMappingArray = array_merge($associationMappingArray, $manyToManyMappingArray);
                $array['manyToMany'][$name] = $associationMappingArray;
            }
        }
        if (isset($metadata->lifecycleCallbacks)) {
            $array['lifecycleCallbacks'] = $metadata->lifecycleCallbacks;
        }

        return $this->yamlDump([$metadata->name => $array], 10);
    }

    /**
     * Dumps a PHP array to a YAML string.
     *
     * The yamlDump method, when supplied with an array, will do its best
     * to convert the array into friendly YAML.
     *
     * @param array   $array  PHP array
     * @param integer $inline [optional] The level where you switch to inline YAML
     *
     * @return string A YAML string representing the original PHP array
     */
    protected function yamlDump($array, $inline = 2)
    {
        return Yaml::dump($array, $inline);
    }
}
