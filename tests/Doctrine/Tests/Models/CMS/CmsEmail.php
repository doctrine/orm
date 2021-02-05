<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

/**
 * CmsEmail
 *
 * @Entity
 * @Table(name="cms_emails")
 */
class CmsEmail
{
    /**
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    public $id;

    /** @Column(length=250) */
    public $email;

    /** @OneToOne(targetEntity="CmsUser", mappedBy="email") */
    public $user;

    public function getId()
    {
        return $this->id;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email): void
    {
        $this->email = $email;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(CmsUser $user): void
    {
        $this->user = $user;
    }
}
