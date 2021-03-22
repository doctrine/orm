<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Hydration;

use Doctrine\Common\Collections\Collection;

/** @Entity */
class EntityWithArrayDefaultArrayValueM2M
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @psalm-var Collection<int, SimpleEntity>
     * @ManyToMany(targetEntity=SimpleEntity::class)
     */
    public $collection = [];
}
