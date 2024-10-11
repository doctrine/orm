<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DateTimeCompositeKey;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
class ArticleAudit
{
    #[Id]
    #[ManyToOne(targetEntity: Article::class, inversedBy: 'audit')]
    private Article $article;

    #[Id]
    #[Column]
    private DateTimeImmutable $issuedAt;

    #[Id]
    #[Column]
    private string $changedKey;

    public function __construct(DateTimeImmutable $issuedAt, string $changedKey, Article $article)
    {
        $this->issuedAt   = $issuedAt;
        $this->changedKey = $changedKey;
        $this->article    = $article;
    }

    public function getArticle(): Article
    {
        return $this->article;
    }

    public function getIssuedAt(): DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function getChangedKey(): string
    {
        return $this->changedKey;
    }
}
