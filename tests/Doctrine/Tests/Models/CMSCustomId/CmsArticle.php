<?php

namespace Doctrine\Tests\Models\CMSCustomId;

/**
 * @Entity
 * @Table(name="cms_articles_customid")
 */
class CmsArticle
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
     * @Column(type="text")
     */
    public $text;
    /**
     * @ManyToOne(targetEntity="CmsUser", inversedBy="articles")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    public $user;
    /**
     * @OneToMany(targetEntity="CmsComment", mappedBy="article")
     */
    public $comments;

    /**
     * @Version @column(type="integer")
     */
    public $version;

    public function setAuthor(CmsUser $author) {
        $this->user = $author;
    }

    public function addComment(CmsComment $comment) {
        $this->comments[] = $comment;
        $comment->setArticle($this);
    }
}
