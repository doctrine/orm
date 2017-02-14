<?php

namespace Doctrine\Tests\Models\DDC3231;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity(repositoryClass="DDC3231User1Repository")
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
