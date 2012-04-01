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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools\Export\Driver;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Doctrine\ORM\Tools\Export\ExportException;

/**
 * Abstract base class which is to be used for the Exporter drivers
 * which can be found in \Doctrine\ORM\Tools\Export\Driver
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Guilheme Blanco <guilhermeblanco@hotmail.com>
 */
abstract class AbstractExporter
{
    /**
     * @var array
     */
    protected $_metadata = array();

    /**
     * @var string
     */
    protected $_outputDir;

    /**
     * @var string
     */
    protected $_extension;

    /**
     * @var boolean
     */
    protected $_overwriteExistingFiles = false;

    /**
     * Constructor
     *
     * @param string $dir
     */
    public function __construct($dir = null)
    {
        $this->_outputDir = $dir;
    }

    /**
     * Define the overwrite existing file
     *
     * @param boolean $overwrite
     */
    public function setOverwriteExistingFiles($overwrite)
    {
        $this->_overwriteExistingFiles = $overwrite;
    }

    /**
     * Converts a single ClassMetadata instance to the exported format and returns it
     *
     * @param ClassMetadataInfo $metadata
     *
     * @return mixed $exported
     */
    abstract public function exportClassMetadata(ClassMetadataInfo $metadata);

    /**
     * Set the array of ClassMetadataInfo instances to export
     *
     * @param array $metadata
     */
    public function setMetadata(array $metadata)
    {
        $this->_metadata = $metadata;
    }

    /**
     * Get the extension used to generated the path to a class
     *
     * @return string $extension
     */
    public function getExtension()
    {
        return $this->_extension;
    }

    /**
     * Set the directory to output the mapping files to
     *
     *     [php]
     *     $exporter = new YamlExporter($metadata);
     *     $exporter->setOutputDir(__DIR__ . '/yaml');
     *     $exporter->export();
     *
     * @param string $dir
     */
    public function setOutputDir($dir)
    {
        $this->_outputDir = $dir;
    }

    /**
     * Export each ClassMetadata instance to a single Doctrine Mapping file named after the entity
     *
     */
    public function export()
    {
        if ( ! is_dir($this->_outputDir)) {
            mkdir($this->_outputDir, 0777, true);
        }

        foreach ($this->_metadata as $metadata) {
            // In case output is returned, write it to a file, skip otherwise
            if ($output = $this->exportClassMetadata($metadata)) {
                $path = $this->generateOutputPath($metadata);
                $dir  = dirname($path);

                if ( ! is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }

                if (file_exists($path) && ! $this->_overwriteExistingFiles) {
                    throw ExportException::attemptOverwriteExistingFile($path);
                }

                file_put_contents($path, $output);
            }
        }
    }

    /**
     * Generate the path to write the class for the given ClassMetadataInfo instance
     *
     * @param ClassMetadataInfo $metadata
     *
     * @return string $path
     */
    protected function generateOutputPath(ClassMetadataInfo $metadata)
    {
        return $this->_outputDir . '/' . str_replace('\\', '.', $metadata->name) . $this->_extension;
    }

    /**
     * Set the directory to output the mapping files to
     *
     *     [php]
     *     $exporter = new YamlExporter($metadata, __DIR__ . '/yaml');
     *     $exporter->setExtension('.yml');
     *     $exporter->export();
     *
     * @param string $extension
     */
    public function setExtension($extension)
    {
        $this->_extension = $extension;
    }

    /**
     * Retrieve the inheritance type string
     *
     * @param integer $type
     *
     * @return string
     */
    protected function getInheritanceTypeString($type)
    {
        switch ($type) {
            case ClassMetadataInfo::INHERITANCE_TYPE_NONE:
                return 'NONE';

            case ClassMetadataInfo::INHERITANCE_TYPE_JOINED:
                return 'JOINED';

            case ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_TABLE:
                return 'SINGLE_TABLE';

            case ClassMetadataInfo::INHERITANCE_TYPE_TABLE_PER_CLASS:
                return 'PER_CLASS';
        }
    }

    /**
     * Retrieve the change tracking policy
     *
     * @param integer $policy
     *
     * @return string
     */
    protected function getChangeTrackingPolicyString($policy)
    {
        switch ($policy) {
            case ClassMetadataInfo::CHANGETRACKING_DEFERRED_IMPLICIT:
                return 'DEFERRED_IMPLICIT';

            case ClassMetadataInfo::CHANGETRACKING_DEFERRED_EXPLICIT:
                return 'DEFERRED_EXPLICIT';

            case ClassMetadataInfo::CHANGETRACKING_NOTIFY:
                return 'NOTIFY';
        }
    }

    /**
     * Retrieve the ID generator type
     *
     * @param integer $type
     *
     * @return string
     */
    protected function getIdGeneratorTypeString($type)
    {
        switch ($type) {
            case ClassMetadataInfo::GENERATOR_TYPE_AUTO:
                return 'AUTO';

            case ClassMetadataInfo::GENERATOR_TYPE_SEQUENCE:
                return 'SEQUENCE';

            case ClassMetadataInfo::GENERATOR_TYPE_TABLE:
                return 'TABLE';

            case ClassMetadataInfo::GENERATOR_TYPE_IDENTITY:
                return 'IDENTITY';
        }
    }
}