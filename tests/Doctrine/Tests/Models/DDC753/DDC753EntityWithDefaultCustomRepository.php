<?php

namespace Doctrine\Tests\Models\DDC753;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity()
 */
class DDC753EntityWithDefaultCustomRepository
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /** @ORM\Column(type="string") */
    protected $name;

}
