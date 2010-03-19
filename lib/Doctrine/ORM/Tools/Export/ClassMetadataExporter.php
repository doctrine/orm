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

use Doctrine\ORM\Tools\ClassMetadataReader,
    Doctrine\ORM\EntityManager;

/**
 * Class used for converting your mapping information between the
 * supported formats: yaml, xml, and php/annotation.
 *
 *     [php]
 *     // Unify all your mapping information which is written in php, xml, yml
 *     // and convert it to a single set of yaml files.
 *
 *     $cme = new Doctrine\ORM\Tools\Export\ClassMetadataExporter();
 *     $cme->addMappingSource(__DIR__ . '/Entities');
 *     $cme->addMappingSource(__DIR__ . '/xml');
 *     $cme->addMappingSource(__DIR__ . '/yaml');
 *
 *     $exporter = $cme->getExporter('yaml');
 *     $exporter->setOutputDir(__DIR__ . '/new_yaml');
 *
 *     $exporter->setMetadatas($cme->getMetadatas());
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
    private static $_exporterDrivers = array(
        'xml' => 'Doctrine\ORM\Tools\Export\Driver\XmlExporter',
        'yaml' => 'Doctrine\ORM\Tools\Export\Driver\YamlExporter',
        'yml' => 'Doctrine\ORM\Tools\Export\Driver\YamlExporter',
        'php' => 'Doctrine\ORM\Tools\Export\Driver\PhpExporter',
        'annotation' => 'Doctrine\ORM\Tools\Export\Driver\AnnotationExporter'
    );

    public function __construct()
    {
        $this->_reader = new ClassMetadataReader();
    }

    /**
     * Register a new exporter driver class under a specified name
     *
     * @param string $name
     * @param string $class
     */
    public static function registerExportDriver($name, $class)
    {
        self::$_exporterDrivers[$name] = $class;
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
        $this->_reader->setEntityManager($em);
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
        if ( ! isset(self::$_exporterDrivers[$type])) {
            throw ExportException::invalidExporterDriverType($type);
        }

        $class = self::$_exporterDrivers[$type];

        return new $class($source);
    }

    /**
     * Add a new mapping directory to the array of directories to convert and export
     * to another format
     *
     *     [php]
     *     $cme = new Doctrine\ORM\Tools\Export\ClassMetadataExporter();
     *     $cme->addMappingSource(__DIR__ . '/yaml');
     *     $cme->addMappingSource($schemaManager);
     *
     * @param string $source   The source for the mapping files
     * @param string $type  The type of mapping files (yml, xml, etc.)
     * @return void
     */
    public function addMappingSource($source, $type = null)
    {
        $this->_reader->addMappingSource($source, $type);
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
        return $this->_reader->getMetadatas();
    }
}