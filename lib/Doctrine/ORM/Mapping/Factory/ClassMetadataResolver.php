<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

/**
 * Interface ClassMetadataResolver
 */
interface ClassMetadataResolver
{
    public function resolveMetadataClassName(string $className) : string;

    public function resolveMetadataClassPath(string $className) : string;
}
