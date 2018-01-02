<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3231;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity(repositoryClass=DDC3231User2Repository::class)
 * @ORM\Table(name="users2")
 */
class DDC3231User2
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;
}
