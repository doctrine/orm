<?php

namespace Doctrine\Tests\Models\CMS;

/**
 * @Entity
 * @Table(name="cms_article_tags")
 */
class CmsArticleTag
{
    /**
     * @Id
     * @Column(type="string", length=255)
     */
    private $tag;

    /**
     * @Id
     * @ManyToOne(targetEntity="CmsArticle");
     */
    private $article;


    public function getTag()
    {
        return $this->tag;
    }

    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    public function getArticle()
    {
        return $this->article;
    }

    public function setArticle($article)
    {
        $this->article = $article;
    }
}
