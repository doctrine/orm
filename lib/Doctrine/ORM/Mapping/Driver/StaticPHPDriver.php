<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\ORM\Mapping;

/**
 * The StaticPHPDriver calls a static loadMetadata() method on your entity
 * classes where you can manually populate the ClassMetadata instance.
 *
 * @link   www.doctrine-project.org
 * @since  2.2
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 */
class StaticPHPDriver implements MappingDriver
{
    /**
     * Paths of entity directories.
     *
     * @var array
     */
    private $paths = [];

    /**
     * Map of all class names.
     *
     * @var array
     */
    private $classNames;

    /**
     * Constructor.
     *
     * @param array|string $paths
     */
    public function __construct($paths)
    {
        $this->addPaths((array) $paths);
    }

    /**
     * Adds paths.
     *
     * @param array $paths
     *
     * @return void
     */
    public function addPaths(array $paths)
    {
        $this->paths = array_unique(array_merge($this->paths, $paths));
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass(
        string $className,
        Mapping\ClassMetadata $metadata,
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext
    )
    {
        $className::loadMetadata($metadata, $metadataBuildingContext);
    }

    /**
     * {@inheritDoc}
     * @todo Same code exists in AnnotationDriver, should we re-use it somehow or not worry about it?
     */
    public function getAllClassNames()
    {
        if ($this->classNames !== null) {
            return $this->classNames;
        }

        if (!$this->paths) {
            throw Mapping\MappingException::pathRequired();
        }

        $classes = [];
        $includedFiles = [];

        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                throw Mapping\MappingException::fileMappingDriversRequireConfiguredDirectoryPath($path);
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($file->getBasename('.php') == $file->getBasename()) {
                    continue;
                }

                $sourceFile = realpath($file->getPathName());
                require_once $sourceFile;
                $includedFiles[] = $sourceFile;
            }
        }

        $declared = get_declared_classes();

        foreach ($declared as $className) {
            $rc = new \ReflectionClass($className);
            $sourceFile = $rc->getFileName();
            if (in_array($sourceFile, $includedFiles) && !$this->isTransient($className)) {
                $classes[] = $className;
            }
        }

        $this->classNames = $classes;

        return $classes;
    }

    /**
     * {@inheritdoc}
     */
    public function isTransient($className)
    {
        return ! method_exists($className, 'loadMetadata');
    }
}
