<?php

namespace Doctrine\Tests\Models\CMS;

/**
 * @DoctrineEntity
 * @DoctrineTable(name="cms_comments")
 */
class CmsComment
{
    /**
     * @DoctrineColumn(type="integer")
     * @DoctrineId
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
     * @DoctrineManyToOne(targetEntity="CmsArticle")
     * @DoctrineJoinColumn(name="article_id", referencedColumnName="id")
     */
    public $article;
}
