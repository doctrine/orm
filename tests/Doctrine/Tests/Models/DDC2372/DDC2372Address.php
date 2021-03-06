<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC2372;

/** @Entity @Table(name="addresses") */
class DDC2372Address
{
    /**
     * @var int
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @Column(type="string", length=255)
     */
    private $street;

    /**
     * @var User
     * @OneToOne(targetEntity="User", mappedBy="address")
     */
    private $user;

    public function getId(): int
    {
        return $this->id;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        if ($this->user !== $user) {
            $this->user = $user;
            $user->setAddress($this);
        }
    }
}
