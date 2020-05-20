<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GeneratorChanges;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="generator_changes")
 */
class GeneratorChanges
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=255);
     */
    public $name;

    public function __construct()
    {
        $this->setName('');
    }

    /**
     * @return mixed
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId(?int $id):void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName(string $name):void
    {
        $this->name = $name;
    }
}