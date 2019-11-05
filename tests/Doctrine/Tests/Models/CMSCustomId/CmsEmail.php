<?php

namespace Doctrine\Tests\Models\CMSCustomId;

/**
 * CmsEmail
 *
 * @Entity
 * @Table(name="cms_emails_customid")
 */
class CmsEmail
{
    /**
     * @Id
     * @Column(type="CustomIdObject")
     * @GeneratedValue(strategy="NONE")
     */
    public $id;

    /**
     * @Column(length=250)
     */
    public $email;

    /**
     * @OneToOne(targetEntity="CmsUser", mappedBy="email")
     */
    public $user;

    public function getId() {
        return $this->id;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function getUser() {
        return $this->user;
    }

    public function setUser(CmsUser $user) {
        $this->user = $user;
    }
}