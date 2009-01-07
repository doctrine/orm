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
     * @DoctrineColumn(type="varchar", length=255)
     */
    public $topic;
    /**
     * @DoctrineColumn(type="varchar")
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
