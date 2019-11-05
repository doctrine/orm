<?php

namespace Doctrine\Tests\Models\CMSCustomId;

/**
 * @Entity
 * @Table(name="cms_comments_customid")
 */
class CmsComment
{
    /**
     * @Id
     * @Column(type="CustomIdObject")
     * @GeneratedValue(strategy="NONE")
     */
    public $id;
    /**
     * @Column(type="string", length=255)
     */
    public $topic;
    /**
     * @Column(type="string")
     */
    public $text;
    /**
     * @ManyToOne(targetEntity="CmsArticle", inversedBy="comments")
     * @JoinColumn(name="article_id", referencedColumnName="id")
     */
    public $article;

    public function setArticle(CmsArticle $article) {
        $this->article = $article;
    }

    public function __toString() {
        return __CLASS__."[id=".$this->id."]";
    }
}
