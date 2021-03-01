<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Hydration;

/** @Entity */
class SimpleEntity
{
    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    public $id;
}
