<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="cms_articles")
 */
class CmsArticle
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     * @Column(type="string", length=255)
     */
    public $topic;

    /**
     * @var string
     * @Column(type="text")
     */
    public $text;

    /**
     * @var CmsUser
     * @ManyToOne(targetEntity="CmsUser", inversedBy="articles")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    public $user;

    /**
     * @var Collection<int, CmsComment>
     * @OneToMany(targetEntity="CmsComment", mappedBy="article")
     */
    public $comments;

    /**
     * @var int
     * @Version
     * @column(type="integer")
     */
    public $version;

    public function setAuthor(CmsUser $author): void
    {
        $this->user = $author;
    }

    public function addComment(CmsComment $comment): void
    {
        $this->comments[] = $comment;
        $comment->setArticle($this);
    }
}
