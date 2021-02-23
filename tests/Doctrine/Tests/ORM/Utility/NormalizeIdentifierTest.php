<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Utility;

use Doctrine\ORM\Utility\NormalizeIdentifier;
use Doctrine\Tests\Models\IdentityIsAssociation\CompositeId;
use Doctrine\Tests\Models\IdentityIsAssociation\NestedAssociationToToOneAssociationIdToSimpleId;
use Doctrine\Tests\Models\IdentityIsAssociation\SimpleId;
use Doctrine\Tests\Models\IdentityIsAssociation\ToOneAssociationIdToSimpleId;
use Doctrine\Tests\Models\IdentityIsAssociation\ToOneCompositeAssociationToMultipleSimpleId;
use Doctrine\Tests\OrmFunctionalTestCase;
use ReflectionClass;
use function array_keys;
use function get_class;
use function is_object;

/**
 * @covers \Doctrine\ORM\Utility\NormalizeIdentifier
 */
class NormalizeIdentifierTest extends OrmFunctionalTestCase
{
    /**
     * Identifier flattener
     *
     * @var NormalizeIdentifier
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
        $this->assertSameIdentifierStructure(
            $expectedIdentifierStructure,
            ($this->normalizeIdentifier)(
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
        self::assertSame(array_keys($expectedId), array_keys($id));

        foreach ($expectedId as $field => $value) {
            if (! is_object($value)) {
                self::assertSame($id[$field], $value);

                continue;
            }

            self::assertInstanceOf(get_class($value), $id[$field]);

            $nestedIdProperties       = [];
            $nestedExpectedProperties = [];

            foreach ((new ReflectionClass($value))->getProperties() as $property) {
                $propertyName = $property->getName();

                $nestedExpectedProperties[$propertyName] = $property->getValue($value);
                $nestedIdProperties[$propertyName]       = $property->getValue($id[$field]);
            }

            $this->assertSameIdentifierStructure($nestedExpectedProperties, $nestedIdProperties);
        }
    }

    public function expectedIdentifiersProvider() : array
    {
        $simpleIdA                    = new SimpleId();
        $simpleIdB                    = new SimpleId();
        $toOneAssociationIdToSimpleId = new ToOneAssociationIdToSimpleId();

        $simpleIdA->id                          = 123;
        $simpleIdB->id                          = 456;
        $toOneAssociationIdToSimpleId->simpleId = $simpleIdA;

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
                ['simpleId' => $simpleIdA],
            ],
            ToOneCompositeAssociationToMultipleSimpleId::class => [
                ToOneCompositeAssociationToMultipleSimpleId::class,
                ['simpleIdA' => 123, 'simpleIdB' => 456],
                ['simpleIdA' => $simpleIdA, 'simpleIdB' => $simpleIdB],
            ],
            NestedAssociationToToOneAssociationIdToSimpleId::class => [
                NestedAssociationToToOneAssociationIdToSimpleId::class,
                ['nested' => 123],
                ['nested' => $toOneAssociationIdToSimpleId],
            ],
        ];
    }
}
