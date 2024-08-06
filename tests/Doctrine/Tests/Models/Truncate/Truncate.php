<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Truncate;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="truncate")
 */
class Truncate
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     *
     * @var int
     */
    private $id;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @ORM\Column(type="string", length=25)
     */
    private $test;

    public function setTest(string $test): void
    {
        $this->test = $test;
    }

    public function getTest(): string
    {
        return $this->test;
    }
}