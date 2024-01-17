<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH-10927
 */
class GH10927Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $platform = $this->_em->getConnection()->getDatabasePlatform();
        if (! $platform instanceof PostgreSQLPlatform) {
            self::markTestSkipped('The ' . self::class . ' requires the use of postgresql.');
        }

        $this->setUpEntitySchema([
            GH10927RootMappedSuperclass::class,
            GH10927InheritedMappedSuperclass::class,
            GH10927EntityA::class,
            GH10927EntityB::class,
            GH10927EntityC::class,
        ]);
    }

    public function testSequenceGeneratorDefinitionForRootMappedSuperclass(): void
    {
        $metadata = $this->_em->getClassMetadata(GH10927RootMappedSuperclass::class);

        self::assertNull($metadata->sequenceGeneratorDefinition);
    }

    public function testSequenceGeneratorDefinitionForEntityA(): void
    {
        $metadata = $this->_em->getClassMetadata(GH10927EntityA::class);

        self::assertSame('GH10927EntityA_id_seq', $metadata->sequenceGeneratorDefinition['sequenceName']);
    }

    public function testSequenceGeneratorDefinitionForInheritedMappedSuperclass(): void
    {
        $metadata = $this->_em->getClassMetadata(GH10927InheritedMappedSuperclass::class);

        self::assertSame('GH10927InheritedMappedSuperclass_id_seq', $metadata->sequenceGeneratorDefinition['sequenceName']);
    }

    public function testSequenceGeneratorDefinitionForEntityB(): void
    {
        $metadata = $this->_em->getClassMetadata(GH10927EntityB::class);

        self::assertSame('GH10927EntityB_id_seq', $metadata->sequenceGeneratorDefinition['sequenceName']);
    }

    public function testSequenceGeneratorDefinitionForEntityC(): void
    {
        $metadata = $this->_em->getClassMetadata(GH10927EntityC::class);

        self::assertSame('GH10927EntityB_id_seq', $metadata->sequenceGeneratorDefinition['sequenceName']);
    }
}

/**
 * @ORM\MappedSuperclass()
 */
class GH10927RootMappedSuperclass
{
}

/**
 * @ORM\Entity()
 */
class GH10927EntityA extends GH10927RootMappedSuperclass
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\Column(type="integer")
     *
     * @var int|null
     */
    private $id = null;
}

/**
 * @ORM\MappedSuperclass()
 */
class GH10927InheritedMappedSuperclass extends GH10927RootMappedSuperclass
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\Column(type="integer")
     *
     * @var int|null
     */
    private $id = null;
}

/**
 * @ORM\Entity()
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"B" = "GH10927EntityB", "C" = "GH10927EntityC"})
 */
class GH10927EntityB extends GH10927InheritedMappedSuperclass
{
}

/**
 * @ORM\Entity()
 */
class GH10927EntityC extends GH10927EntityB
{
}
