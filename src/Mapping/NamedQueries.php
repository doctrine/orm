<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * @deprecated Named queries won't be supported in ORM 3.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class NamedQueries implements MappingAttribute
{
    /** @var array<\Doctrine\ORM\Mapping\NamedQuery> */
    public $value;
}
