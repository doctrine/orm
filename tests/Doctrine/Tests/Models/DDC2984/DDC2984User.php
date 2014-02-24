<?php

namespace Doctrine\Tests\Models\DDC2984;

use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity @ORM\Table(name="users") */
class DDC2984User
{
    /**
     * @ORM\Id @ORM\Column(type="ddc2984_domain_user_id")
     * @ORM\GeneratedValue(strategy="NONE")
     *
     * @var DDC2984DomainUserId
     */
    private $userId;

    /** @ORM\Column(type="string", length=50) */
    private $name;

    public function __construct(DDC2984DomainUserId $aUserId)
    {
        $this->userId = $aUserId;
    }

    /**
     * @return DDC2984DomainUserId
     */
    public function userId()
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function applyName($name)
    {
        $this->name = $name;
    }

    /**
     * @param DDC2984User $other
     * @return bool
     */
    public function sameIdentityAs(DDC2984User $other)
    {
        return $this->userId()->sameValueAs($other->userId());
    }
}