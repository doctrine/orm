<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("CLASS")
 * @template TKey of int|string
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class DiscriminatorMap implements Annotation
{
    /**
     * @var array<int|string, string>
     * @psalm-var array<TKey, class-string>
     */
    public $value;

    /**
     * @param array<int|string, string> $value
     * @psalm-param array<TKey, class-string> $value
     */
    public function __construct(array $value)
    {
        $this->value = $value;
    }
}
