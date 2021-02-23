<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeKeyInheritance;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class JoinedChildClass extends JoinedRootClass
{
    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    public $extension = 'ext';

    /**
     * @ORM\Column(type="string")
     * @ORM\Id
     *
     * @var string
     */
    private $additionalId = 'additional';
}
