<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\Models\DataTransferObjects\DtoWithArrayOfEnums;
use Doctrine\Tests\Models\DataTransferObjects\DtoWithEnum;
use Doctrine\Tests\Models\Enums\Card;
use Doctrine\Tests\Models\Enums\CardWithDefault;
use Doctrine\Tests\Models\Enums\CardWithNullable;
use Doctrine\Tests\Models\Enums\Product;
use Doctrine\Tests\Models\Enums\Quantity;
use Doctrine\Tests\Models\Enums\Scale;
use Doctrine\Tests\Models\Enums\Suit;
use Doctrine\Tests\Models\Enums\TypedCard;
use Doctrine\Tests\Models\Enums\Unit;
use Doctrine\Tests\OrmFunctionalTestCase;

use function dirname;
use function sprintf;
use function uniqid;

/**
 * @requires PHP 8.1
 */
class EnumTest extends OrmFunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->_em         = $this->getEntityManager(null, new AttributeDriver([dirname(__DIR__, 2) . '/Models/Enums']));
        $this->_schemaTool = new SchemaTool($this->_em);

        if ($this->isSecondLevelCacheEnabled) {
            $this->markTestSkipped();
        }
    }

    /**
     * @param class-string $cardClass
     *
     * @dataProvider provideCardClasses
     */
    public function testEnumMapping(string $cardClass): void
    {
        $this->setUpEntitySchema([$cardClass]);

        $card       = new $cardClass();
        $card->suit = Suit::Clubs;

        $this->_em->persist($card);
        $this->_em->flush();
        $this->_em->clear();

        $fetchedCard = $this->_em->find(Card::class, $card->id);

        $this->assertInstanceOf(Suit::class, $fetchedCard->suit);
        $this->assertEquals(Suit::Clubs, $fetchedCard->suit);
    }

    public function testEnumHydrationObjectHydrator(): void
    {
        $this->setUpEntitySchema([Card::class]);

        $card1       = new Card();
        $card1->suit = Suit::Clubs;
        $card2       = new Card();
        $card2->suit = Suit::Hearts;

        $this->_em->persist($card1);
        $this->_em->persist($card2);
        $this->_em->flush();

        unset($card1, $card2);
        $this->_em->clear();

        /** @var list<Card> $foundCards */
        $foundCards = $this->_em->createQueryBuilder()
            ->select('c')
            ->from(Card::class, 'c')
            ->where('c.suit = :suit')
            ->setParameter('suit', Suit::Clubs)
            ->getQuery()
            ->getResult();

        self::assertNotEmpty($foundCards);
        foreach ($foundCards as $card) {
            self::assertSame(Suit::Clubs, $card->suit);
        }
    }

    public function testEnumArrayHydrationObjectHydrator(): void
    {
        $this->setUpEntitySchema([Scale::class]);

        $scale                 = new Scale();
        $scale->supportedUnits = [Unit::Gram, Unit::Meter];

        $this->_em->persist($scale);
        $this->_em->flush();
        $this->_em->clear();

        $result = $this->_em->createQueryBuilder()
            ->from(Scale::class, 's')
            ->select('s')
            ->getQuery()
            ->getResult();

        self::assertInstanceOf(Scale::class, $result[0]);
        self::assertEqualsCanonicalizing([Unit::Gram, Unit::Meter], $result[0]->supportedUnits);
    }

    public function testEnumHydration(): void
    {
        $this->setUpEntitySchema([Card::class, CardWithNullable::class]);

        $card       = new Card();
        $card->suit = Suit::Clubs;

        $this->_em->persist($card);
        $this->_em->flush();
        $this->_em->clear();

        $result = $this->_em->createQueryBuilder()
            ->from(Card::class, 'c')
            ->select('c.id, c.suit')
            ->getQuery()
            ->getResult();

        $this->assertInstanceOf(Suit::class, $result[0]['suit']);
        $this->assertEquals(Suit::Clubs, $result[0]['suit']);
    }

    public function testEnumHydrationArrayHydrator(): void
    {
        $this->setUpEntitySchema([Card::class, CardWithNullable::class]);

        $card       = new Card();
        $card->suit = Suit::Clubs;

        $this->_em->persist($card);
        $this->_em->flush();
        $this->_em->clear();

        $result = $this->_em->createQueryBuilder()
            ->from(Card::class, 'c')
            ->select('c')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);

        $this->assertInstanceOf(Suit::class, $result[0]['suit']);
        $this->assertEquals(Suit::Clubs, $result[0]['suit']);
    }

    public function testNullableEnumHydration(): void
    {
        $this->setUpEntitySchema([Card::class, CardWithNullable::class]);

        $cardWithNullable       = new CardWithNullable();
        $cardWithNullable->suit = null;

        $this->_em->persist($cardWithNullable);
        $this->_em->flush();
        $this->_em->clear();

        $result = $this->_em->createQueryBuilder()
            ->from(CardWithNullable::class, 'c')
            ->select('c.id, c.suit')
            ->getQuery()
            ->getResult();

        $this->assertNull($result[0]['suit']);
    }

    public function testEnumArrayHydration(): void
    {
        $this->setUpEntitySchema([Scale::class]);

        $scale                 = new Scale();
        $scale->supportedUnits = [Unit::Gram, Unit::Meter];

        $this->_em->persist($scale);
        $this->_em->flush();
        $this->_em->clear();

        $result = $this->_em->createQueryBuilder()
            ->from(Scale::class, 's')
            ->select('s.id, s.supportedUnits')
            ->getQuery()
            ->getResult();

        self::assertEqualsCanonicalizing([Unit::Gram, Unit::Meter], $result[0]['supportedUnits']);
    }

    public function testEnumInDtoHydration(): void
    {
        $this->setUpEntitySchema([Card::class, CardWithNullable::class]);

        $card       = new Card();
        $card->suit = Suit::Clubs;

        $this->_em->persist($card);
        $this->_em->flush();
        $this->_em->clear();

        $result = $this->_em->createQueryBuilder()
            ->from(CardWithNullable::class, 'c')
            ->select('NEW ' . DtoWithEnum::class . '(c.suit)')
            ->getQuery()
            ->getResult();

        $this->assertNull($result[0]->suit);
    }

    public function testNullableEnumInDtoHydration(): void
    {
        $this->setUpEntitySchema([Card::class, CardWithNullable::class]);

        $cardWithNullable       = new CardWithNullable();
        $cardWithNullable->suit = null;

        $this->_em->persist($cardWithNullable);
        $this->_em->flush();
        $this->_em->clear();

        $result = $this->_em->createQueryBuilder()
            ->from(CardWithNullable::class, 'c')
            ->select('NEW ' . DtoWithEnum::class . '(c.suit)')
            ->getQuery()
            ->getResult();

        $this->assertNull($result[0]->suit);
    }

    public function testEnumArrayInDtoHydration(): void
    {
        $this->setUpEntitySchema([Scale::class]);

        $scale                 = new Scale();
        $scale->supportedUnits = [Unit::Gram, Unit::Meter];

        $this->_em->persist($scale);
        $this->_em->flush();
        $this->_em->clear();

        $result = $this->_em->createQueryBuilder()
            ->from(Scale::class, 's')
            ->select(new Func('NEW ' . DtoWithArrayOfEnums::class, ['s.supportedUnits']))
            ->getQuery()
            ->getResult();

        self::assertEqualsCanonicalizing([Unit::Gram, Unit::Meter], $result[0]->supportedUnits);
    }

    public function testEnumChangeSetsSimpleObjectHydrator(): void
    {
        $this->setUpEntitySchema([Card::class]);

        $card       = new Card();
        $card->suit = Suit::Clubs;

        $this->_em->persist($card);
        $this->_em->flush();
        $this->_em->clear();

        $result = $this->_em->find(Card::class, $card->id);

        $this->_em->getUnitOfWork()->computeChangeSets();

        self::assertFalse($this->_em->getUnitOfWork()->isScheduledForUpdate($result));
    }

    public function testEnumChangeSetsObjectHydrator(): void
    {
        $this->setUpEntitySchema([Card::class]);

        $card       = new Card();
        $card->suit = Suit::Clubs;

        $this->_em->persist($card);
        $this->_em->flush();
        $this->_em->clear();

        $result = $this->_em->createQueryBuilder()
            ->from(Card::class, 'c')
            ->select('c')
            ->getQuery()
            ->getResult();

        $this->_em->getUnitOfWork()->computeChangeSets();

        self::assertFalse($this->_em->getUnitOfWork()->isScheduledForUpdate($result[0]));
    }

    public function testEnumArrayChangeSets(): void
    {
        $this->setUpEntitySchema([Scale::class]);

        $scale                 = new Scale();
        $scale->supportedUnits = [Unit::Gram];

        $this->_em->persist($scale);
        $this->_em->flush();
        $this->_em->clear();

        $result = $this->_em->createQueryBuilder()
            ->from(Scale::class, 's')
            ->select('s')
            ->getQuery()
            ->getResult();

        $this->_em->getUnitOfWork()->computeChangeSets();

        self::assertFalse($this->_em->getUnitOfWork()->isScheduledForUpdate($result[0]));
    }

    public function testFindByEnum(): void
    {
        $this->setUpEntitySchema([Card::class]);

        $card1       = new Card();
        $card1->suit = Suit::Clubs;
        $card2       = new Card();
        $card2->suit = Suit::Hearts;

        $this->_em->persist($card1);
        $this->_em->persist($card2);
        $this->_em->flush();

        unset($card1, $card2);
        $this->_em->clear();

        /** @var list<Card> $foundCards */
        $foundCards = $this->_em->getRepository(Card::class)->findBy(['suit' => Suit::Clubs]);
        $this->assertNotEmpty($foundCards);
        foreach ($foundCards as $card) {
            $this->assertSame(Suit::Clubs, $card->suit);
        }
    }

    /**
     * @param class-string $cardClass
     *
     * @dataProvider provideCardClasses
     */
    public function testEnumWithNonMatchingDatabaseValueThrowsException(string $cardClass): void
    {
        $this->setUpEntitySchema([$cardClass]);

        $card       = new $cardClass();
        $card->suit = Suit::Clubs;

        $this->_em->persist($card);
        $this->_em->flush();
        $this->_em->clear();

        $metadata = $this->_em->getClassMetadata($cardClass);
        $this->_em->getConnection()->update(
            $metadata->table['name'],
            [$metadata->fieldMappings['suit']['columnName'] => 'invalid'],
            [$metadata->fieldMappings['id']['columnName'] => $card->id]
        );

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(sprintf(
            <<<'EXCEPTION'
Context: Trying to hydrate enum property "%s::$suit"
Problem: Case "invalid" is not listed in enum "Doctrine\Tests\Models\Enums\Suit"
Solution: Either add the case to the enum type or migrate the database column to use another case of the enum
EXCEPTION
            ,
            $cardClass
        ));

        $this->_em->find($cardClass, $card->id);
    }

    /** @return array<string, array{class-string}> */
    public static function provideCardClasses(): array
    {
        return [
            Card::class => [Card::class],
            TypedCard::class => [TypedCard::class],
        ];
    }

    public function testItAllowsReadingAttributes(): void
    {
        $metadata = $this->_em->getClassMetadata(Card::class);
        $property = $metadata->getReflectionProperty('suit');

        $attributes = $property->getAttributes();

        $this->assertCount(1, $attributes);
        $this->assertEquals(Column::class, $attributes[0]->getName());
    }

    public function testEnumMappingWithEmbeddable(): void
    {
        $this->setUpEntitySchema([Product::class]);

        $product                  = new Product();
        $product->quantity        = new Quantity();
        $product->quantity->value = 10;
        $product->quantity->unit  = Unit::Gram;

        $this->_em->persist($product);
        $this->_em->flush();
        $this->_em->clear();

        $fetchedProduct = $this->_em->find(Product::class, $product->id);

        $this->assertInstanceOf(Unit::class, $fetchedProduct->quantity->unit);
        $this->assertEquals(Unit::Gram, $fetchedProduct->quantity->unit);
    }

    public function testEnumArrayMapping(): void
    {
        $this->setUpEntitySchema([Scale::class]);

        $scale                 = new Scale();
        $scale->supportedUnits = [Unit::Gram, Unit::Meter];

        $this->_em->persist($scale);
        $this->_em->flush();
        $this->_em->clear();

        $fetchedScale = $this->_em->find(Scale::class, $scale->id);

        $this->assertIsArray($fetchedScale->supportedUnits);
        $this->assertContains(Unit::Gram, $fetchedScale->supportedUnits);
        $this->assertContains(Unit::Meter, $fetchedScale->supportedUnits);
    }

    public function testEnumWithDefault(): void
    {
        $this->setUpEntitySchema([CardWithDefault::class]);

        $table  = $this->_em->getClassMetadata(CardWithDefault::class)->getTableName();
        $cardId = uniqid('', true);

        $this->_em->getConnection()->insert($table, ['id' => $cardId]);
        $card = $this->_em->find(CardWithDefault::class, $cardId);

        self::assertSame(Suit::Hearts, $card->suit);
    }
}
