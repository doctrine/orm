<?php

#namespace Doctrine\Tests\ORM\Models\Cms;

/**
 * @DoctrineEntity
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
     * @DoctrineOneToMany(targetEntity="CmsPhonenumber", mappedBy="user", cascade={"save"})
     */
    public $phonenumbers;
    /**
     * @DoctrineOneToMany(targetEntity="CmsArticle", mappedBy="user")
     */
    public $articles;

    /**
     * Adds a phonenumber to the user.
     *
     * @param <type> $phone
     */
    public function addPhonenumber(CmsPhonenumber $phone) {
        $this->phonenumbers[] = $phone;
        $phone->user = $this;
    }
}
