<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\Enums\Suit;
use Doctrine\Tests\Models\GeoNames\Admin1;
use Doctrine\Tests\Models\GeoNames\Admin1AlternateName;
use Doctrine\Tests\Models\GeoNames\Country;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function defined;

class BasicEntityPersisterCompositeTypeParametersTest extends OrmTestCase
{
    protected BasicEntityPersister $persister;
    protected EntityManagerMock $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->getTestEntityManager();

        $this->entityManager->getClassMetadata(Country::class);
        $this->entityManager->getClassMetadata(Admin1::class);
        $this->entityManager->getClassMetadata(Admin1AlternateName::class);

        $this->persister = new BasicEntityPersister($this->entityManager, $this->entityManager->getClassMetadata(Admin1AlternateName::class));
    }

    public function testExpandParametersWillExpandCompositeEntityKeys(): void
    {
        $country = new Country('IT', 'Italy');
        $admin1  = new Admin1(10, 'Rome', $country);

        [$values, $types] = $this->persister->expandParameters(['admin1' => $admin1]);

        self::assertEquals(['integer', 'string'], $types);
        self::assertEquals([10, 'IT'], $values);
    }

    public function testExpandCriteriaParametersWillExpandCompositeEntityKeys(): void
    {
        $country = new Country('IT', 'Italy');
        $admin1  = new Admin1(10, 'Rome', $country);

        $criteria = defined(Criteria::class . '::ASC') ? Criteria::create(true) : Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('admin1', $admin1));

        [$values, $types] = $this->persister->expandCriteriaParameters($criteria);

        self::assertEquals(['integer', 'string'], $types);
        self::assertEquals([10, 'IT'], $values);
    }

    #[DataProvider('provideMembershipOperators')]
    public function testExpandCriteriaParametersPreservesValueAndTypeOrder(string $operator): void
    {
        $rome  = new Admin1(10, 'Rome', new Country('IT', 'Italy'));
        $paris = new Admin1(20, 'Paris', new Country('FR', 'France'));

        $criteria = defined(Criteria::class . '::ASC') ? Criteria::create(true) : Criteria::create();
        $criteria->where(new Comparison('name', $operator, ['first' => Suit::Hearts, 9 => null, 'duplicate' => Suit::Hearts]));
        $criteria->andWhere(new Comparison('admin1', $operator, [7 => $rome, 'null' => null, 2 => $paris, 'duplicate' => $rome]));
        $criteria->andWhere(Criteria::expr()->eq('id', Suit::Spades));
        $criteria->andWhere(Criteria::expr()->eq('name', null));
        $criteria->andWhere(Criteria::expr()->neq('id', null));

        [$values, $types] = $this->persister->expandCriteriaParameters($criteria);

        self::assertSame(['H', 'H', 10, 'IT', 20, 'FR', 10, 'IT', 'S'], $values);
        self::assertSame(['string', 'string', 'integer', 'string', 'integer', 'string', 'integer', 'string', 'string'], $types);
    }

    #[DataProvider('provideMembershipOperators')]
    public function testExpandCriteriaParametersSkipsNullAndEmptyLists(string $operator): void
    {
        $criteria = defined(Criteria::class . '::ASC') ? Criteria::create(true) : Criteria::create();
        $criteria->where(new Comparison('name', $operator, [null, null]));
        $criteria->andWhere(new Comparison('admin1', $operator, []));

        self::assertSame([[], []], $this->persister->expandCriteriaParameters($criteria));
    }

    #[DataProvider('provideMembershipOperators')]
    public function testExpandCriteriaParametersWrapsScalarMembershipValues(string $operator): void
    {
        $criteria = defined(Criteria::class . '::ASC') ? Criteria::create(true) : Criteria::create();
        $criteria->where(new Comparison('name', $operator, 'Rome'));

        self::assertSame([['Rome'], ['string']], $this->persister->expandCriteriaParameters($criteria));
    }

    public function testExpandCriteriaParametersWithoutWhereExpression(): void
    {
        $criteria = defined(Criteria::class . '::ASC') ? Criteria::create(true) : Criteria::create();

        self::assertSame([[], []], $this->persister->expandCriteriaParameters($criteria));
    }

    public function testExpandCriteriaParametersPreservesArrayValuedEquality(): void
    {
        $criteria = defined(Criteria::class . '::ASC') ? Criteria::create(true) : Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', ['first' => Suit::Hearts, 'nested' => ['Rome', 'Paris']]));

        [$values, $types] = $this->persister->expandCriteriaParameters($criteria);

        self::assertSame([['H', ['Rome', 'Paris']]], $values);
        self::assertSame([ArrayParameterType::STRING], $types);
    }

    /** @return iterable<string, array{string}> */
    public static function provideMembershipOperators(): iterable
    {
        yield 'IN' => [Comparison::IN];
        yield 'NOT IN' => [Comparison::NIN];
    }
}
