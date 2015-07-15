<?php

namespace Doctrine\Tests\Models\DDC3699;

/**
 * @Entity
 * @Table(name="ddc3699_relation_many")
 */
class DDC3699RelationMany
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="DDC3699Child", inversedBy="relations")
     * @JoinColumn(name="child", referencedColumnName="id")
     */
    protected $child;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getChild()
    {
        return $this->child;
    }

    public function setChild($child)
    {
        $this->child = $child;
    }
}
