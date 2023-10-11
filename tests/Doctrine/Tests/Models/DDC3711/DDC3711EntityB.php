<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3711;

use Doctrine\Common\Collections\ArrayCollection;

class DDC3711EntityB
{
    private int|null $id1 = null;

    private int|null $id2 = null;

    /** @var ArrayCollection */
    private $entityA;

    public function getId1(): int
    {
        return $this->id1;
    }

    public function setId1(int $id1): void
    {
        $this->id1 = $id1;
    }

    public function getId2(): int
    {
        return $this->id2;
    }

    public function setId2(int $id2): void
    {
        $this->id2 = $id2;
    }

    public function getEntityA(): ArrayCollection
    {
        return $this->entityA;
    }

    public function addEntityA(ArrayCollection $entityA): void
    {
        $this->entityA[] = $entityA;
    }
}
