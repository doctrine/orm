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
final class OrderBy implements MappingAttribute
{
    /**
     * @var array<string>
     * @readonly
     */
    public $value;

    /** @param array<string> $value */
    public function __construct(array $value)
    {
        $this->value = $value;
    }
}
