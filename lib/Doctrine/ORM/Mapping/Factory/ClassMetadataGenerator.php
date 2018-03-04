<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

use Doctrine\ORM\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Mapping\Exporter\ClassMetadataExporter;

/**
 * This factory is used to generate metadata classes.
 */
class ClassMetadataGenerator
{
    /** @var MappingDriver */
    protected $mappingDriver;

    /** @var ClassMetadataExporter */
    private $metadataExporter;

    public function __construct(
        MappingDriver $mappingDriver,
        ?ClassMetadataExporter $metadataExporter = null
    ) {
        $this->mappingDriver    = $mappingDriver;
        $this->metadataExporter = $metadataExporter ?: new ClassMetadataExporter();
    }

    /**
     * Generates class metadata code.
     */
    public function generate(ClassMetadataDefinition $definition) : string
    {
        $metadata = $this->mappingDriver->loadMetadataForClass(
            $definition->entityClassName,
            $definition->parentClassMetadata
        );

        return $this->metadataExporter->export($metadata);
    }
}
