<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * @Annotation
 * @Target("ANNOTATION")
 */
final class NamedQuery implements MappingAttribute
{
    /** @var string */
    public $name;

    /** @var string */
    public $query;
}
