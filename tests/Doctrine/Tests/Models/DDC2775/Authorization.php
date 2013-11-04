<?php

namespace Doctrine\Tests\Models\DDC2775;

/**
 * @Entity
 */
class Authorization
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="authorizations")
     */
    private $user;

    /**
     * @ManyToOne(targetEntity="Role", inversedBy="authorizations")
     */
    private $role;

    public function getId()
    {
        return $this->id;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function setRole(Role $role)
    {
        $this->role = $role;
    }
}
