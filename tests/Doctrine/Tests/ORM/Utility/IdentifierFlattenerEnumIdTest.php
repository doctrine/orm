<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Utility;

use Doctrine\ORM\Utility\IdentifierFlattener;
use Doctrine\Tests\Models\Enums\Suit;
use Doctrine\Tests\Models\Enums\TypedCardEnumCompositeId;
use Doctrine\Tests\Models\Enums\TypedCardEnumId;
use Doctrine\Tests\Models\Enums\Unit;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the IdentifierFlattener utility class
 */
#[CoversClass(IdentifierFlattener::class)]
class IdentifierFlattenerEnumIdTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            TypedCardEnumId::class,
            TypedCardEnumCompositeId::class,
        );
    }

    #[Group('utilities')]
    public function testFlattenIdentifierWithEnumId(): void
    {
        $typedCardEnumIdEntity       = new TypedCardEnumId();
        $typedCardEnumIdEntity->suit = Suit::Clubs;

        $this->_em->persist($typedCardEnumIdEntity);
        $this->_em->flush();
        $this->_em->clear();

        $findTypedCardEnumIdEntityNotFound = $this->_em->getRepository(TypedCardEnumId::class)->find(Suit::Diamonds);

        self::assertNull($findTypedCardEnumIdEntityNotFound, 'Search by non-persisted Enum ID does not work');

        $findTypedCardEnumIdEntity = $this->_em->getRepository(TypedCardEnumId::class)->find(Suit::Clubs);

        self::assertNotNull($findTypedCardEnumIdEntity, 'Search by Enum ID does not work');

        $class = $this->_em->getClassMetadata(TypedCardEnumId::class);

        $id = $class->getIdentifierValues($findTypedCardEnumIdEntity);

        self::assertCount(1, $id, 'We should have 1 identifier');

        self::assertEquals(Suit::Clubs, $findTypedCardEnumIdEntity->suit);
    }

    #[Group('utilities')]
    public function testFlattenIdentifierWithCompositeEnumId(): void
    {
        $typedCardEnumCompositeIdEntity       = new TypedCardEnumCompositeId();
        $typedCardEnumCompositeIdEntity->suit = Suit::Clubs;
        $typedCardEnumCompositeIdEntity->unit = Unit::Gram;

        $this->_em->persist($typedCardEnumCompositeIdEntity);
        $this->_em->flush();
        $this->_em->clear();

        $findTypedCardEnumCompositeIdEntityNotFound = $this->_em->getRepository(TypedCardEnumCompositeId::class)->find(['suit' => Suit::Diamonds, 'unit' => Unit::Gram]);

        self::assertNull($findTypedCardEnumCompositeIdEntityNotFound, 'Search by non-persisted composite Enum ID does not work');

        $findTypedCardEnumCompositeIdEntity = $this->_em->getRepository(TypedCardEnumCompositeId::class)->find(['suit' => Suit::Clubs, 'unit' => Unit::Gram]);

        self::assertNotNull($findTypedCardEnumCompositeIdEntity, 'Search by composite Enum ID does not work');

        $class = $this->_em->getClassMetadata(TypedCardEnumCompositeId::class);

        $id = $class->getIdentifierValues($findTypedCardEnumCompositeIdEntity);

        self::assertCount(2, $id, 'We should have 2 identifiers');

        self::assertEquals(Suit::Clubs, $findTypedCardEnumCompositeIdEntity->suit);
        self::assertEquals(Unit::Gram, $findTypedCardEnumCompositeIdEntity->unit);
    }
}
