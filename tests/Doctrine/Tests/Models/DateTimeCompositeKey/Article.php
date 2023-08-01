<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DateTimeCompositeKey;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity]
class Article
{
    #[Id]
    #[Column]
    #[GeneratedValue]
    private int|null $id = null;
    #[Column]
    private string $title;

    #[Column]
    private string $content;

    /** @var Collection<int, ArticleAudit> */
    #[OneToMany(targetEntity: ArticleAudit::class, mappedBy: 'article', cascade: ['ALL'])]
    private Collection $audit;

    public function __construct(string $title, string $content)
    {
        $this->title   = $title;
        $this->content = $content;
        $this->audit   = new ArrayCollection();
    }

    public function changeTitle(string $newTitle): void
    {
        $this->title = $newTitle;
        $this->updateAudit('title');
    }

    public function changeContent(string $newContent): void
    {
        $this->content = $newContent;
        $this->updateAudit('content');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, ArticleAudit>
     */
    public function getAudit(): Collection
    {
        return $this->audit;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    private function updateAudit(string $changedKey): void
    {
        $this->audit[] = new ArticleAudit(
            new DateTimeImmutable(),
            $changedKey,
            $this
        );
    }
}
