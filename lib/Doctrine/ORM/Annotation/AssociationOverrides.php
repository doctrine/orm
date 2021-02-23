<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * This annotation is used to override association mappings of relationship properties.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class AssociationOverrides implements Annotation
{
    /**
     * Mapping overrides of relationship properties.
     *
     * @var array<\Doctrine\ORM\Annotation\AssociationOverride>
     */
    public $value;
}
