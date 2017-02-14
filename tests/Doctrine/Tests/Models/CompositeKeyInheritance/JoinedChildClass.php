<?php

namespace Doctrine\Tests\Models\CompositeKeyInheritance;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class JoinedChildClass extends JoinedRootClass
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
