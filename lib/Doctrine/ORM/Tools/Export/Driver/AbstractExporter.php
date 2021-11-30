<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Export\Driver;

use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\Export\ExportException;

use function chmod;
use function dirname;
use function file_exists;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function str_replace;

/**
 * Abstract base class which is to be used for the Exporter drivers
 * which can be found in \Doctrine\ORM\Tools\Export\Driver.
 *
 * @deprecated 2.7 This class is being removed from the ORM and won't have any replacement
 *
 * @link    www.doctrine-project.org
 */
abstract class AbstractExporter
{
    /** @var ClassMetadata[] */
    protected $_metadata = [];

    /** @var string|null */
    protected $_outputDir;

    /** @var string|null */
    protected $_extension;

    /** @var bool */
    protected $_overwriteExistingFiles = false;

    /**
     * @param string|null $dir
     */
    public function __construct($dir = null)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/issues/8458',
            '%s is deprecated with no replacement',
            self::class
        );

        $this->_outputDir = $dir;
    }

    /**
     * @param bool $overwrite
     *
     * @return void
     */
    public function setOverwriteExistingFiles($overwrite)
    {
        $this->_overwriteExistingFiles = $overwrite;
    }

    /**
     * Converts a single ClassMetadata instance to the exported format
     * and returns it.
     *
     * @return string
     */
    abstract public function exportClassMetadata(ClassMetadataInfo $metadata);

    /**
     * Sets the array of ClassMetadata instances to export.
     *
     * @psalm-param list<ClassMetadata> $metadata
     *
     * @return void
     */
    public function setMetadata(array $metadata)
    {
        $this->_metadata = $metadata;
    }

    /**
     * Gets the extension used to generated the path to a class.
     *
     * @return string|null
     */
    public function getExtension()
    {
        return $this->_extension;
    }

    /**
     * Sets the directory to output the mapping files to.
     *
     *     [php]
     *     $exporter = new YamlExporter($metadata);
     *     $exporter->setOutputDir(__DIR__ . '/yaml');
     *     $exporter->export();
     *
     * @param string $dir
     *
     * @return void
     */
    public function setOutputDir($dir)
    {
        $this->_outputDir = $dir;
    }

    /**
     * Exports each ClassMetadata instance to a single Doctrine Mapping file
     * named after the entity.
     *
     * @return void
     *
     * @throws ExportException
     */
    public function export()
    {
        if (! is_dir($this->_outputDir)) {
            mkdir($this->_outputDir, 0775, true);
        }

        foreach ($this->_metadata as $metadata) {
            // In case output is returned, write it to a file, skip otherwise
            $output = $this->exportClassMetadata($metadata);
            if ($output) {
                $path = $this->_generateOutputPath($metadata);
                $dir  = dirname($path);
                if (! is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }

                if (file_exists($path) && ! $this->_overwriteExistingFiles) {
                    throw ExportException::attemptOverwriteExistingFile($path);
                }

                file_put_contents($path, $output);
                chmod($path, 0664);
            }
        }
    }

    /**
     * Generates the path to write the class for the given ClassMetadataInfo instance.
     *
     * @return string
     */
    protected function _generateOutputPath(ClassMetadataInfo $metadata)
    {
        return $this->_outputDir . '/' . str_replace('\\', '.', $metadata->name) . $this->_extension;
    }

    /**
     * Sets the directory to output the mapping files to.
     *
     *     [php]
     *     $exporter = new YamlExporter($metadata, __DIR__ . '/yaml');
     *     $exporter->setExtension('.yml');
     *     $exporter->export();
     *
     * @param string $extension
     *
     * @return void
     */
    public function setExtension($extension)
    {
        $this->_extension = $extension;
    }

    /**
     * @param int $type
     * @psalm-param ClassMetadataInfo::INHERITANCE_TYPE_* $type
     *
     * @return string
     */
    protected function _getInheritanceTypeString($type)
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
     * @param int $mode
     * @psalm-param ClassMetadataInfo::FETCH_* $mode
     *
     * @return string
     */
    protected function _getFetchModeString($mode)
    {
        switch ($mode) {
            case ClassMetadataInfo::FETCH_EAGER:
                return 'EAGER';

            case ClassMetadataInfo::FETCH_EXTRA_LAZY:
                return 'EXTRA_LAZY';

            case ClassMetadataInfo::FETCH_LAZY:
                return 'LAZY';
        }
    }

    /**
     * @param int $policy
     * @psalm-param ClassMetadataInfo::CHANGETRACKING_* $policy
     *
     * @return string
     */
    protected function _getChangeTrackingPolicyString($policy)
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
     * @param int $type
     * @psalm-param ClassMetadataInfo::GENERATOR_TYPE_* $type
     *
     * @return string
     */
    protected function _getIdGeneratorTypeString($type)
    {
        switch ($type) {
            case ClassMetadataInfo::GENERATOR_TYPE_AUTO:
                return 'AUTO';

            case ClassMetadataInfo::GENERATOR_TYPE_SEQUENCE:
                return 'SEQUENCE';

            case ClassMetadataInfo::GENERATOR_TYPE_IDENTITY:
                return 'IDENTITY';

            case ClassMetadataInfo::GENERATOR_TYPE_UUID:
                return 'UUID';

            case ClassMetadataInfo::GENERATOR_TYPE_CUSTOM:
                return 'CUSTOM';
        }
    }
}
