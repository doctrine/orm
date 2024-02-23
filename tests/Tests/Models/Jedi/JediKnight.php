<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Jedi;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="jedi_knights")
 */
class JediKnight
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     *
     * @var int|null
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=100)
     *
     * @var string
     */
    public $name;

    /**
     * @ORM\OneToOne(targetEntity="JediKnight", inversedBy="padawan")
     * @ORM\JoinColumn(name="master_id", nullable=true)
     *
     * @var self|null
     */
    public $master;

    /**
     * @ORM\OneToOne(targetEntity="JediKnight", mappedBy="master")
     * @ORM\JoinColumn(name="padawan_id", nullable=true)
     *
     * @var self|null
     */
    public $padawan;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
