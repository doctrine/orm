<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Encrypt;

use Doctrine\ORM\Encrypt\Exception\UnsupportedEncryptedFieldUsage;
use Doctrine\Tests\Models\Encrypt\EncryptCustomer;
use Doctrine\Tests\Models\Encrypt\EncryptEmployee;
use Doctrine\Tests\Models\Encrypt\EncryptGroup;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function sprintf;

class EncryptDqlTest extends EncryptFunctionalTestCase
{
    /** @return iterable<string, array{string, int|string}> */
    public static function equalityProvider(): iterable
    {
        yield 'named parameter' => [
            sprintf('SELECT c FROM %s c WHERE c.ssn = :ssn', EncryptCustomer::class),
            'ssn',
        ];

        yield 'positional parameter' => [
            sprintf('SELECT c FROM %s c WHERE c.ssn = ?1', EncryptCustomer::class),
            1,
        ];

        yield 'parameter on left side' => [
            sprintf('SELECT c FROM %s c WHERE c.ssn = :ssn', EncryptCustomer::class),
            'ssn',
        ];
    }

    #[DataProvider('equalityProvider')]
    public function testWhereEquality(string $dql, int|string $paramKey): void
    {
        $this->initCustomers();
        $this->_em->clear();

        $result = $this->_em->createQuery($dql)
            ->setParameter($paramKey, '123-45-6789')
            ->getResult();

        self::assertCount(2, $result);
        $this->assertCustomer('joe', $result[0]);
        $this->assertCustomer('jax', $result[1]);
    }

    public function testWhereNotEqual(): void
    {
        $this->initCustomers();
        $this->_em->clear();

        $result = $this->_em->createQuery(sprintf('SELECT c FROM %s c WHERE c.ssn <> :ssn', EncryptCustomer::class))
            ->setParameter('ssn', '123-45-6789')
            ->getResult();

        self::assertCount(2, $result);
        $this->assertCustomer('jane', $result[0]);
        $this->assertCustomer('jim', $result[1]);
    }

    /** @return iterable<string, array{string, array<string, string>}> */
    public static function inListProvider(): iterable
    {
        yield 'single array parameter' => [
            sprintf('SELECT c FROM %s c WHERE c.ssn IN (:ssns)', EncryptCustomer::class),
            ['ssns' => ['123-45-6789', '987-65-4321']],
        ];

        yield 'separate scalar parameters' => [
            sprintf('SELECT c FROM %s c WHERE c.ssn IN (:first, :second)', EncryptCustomer::class),
            ['first' => '123-45-6789', 'second' => '987-65-4321'],
        ];
    }

    /** @param array<string, mixed> $params */
    #[DataProvider('inListProvider')]
    public function testWhereIn(string $dql, array $params): void
    {
        $this->initCustomers();
        $this->_em->clear();

        $query = $this->_em->createQuery($dql);

        foreach ($params as $name => $value) {
            $query->setParameter($name, $value);
        }

        $result = $query->getResult();

        self::assertCount(3, $result);
        $this->assertCustomer('joe', $result[0]);
        $this->assertCustomer('jane', $result[1]);
        $this->assertCustomer('jax', $result[2]);
    }

    public function testWhereNotInExcludesMatchingRows(): void
    {
        $this->initCustomers();
        $this->_em->clear();

        $result = $this->_em->createQuery(sprintf('SELECT c FROM %s c WHERE c.ssn NOT IN (:ssns)', EncryptCustomer::class))
            ->setParameter('ssns', ['123-45-6789', '987-65-4321'])
            ->getResult();

        self::assertCount(1, $result);
        $this->assertCustomer('jim', $result[0]);
    }

