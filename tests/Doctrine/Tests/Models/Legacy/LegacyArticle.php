<?php

namespace Doctrine\Tests\Models\Legacy;

/**
 * @Entity
 * @Table(name="legacy_articles")
 */
class LegacyArticle
{
    /**
     * @Id
     * @Column(name="iArticleId", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @Column(name="sTopic", type="string", length=255)
     */
    public $topic;
    /**
     * @Column(name="sText", type="text")
     */
    public $text;
    /**
     * @ManyToOne(targetEntity="LegacyUser", inversedBy="articles")
     * @JoinColumn(name="iUserId", referencedColumnName="iUserId")
     */
    public $user;
    
    public function setAuthor(LegacyUser $author) 
    {
        $this->user = $author;
    }
}
