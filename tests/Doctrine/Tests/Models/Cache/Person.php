<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

/**
 * @Entity
 * @Table("cache_person")
 * @Cache("NONSTRICT_READ_WRITE")
 */
class Person
{
    /**
     * @var int
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @var string
     * @Column(unique=true)
     */
    public $name;

    /**
     * @var Address
     * @OneToOne(targetEntity="Address", mappedBy="person")
     */
    public $address;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
