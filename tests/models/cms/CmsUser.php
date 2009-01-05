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
     * @DoctrineColumn(type="string", length=50)
     */
    public $status;
    /**
     * @DoctrineColumn(type="string", length=255)
     */
    public $username;
    /**
     * @DoctrineColumn(type="string", length=255)
     */
    public $name;
    /**
     * @DoctrineOneToMany(targetEntity="CmsPhonenumber", mappedBy="user")
     */
    public $phonenumbers;
    /**
     * @DoctrineOneToMany(targetEntity="CmsArticle", mappedBy="user")
     */
    public $articles;
}
