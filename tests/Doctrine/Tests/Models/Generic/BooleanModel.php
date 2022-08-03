<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Generic;

/**
 * @Entity
 * @Table(name="boolean_model")
 */
class BooleanModel
{
    /**
     * @var int
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    public $booleanField;
}
