<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class JoinColumns implements Annotation
{
    /** @var array<\Doctrine\ORM\Mapping\JoinColumn> */
    public $value;

    public function __construct(array $value)
    {
        $this->value = $value;
    }
}
