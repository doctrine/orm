<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * The EntityListeners attribute specifies the callback listener classes to be used for an entity or mapped superclass.
 * The EntityListeners attribute may be applied to an entity class or mapped superclass.
 *
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class EntityListeners implements MappingAttribute
{
    /**
     * Specifies the names of the entity listeners.
     *
     * @var array<string>
     * @readonly
     */
    public $value = [];

    /** @param array<string> $value */
    public function __construct(array $value = [])
    {
        $this->value = $value;
    }
}
