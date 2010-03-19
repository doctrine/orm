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
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\MappingException,
    Doctrine\ORM\Mapping\Driver\Driver,
    Doctrine\ORM\Mapping\Driver\AnnotationDriver,
    Doctrine\ORM\EntityManager;

/**
 * Class to read metadata mapping information from multiple sources into an array
 * of ClassMetadataInfo instances.
 *
 * The difference between this class and the ClassMetadataFactory is that this
 * is just a tool for reading in the mapping information from files without 
 * having it bound to the actual ORM and the mapping information referenced by 
 * the EntityManager. This allows us to read any source of mapping information
 * and return a single array of aggregated ClassMetadataInfo instances.
 *
 * These arrays are used for exporting the mapping information to the supported
 * mapping drivers, generating entities, generating repositories, etc.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class ClassMetadataReader
{
    private static $_mappingDrivers = array(
        'annotation' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
        'yaml' => 'Doctrine\ORM\Mapping\Driver\YamlDriver',
        'yml' => 'Doctrine\ORM\Mapping\Driver\YamlDriver',
        'xml'  => 'Doctrine\ORM\Mapping\Driver\XmlDriver',
        'php' => 'Doctrine\ORM\Mapping\Driver\PhpDriver',
        'database' => 'Doctrine\ORM\Mapping\Driver\DatabaseDriver'
    );

    private $_mappingSources = array();
    private $_em;

    /**
     * Register a new mapping driver class under a specified name
     *
     * @param string $name
     * @param string $class
     */
    public static function registerMappingDriver($name, $class)
    {
        self::$_mappingDrivers[$name] = $class;
    }

    /**
     * Optionally set the EntityManager instance to get the AnnotationDriver
     * from instead of creating a new instance of the AnnotationDriver
     *
     * @param EntityManager $em
     * @return void
     */
    public function setEntityManager(EntityManager $em)
    {
        $this->_em = $em;
    }

    /**
     * Get an array of ClassMetadataInfo instances for all the configured mapping
     * directories. Reads the mapping directories and populates ClassMetadataInfo
     * instances.
     *
     * @return array $classes
     */
    public function getMetadatas()
    {
        $classes = array();

        foreach ($this->_mappingSources as $d) {
            list($source, $driver) = $d;

            $allClasses = $driver->getAllClassNames();

            foreach ($allClasses as $className) {
                if (class_exists($className, false)) {
                    $metadata = new ClassMetadata($className);
                } else {
                    $metadata = new ClassMetadataInfo($className);
                }

                $driver->loadMetadataForClass($className, $metadata);

                if ( ! $metadata->isMappedSuperclass) {
                    $classes[$metadata->name] = $metadata;
                }
            }
        }

        return $classes;
    }

    /**
     * Add a new mapping directory to the array of directories to convert and export
     * to another format
     *
     * @param string $source   The source for the mapping
     * @param string $type  The type of mapping files (yml, xml, etc.)
     * @return void
     */
    public function addMappingSource($source, $type = null)
    {
        if ($type === null) {
            $type = $this->_determineSourceType($source);
        }

        if ( ! isset(self::$_mappingDrivers[$type])) {
            throw ExportException::invalidMappingDriverType($type);
        }

        $source = $this->_getSourceByType($type, $source);
        $driver = $this->_getMappingDriver($type, $source);
        $this->_mappingSources[] = array($source, $driver);
    }

    /**
     * Get an instance of a mapping driver
     *
     * @param string $type   The type of mapping driver (yaml, xml, annotation, etc.)
     * @param string $source The source for the driver
     * @return AbstractDriver $driver
     */
    private function _getMappingDriver($type, $source = null)
    {
        if ($source instanceof \Doctrine\ORM\Mapping\Driver\Driver) {
            return $source;
        }

        if ( ! isset(self::$_mappingDrivers[$type])) {
            return false;
        }

        $class = self::$_mappingDrivers[$type];

        if (is_subclass_of($class, 'Doctrine\ORM\Mapping\Driver\AbstractFileDriver')) {
            if (is_null($source)) {
                throw MappingException::fileMappingDriversRequireConfiguredDirectoryPath();
            }

            $driver = new $class($source);
        } else if ($class == 'Doctrine\ORM\Mapping\Driver\AnnotationDriver') {
            $reader = new \Doctrine\Common\Annotations\AnnotationReader(new \Doctrine\Common\Cache\ArrayCache);
            $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');

            $driver = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, $source);
        } else {
            $driver = new $class($source);
        }

        return $driver;
    }

    private function _determineSourceType($source)
    {
        // If the --from=<VALUE> is a directory lets determine if it is
        // annotations, yaml, xml, etc.
        if (is_dir($source)) {
            $source = realpath($source);

            // Find the files in the directory
            $files = glob($source . '/*.*');
            
            if ( ! $files) {
                throw new \InvalidArgumentException(
                    sprintf('No mapping files found in "%s"', $source)
                );
            }

            // Get the contents of the first file
            $contents = file_get_contents($files[0]);

            // Check if it has a class definition in it for annotations
            if (preg_match("/class (.*)/", $contents)) {
                return 'annotation';
            // Otherwise lets determine the type based on the extension of the 
            // first file in the directory (yml, xml, etc)
            } else {
                $info = pathinfo($files[0]);

                return $info['extension'];
            }
        // Nothing special for database
        } else if ($source == 'database') {
            return 'database';
        }
    }

    private function _getSourceByType($type, $source)
    {
        $source = realpath($source);

        // If --from==database then the source is an instance of SchemaManager
        // for the current EntityMAnager
        if ($type == 'database' && $this->_em) {
            return $this->_em->getConnection()->getSchemaManager();
        // If source is annotation then lets try and find the existing annotation
        // driver for the source instead of re-creating a new instance
        } else if ($type == 'annotation') {
            if ($this->_em) {
                $metadataDriverImpl = $this->_em->getConfiguration()->getMetadataDriverImpl();
                // Find the annotation driver in the chain of drivers
                if ($metadataDriverImpl instanceof DriverChain) {
                    foreach ($metadataDriverImpl->getDrivers() as $namespace => $driver) {
                        if ($this->_isAnnotationDriverForPath($driver, $source)) {
                            return $driver;
                        }
                    }
                } else if ($this->_isAnnotationDriverForPath($metadataDriverImpl, $source)) {
                    return $metadataDriverImpl;
                } else if ($metadataDriverImpl instanceof AnnotationDriver) {
                    $metadataDriverImpl->addPaths(array($source));
                    return $metadataDriverImpl;
                } else {
                    return $source;
                }
            } else {
                return $source;
            }
        } else {
            return $source;
        }
    }

    /**
     * Check to see if the given metadata driver is the annotation driver for the
     * given directory path
     *
     * @param Driver $driver
     * @param string $path 
     * @return boolean
     */
    private function _isAnnotationDriverForPath(Driver $driver, $path)
    {
        if ( ! $driver instanceof AnnotationDriver) {
            return false;
        }

        if (in_array(realpath($path), $driver->getPaths())) {
            return true;
        } else {
            return false;
        }
    }
}