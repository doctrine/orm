<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Encrypt;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\Encrypt\Exception\UnsupportedEncryptedFieldUsage;
use Doctrine\Tests\Models\Encrypt\EncryptGroup;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Extra-lazy many-to-many collections: matching() by encrypted target fields goes through
 * ManyToManyPersister::loadCriteria() with encrypted parameters.
 */
class EncryptManyToManyTest extends EncryptFunctionalTestCase
{
    public function testMatchingManyToManyCollectionByEncryptedFieldFindsElement(): void
    {
        $admins = $this->persistGroup('admins', 'be kind');
        $users  = $this->persistGroup('users', 'be nice');

        $customer = $this->persistCustomerWithGroups($admins, $users);
        $this->_em->clear();

        $found = $this->loadGroups($customer)->matching(
            Criteria::create()->where(Criteria::expr()->eq('name', 'admins')),
        );

        self::assertCount(1, $found);
        self::assertSame($admins->id, $found->first()->id);
    }

    /** @return iterable<string, array{Criteria, string}> */
    public static function throwingCriteriaProvider(): iterable
    {
        yield 'non-equality operator' => [
            Criteria::create()->where(Criteria::expr()->gt('name', 'a')),
            EncryptGroup::class . '::$name is encrypted; filtering by an encrypted field only supports equality operators (=, <>, IN, NIN), got ">".',
        ];

        yield 'ordering by encrypted field' => [
            Criteria::create()->orderBy(['name' => Order::Ascending]),
            EncryptGroup::class . '::$name is encrypted; ordering by an encrypted field is not supported.',
        ];

        yield 'non-queryable field' => [
            Criteria::create()->where(Criteria::expr()->eq('motto', 'be kind')),
            EncryptGroup::class . '::$motto is encrypted; filtering by an encrypted field without queryType set is not supported.',
        ];
    }

    #[DataProvider('throwingCriteriaProvider')]
    public function testMatchingManyToManyCollectionThrows(Criteria $criteria, string $expectedMessage): void
    {
        $customer = $this->persistCustomerWithGroups($this->persistGroup('admins', 'be kind'));
        $this->_em->clear();

        $groups = $this->loadGroups($customer);

        $this->expectException(UnsupportedEncryptedFieldUsage::class);
        $this->expectExceptionMessage($expectedMessage);

        $groups->matching($criteria);
    }

    public function testMatchingManyToManyCollectionWithIsNullOnEncryptedFieldFindsElement(): void
    {
        $anonymous = $this->persistGroup('anonymous', null);
        $admins    = $this->persistGroup('admins', 'be kind');

        $customer = $this->persistCustomerWithGroups($anonymous, $admins);
        $this->_em->clear();

        $found = $this->loadGroups($customer)->matching(
            Criteria::create()->where(Criteria::expr()->isNull('motto')),
        );

        self::assertCount(1, $found);
        self::assertSame($anonymous->id, $found->first()->id);
    }
}
