<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC753;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity(repositoryClass = DDC753CustomRepository::class)
 */
class DDC753EntityWithCustomRepository
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
