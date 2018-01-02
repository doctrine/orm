<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * This annotation is used to override the mapping of a entity property.
 *
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 * @since   2.3
 *
 * @Annotation
 * @Target("CLASS")
 */
final class AttributeOverrides implements Annotation
{
    /**
     * One or more field or property mapping overrides.
     *
     * @var array<\Doctrine\ORM\Annotation\AttributeOverride>
     */
    public $value;
}
