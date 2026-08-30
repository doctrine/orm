<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\BatchLoading;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class BatchLoadProfile
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    #[ORM\Column]
    public string $bio = '';

    #[ORM\OneToOne(targetEntity: BatchLoadOwner::class, inversedBy: 'profile')]
    public BatchLoadOwner|null $owner = null;
}
