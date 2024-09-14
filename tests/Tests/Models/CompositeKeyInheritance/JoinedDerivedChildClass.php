<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeKeyInheritance;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name = "joined_derived_child")
 */
class JoinedDerivedChildClass extends JoinedDerivedRootClass
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
