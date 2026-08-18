<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Stringable;

#[Table(name: 'cms_comments')]
#[Entity]
class CmsComment implements Stringable
{
    /** @var int */
    #[Column(type: 'integer')]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;

    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $topic;

    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $text;

    /** @var CmsUser|null */
    #[ManyToOne(targetEntity: 'CmsUser')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    public $user;

    /** @var CmsArticle */
    #[ManyToOne(targetEntity: 'CmsArticle', inversedBy: 'comments')]
    #[JoinColumn(name: 'article_id', referencedColumnName: 'id')]
    public $article;

    public function setAuthor(CmsUser|null $author): void
    {
        $this->user = $author;
    }

    public function setArticle(CmsArticle $article): void
    {
        $this->article = $article;
    }

    public function __toString(): string
    {
        return self::class . '[id=' . $this->id . ']';
    }
}
