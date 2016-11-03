<?php

namespace Doctrine\Tests\Models\DDC753;

/**
 * @Entity(repositoryClass = "Doctrine\Tests\Models\DDC753\DDC753CustomRepository")
 */
class DDC753EntityWithCustomRepository
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
