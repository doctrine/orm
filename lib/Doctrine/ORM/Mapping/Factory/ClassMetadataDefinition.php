<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

use Doctrine\ORM\Mapping\ClassMetadata;

class ClassMetadataDefinition
{
    /** @var string */
    public $entityClassName;

    /** @var string */
    public $metadataClassName;

    /** @var ClassMetadata|null */
    public $parentClassMetadata;

    public function __construct(
        string $entityClassName,
        string $metadataClassName,
        ?ClassMetadata $parentMetadata = null
    ) {
        $this->entityClassName     = $entityClassName;
        $this->metadataClassName   = $metadataClassName;
        $this->parentClassMetadata = $parentMetadata;
    }
}
