<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueGenerators;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("vg_association_identifier_target")
 */
class AssociationIdentifierTarget
{
    public const ID = 123;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @var int
     */
    private $id = self::ID;

    public function getId() : int
    {
        return $this->id;
    }
}
