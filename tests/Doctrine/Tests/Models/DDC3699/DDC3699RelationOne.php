<?php

namespace Doctrine\Tests\Models\DDC3699;

/**
 * @Entity
 * @Table(name="ddc3699_relation_one")
 */
class DDC3699RelationOne
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @OneToOne(targetEntity="DDC3699Child", mappedBy="oneRelation")
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
