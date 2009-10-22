<?php

namespace Doctrine\Tests\Models\CMS;

/**
 * CmsAddress
 *
 * @author Roman S. Borschel
 * @Entity
 * @Table(name="cms_addresses")
 */
class CmsAddress
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(type="string", length=50)
     */
    public $country;

    /**
     * @Column(type="string", length=50)
     */
    public $zip;

    /**
     * @Column(type="string", length=50)
     */
    public $city;

    /**
     * @OneToOne(targetEntity="CmsUser")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    public $user;

    public function getId() {
        return $this->id;
    }
    
    public function getUser() {
        return $this->user;
    }

    public function getCountry() {
        return $this->country;
    }

    public function getZipCode() {
        return $this->zip;
    }

    public function getCity() {
        return $this->city;
    }
    
    public function setUser(CmsUser $user) {
        if ($this->user !== $user) {
            $this->user = $user;
            $user->setAddress($this);
        }
    }
}