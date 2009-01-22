<?php

namespace Doctrine\Tests\Models\CMS;

/**
 * @DoctrineEntity(tableName="cms_articles")
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
     * @DoctrineManyToOne(targetEntity="Doctrine\Tests\Models\CMS\CmsUser",
            joinColumns={"user_id" = "id"})
     */
    public $user;
    /**
     * @DoctrineOneToMany(targetEntity="Doctrine\Tests\Models\CMS\CmsComment", mappedBy="article")
     */
    public $comments;
}
