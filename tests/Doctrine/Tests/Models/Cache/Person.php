<?php

namespace Doctrine\Tests\Models\Cache;

/**
 * @Entity
 * @Table("cache_person")
 * @Cache("NONSTRICT_READ_WRITE")
 */
class Person
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @Column(unique=true)
     */
    public $name;

    /**
     * @OneToOne(targetEntity="Address", mappedBy="person")
     */
    public $address;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
