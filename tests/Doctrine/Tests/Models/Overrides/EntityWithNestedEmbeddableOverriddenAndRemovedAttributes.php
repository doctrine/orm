<?php

namespace Doctrine\Tests\Models\Overrides;

/**
 * @Entity
 */
class EntityWithNestedEmbeddableOverriddenAndRemovedAttributes
{

    /** @Id @Column(type="integer") */
    private $id;

    /**
     * @Embedded(class="Doctrine\Tests\Models\ValueObjects\NestedValueObject", columnPrefix=false)
     * @AttributeOverrides({
     *     @AttributeOverride(name="nested.value", column=@Column(type="string", name="value_override")),
     *     @AttributeOverride(name="nested.count", column=null),
     * })
     */
    public $nestedValueObject;
}
