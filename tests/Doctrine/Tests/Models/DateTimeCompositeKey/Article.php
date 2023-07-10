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

    /** @var Collection<int, ArticleAudit> */
    #[OneToMany(targetEntity: ArticleAudit::class, mappedBy: 'article', cascade: ['ALL'])]
    private Collection $audit;

    public function __construct(string $title)
    {
        $this->title = $title;
        $this->audit = new ArrayCollection();
    }

    public function changeTitle(string $newTitle): void
    {
        $this->title = $newTitle;
        $this->updateAudit('title');
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

    private function updateAudit(string $changedKey): void
    {
        $this->audit[] = new ArticleAudit(
            new DateTimeImmutable(),
            $changedKey,
            $this
        );
    }
}
