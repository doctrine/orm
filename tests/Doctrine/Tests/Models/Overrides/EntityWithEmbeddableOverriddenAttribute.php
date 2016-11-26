<?php

namespace Doctrine\Tests\Models\Overrides;

/**
 * @Entity
 */
class EntityWithEmbeddableOverriddenAttribute
{
    /** @Id @Column(type="integer") */
    private $id;

    /**
     * @Embedded(class="Doctrine\Tests\Models\ValueObjects\ValueObject", columnPrefix=false)
     * @AttributeOverride(name="value", column=@Column(type="string", name="value_override"))
     */
    public $valueObject;
}
