<?php

namespace Doctrine\Tests\Models\Hydration;

/** @Entity */
class EntityWithArrayDefaultArrayValueM2M
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    public $id;

    /** @ManyToMany(targetEntity=SimpleEntity::class) */
    public $collection = [];
}
