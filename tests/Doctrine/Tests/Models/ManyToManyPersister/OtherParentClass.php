<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ManyToManyPersister;

/**
 * @Entity
 * @Table(name="manytomanypersister_other_parent")
 */
class OtherParentClass
{
    /**
     * @Id
     * @Column(name="id", type="integer")
     * @var int
     */
    public $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }
}
