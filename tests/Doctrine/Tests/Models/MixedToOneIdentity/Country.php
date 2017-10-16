<?php

namespace Doctrine\Tests\Models\MixedToOneIdentity;

/** @Entity */
class Country
{
    /** @Id @Column(type="string") @GeneratedValue(strategy="NONE") */
    public $country;
}
