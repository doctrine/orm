<?php

namespace Doctrine\Tests\Models\Overrides;

/**
 * @Entity
 */
class EntityWithEmbeddableRemovedAttribute
{
    /** @Id @Column(type="integer") */
    private $id;

    /**
     * @Embedded(class="Doctrine\Tests\Models\ValueObjects\ValueObject", columnPrefix=false)
     * @AttributeOverride(name="value", column=null)
     */
    public $valueObject;
}
