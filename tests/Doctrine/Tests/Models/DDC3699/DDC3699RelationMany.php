<?php

namespace Doctrine\Tests\Models\DDC3699;

/**
 * @Entity
 * @Table(name="ddc3699_relation_many")
 */
class DDC3699RelationMany
{
    /** @Id @Column(type="integer") */
    public $id;

    /** @ManyToOne(targetEntity="DDC3699Child", inversedBy="relations") */
    public $child;
}
