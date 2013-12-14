<?php

namespace Doctrine\Tests\Models\DDC2775;

/**
 * @Entity @Table(name="authorizations")
 */
class Authorization
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="authorizations")
     */
    public $user;

    /**
     * @ManyToOne(targetEntity="Role", inversedBy="authorizations")
     */
    public $role;
}