    public function testQueryCacheHitStillEncryptsParameters(): void
    {
        $adapter = new ArrayAdapter();
        $this->_em->getConfiguration()->setQueryCache($adapter);

        $this->initCustomers();
        $this->_em->clear();

        self::assertCount(0, $adapter->getValues());

        $dql = sprintf('SELECT c FROM %s c WHERE c.ssn = :ssn', EncryptCustomer::class);

        $first = $this->_em->createQuery($dql)
            ->setParameter('ssn', '123-45-6789')
            ->getResult();

        self::assertCount(1, $adapter->getValues());

        $second = $this->_em->createQuery($dql)
            ->setParameter('ssn', '123-45-6789')
            ->getResult();

        self::assertCount(2, $first);
        self::assertCount(2, $second);

        $this->assertCustomer('joe', $first[0]);
        $this->assertCustomer('jax', $first[1]);

        self::assertEquals($first[0], $second[0]);
        self::assertEquals($first[1], $second[1]);
    }

    public function testWhereMixesPlainAndEncryptedParameters(): void
    {
        $this->initCustomers();
        $this->_em->clear();

        $result = $this->_em->createQuery(sprintf('SELECT c FROM %s c WHERE c.name = :name AND c.ssn = :ssn', EncryptCustomer::class))
            ->setParameter('name', 'joe')
            ->setParameter('ssn', '123-45-6789')
            ->getResult();

        self::assertCount(1, $result);
        $this->assertCustomer('joe', $result[0]);
    }

    /** @return iterable<string, array{string, array<string, mixed>, string}> */
    public static function unsupportedUsageProvider(): iterable
    {
        yield 'non-queryable field' => [
            sprintf('SELECT c FROM %s c WHERE c.note = :note', EncryptCustomer::class),
            ['note' => 'top secret'],
            EncryptCustomer::class . '::$note is encrypted; filtering by an encrypted field without queryType set is not supported.',
        ];

        yield 'non-equality operator' => [
            sprintf('SELECT c FROM %s c WHERE c.ssn > :ssn', EncryptCustomer::class),
            ['ssn' => '123-45-6789'],
            EncryptCustomer::class . '::$ssn is encrypted; filtering by an encrypted field only supports equality operators (=, <>, IN, NIN), got ">".',
        ];

        yield 'BETWEEN' => [
            sprintf('SELECT c FROM %s c WHERE c.ssn BETWEEN :low AND :high', EncryptCustomer::class),
            ['low' => '111-11-1111', 'high' => '999-99-9999'],
            EncryptCustomer::class . '::$ssn is encrypted; filtering by an encrypted field only supports equality operators (=, <>, IN, NIN), got "BETWEEN".',
        ];

        yield 'LIKE' => [
            sprintf('SELECT c FROM %s c WHERE c.ssn LIKE :pattern', EncryptCustomer::class),
            ['pattern' => '123-%'],
            EncryptCustomer::class . '::$ssn is encrypted; filtering by an encrypted field only supports equality operators (=, <>, IN, NIN), got "LIKE".',
        ];

        yield 'literal comparison' => [
            sprintf('SELECT c FROM %s c WHERE c.ssn = \'123-45-6789\'', EncryptCustomer::class),
            [],
            EncryptCustomer::class . '::$ssn is encrypted; comparing or assigning a DQL literal is not supported, bind a parameter instead.',
        ];

        yield 'literal in IN list' => [
            sprintf('SELECT c FROM %s c WHERE c.ssn IN (\'123-45-6789\')', EncryptCustomer::class),
            [],
            EncryptCustomer::class . '::$ssn is encrypted; comparing or assigning a DQL literal is not supported, bind a parameter instead.',
        ];
    }

    /** @param array<string, mixed> $params */
    #[DataProvider('unsupportedUsageProvider')]
    public function testWhereUnsupportedUsageThrows(string $dql, array $params, string $expectedMessage): void
    {
        $this->expectException(UnsupportedEncryptedFieldUsage::class);
        $this->expectExceptionMessage($expectedMessage);

        $query = $this->_em->createQuery($dql);

        foreach ($params as $name => $value) {
            $query->setParameter($name, $value);
        }

        $query->getResult();
    }

