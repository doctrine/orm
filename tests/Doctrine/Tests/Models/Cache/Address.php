<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

/**
 * @Entity
 * @Table("cache_client_address")
 */
class Address
{
    /**
     * @var int
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @var Person
     * @JoinColumn(name="person_id", referencedColumnName="id")
     * @OneToOne(targetEntity="Person", inversedBy="address")
     */
    public $person;

    /**
     * @var string
     * @Column
     */
    public $location;

    public function __construct(string $location)
    {
        $this->location = $location;
    }
}
