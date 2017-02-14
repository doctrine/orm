<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ORM\Annotation as ORM;

/**
 * CmsEmail
 *
 * @ORM\Entity
 * @ORM\Table(name="cms_emails")
 */
class CmsEmail
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\Column(length=250)
     */
    public $email;

    /**
     * @ORM\OneToOne(targetEntity="CmsUser", mappedBy="email")
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