<?php

namespace Doctrine\Tests\Models\DDC117;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class DDC117ArticleDetails
{
    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="DDC117Article", inversedBy="details")
     * @ORM\JoinColumn(name="article_id", referencedColumnName="article_id")
     */
    private $article;

    /**
     * @ORM\Column(type="text")
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