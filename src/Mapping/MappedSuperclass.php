<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\ORM\EntityRepository;

#[Attribute(Attribute::TARGET_CLASS)]
final class MappedSuperclass implements MappingAttribute
{
    /** @param class-string<EntityRepository>|null $repositoryClass */
    public function __construct(
        public readonly string|null $repositoryClass = null,
    ) {
    }
}
