<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3699;

/**
 * @Entity
 * @Table(name="ddc3699_relation_many")
 */
class DDC3699RelationMany
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     */
    public $id;

    /**
     * @var DDC3699Child
     * @ManyToOne(targetEntity="DDC3699Child", inversedBy="relations")
     */
    public $child;
}
