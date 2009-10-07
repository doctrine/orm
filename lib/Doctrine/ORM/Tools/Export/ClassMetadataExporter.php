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

namespace Doctrine\ORM\Tools\Export;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Class used for converting your mapping information between the 
 * supported formats: yaml, xml, and php/annotation.
 *
 *     [php]
 *     // Unify all your mapping information which is written in php, xml, yml
 *     // and convert it to a single set of yaml files.
 *     
 *     $cme = new Doctrine\ORM\Tools\Export\ClassMetadataExporter();
 *     $cme->addMappingSource(__DIR__ . '/Entities', 'php');
 *     $cme->addMappingSource(__DIR__ . '/xml', 'xml');
 *     $cme->addMappingSource(__DIR__ . '/yaml', 'yaml');
 *     
 *     $exporter = $cme->getExporter('yaml');
 *     $exporter->setOutputDir(__DIR__ . '/new_yaml');
 *
 *     $exporter->setMetadatas($cme->getMetadatasForMappingSources());
 *     $exporter->export();
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class ClassMetadataExporter
{
    private $_exporterDrivers = array(
        'xml' => 'Doctrine\ORM\Tools\Export\Driver\XmlExporter',
        'yaml' => 'Doctrine\ORM\Tools\Export\Driver\YamlExporter',
        'yml' => 'Doctrine\ORM\Tools\Export\Driver\YamlExporter',
        'php' => 'Doctrine\ORM\Tools\Export\Driver\PhpExporter',
        'annotation' => 'Doctrine\ORM\Tools\Export\Driver\AnnotationExporter'
    );

    private $_mappingDrivers = array(
        'annotation' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
        'yaml' => 'Doctrine\ORM\Mapping\Driver\YamlDriver',
        'yml' => 'Doctrine\ORM\Mapping\Driver\YamlDriver',
        'xml'  => 'Doctrine\ORM\Mapping\Driver\XmlDriver',
        'database' => 'Doctrine\ORM\Mapping\Driver\DatabaseDriver'
    );

    private $_mappingSources = array();

    /**
     * Add a new mapping directory to the array of directories to convert and export
     * to another format
     *
     *     [php]
     *     $cme = new Doctrine\ORM\Tools\Export\ClassMetadataExporter();
     *     $cme->addMappingSource(__DIR__ . '/yaml', 'yaml');
     *     $cme->addMappingSource($schemaManager, 'database');
     *
     * @param string $source   The source for the mapping
     * @param string $type  The type of mapping files (yml, xml, etc.)
     * @return void
     */
    public function addMappingSource($source, $type)
    {
        if ($type === 'php') {
            $this->_mappingSources[] = array($source, $type);
        } else {
            if ( ! isset($this->_mappingDrivers[$type])) {
                throw DoctrineException::invalidMappingDriverType($type);
            }

            $driver = $this->getMappingDriver($type, $source);
            $this->_mappingSources[] = array($source, $driver);
        }
    }

    /**
     * Get an instance of a mapping driver
     *
     * @param string $type   The type of mapping driver (yaml, xml, annotation, etc.)
     * @param string $source The source for the driver
     * @return AbstractDriver $driver
     */
    public function getMappingDriver($type, $source = null)
    {
        if ( ! isset($this->_mappingDrivers[$type])) {
            return false;
        }
        $class = $this->_mappingDrivers[$type];
        if (is_subclass_of($class, 'Doctrine\ORM\Mapping\Driver\AbstractFileDriver')) {
            if (is_null($source)) {
                throw DoctrineException::fileMappingDriversRequireDirectoryPath();
            }
            $driver = new $class($source, constant($class . '::PRELOAD'));
        } else if ($class == 'Doctrine\ORM\Mapping\Driver\AnnotationDriver') {
            $reader = new \Doctrine\Common\Annotations\AnnotationReader(new \Doctrine\Common\Cache\ArrayCache);
            $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
            $driver = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader);
        } else if ($class == 'Doctrine\ORM\Mapping\Driver\DatabaseDriver') {
            $driver = new \Doctrine\ORM\Mapping\Driver\DatabaseDriver($source);
        }
        return $driver;
    }

    /**
     * Get the array of added mapping directories
     *
     * @return array $mappingDirectories
     */
    public function getMappingSources()
    {
        return $this->_mappingSources;
    }

    /**
     * Get an array of ClassMetadataInfo instances for all the configured mapping
     * directories. Reads the mapping directories and populates ClassMetadataInfo
     * instances.
     *
     * @return array $classes
     */
    public function getMetadatasForMappingSources()
    {
        $classes = array();

        foreach ($this->_mappingSources as $d) {
            list($source, $driver) = $d;
            if ($driver == 'php') {
                $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source),
                                                      \RecursiveIteratorIterator::LEAVES_ONLY);

                foreach ($iter as $item) {
                    $info = pathinfo($item->getPathName());
                    if (! isset($info['extension']) || $info['extension'] != 'php') {
                        continue;
                    }
                    include $item->getPathName();
                    $vars = get_defined_vars();
                    foreach ($vars as $var) {
                        if ($var instanceof \Doctrine\ORM\Mapping\ClassMetadataInfo) {
                            $classes[$var->name] = $var;
                        }
                    }
                }
            } else {
                if ($driver instanceof \Doctrine\ORM\Mapping\Driver\AnnotationDriver) {
                    $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source),
                                                          \RecursiveIteratorIterator::LEAVES_ONLY);

                    $declared = get_declared_classes();          
                    foreach ($iter as $item) {
                        $info = pathinfo($item->getPathName());
                        if (! isset($info['extension']) || $info['extension'] != 'php') {
                            continue;
                        }
                        require_once $item->getPathName();
                    }
                    $declared = array_diff(get_declared_classes(), $declared);

                    foreach ($declared as $className) {                 
                        if ( ! $driver->isTransient($className)) {
                            $metadata = new ClassMetadata($className);  
                            $driver->loadMetadataForClass($className, $metadata);
                            $classes[$metadata->name] = $metadata;
                        }
                    }
                } else {
                    $preloadedClasses = $driver->preload(true);
                    foreach ($preloadedClasses as $className) {
                        $metadata = new ClassMetadataInfo($className);    
                        $driver->loadMetadataForClass($className, $metadata);
                        $classes[$metadata->name] = $metadata;
                    }
                }
            }
        }

        foreach ($classes as $key => $class) {
            if ($class->isMappedSuperclass) {
                unset($classes[$key]);
            }
        }
        return $classes;
    }

    /**
     * Get a exporter driver instance
     *
     * @param string $type   The type to get (yml, xml, etc.)
     * @param string $source    The directory where the exporter will export to
     * @return AbstractExporter $exporter
     */
    public function getExporter($type, $source = null)
    {
        if ( ! isset($this->_exporterDrivers[$type])) {
            throw DoctrineException::invalidExporterDriverType($type);
        }

        $class = $this->_exporterDrivers[$type];
        return new $class($source);
    }
}