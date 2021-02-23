<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ManyToManyPersister;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="manytomanypersister_other_parent")
 */
class OtherParentClass
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     *
     * @var int
     */
    public $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }
}
