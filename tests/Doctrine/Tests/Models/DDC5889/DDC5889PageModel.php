<?php

namespace Doctrine\Tests\Models\DDC5889;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity()
 * @Cache(usage="NONSTRICT_READ_WRITE")
 */
class DDC5889PageModel
{
    /**
     * @Id()
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var ArrayCollection
     * @Cache(usage="NONSTRICT_READ_WRITE")
     * @OneToMany(targetEntity="\Doctrine\Tests\Models\DDC5889\DDC5889TranslationModel", mappedBy="page", cascade={"persist", "remove"}, indexBy="language", orphanRemoval=true)
     */
    private $translations;

    /**
     * @var string
     * @Column(type="string")
     */
    private $title;

    /**
     * DDC5889PageModel constructor.
     * @param string $title
     */
    public function __construct($title)
    {
        $this->translations = new ArrayCollection();
        $this->title = $title;
    }

    /**
     * @return ArrayCollection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}
