<?php
namespace Doctrine\Tests\Models\CompositeKeyInheritance;

/**
 * @Entity
 * @Table(name = "joined_derived_root")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"child" = "JoinedDerivedChildClass", "root" = "JoinedDerivedRootClass"})
 */
class JoinedDerivedRootClass
{
    /**
     * @var JoinedDerivedIdentityClass
     * @ManyToOne(
     *     targetEntity="JoinedDerivedIdentityClass",
     *     inversedBy="children"
     * )
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
