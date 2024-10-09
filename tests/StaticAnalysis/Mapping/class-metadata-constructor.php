<?php

declare(strict_types=1);

namespace Doctrine\StaticAnalysis\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;

/** @template T of object */
class MetadataGenerator
{
    /**
     * @param class-string<T> $entityName
     *
     * @return ClassMetadata<T>
     */
    public function createMetadata(string $entityName): ClassMetadata
    {
        return new ClassMetadata($entityName);
    }
}
