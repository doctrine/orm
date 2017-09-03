<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueGenerators;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("vg_association_identifier")
 */
class AssociationIdentifier
{
    /**
     * @ORM\Column(type="string")
     * @ORM\Id
     * @ORM\GeneratedValue("CUSTOM")
     * @ORM\CustomIdGenerator("Doctrine\Tests\Models\ValueGenerators\FooGenerator")
     * @var string|null
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="AssociationIdentifierTarget", cascade={"persist"})
     * @ORM\Id
     * @var AssociationIdentifierTarget
     */
    private $target;

    /**
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue("CUSTOM")
     * @ORM\CustomIdGenerator("Doctrine\Tests\Models\ValueGenerators\BarGenerator")
     * @var string|null
     */
    private $regular;

    public function __construct()
    {
        $this->target = new AssociationIdentifierTarget();
    }

    public function getTarget() : AssociationIdentifierTarget
    {
        return $this->target;
    }

    public function getId() : ?string
    {
        return $this->id;
    }

    public function getRegular() : ?string
    {
        return $this->regular;
    }
}
