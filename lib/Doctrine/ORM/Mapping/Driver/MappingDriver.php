<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataBuildingContext;

/**
 * Contract for metadata drivers.
 *
 * @package Doctrine\ORM\Mapping\Driver
 * @since  3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
interface MappingDriver
{
    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @param string                       $className
     * @param ClassMetadata                $metadata
     * @param ClassMetadataBuildingContext $metadataBuildingContext
     *
     * @return void
     */
    public function loadMetadataForClass(
        string $className,
        ClassMetadata $metadata, // ComponentMetadata $parent
        ClassMetadataBuildingContext $metadataBuildingContext
    ); // : ComponentMetadata

    /**
     * Gets the names of all mapped classes known to this driver.
     *
     * @return array The names of all mapped classes known to this driver.
     */
    public function getAllClassNames();

    /**
     * Returns whether the class with the specified name should have its metadata loaded.
     * This is only the case if it is either mapped as an Entity or a MappedSuperclass.
     *
     * @param string $className
     *
     * @return boolean
     */
    public function isTransient($className);
}
