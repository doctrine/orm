<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3711;

use Doctrine\Common\Collections\ArrayCollection;

class DDC3711EntityA
{
    /** @var int */
    private $id1;

    /** @var int */
    private $id2;

    /** @var ArrayCollection */
    private $entityB;

    public function getId1(): mixed
    {
        return $this->id1;
    }

    public function setId1(mixed $id1): void
    {
        $this->id1 = $id1;
    }

    public function getId2(): mixed
    {
        return $this->id2;
    }

    public function setId2(mixed $id2): void
    {
        $this->id2 = $id2;
    }

    public function getEntityB(): ArrayCollection
    {
        return $this->entityB;
    }

    public function addEntityB(ArrayCollection $entityB): DDC3711EntityA
    {
        $this->entityB[] = $entityB;

        return $this;
    }
}
