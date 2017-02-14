<?php

namespace Doctrine\Tests\Models\DDC117;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class DDC117ApproveChanges
{
    /**
     * @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="DDC117ArticleDetails")
     * @ORM\JoinColumn(name="details_id", referencedColumnName="article_id")
     */
    private $articleDetails;

    /**
     * @ORM\ManyToOne(targetEntity="DDC117Reference")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="source_id", referencedColumnName="source_id"),
     *  @ORM\JoinColumn(name="target_id", referencedColumnName="target_id")
     * })
     */
    private $reference;

    /**
     * @ORM\ManyToOne(targetEntity="DDC117Translation")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="trans_article_id", referencedColumnName="article_id"),
     *  @ORM\JoinColumn(name="trans_language", referencedColumnName="language")
     * })
     */
    private $translation;

    public function __construct($details, $reference, $translation)
    {
        $this->articleDetails = $details;
        $this->reference = $reference;
        $this->translation = $translation;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getArticleDetails()
    {
        return $this->articleDetails;
    }

    public function getReference()
    {
        return $this->reference;
    }

    public function getTranslation()
    {
        return $this->translation;
    }
}