<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeKeyInheritance;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;

/** @Entity */
class JoinedChildClass extends JoinedRootClass
{
    /**
     * @var string
     * @Column(type="string", length=255)
     */
    public $extension = 'ext';

    /**
     * @var string
     * @Column(type="string", length=255)
     * @Id
     */
    private $additionalId = 'additional';
}
