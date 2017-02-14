<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="cms_articles")
 */
class CmsArticle
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @ORM\Column(type="string", length=255)
     */
    public $topic;
    /**
     * @ORM\Column(type="text")
     */
    public $text;
    /**
     * @ORM\ManyToOne(targetEntity="CmsUser", inversedBy="articles")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    public $user;
    /**
     * @ORM\OneToMany(targetEntity="CmsComment", mappedBy="article")
     */
    public $comments;

    /**
     * @ORM\Version
     * @ORM\Column(type="integer")
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
