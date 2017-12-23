<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3231;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity(repositoryClass=DDC3231User1Repository::class)
 * @ORM\Table(name="users")
 */
class DDC3231User1
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
