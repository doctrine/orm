<?php

namespace Doctrine\Tests\Models\DDC117;

/**
 * @Entity
 */
class DDC117ArticleDetails
{
    /**
     * @Id
     * @OneToOne(targetEntity="DDC117Article", inversedBy="details")
     * @JoinColumn(name="article_id", referencedColumnName="article_id")
     */
    private $article;

    /**
     * @Column(type="text")
     */
    private $text;

    public function __construct($article, $text)
    {
        $this->article = $article;
        $article->setDetails($this);

        $this->update($text);
    }

    public function update($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }
}