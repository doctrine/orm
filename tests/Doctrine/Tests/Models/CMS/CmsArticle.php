<?php

namespace Doctrine\Tests\Models\CMS;

/**
 * @Entity
 * @Table(name="cms_articles")
 */
class CmsArticle
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
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
     * @ManyToOne(targetEntity="CmsUser")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    public $user;
    /**
     * @OneToMany(targetEntity="CmsComment", mappedBy="article")
     */
    public $comments;
    
    public function setAuthor(CmsUser $author) {
        $this->user = $author;
    }
}
