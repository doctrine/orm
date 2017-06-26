<?php

namespace Doctrine\Tests\Models\Quote;

/**
 * @Entity
 * @Table(name="`quote-city`")
 */
class City
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer", name="`city-id`")
     */
    public $id;

    /**
     * @Column(name="`city-name`")
     */
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
