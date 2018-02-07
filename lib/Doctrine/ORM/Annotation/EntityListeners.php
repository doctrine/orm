<?php

declare(strict_types=1);

namespace Doctrine\ORM\Annotation;

/**
 * The EntityListeners annotation specifies the callback listener classes to be used for an entity or mapped superclass.
 * The EntityListeners annotation may be applied to an entity class or mapped superclass.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class EntityListeners implements Annotation
{
    /**
     * Specifies the names of the entity listeners.
     *
     * @var array<string>
     */
    public $value = [];
}
