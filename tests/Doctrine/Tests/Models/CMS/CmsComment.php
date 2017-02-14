<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="cms_comments")
 */
class CmsComment
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @ORM\Column(type="string", length=255)
     */
    public $topic;
    /**
     * @ORM\Column(type="string")
     */
    public $text;
    /**
     * @ORM\ManyToOne(targetEntity="CmsArticle", inversedBy="comments")
     * @ORM\JoinColumn(name="article_id", referencedColumnName="id")
     */
    public $article;

    public function setArticle(CmsArticle $article) {
        $this->article = $article;
    }

    public function __toString() {
        return __CLASS__."[id=".$this->id."]";
    }
}
