<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Quote;

/**
 * @Entity
 * @Table(name="`quote-address`")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({"simple" = Address::class, "full" = FullAddress::class})
 */
class Address
{
    /**
     * @var int
     * @Id
     * @GeneratedValue
     * @Column(type="integer", name="`address-id`")
     */
    public $id;

    /**
     * @var string
     * @Column(name="`address-zip`")
     */
    public $zip;

    /**
     * @var User
     * @OneToOne(targetEntity="User", inversedBy="address")
     * @JoinColumn(name="`user-id`", referencedColumnName="`user-id`")
     */
    public $user;

    public function setUser(User $user): void
    {
        if ($this->user !== $user) {
            $this->user = $user;
            $user->setAddress($this);
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getZip(): string
    {
        return $this->zip;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
