<?php

namespace Doctrine\Tests\Models\MixedToOneIdentity;

/** @Entity */
class Country
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="string") @GeneratedValue(strategy="NONE") */
    public $country;
}
