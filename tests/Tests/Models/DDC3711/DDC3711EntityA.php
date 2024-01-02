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

    /** @return mixed */
    public function getId1()
    {
        return $this->id1;
    }

    /** @param mixed $id1 */
    public function setId1($id1): void
    {
        $this->id1 = $id1;
    }

    /** @return mixed */
    public function getId2()
    {
        return $this->id2;
    }

    /** @param mixed $id2 */
    public function setId2($id2): void
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
