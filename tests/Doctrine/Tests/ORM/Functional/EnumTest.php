<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\Models\Enums\Card;
use Doctrine\Tests\Models\Enums\CardWithDefault;
use Doctrine\Tests\Models\Enums\Product;
use Doctrine\Tests\Models\Enums\Quantity;
use Doctrine\Tests\Models\Enums\Scale;
use Doctrine\Tests\Models\Enums\Suit;
use Doctrine\Tests\Models\Enums\TypedCard;
use Doctrine\Tests\Models\Enums\TypedCardEnumDefaultValue;
use Doctrine\Tests\Models\Enums\TypedCardEnumDefaultValueIncorrect;
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

    public function testIncorrectDefaultEnumValue(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(sprintf(
            'Attempting to assign default value %s of enum %s as enum in entity %s::$%s of type %s',
            (Unit::Gram)->name,
            Unit::class,
            TypedCardEnumDefaultValueIncorrect::class,
            'suit',
            Suit::class
        ));

        $this->setUpEntitySchema([TypedCardEnumDefaultValueIncorrect::class]);
    }

    public function testDefaultEnumValue(): void
    {
        $this->setUpEntitySchema([TypedCardEnumDefaultValue::class]);

        $card                             = new TypedCardEnumDefaultValue();
        $card->suit                       = Suit::Clubs;
        $card->suitDefaultNull            = Suit::Clubs;
        $card->suitDefaultNullNullable    = Suit::Clubs;
        $card->suitDefaultNotNullNullable = Suit::Clubs;

        $this->_em->persist($card);
        $this->_em->flush();
        $this->_em->clear();

        $metadata = $this->_em->getClassMetadata(TypedCardEnumDefaultValue::class);
        $this->_em->getConnection()->update(
            $metadata->table['name'],
            [
                $metadata->fieldMappings['suit']['columnName'] => 'invalid',
                $metadata->fieldMappings['suitDefaultNull']['columnName'] => 'invalid',
                $metadata->fieldMappings['suitDefaultNullNullable']['columnName'] => 'invalid',
                $metadata->fieldMappings['suitDefaultNotNullNullable']['columnName'] => 'invalid',
            ],
            [$metadata->fieldMappings['id']['columnName'] => $card->id]
        );

        $class = $this->_em->find(TypedCardEnumDefaultValue::class, $card->id);

        $this->assertSame(Suit::Spades, $class->suit);
        $this->assertNull($class->suitDefaultNull);
        $this->assertNull($class->suitDefaultNullNullable);
        $this->assertSame(Suit::Spades, $class->suitDefaultNotNullNullable);

        $this->_em->clear();
        unset($class);

        $this->_em->getConnection()->update(
            $metadata->table['name'],
            [
                $metadata->fieldMappings['suitDefaultNullNullable']['columnName'] => null,
                $metadata->fieldMappings['suitDefaultNotNullNullable']['columnName'] => null,
            ],
            [$metadata->fieldMappings['id']['columnName'] => $card->id]
        );

        $class = $this->_em->find(TypedCardEnumDefaultValue::class, $card->id);

        $this->assertNull($class->suitDefaultNullNullable);
        $this->assertNull($class->suitDefaultNotNullNullable);
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

    /**
     * @return array<string, array{class-string}>
     */
    public function provideCardClasses(): array
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
