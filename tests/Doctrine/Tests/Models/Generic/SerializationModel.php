<?php

namespace Doctrine\Tests\Models\Generic;

/**
 * @Entity
 * @Table(name="serialize_model")
 */
class SerializationModel
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @Column(name="the_array", type="array", nullable=true)
     */
    public $array;

    /**
     * @Column(name="the_obj", type="object", nullable=true)
     */
    public $object;
}