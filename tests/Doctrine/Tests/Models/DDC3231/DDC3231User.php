<?php

namespace Doctrine\Tests\Models\DDC3231;

/**
 * @Entity(repositoryClass="DDC3231UserRepository")
 * @Table(name="users")
 */
class DDC3231User
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
