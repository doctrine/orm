<?php

namespace Doctrine\Tests\Models\DDC117;

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

    /**
     * @Id @column(type="string")
     */
    private $language;

    /**
     * @column(type="string")
     */
    private $title;

    /**
     * @ManyToMany(targetEntity="DDC117Editor", mappedBy="reviewingTranslations")
     */
    public $reviewedByEditors;

    /**
     * @OneToMany(targetEntity="DDC117Editor", mappedBy="lastTranslation")
     */
    public $lastTranslatedBy;

    public function __construct($article, $language, $title)
    {
        $this->article = $article;
        $this->language = $language;
        $this->title = $title;
        $this->reviewedByEditors = new \Doctrine\Common\Collections\ArrayCollection();
        $this->lastTranslatedBy = new \Doctrine\Common\Collections\ArrayCollection();
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
