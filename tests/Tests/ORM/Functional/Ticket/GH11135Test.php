<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH11135Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH11135MappedSuperclass::class,
            GH11135EntityWithOverride::class,
            GH11135EntityWithoutOverride::class,
        ]);
    }

    public function testOverrideInheritsDeclaringClass(): void
    {
        $cm1 = $this->_em->getClassMetadata(GH11135EntityWithOverride::class);
        $cm2 = $this->_em->getClassMetadata(GH11135EntityWithoutOverride::class);

        self::assertSame($cm1->getFieldMapping('id')['declared'], $cm2->getFieldMapping('id')['declared']);
        self::assertSame($cm1->getAssociationMapping('ref')['declared'], $cm2->getAssociationMapping('ref')['declared']);
    }
}

/**
 * @ORM\MappedSuperclass
 */
class GH11135MappedSuperclass
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="GH11135EntityWithoutOverride")
     *
     * @var GH11135EntityWithoutOverride
     */
    private $ref;
}

/**
 * @ORM\Entity()
 * @ORM\AttributeOverrides({
 *      @ORM\AttributeOverride(name="id", column=@ORM\Column(name="id_overridden"))
 * })
 * @ORM\AssociationOverrides({
 *      @ORM\AssociationOverride(name="ref", joinColumns=@ORM\JoinColumn(name="ref_overridden", referencedColumnName="id"))
 * })
 */
class GH11135EntityWithOverride extends GH11135MappedSuperclass
{
}

/**
 * @ORM\Entity()
 */
class GH11135EntityWithoutOverride extends GH11135MappedSuperclass
{
}
