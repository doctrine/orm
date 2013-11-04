<?php

namespace Doctrine\Tests\Models\DDC2775;

/**
 * @Entity @Table(name="users")
 */
class User
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @OneToMany(targetEntity="Role", mappedBy="user", cascade={"all"}, orphanRemoval=true)
     */
    public $roles;

    /**
     * @OneToMany(targetEntity="Authorization", mappedBy="user", cascade={"all"}, orphanRemoval=true)
     */
    public $authorizations;

    public function getId()
    {
        return $this->id;
    }

    public function addRole(Role $role)
    {
        $this->roles[] = $role;
        $role->setUser($this);
    }

    public function addAuthorization(Authorization $authorization)
    {
        $this->authorizations[] = $authorization;
        $authorization->setUser($this);
    }
}
