<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ORM\EntityRepository;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class MappedSuperclass implements MappingAttribute
{
    /** @psalm-param class-string<EntityRepository>|null $repositoryClass */
    public function __construct(
        public readonly string|null $repositoryClass = null,
    ) {
    }
}
