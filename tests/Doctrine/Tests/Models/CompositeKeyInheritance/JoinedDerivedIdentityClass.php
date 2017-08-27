<?php
namespace Doctrine\Tests\Models\CompositeKeyInheritance;

/**
 * @Entity
 * @Table(name = "joined_derived_identity")
 */
class JoinedDerivedIdentityClass
{
    /**
     * @var string
     * @Column(type="string")
     * @Id
     */
    protected $id = 'part-0';

    /**
     * @var JoinedDerivedRootClass[]
     * @OneToMany(
     *     targetEntity="JoinedDerivedRootClass",
     *     mappedBy="keyPart1"
     * )
     */
    protected $children;
}
