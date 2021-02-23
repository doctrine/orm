<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ORM\Annotation as ORM;

/**
 * CmsAddress
 *
 * @ORM\Entity
 * @ORM\Table(name="cms_addresses")
 * @ORM\EntityListeners({CmsAddressListener::class})
 */
class CmsAddress
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id @ORM\GeneratedValue
     */
    public $id;

    /** @ORM\Column(length=50) */
    public $country;

    /** @ORM\Column(length=50) */
    public $zip;

    /** @ORM\Column(length=50) */
    public $city;

    /**
     * Testfield for Schema Updating Tests.
     */
    public $street;

    /**
     * @ORM\OneToOne(targetEntity=CmsUser::class, inversedBy="address")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    public $user;

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getZipCode()
    {
        return $this->zip;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setUser(CmsUser $user)
    {
        if ($this->user !== $user) {
            $this->user = $user;
            $user->setAddress($this);
        }
    }
}
