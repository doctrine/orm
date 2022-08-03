<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Quote;

/**
 * @Entity
 * @Table(name="table")
 */
class NumericEntity
{
    /**
     * @var int
     * @Id
     * @Column(type="integer", name="`1:1`")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     * @Column(type="string", name="`2:2`")
     */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
