<?php

namespace Doctrine\Tests\Models\DDC117;

/**
 * @Entity
 */
class DDC117Editor
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $name;

    /**
     * @ManyToMany(targetEntity="DDC117Translation", inversedBy="reviewedByEditors")
     * @JoinTable(
     *   inverseJoinColumns={
     *     @JoinColumn(name="article_id", referencedColumnName="article_id"),
     *     @JoinColumn(name="language", referencedColumnName="language")
     *   },
     *   joinColumns={
     *     @JoinColumn(name="editor_id", referencedColumnName="id")
     *   }
     * )
     */
    public $reviewingTranslations;

    /**
     * @ManyToOne(targetEntity="DDC117Translation", inversedBy="lastTranslatedBy")
     * @JoinColumns({
     *   @JoinColumn(name="lt_article_id", referencedColumnName="article_id"),
     *   @JoinColumn(name="lt_language", referencedColumnName="language")
     * })
     */
    public $lastTranslation;

    public function __construct($name = "")
    {
        $this->name = $name;
        $this->reviewingTranslations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function addLastTranslation(DDC117Translation $t)
    {
        $this->lastTranslation = $t;
        $t->lastTranslatedBy[] = $this;
    }
}