    public function testDqlUpdateEncryptsSetParameter(): void
    {
        [$joe] = $this->initCustomers();
        $this->_em->clear();

        $affected = $this->_em->createQuery(sprintf('UPDATE %s c SET c.ssn = :ssn WHERE c.id = :id', EncryptCustomer::class))
            ->setParameter('ssn', '999-99-9999')
            ->setParameter('id', $joe->id)
            ->execute();

        self::assertSame(1, $affected);

        $raw = $this->fetchRawColumn('SELECT ssn FROM encrypt_customers WHERE id = ?', [$joe->id]);

        self::assertNotSame('999-99-9999', $raw);
        self::assertSame('999-99-9999', $this->decrypt($raw));
    }

    public function testDqlUpdateWritesNonQueryableEncryptedField(): void
    {
        $group = $this->persistGroup('admins', 'be kind');
        $this->_em->clear();

        $affected = $this->_em->createQuery(sprintf('UPDATE %s g SET g.motto = :motto WHERE g.name = :name', EncryptGroup::class))
            ->setParameter('motto', 'be nice')
            ->setParameter('name', 'admins')
            ->execute();

        self::assertSame(1, $affected);

        $raw = $this->fetchRawColumn('SELECT motto FROM encrypt_groups WHERE id = ?', [$group->id]);

        self::assertNotSame('be nice', $raw);
        self::assertSame('be nice', $this->decrypt($raw));
    }

    public function testDqlUpdateSetsEncryptedFieldToNull(): void
    {
        $group = $this->persistGroup('admins', 'be kind');
        $this->_em->clear();

        $affected = $this->_em->createQuery(sprintf('UPDATE %s g SET g.motto = NULL WHERE g.name = :name', EncryptGroup::class))
            ->setParameter('name', 'admins')
            ->execute();

        self::assertSame(1, $affected);
        self::assertNull($this->_em->getConnection()->fetchOne('SELECT motto FROM encrypt_groups WHERE id = ?', [$group->id]));
    }

    public function testDqlUpdateWithLiteralThrows(): void
    {
        $this->expectException(UnsupportedEncryptedFieldUsage::class);
        $this->expectExceptionMessage(EncryptCustomer::class . '::$ssn is encrypted; comparing or assigning a DQL literal is not supported, bind a parameter instead.');

        $this->_em->createQuery(sprintf('UPDATE %s c SET c.ssn = \'999-99-9999\'', EncryptCustomer::class))
            ->execute();
    }

    public function testJoinedInheritanceBulkUpdateEncryptsSetParameter(): void
    {
        $employee             = new EncryptEmployee();
        $employee->secret     = 'old secret';
        $employee->department = 'r&d';

        $this->_em->persist($employee);
        $this->_em->flush();
        $this->_em->clear();

        $affected = $this->_em->createQuery(sprintf('UPDATE %s e SET e.secret = :secret WHERE e.department = :department', EncryptEmployee::class))
            ->setParameter('secret', 'new secret')
            ->setParameter('department', 'r&d')
            ->execute();

        self::assertSame(1, $affected);

        $raw = $this->fetchRawColumn('SELECT secret FROM encrypt_persons WHERE id = ?', [$employee->id]);

        self::assertNotSame('new secret', $raw);
        self::assertSame('new secret', $this->decrypt($raw));
    }

    public function testDqlDeleteByEncryptedField(): void
    {
        [, $jane] = $this->initCustomers();
        $this->_em->clear();

        $affected = $this->_em->createQuery(sprintf('DELETE FROM %s c WHERE c.ssn = :ssn', EncryptCustomer::class))
            ->setParameter('ssn', '987-65-4321')
            ->execute();

        self::assertSame(1, $affected);
        self::assertFalse($this->_em->getConnection()->fetchOne('SELECT id FROM encrypt_customers WHERE id = ?', [$jane->id]));
    }
}
