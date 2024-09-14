<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("PROPERTY")
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class GeneratedValue implements MappingAttribute
{
    /**
     * The type of ID generator.
     *
     * @var string
     * @psalm-var 'AUTO'|'SEQUENCE'|'IDENTITY'|'NONE'|'CUSTOM'
     * @readonly
     * @Enum({"AUTO", "SEQUENCE", "TABLE", "IDENTITY", "NONE", "UUID", "CUSTOM"})
     */
    public $strategy = 'AUTO';

    /** @psalm-param 'AUTO'|'SEQUENCE'|'IDENTITY'|'NONE'|'CUSTOM' $strategy */
    public function __construct(string $strategy = 'AUTO')
    {
        $this->strategy = $strategy;
    }
}
