<?php

namespace Doctrine\Tests\Models\DDC2372;

use Doctrine\ORM\Annotation as ORM;

/** @ORM\Entity @ORM\Table(name="addresses") */
class DDC2372Address
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** @ORM\Column(type="string", length=255) */
    private $street;

    /** @ORM\OneToOne(targetEntity="User", mappedBy="address") */
    private $user;

    public function getId()
    {
        return $this->id;
    }

    public function getStreet()
    {
        return $this->street;
    }

    public function setStreet($street)
    {
        $this->street = $street;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        if ($this->user !== $user) {
            $this->user = $user;
            $user->setAddress($this);
        }
    }
}