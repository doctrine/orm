<?php

namespace Doctrine\Tests\Models\Generic;

/**
 * @Entity
 * @Table(name="`not-a-simple-entity`")
 */
class NonAlphaColumnsEntity
{
    /**
     * @Id
     * @Column(type="integer", name="`simple-entity-id`")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(type="string", name="`simple-entity-value`")
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