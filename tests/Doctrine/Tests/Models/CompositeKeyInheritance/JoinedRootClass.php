<?php
namespace Doctrine\Tests\Models\CompositeKeyInheritance;

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"child" = "JoinedChildClass", "root" = "JoinedRootClass"})
 */
class JoinedRootClass
{
    /**
     * @var string
     * @Column(type="string")
     * @Id
     */
    protected $keyPart1 = 'part-1';

    /**
     * @var string
     * @Column(type="string")
     * @Id
     */
    protected $keyPart2 = 'part-2';
}
