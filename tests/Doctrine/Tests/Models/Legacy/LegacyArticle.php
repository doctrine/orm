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
    public $_id;
    /**
     * @Column(name="sTopic", type="string", length=255)
     */
    public $_topic;
    /**
     * @Column(name="sText", type="text")
     */
    public $_text;
    /**
     * @ManyToOne(targetEntity="LegacyUser", inversedBy="_articles")
     * @JoinColumn(name="iUserId", referencedColumnName="iUserId")
     */
    public $_user;
    public function setAuthor(LegacyUser $author) {
        $this->_user = $author;
    }
}
