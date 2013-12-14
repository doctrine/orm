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
    public $id;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="roles")
     */
    public $user;

    /**
     * @OneToMany(targetEntity="Authorization", mappedBy="role", cascade={"all"}, orphanRemoval=true)
     */
    public $authorizations;

    public function addAuthorization(Authorization $authorization)
    {
        $this->authorizations[] = $authorization;
        $authorization->role = $this;
    }
}
