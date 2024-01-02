<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class DiscriminatorMap implements MappingAttribute
{
    /**
     * @var array<int|string, string>
     * @readonly
     */
    public $value;

    /** @param array<int|string, string> $value */
    public function __construct(array $value)
    {
        $this->value = $value;
    }
}
