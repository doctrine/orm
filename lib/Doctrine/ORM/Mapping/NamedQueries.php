<?php

namespace Doctrine\ORM\Mapping;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class NamedQueries implements Annotation
{
    /** @var array<\Doctrine\ORM\Mapping\NamedQuery> */
    public $value;
}
