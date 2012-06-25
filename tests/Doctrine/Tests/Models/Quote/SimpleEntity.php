<?php

namespace Doctrine\Tests\Models\Quote;

/**
 * @Entity
 * @Table(name="`ddc-1719-simple-entity`")
 */
class SimpleEntity
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