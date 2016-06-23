<?php

namespace Doctrine\Tests\Models\DDC5889;

/**
 * @Entity()
 * @Cache(usage="NONSTRICT_READ_WRITE")
 */
class DDC5889TranslationModel
{
    /**
     * @Id()
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * DDC5889PageModel
     * @ManyToOne(targetEntity="\Doctrine\Tests\Models\DDC5889\DDC5889PageModel", inversedBy="translations")
     */
    private $page;

    /**
     * @var string
     * @Column(type="text")
     */
    private $translatedText;

    /**
     * @var string
     * @Column(type="string")
     */
    private $language;

    /**
     * DDC5889TranslationModel constructor.
     * @param $page
     * @param $translatedText
     * @param $language
     */
    public function __construct($page, $translatedText, $language)
    {
        $this->page = $page;
        $this->translatedText = $translatedText;
        $this->language = $language;
    }
}
