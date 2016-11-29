<?php

namespace Doctrine\Tests\Models\Cache;

/**
 * @Entity
 * @Table("cache_client_address")
 */
class Address
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @JoinColumn(name="person_id", referencedColumnName="id")
     * @OneToOne(targetEntity="Person", inversedBy="address")
     */
    public $person;

    /**
     * @Column
     */
    public $location;

    public function __construct($location)
    {
        $this->location = $location;
    }
}
