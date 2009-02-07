<?php

namespace Doctrine\Tests\Models\CMS;

/**
 * @DoctrineEntity
 * @DoctrineTable(name="cms_users")
 */
class CmsUser
{
    /**
     * @DoctrineId
     * @DoctrineColumn(type="integer")
     * @DoctrineIdGenerator("auto")
     */
    public $id;
    /**
     * @DoctrineColumn(type="varchar", length=50)
     */
    public $status;
    /**
     * @DoctrineColumn(type="varchar", length=255)
     */
    public $username;
    /**
     * @DoctrineColumn(type="varchar", length=255)
     */
    public $name;
    /**
     * @DoctrineOneToMany(targetEntity="CmsPhonenumber", mappedBy="user", cascade={"save", "delete"})
     */
    public $phonenumbers;
    /**
     * @DoctrineOneToMany(targetEntity="CmsArticle", mappedBy="user")
     */
    public $articles;
    /**
     * @DoctrineOneToOne(targetEntity="CmsAddress", mappedBy="user", cascade={"save"})
     */
    public $address;
    /**
     * @DoctrineManyToMany(targetEntity="CmsGroup", cascade={"save"})
     * @DoctrineJoinTable(name="cms_users_groups",
            joinColumns={{"name"="user_id", "referencedColumnName"="id"}},
            inverseJoinColumns={{"name"="group_id", "referencedColumnName"="id"}})
     */
    public $groups;

    /**
     * Adds a phonenumber to the user.
     *
     * @param CmsPhonenumber $phone
     */
    public function addPhonenumber(CmsPhonenumber $phone) {
        $this->phonenumbers[] = $phone;
        $phone->user = $this;
    }

    public function removePhonenumber($index) {
        if (isset($this->phonenumbers[$index])) {
            $ph = $this->phonenumbers[$index];
            unset($this->phonenumbers[$index]);
            $ph->user = null;
            return true;
        }
        return false;
    }
}
