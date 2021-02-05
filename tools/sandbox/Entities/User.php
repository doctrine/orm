<?php

declare(strict_types=1);

namespace Entities;

/** @Entity @Table(name="users") */
class User
{
    /**
     * @var int
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @Column(type="string", length=50)
     */
    private $name;

    /**
     * @var Address
     * @OneToOne(targetEntity="Address", inversedBy="user")
     * @JoinColumn(name="address_id", referencedColumnName="id")
     */
    private $address;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): void
    {
        if ($this->address !== $address) {
            $this->address = $address;
            $address->setUser($this);
        }
    }
}
