<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC753;

/**
 * @Entity(repositoryClass = "\stdClass")
 */
class DDC753EntityWithInvalidRepository
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
