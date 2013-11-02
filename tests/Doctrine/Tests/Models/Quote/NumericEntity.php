<?php

namespace Doctrine\Tests\Models\Quote;

/**
 * @Entity
 * @Table(name="table")
 */
class NumericEntity
{
    /**
     * @Id
     * @Column(type="integer", name="`1:1`")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(type="string", name="`2:2`")
     */
    public $value;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

}