<?php

namespace Doctrine\Tests\Models\DDC2372;

use Doctrine\ORM\Annotation as ORM;

/** @ORM\Entity @ORM\Table(name="users") */
class DDC2372User
{
    use Traits\DDC2372AddressTrait;

    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** @ORM\Column(type="string", length=50) */
    private $name;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}