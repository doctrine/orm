<?php
namespace Shitty\Tests\Models\CompositeKeyInheritance;

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
