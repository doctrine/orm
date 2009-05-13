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
     * @DoctrineColumn(type="string", length=255)
     */
    public $topic;
    /**
     * @DoctrineColumn(type="string")
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
