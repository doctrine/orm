<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * Is used to specify an array of native SQL named queries.
 * The NamedNativeQueries annotation can be applied to an entity or mapped superclass.
 *
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 * @since   2.3
 *
 * @Annotation
 * @Target("CLASS")
 */
final class NamedNativeQueries implements Annotation
{
    /**
     * One or more NamedNativeQuery annotations.
     *
     * @var array<\Doctrine\ORM\Annotation\NamedNativeQuery>
     */
    public $value = [];
}
