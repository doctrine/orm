<?php

namespace Doctrine\Tests\Models\Hydration;

use Doctrine\ORM\Annotation as ORM;

/** @ORM\Entity */
class EntityWithArrayDefaultArrayValueM2M
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    public $id;

    /** @ORM\ManyToMany(targetEntity=SimpleEntity::class) */
    public $collection = [];
}
