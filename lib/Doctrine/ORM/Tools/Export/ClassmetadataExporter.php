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
use Doctrine\ORM\Mapping\Classmetadata;

/**
 * Class used for converting your mapping information between the 
 * supported formats: yaml, xml, and php/annotation.
 *
 *      [php]
 *      // Unify all your mapping information which is written in php, xml, yml
 *      // and convert it to a single set of yaml files.
 *
 *      $cme = new Doctrine\ORM\Tools\Export\ClassmetadataExporter();
 *      $cme->addMappingDir(__DIR__ . '/Entities', 'php');
 *      $cme->addMappingDir(__DIR__ . '/xml', 'xml');
 *      $cme->addMappingDir(__DIR__ . '/yaml', 'yaml');
 *
 *      $exporter = $cme->getExporter('yaml');
 *      $exporter->setOutputDir(__DIR__ . '/new_yaml');
 *      $exporter->export();
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class ClassmetadataExporter
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
        'xml'  => 'Doctrine\ORM\Mapping\Driver\XmlDriver'
    );

    private $_mappingDirectories = array();

    public function addMappingDir($dir, $type)
    {
        if ($type === 'php') {
            $this->_mappingDirectories[] = array($dir, $type);
        } else {
            if ( ! isset($this->_mappingDrivers[$type])) {
                throw DoctrineException::invalidMappingDriverType($type);
            }

            $class = $this->_mappingDrivers[$type];
            if (is_subclass_of($class, 'Doctrine\ORM\Mapping\Driver\AbstractFileDriver')) {
                $driver = new $class($dir, constant($class . '::PRELOAD'));
            } else {
                $reader = new \Doctrine\Common\Annotations\AnnotationReader(new \Doctrine\Common\Cache\ArrayCache);
                $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
                $driver = new $class($reader);
            }
            $this->_mappingDirectories[] = array($dir, $driver);
        }
    }

    private function _getMetadataInstances()
    {
        $classes = array();

        foreach ($this->_mappingDirectories as $d) {
            list($dir, $driver) = $d;
            if ($driver == 'php') {
                $iter = new \FilesystemIterator($dir);

                foreach ($iter as $item) {
                    include $item->getPathName();
                    $vars = get_defined_vars();
                    foreach ($vars as $var) {
                        if ($var instanceof \Doctrine\ORM\Mapping\ClassMetadata) {
                            $classes[] = $var;
                        }
                    }
                }
                $classes = array_unique($classes);
                $classes = array_values($classes);
            } else {
                if ($driver instanceof \Doctrine\ORM\Mapping\Driver\AnnotationDriver) {
                    $iter = new \FilesystemIterator($dir);

                    $declared = get_declared_classes();          
                    foreach ($iter as $item) {
                        $baseName = $item->getBaseName();
                        if ($baseName[0] == '.') {
                            continue;
                        }
                        require_once $item->getPathName();
                    }
                    $declared = array_diff(get_declared_classes(), $declared);

                    foreach ($declared as $className) {                 
                        if ( ! $driver->isTransient($className)) {
                            $metadata = new ClassMetadata($className);
                            $driver->loadMetadataForClass($className, $metadata);
                            $classes[] = $metadata;
                        }
                    }
                } else {
                    $preloadedClasses = $driver->preload(true);
                    foreach ($preloadedClasses as $className) {
                        $metadata = new ClassMetadata($className);
                        $driver->loadMetadataForClass($className, $metadata);
                        $classes[] = $metadata;
                    }
                }
            }
        }

        return $classes;
    }

    public function getExporter($type, $dir = null)
    {
        if ( ! isset($this->_exporterDrivers[$type])) {
            throw DoctrineException::invalidExporterDriverType($type);
        }

        $class = $this->_exporterDrivers[$type];
        return new $class($this->_getMetadataInstances());
    }
}