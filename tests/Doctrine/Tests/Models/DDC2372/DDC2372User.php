<?php

namespace Doctrine\Tests\Models\DDC2372;

use Doctrine\Tests\Models\DDC2372\Traits\DDC2372AddressTrait;

/** @Entity @Table(name="users") */
class DDC2372User
{
    use DDC2372AddressTrait;

    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
    /** @Column(type="string", length=50) */
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