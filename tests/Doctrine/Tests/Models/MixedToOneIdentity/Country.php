<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\MixedToOneIdentity;

/** @Entity */
class Country
{
    /** @Id @Column(type="string") @GeneratedValue(strategy="NONE") */
    public $country;
}
