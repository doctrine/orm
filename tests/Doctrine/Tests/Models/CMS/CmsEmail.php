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
     * @var int
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    public $id;

    /**
     * @var string
     * @Column(length=250)
     */
    public $email;

    /**
     * @var CmsUser
     * @OneToOne(targetEntity="CmsUser", mappedBy="email")
     */
    public $user;

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getUser(): CmsUser
    {
        return $this->user;
    }

    public function setUser(CmsUser $user): void
    {
        $this->user = $user;
    }
}
