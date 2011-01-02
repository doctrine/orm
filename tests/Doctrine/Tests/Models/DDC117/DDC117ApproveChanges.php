<?php

namespace Doctrine\Tests\Models\DDC117;

/**
 * @Entity
 */
class DDC117ApproveChanges
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="DDC117ArticleDetails")
     * @JoinColumn(name="details_id", referencedColumnName="article_id")
     */
    private $articleDetails;

    /**
     * @ManyToOne(targetEntity="DDC117Reference")
     * @JoinColumns({
     *  @JoinColumn(name="source_id", referencedColumnName="source_id"),
     *  @JoinColumn(name="target_id", referencedColumnName="target_id")
     * })
     */
    private $reference;

    /**
     * @ManyToOne(targetEntity="DDC117Translation")
     * @JoinColumns({
     *  @JoinColumn(name="trans_article_id", referencedColumnName="article_id"),
     *  @JoinColumn(name="trans_language", referencedColumnName="language")
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