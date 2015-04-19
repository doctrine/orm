<?php

namespace Doctrine\Tests\Models\DDC3699;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="ddc3699_child")
 */
class DDC3699Child extends DDC3699Parent
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @Column(type="string")
     */
    protected $childField;

    /**
     * @OneToOne(targetEntity="DDC3699RelationOne", inversedBy="child")
     */
    protected $oneRelation;

    /**
     * @OneToMany(targetEntity="DDC3699RelationMany", mappedBy="child")
     */
    protected $relations;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getChildField()
    {
        return $this->childField;
    }

    public function setChildField($childField)
    {
        $this->childField = $childField;
    }

    public function getOneRelation()
    {
        return $this->oneRelation;
    }

    public function setOneRelation($oneRelation)
    {
        $this->oneRelation = $oneRelation;
    }

    public function hasRelation($relation)
    {
        return $this->relations && $this->relations->contains($relation);
    }

    public function addRelation($relation)
    {
        if (!$this->hasRelation($relation)) {
            $this->relations[] = $relation;
        }

        return $this;
    }

    public function removeRelation($relation)
    {
        $this->relations->removeElement($relation);

        return $this;
    }

    public function getRelations()
    {
        return $this->relations;
    }
}