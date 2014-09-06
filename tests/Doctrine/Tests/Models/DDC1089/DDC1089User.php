<?php

namespace Doctrine\Tests\Models\DDC1089;

/**
 * @Entity(repositoryClass="DDC1089UserRepository")
 * @Table(name="users")
 */
class DDC1089User
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
