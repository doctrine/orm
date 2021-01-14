<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeKeyRelations;

/**
 * @Entity
 */
class CustomerClass
{
    /**
     * @var string
     * @Id @Column(type="string")
     */
    public $companyCode;

    /**
     * @var string
     * @Id @Column(type="string")
     */
    public $code;

    /**
     * @var string
     * @Column(type="string")
     */
    public $name;
}
