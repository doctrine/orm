<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\FriendObject;

use Doctrine\ORM\Annotation as ORM;

/** @ORM\Entity */
class ComparableObject
{
    /** @ORM\Id @ORM\Column(type="integer") */
    public $id;

    /** @ORM\Column(type="string") */
    private $comparedField;

    public function equalTo(self $other) : bool
    {
        return $other === $this
            || $other->comparedField = $this->comparedField;
    }

    public function setComparedFieldValue(string $value) : void
    {
        $this->comparedField = $value;
    }
}
