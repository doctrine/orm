<?php
namespace Doctrine\Tests\Models\CompositeKeyInheritance;

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"child" = "SingleChildClass",})
 */
class SingleRootClass
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
