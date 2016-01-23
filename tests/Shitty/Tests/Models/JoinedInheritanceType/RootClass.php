<?php

namespace Shitty\Tests\Models\JoinedInheritanceType;

/**
 * @Entity
 * @InheritanceType("JOINED")
 */
class RootClass
{
    /**
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    public $id;
}