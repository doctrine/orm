<?php

#namespace Doctrine\Tests\Models\CMS;

/**
 * @DoctrineEntity
 */
class CmsArticle
{
    /**
     * @DoctrineId
     * @DoctrineColumn(type="integer")
     * @DoctrineIdGenerator("auto")
     */
    public $id;
    /**
     * @DoctrineColumn(type="string", length=255)
     */
    public $topic;
    /**
     * @DoctrineColumn(type="string")
     */
    public $text;
    /**
     * @DoctrineManyToOne(targetEntity="CmsUser", joinColumns={"user_id" = "id"})
     */
    public $user;
    /**
     * @DoctrineOneToMany(targetEntity="CmsComment", mappedBy="article")
     */
    public $comments;
}
