<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Utility;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Utility\NormalizeIdentifier;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @covers \Doctrine\ORM\Utility\NormalizeIdentifier
 */
class NormalizeIdentifierTest extends OrmFunctionalTestCase
{
    /**
     * Identifier flattener
     *
     * @var \Doctrine\ORM\Utility\NormalizeIdentifier
     */
    private $normalizeIdentifier;

    protected function setUp() : void
    {
        parent::setUp();

        $this->normalizeIdentifier = new NormalizeIdentifier();
    }

    /**
     * @dataProvider expectedIdentifiersProvider
     */
    public function testIdentifierNormalization(
        string $targetClass,
        array $id,
        array $expectedIdentifierStructure
    ) : void {
//        self::assertEquals(
//            $expectedIdentifierStructure,
//            $this->normalizeIdentifier->__invoke(
//                $this->em,
//                $this->em->getClassMetadata($targetClass),
//                $id
//            )
//        );
        $this->assertSameIdentifierStructure(
            $expectedIdentifierStructure,
            $this->normalizeIdentifier->__invoke(
                $this->em,
                $this->em->getClassMetadata($targetClass),
                $id
            )
        );
    }

    /**
     * Recursively analyzes a given identifier.
     * If objects are found, then recursively analyizes object structures
     */
    private function assertSameIdentifierStructure(array $expectedId, array $id) : void
    {
        self::assertSame(\array_keys($expectedId), \array_keys($id));

        foreach ($expectedId as $field => $value) {
            if (! \is_object($value)) {
                self::assertSame($id[$field], $value);

                continue;
            }

            self::assertInstanceOf(\get_class($value), $id[$field]);

            $nestedIdProperties       = [];
            $nestedExpectedProperties = [];

            foreach ((new \ReflectionClass($value))->getProperties() as $property) {
                $propertyName = $property->getName();

                $nestedExpectedProperties[$propertyName] = $property->getValue($value);
                $nestedIdProperties[$propertyName]       = $property->getValue($id[$field]);
            }

            $this->assertSameIdentifierStructure($nestedExpectedProperties, $nestedIdProperties);
        }
    }

    public function expectedIdentifiersProvider() : array
    {
        $simpleId = new SimpleId();

        $simpleId->id = 123;

        return [
            'simple single-field id fetch' => [
                SimpleId::class,
                ['id' => 123],
                ['id' => 123],
            ],
            'simple multi-field id fetch' => [
                CompositeId::class,
                ['idA' => 123, 'idB' => 123],
                ['idA' => 123, 'idB' => 123],
            ],
            'simple multi-field id fetch, order reversed' => [
                CompositeId::class,
                ['idB' => 123, 'idA' => 123],
                ['idA' => 123, 'idB' => 123],
            ],
            ToOneAssociationIdToSimpleId::class => [
                ToOneAssociationIdToSimpleId::class,
                ['simpleId' => 123],
                ['simpleId' => $simpleId],
            ],
        ];
    }
}

// @TODO move following entities to their own namespace
/** @ORM\Entity */
class SimpleId
{
    /** @ORM\Id @ORM\Column(type="integer") */
    public $id;
}

/** @ORM\Entity */
class CompositeId
{
    /** @ORM\Id @ORM\Column(type="integer") */
    public $idA;

    /** @ORM\Id @ORM\Column(type="integer") */
    public $idB;
}

/** @ORM\Entity */
class ToOneAssociationIdToSimpleId
{
    /** @ORM\Id @ORM\ManyToOne(targetEntity=SimpleId::class) */
    public $simpleId;
}

/** @ORM\Entity */
class ToOneAssociationIdToCompositeId
{
    /** @ORM\Id @ORM\ManyToOne(targetEntity=CompositeId::class) */
    public $compositeId;
}

/** @ORM\Entity */
class ToOneCompositeAssociationToSimpleIdAndCompositeId
{
    /** @ORM\Id @ORM\ManyToOne(targetEntity=SimpleId::class) */
    public $simpleId;

    /** @ORM\Id @ORM\ManyToOne(targetEntity=CompositeId::class) */
    public $compositeId;
}

/** @ORM\Entity */
class NestedAssociationToToOneAssociationIdToSimpleId
{
    /** @ORM\Id @ORM\ManyToOne(targetEntity=ToOneAssociationIdToSimpleId::class) */
    public $nested;
}

/** @ORM\Entity */
class NestedAssociationToToOneCompositeAssociationToSimpleIdAndCompositeId
{
    /** @ORM\Id @ORM\ManyToOne(targetEntity=ToOneCompositeAssociationToSimpleIdAndCompositeId::class) */
    public $nested;
}
