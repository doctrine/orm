<?php

namespace Doctrine\Tests\Models\OneToOne;

/**
 *
 * @Entity
 * @Table(name="second_entity")
 */
class SecondEntity
{
    /**
     * @Id
     * @Column(name="fist_entity_id")
     */
    public $fist_entity_id;

    /**
     * @Column(name="name")
     */
    public $name;

}
