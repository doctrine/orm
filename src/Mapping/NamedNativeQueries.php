<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * Is used to specify an array of native SQL named queries.
 * The NamedNativeQueries annotation can be applied to an entity or mapped superclass.
 *
 * @deprecated Named queries won't be supported in ORM 3.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class NamedNativeQueries implements MappingAttribute
{
    /**
     * One or more NamedNativeQuery annotations.
     *
     * @var array<\Doctrine\ORM\Mapping\NamedNativeQuery>
     */
    public $value = [];
}
