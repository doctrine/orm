<?php

#namespace Doctrine\Tests\Models\CMS;

/**
 * @DoctrineEntity
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
     * @DoctrineColumn(type="string", length=255)
     */
    public $topic;
    /**
     * @DoctrineColumn(type="string")
     */
    public $text;
    /**
     * @DoctrineManyToOne(targetEntity="CmsArticle", joinColumns={"article_id" = "id"})
     */
    public $article;
}
