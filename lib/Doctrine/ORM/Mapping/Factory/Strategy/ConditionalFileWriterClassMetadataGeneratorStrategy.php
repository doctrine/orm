<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory\Strategy;

use Doctrine\ORM\Mapping\Factory\ClassMetadataBuildingContext;
use Doctrine\ORM\Mapping\Factory\ClassMetadataDefinition;
use function file_exists;

class ConditionalFileWriterClassMetadataGeneratorStrategy extends FileWriterClassMetadataGeneratorStrategy
{
    /**
     * {@inheritdoc}
     */
    public function generate(
        string $filePath,
        ClassMetadataDefinition $definition,
        ClassMetadataBuildingContext $metadataBuildingContext
    ) : void
    {
        if (! file_exists($filePath)) {
            parent::generate($filePath, $definition, $metadataBuildingContext);

            return;
        }

        require $filePath;
    }
}
