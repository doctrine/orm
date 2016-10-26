<?php

namespace Doctrine\Tests\Models\DDC753;

/**
 * @Entity(repositoryClass = "\stdClass")
 */
class DDC753EntityWithInvalidRepository
{

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /** @column(type="string") */
    protected $name;

}
