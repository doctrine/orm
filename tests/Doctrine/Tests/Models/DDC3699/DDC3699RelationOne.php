<?php

namespace Doctrine\Tests\Models\DDC3699;

/**
 * @Entity
 * @Table(name="ddc3699_relation_one")
 */
class DDC3699RelationOne
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") */
    public $id;

    /** @OneToOne(targetEntity="DDC3699Child", mappedBy="oneRelation") */
    public $child;
}
