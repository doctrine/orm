<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Generic;

/**
 * @Entity
 * @Table(name="serialize_model")
 */
class SerializationModel
{
    /**
     * @var int
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var mixed[]
     * @Column(name="the_array", type="array", nullable=true)
     */
    public $array;

    /**
     * @var object
     * @Column(name="the_obj", type="object", nullable=true)
     */
    public $object;
}
