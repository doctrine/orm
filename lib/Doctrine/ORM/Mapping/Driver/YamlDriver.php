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

use Doctrine\ORM\Mapping\ClassMetadata;

if ( ! class_exists('sfYaml', false)) {
    require_once __DIR__ . '/../../../../vendor/sfYaml/sfYaml.class.php';
    require_once __DIR__ . '/../../../../vendor/sfYaml/sfYamlDumper.class.php';
    require_once __DIR__ . '/../../../../vendor/sfYaml/sfYamlInline.class.php';
    require_once __DIR__ . '/../../../../vendor/sfYaml/sfYamlParser.class.php';
}

/**
 * The YamlDriver reads the mapping metadata from yaml schema files.
 *
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @since 2.0
 */
class YamlDriver
{
    protected
        $_paths = array(),
        $_entities = array();

    public function __construct($paths)
    {
        $this->_paths = $paths;
        $this->_entities = $this->_loadYaml($this->_paths);
    }

    public function getEntities()
    {
        return $this->_entities;
    }

    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $entity = $this->_entities[$className];

        if (isset($entity['repositoryClass']) && $entity['repositoryClass']) {
            $metadata->setCustomRepositoryClass($entity['repositoryClass']);
        }

        if (isset($entity['table'])) {
            $metadata->setPrimaryTable($entity['table']);
        }

        if (isset($entity['inheritanceType']) && $entity['inheritanceType']) {
            $metadata->setInheritanceType($entity['inheritanceType']);
        }

        if (isset($entity['discriminatorColumn'])) {
            $metadata->setDiscriminatorColumn($entity['discriminatorColumn']);
        }

        if (isset($entity['discriminatorValue']) && $entity['discriminatorValue']) {
            $metadata->setDiscriminatorValue($entity['discriminatorValue']);
        }

        if (isset($entity['subClasses']) && $entity['subClasses']) {
            $metadata->setSubclasses((array) $entity['subClasses']);
        }

        $relationTypes = array('OneToOne', 'OneToMany', 'ManyToOne', 'ManyToMany');

        foreach ($entity['properties'] as $name => $property) {
            $mapping = array();
            $mapping['fieldName'] = $name;

            $joinColumns = array();
            if (isset($property['joinColumn']) && $property['joinColumn']) {
                $joinColumns[] = $property['joinColumn'];
            } else if (isset($property['joinColumns']) && $property['joinColumns']) {
                $joinColumns = $property['joinColumns'];
            }

            $type = $property['type'];

            $mapping = array_merge($mapping, $property);

            if (in_array($type, $relationTypes)) {
                unset($property['type']);

                switch ($type) {
                    case 'ManyToOne':
                    case 'OneToOne':
                        $mapping['joinColumns'] = $joinColumns;
                        break;
                    case 'ManyToMany':
                        $joinTable = array();
                        if (isset($property['joinTable'])) {
                            $joinTable = $property['joinTable'];
                        }
                        $mapping['joinTable'] = $joinTable;
                        break;
                    case 'OneToMany':
                    default:
                        break;
                }

                $func = 'map' . $type;
                $metadata->$func($mapping);
            } else {
                $metadata->mapField($mapping);
            }
            
        }
    }

    protected function _loadYaml($paths)
    {
        $array = array();
        foreach ((array) $paths as $path) {
            if (is_dir($path)) {
                $files = glob($path . '/*.yml');
                foreach ($files as $file) {
                    $array = array_merge($array, \sfYaml::load($file));
                }
            } else if (is_file($path)) {
                $array = array_merge($array, \sfYaml::load($path));
            }
        }
        return $array;
    }
}