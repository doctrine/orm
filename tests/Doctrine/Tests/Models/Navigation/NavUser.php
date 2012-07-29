<?php

namespace Doctrine\Tests\Models\Navigation;

/**
 * @Entity
 * @Table(name="navigation_users")
 */
class NavUser
{
    /**
     * @Id
     * @Column(type="integer")
     * @generatedValue
     */
    private $id;

    /**
     * @column(type="string")
     */
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

