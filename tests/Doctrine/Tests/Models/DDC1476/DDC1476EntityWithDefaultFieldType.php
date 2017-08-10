<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC1476;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;

/**
 * @ORM\Entity()
 */
class DDC1476EntityWithDefaultFieldType
{
    /**
     * @ORM\Id
     * @ORM\Column()
     * @ORM\GeneratedValue("NONE")
     */
    protected $id;

    /** @ORM\Column() */
    protected $name;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
