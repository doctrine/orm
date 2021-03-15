<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

/**
 * @Entity
 * @Table("cache_client")
 */
class Client
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

    public function __construct($name)
    {
        $this->name = $name;
    }
}
