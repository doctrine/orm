<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC117;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class DDC117Translation
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=DDC117Article::class, inversedBy="translations")
     * @ORM\JoinColumn(name="article_id", referencedColumnName="article_id")
     */
    private $article;

    /**
     * @ORM\Id @ORM\Column(type="string")
     */
    private $language;

    /**
     * @ORM\Column(type="string")
     */
    private $title;

    /**
     * @ORM\ManyToMany(targetEntity=DDC117Editor::class, mappedBy="reviewingTranslations")
     */
    public $reviewedByEditors;

    /**
     * @ORM\OneToMany(targetEntity=DDC117Editor::class, mappedBy="lastTranslation")
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
