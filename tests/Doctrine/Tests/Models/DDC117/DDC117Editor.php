<?php

namespace Doctrine\Tests\Models\DDC117;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class DDC117Editor
{
    /**
     * @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public $name;

    /**
     * @ORM\ManyToMany(targetEntity="DDC117Translation", inversedBy="reviewedByEditors")
     * @ORM\JoinTable(
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="article_id", referencedColumnName="article_id"),
     *     @ORM\JoinColumn(name="language", referencedColumnName="language")
     *   },
     *   joinColumns={
     *     @ORM\JoinColumn(name="editor_id", referencedColumnName="id")
     *   }
     * )
     */
    public $reviewingTranslations;

    /**
     * @ORM\ManyToOne(targetEntity="DDC117Translation", inversedBy="lastTranslatedBy")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="lt_article_id", referencedColumnName="article_id"),
     *   @ORM\JoinColumn(name="lt_language", referencedColumnName="language")
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