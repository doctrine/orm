<?php

namespace Doctrine\Tests\Models\DDC3231;

/**
 * @Entity(repositoryClass="DDC3231User2Repository")
 * @Table(name="users2")
 */
class DDC3231User2
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=255)
     */
    protected $name;

}
