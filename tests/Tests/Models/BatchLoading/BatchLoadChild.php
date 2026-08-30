<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\BatchLoading;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class BatchLoadChild
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    #[ORM\Column]
    public string $code = '';

    #[ORM\ManyToOne(targetEntity: BatchLoadOwner::class, inversedBy: 'children')]
    public BatchLoadOwner|null $owner = null;

    #[ORM\ManyToOne(targetEntity: BatchLoadOwner::class, inversedBy: 'indexedChildren')]
    public BatchLoadOwner|null $indexedOwner = null;
}
