<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class NamedQueries implements Annotation
{
    /**
     * @var array<\Doctrine\ORM\Annotation\NamedQuery>
     */
    public $value;
}
