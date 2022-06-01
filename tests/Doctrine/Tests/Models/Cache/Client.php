<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

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
     * @param string $name
     */
    public function __construct(
        /**
         * @Column(unique=true)
         */
        public $name
    )
    {
    }
}
