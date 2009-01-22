<?php

namespace Doctrine\Tests\Models\CMS;

/**
 * @DoctrineEntity(tableName="cms_comments")
 */
class CmsComment
{
    /**
     * @DoctrineColumn(type="integer")
     * @DoctrineId
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
     * @DoctrineManyToOne(targetEntity="Doctrine\Tests\Models\CMS\CmsArticle",
            joinColumns={"article_id" = "id"})
     */
    public $article;
}
