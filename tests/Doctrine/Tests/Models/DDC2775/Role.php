<?php

namespace Doctrine\Tests\Models\DDC2775;

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="role_type", type="string")
 * @DiscriminatorMap({"admin"="AdminRole"})
 */
abstract class Role
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="roles")
     */
    private $user;

    /**
     * @OneToMany(targetEntity="Authorization", mappedBy="role", cascade={"all"}, orphanRemoval=true)
     */
    public $authorizations;

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function addAuthorization(Authorization $authorization)
    {
        $this->authorizations[] = $authorization;
        $authorization->setRole($this);
    }
}
