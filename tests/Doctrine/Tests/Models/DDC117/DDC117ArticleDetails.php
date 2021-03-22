<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC117;

/**
 * @Entity
 */
class DDC117ArticleDetails
{
    /**
     * @var DDC117Article
     * @Id
     * @OneToOne(targetEntity="DDC117Article", inversedBy="details")
     * @JoinColumn(name="article_id", referencedColumnName="article_id")
     */
    private $article;

    /**
     * @var string
     * @Column(type="text")
     */
    private $text;

    public function __construct(DDC117Article $article, string $text)
    {
        $this->article = $article;
        $article->setDetails($this);

        $this->update($text);
    }

    public function update(string $text): void
    {
        $this->text = $text;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
