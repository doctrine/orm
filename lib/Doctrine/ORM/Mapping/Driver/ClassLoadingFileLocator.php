<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use Doctrine\ORM\Mapping\MappingException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use SplFileInfo;
use const DIRECTORY_SEPARATOR;
use function array_merge;
use function array_unique;
use function get_declared_classes;
use function in_array;
use function is_dir;
use function is_file;
use function realpath;
use function str_replace;
use function strpos;

class ClassLoadingFileLocator implements FileLocator
{
    /**
     * The paths where to look for mapping files.
     *
     * @var string[]
     */
    protected $paths = [];

    /**
     * The paths excluded from path where to look for mapping files.
     *
     * @var string[]
     */
    protected $excludedPaths = [];

    /**
     * The file extension of mapping documents.
     *
     * @var string|null
     */
    protected $fileExtension = 'php';

    /**
     * Initializes a new FileDriver that looks in the given path(s) for mapping
     * documents and operates in the specified operating mode.
     *
     * @param string|string[] $paths         One or multiple paths where mapping documents can be found.
     * @param string|null     $fileExtension The file extension of mapping documents, usually prefixed with a dot.
     */
    public function __construct($paths, $fileExtension = null)
    {
        $this->addPaths((array) $paths);
        $this->fileExtension = $fileExtension;
    }

    /**
     * Appends lookup paths to metadata driver.
     *
     * @param string[] $paths
     */
    public function addPaths(array $paths) : void
    {
        $this->paths = array_unique(array_merge($this->paths, $paths));
    }

    /**
     * Retrieves the defined metadata lookup paths.
     *
     * @return string[]
     */
    public function getPaths() : array
    {
        return $this->paths;
    }

    /**
     * Append exclude lookup paths to metadata driver.
     *
     * @param string[] $paths
     */
    public function addExcludedPaths(array $paths) : void
    {
        $this->excludedPaths = array_unique(array_merge($this->excludedPaths, $paths));
    }

    /**
     * Retrieve the defined metadata lookup exclude paths.
     *
     * @return string[]
     */
    public function getExcludedPaths() : array
    {
        return $this->excludedPaths;
    }

    /**
     * Gets the file extension used to look for mapping files under.
     */
    public function getFileExtension() : ?string
    {
        return $this->fileExtension;
    }

    /**
     * Sets the file extension used to look for mapping files under.
     *
     * @param string|null $fileExtension The file extension to set.
     */
    public function setFileExtension($fileExtension) : void
    {
        $this->fileExtension = $fileExtension;
    }

    /**
     * {@inheritDoc}
     */
    public function findMappingFile($className)
    {
        $fileName = str_replace('\\', '.', $className) . $this->fileExtension;

        // Check whether file exists
        foreach ($this->paths as $path) {
            if (is_file($path . DIRECTORY_SEPARATOR . $fileName)) {
                return $path . DIRECTORY_SEPARATOR . $fileName;
            }
        }

        throw MappingException::mappingFileNotFound($className, $fileName);
    }

    /**
     * {@inheritDoc}
     */
    public function getAllClassNames($globalBasename)
    {
        $classes       = [];
        $includedFiles = [];

        if (! $this->paths) {
            return $classes;
        }

        foreach ($this->paths as $path) {
            if (! is_dir($path)) {
                throw MappingException::fileMappingDriversRequireConfiguredDirectoryPath($path);
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                $fileName = $file->getBasename($this->fileExtension);
                $filePath = $file->getRealPath();

                if ($fileName === $globalBasename || $fileName === $file->getBasename()) {
                    continue;
                }

                foreach ($this->excludedPaths as $excludedPath) {
                    $exclude = str_replace('\\', '/', realpath($excludedPath));

                    if (strpos($filePath, $exclude) !== false) {
                        continue 2; // Cancel foreach for excluded paths and goes to next file for processing
                    }
                }

                require_once $filePath;

                $includedFiles[] = $filePath;
            }
        }

        $declaredClasses = get_declared_classes();

        foreach ($declaredClasses as $declaredClassName) {
            try {
                $reflection = new ReflectionClass($declaredClassName);

                if (in_array($reflection->getFileName(), $includedFiles, true)) {
                    $classes[] = $declaredClassName;
                }
            } catch (ReflectionException $exception) {
                // Swallow exception as it is irrelevant in the context here
            }
        }

        return $classes;
    }

    /**
     * {@inheritDoc}
     */
    public function fileExists($className)
    {
        $fileName = str_replace('\\', '.', $className) . $this->fileExtension;

        // Check whether file exists
        foreach ($this->paths as $path) {
            if (is_file($path . DIRECTORY_SEPARATOR . $fileName)) {
                return true;
            }
        }

        return false;
    }
}