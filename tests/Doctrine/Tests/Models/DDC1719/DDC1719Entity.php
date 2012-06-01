<?php

namespace Doctrine\Tests\Models\DDC1719;

/**
 * @Entity
 * @Table(name="`ddc-1719-entity`")
 */
class DDC1719Entity
{

    /**
     * @Id
     * @Column(type="integer", name="`entity-id`")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(type="string", name="`entity-value`")
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