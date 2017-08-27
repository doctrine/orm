<?php
namespace Doctrine\Tests\Models\CompositeKeyInheritance;

/**
 * @Entity
 * @Table(name = "joined_derived_child")
 */
class JoinedDerivedChildClass extends JoinedDerivedRootClass
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
