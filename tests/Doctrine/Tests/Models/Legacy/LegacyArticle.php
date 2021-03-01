<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Legacy;

/**
 * @Entity
 * @Table(name="legacy_articles")
 */
class LegacyArticle
{
    /**
     * @var int
     * @Id
     * @Column(name="iArticleId", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     * @Column(name="sTopic", type="string", length=255)
     */
    public $topic;

    /**
     * @var string
     * @Column(name="sText", type="text")
     */
    public $text;

    /**
     * @var LegacyUser
     * @ManyToOne(targetEntity="LegacyUser", inversedBy="_articles")
     * @JoinColumn(name="iUserId", referencedColumnName="iUserId")
     */
    public $user;

    public function setAuthor(LegacyUser $author): void
    {
        $this->user = $author;
    }
}
