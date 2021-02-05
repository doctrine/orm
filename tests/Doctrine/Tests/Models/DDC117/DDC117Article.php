<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC117;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class DDC117Article
{
    /** @Id @Column(type="integer", name="article_id") @GeneratedValue */
    private $id;

    /** @Column */
    private $title;

    /** @OneToMany(targetEntity="DDC117Reference", mappedBy="source", cascade={"remove"}) */
    private $references;

    /** @OneToOne(targetEntity="DDC117ArticleDetails", mappedBy="article", cascade={"persist", "remove"}) */
    private $details;

    /** @OneToMany(targetEntity="DDC117Translation", mappedBy="article", cascade={"persist", "remove"}) */
    private $translations;

    /** @OneToMany(targetEntity="DDC117Link", mappedBy="source", indexBy="target_id", cascade={"persist", "remove"}) */
    private $links;

    public function __construct($title)
    {
        $this->title        = $title;
        $this->references   = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public function setDetails($details): void
    {
        $this->details = $details;
    }

    public function id()
    {
        return $this->id;
    }

    public function addReference($reference): void
    {
        $this->references[] = $reference;
    }

    public function references()
    {
        return $this->references;
    }

    public function addTranslation($language, $title): void
    {
        $this->translations[] = new DDC117Translation($this, $language, $title);
    }

    public function getText()
    {
        return $this->details->getText();
    }

    public function getDetails()
    {
        return $this->details;
    }

    public function getLinks()
    {
        return $this->links;
    }

    public function resetText(): void
    {
        $this->details = null;
    }

    public function getTranslations()
    {
        return $this->translations;
    }
}
