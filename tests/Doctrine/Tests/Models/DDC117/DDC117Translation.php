<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC117;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class DDC117Translation
{
    /**
     * @Id
     * @ManyToOne(targetEntity="DDC117Article", inversedBy="translations")
     * @JoinColumn(name="article_id", referencedColumnName="article_id")
     */
    private $article;

    /** @Id @column(type="string") */
    private $language;

    /** @column(type="string") */
    private $title;

    /** @ManyToMany(targetEntity="DDC117Editor", mappedBy="reviewingTranslations") */
    public $reviewedByEditors;

    /** @OneToMany(targetEntity="DDC117Editor", mappedBy="lastTranslation") */
    public $lastTranslatedBy;

    public function __construct($article, $language, $title)
    {
        $this->article           = $article;
        $this->language          = $language;
        $this->title             = $title;
        $this->reviewedByEditors = new ArrayCollection();
        $this->lastTranslatedBy  = new ArrayCollection();
    }

    public function getArticleId()
    {
        return $this->article->id();
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function getLastTranslatedBy()
    {
        return $this->lastTranslatedBy;
    }

    public function getReviewedByEditors()
    {
        return $this->reviewedByEditors;
    }
}
