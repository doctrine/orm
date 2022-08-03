<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3699;

/**
 * @Entity
 * @Table(name="ddc3699_relation_one")
 */
class DDC3699RelationOne
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     */
    public $id;

    /**
     * @var DDC3699Child
     * @OneToOne(targetEntity="DDC3699Child", mappedBy="oneRelation")
     */
    public $child;
}
