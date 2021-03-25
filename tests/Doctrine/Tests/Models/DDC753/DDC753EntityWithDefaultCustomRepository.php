<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC753;

/**
 * @Entity()
 */
class DDC753EntityWithDefaultCustomRepository
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @var string
     * @column(type="string")
     */
    protected $name;
}
