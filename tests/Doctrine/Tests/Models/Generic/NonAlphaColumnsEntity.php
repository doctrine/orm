<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Generic;

/**
 * @Entity
 * @Table(name="`not-a-simple-entity`")
 */
class NonAlphaColumnsEntity
{
    /**
     * @var int
     * @Id
     * @Column(type="integer", name="`simple-entity-id`")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     * @Column(type="string", name="`simple-entity-value`")
     */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
