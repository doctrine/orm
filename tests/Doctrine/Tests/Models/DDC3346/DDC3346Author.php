<?php

namespace Doctrine\Tests\Models\DDC3346;

/**
 * @Entity
 * @Table(name="ddc3346_users")
 */
class DDC3346Author
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @Column(type="string", length=255, unique=true)
     */
    public $username;

    /**
     * @OneToMany(targetEntity="DDC3346Article", mappedBy="user", fetch="EAGER", cascade={"detach"})
     */
    public $articles = array();
}
