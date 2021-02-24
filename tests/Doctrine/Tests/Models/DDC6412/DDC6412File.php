<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC6412;

/**
 * @Entity
 */
class DDC6412File
{
    /**
     * @var int
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @var string
     * @Column(length=50, name="file_name")
     */
    public $name;
}
