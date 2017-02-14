<?php

namespace Doctrine\Tests\Models\Legacy;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="legacy_articles")
 */
class LegacyArticle
{
    /**
     * @ORM\Id
     * @ORM\Column(name="iArticleId", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(name="sTopic", type="string", length=255)
     */
    public $topic;

    /**
     * @ORM\Column(name="sText", type="text")
     */
    public $text;

    /**
     * @ORM\ManyToOne(targetEntity="LegacyUser", inversedBy="articles")
     * @ORM\JoinColumn(name="iUserId", referencedColumnName="iUserId")
     */
    public $user;
    
    public function setAuthor(LegacyUser $author) 
    {
        $this->user = $author;
    }
}
