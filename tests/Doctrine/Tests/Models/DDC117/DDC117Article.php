<?php

namespace Doctrine\Tests\Models\DDC117;

/**
 * @Entity
 */
class DDC117Article
{
    /** @Id @Column(type="integer", name="article_id") @GeneratedValue */
    private $id;

    /** @Column */
    private $title;

    /**
     * @OneToMany(targetEntity="DDC117Reference", mappedBy="source", cascade={"remove"})
     */
    private $references;

    /**
     * @OneToOne(targetEntity="DDC117ArticleDetails", mappedBy="article", cascade={"persist", "remove"})
     */
    private $details;

    /**
     * @OneToMany(targetEntity="DDC117Translation", mappedBy="article", cascade={"persist", "remove"})
     */
    private $translations;

    /**
     * @OneToMany(targetEntity="DDC117Link", mappedBy="source", indexBy="target_id", cascade={"persist", "remove"})
     */
    private $links;

    public function __construct($title)
    {
        $this->title = $title;
        $this->references = new \Doctrine\Common\Collections\ArrayCollection();
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function setDetails($details)
    {
        $this->details = $details;
    }

    public function id()
    {
        return $this->id;
    }

    public function addReference($reference)
    {
        $this->references[] = $reference;
    }

    public function references()
    {
        return $this->references;
    }

    public function addTranslation($language, $title)
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
    public function resetText()
    {
        $this->details = null;
    }

    public function getTranslations()
    {
        return $this->translations;
    }
}
