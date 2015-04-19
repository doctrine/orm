<?php

namespace Doctrine\Tests\Models\DDC3699;

/**
 * @MappedSuperclass
 */
abstract class DDC3699Parent
{
    const CLASSNAME = __CLASS__;

    /**
     * @Column(type="string")
     */
    protected $parentField;

    public function getParentField()
    {
        return $this->parentField;
    }

    public function setParentField($parentField)
    {
        $this->parentField = $parentField;
    }
}