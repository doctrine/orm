<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC117;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 */
class DDC117Translation
{
    /**
     * @var DDC117Article
     * @Id
     * @ManyToOne(targetEntity="DDC117Article", inversedBy="translations")
     * @JoinColumn(name="article_id", referencedColumnName="article_id")
     */
    private $article;

    /**
     * @var string
     * @Id @column(type="string")
     */
    private $language;

    /**
     * @var string
     * @column(type="string")
     */
    private $title;

    /**
     * @var Collection<int, DDC117Editor>
     * @ManyToMany(targetEntity="DDC117Editor", mappedBy="reviewingTranslations")
     */
    public $reviewedByEditors;

    /**
     * @var Collection<int, DDC117Editor>
     * @OneToMany(targetEntity="DDC117Editor", mappedBy="lastTranslation")
     */
    public $lastTranslatedBy;

    public function __construct(DDC117Article $article, string $language, string $title)
    {
        $this->article           = $article;
        $this->language          = $language;
        $this->title             = $title;
        $this->reviewedByEditors = new ArrayCollection();
        $this->lastTranslatedBy  = new ArrayCollection();
    }

    public function getArticleId(): int
    {
        return $this->article->id();
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getLastTranslatedBy(): Collection
    {
        return $this->lastTranslatedBy;
    }

    public function getReviewedByEditors(): Collection
    {
        return $this->reviewedByEditors;
    }
}
