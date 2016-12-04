<?php

namespace Doctrine\Tests\Models\Overrides;

/**
 * @Entity
 */
class EntityWithEmbeddableOverriddenAndRemovedAttributes
{

    /** @Id @Column(type="integer") */
    private $id;

    /**
     * @Embedded(class="Doctrine\Tests\Models\ValueObjects\ValueObject", columnPrefix=false)
     * @AttributeOverrides({
     *     @AttributeOverride(name="value", column=@Column(type="string", name="value_override")),
     *     @AttributeOverride(name="count", column=null),
     * })
     */
    public $valueObject;
}
