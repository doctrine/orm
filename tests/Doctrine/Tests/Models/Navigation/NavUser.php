<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Navigation;

/**
 * @Entity
 * @Table(name="navigation_users")
 */
class NavUser
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @generatedValue
     */
    private $id;

    /**
     * @var string
     * @column(type="string")
     */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
