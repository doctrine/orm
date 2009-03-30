<?php

namespace Doctrine\Tests\Models\CMS;

/**
 * @DoctrineEntity
 * @DoctrineTable(name="cms_articles")
 */
class CmsArticle
{
    /**
     * @DoctrineId
     * @DoctrineColumn(type="integer")
     * @DoctrineGeneratedValue(strategy="auto")
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
     * @DoctrineManyToOne(targetEntity="CmsUser")
     * @DoctrineJoinColumn(name="user_id", referencedColumnName="id")
     */
    public $user;
    /**
     * @DoctrineOneToMany(targetEntity="CmsComment", mappedBy="article")
     */
    public $comments;
}
