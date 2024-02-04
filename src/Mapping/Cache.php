<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Caching to an entity or a collection.
 *
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target({"CLASS","PROPERTY"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class Cache implements MappingAttribute
{
    /**
     * The concurrency strategy.
     *
     * @Enum({"READ_ONLY", "NONSTRICT_READ_WRITE", "READ_WRITE"})
     * @var string
     * @psalm-var 'READ_ONLY'|'NONSTRICT_READ_WRITE'|'READ_WRITE'
     */
    public $usage = 'READ_ONLY';

    /** @var string|null Cache region name. */
    public $region;

    /** @psalm-param 'READ_ONLY'|'NONSTRICT_READ_WRITE'|'READ_WRITE' $usage */
    public function __construct(string $usage = 'READ_ONLY', ?string $region = null)
    {
        $this->usage  = $usage;
        $this->region = $region;
    }
}
