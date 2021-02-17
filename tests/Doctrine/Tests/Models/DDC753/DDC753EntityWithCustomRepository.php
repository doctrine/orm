<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC753;

/**
 * @Entity(repositoryClass = "Doctrine\Tests\Models\DDC753\DDC753CustomRepository")
 */
class DDC753EntityWithCustomRepository
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
