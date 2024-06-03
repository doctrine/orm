<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeKeyRelations;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;

/** @Entity */
class CustomerClass
{
    /**
     * @var string
     * @Id
     * @Column(type="string")
     */
    public $companyCode;

    /**
     * @var string
     * @Id
     * @Column(type="string")
     */
    public $code;

    /**
     * @var string
     * @Column(type="string")
     */
    public $name;
}
