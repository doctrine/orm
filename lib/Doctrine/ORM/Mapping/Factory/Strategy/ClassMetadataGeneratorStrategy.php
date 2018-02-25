<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory\Strategy;

use Doctrine\ORM\Mapping\Factory\ClassMetadataBuildingContext;
use Doctrine\ORM\Mapping\Factory\ClassMetadataDefinition;

interface ClassMetadataGeneratorStrategy
{
    /**
     * @param string $filePath
     * @param ClassMetadataDefinition $definition
     * @param ClassMetadataBuildingContext $metadataBuildingContext
     *
     * @return void
     */
    public function generate(
        string $filePath,
        ClassMetadataDefinition $definition,
        ClassMetadataBuildingContext $metadataBuildingContext
    ) : void;
}
