<?php
namespace Doctrine\Tests\Models\CompositeKeyInheritance;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name = "joined_derived_child")
 */
class JoinedDerivedChildClass extends JoinedDerivedRootClass
{
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    public $extension = 'ext';

    /**
     * @var string
     * @ORM\Column(type="string")
     * @ORM\Id
     */
    private $additionalId = 'additional';
}
