<?php

namespace Doctrine\Tests\Models\Hydration;

/** @Entity */
class SimpleEntity
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    public $id;
}
