<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeKeyInheritance;

/**
 * @Entity
 */
class JoinedChildClass extends JoinedRootClass
{
    /**
     * @var string
     * @Column(type="string")
     */
    public $extension = 'ext';

    /**
     * @var string
     * @Column(type="string")
     * @Id
     */
    private $additionalId = 'additional';
}
