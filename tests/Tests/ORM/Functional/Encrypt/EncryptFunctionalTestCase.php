<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Encrypt;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Encrypt\Cipher\OpenSSLCipher;
use Doctrine\ORM\Encrypt\KMS\ArrayKeyProviderRegistry;
use Doctrine\ORM\Encrypt\KMS\ArraySymmetricKeyProvider;
use Doctrine\ORM\Encrypt\KMS\KeyProvider;
use Doctrine\Tests\Models\Encrypt\EncryptCustomer;
use Doctrine\Tests\Models\Encrypt\EncryptEmployee;
use Doctrine\Tests\Models\Encrypt\EncryptGroup;
use Doctrine\Tests\Models\Encrypt\EncryptPerson;
use Doctrine\Tests\Models\Encrypt\EncryptTypesEntity;
use Doctrine\Tests\OrmFunctionalTestCase;

use function is_array;
use function is_resource;
use function stream_get_contents;

/**
 * Base class for the end-to-end encryption suites, running the real crypto stack
 * (OpenSSLCipher + ArraySymmetricKeyProvider) against a real database, no mocks.
 */
abstract class EncryptFunctionalTestCase extends OrmFunctionalTestCase
{
    protected const KEY_MATERIAL = 'BRi18ItE5BIL8+XPzXkws6vK3iJQPw198fiy67E7YMw=';

    protected KeyProvider $keyProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->keyProvider = ArraySymmetricKeyProvider::fromScalars(['k1' => self::KEY_MATERIAL]);

        $config = $this->_em->getConfiguration();
        $config->setCipherRegistry(new EncryptTestCipherRegistry(
            [
                'random' => new OpenSSLCipher(),
                'deterministic' => new OpenSSLCipher(deterministic: true),
            ],
            'random',
        ));
        $config->setKeyProviderRegistry(new ArrayKeyProviderRegistry(['main' => $this->keyProvider]));

        $this->setUpEntitySchema([
            EncryptCustomer::class,
            EncryptGroup::class,
            EncryptPerson::class,
            EncryptEmployee::class,
            EncryptTypesEntity::class,
        ]);

        $connection = $this->_em->getConnection();
        $connection->executeStatement('DELETE FROM encrypt_customers_groups');
        $connection->executeStatement('DELETE FROM encrypt_groups');
        $connection->executeStatement('DELETE FROM encrypt_employees');
        $connection->executeStatement('DELETE FROM encrypt_persons');
        $connection->executeStatement('DELETE FROM encrypt_customers');
        $connection->executeStatement('DELETE FROM encrypt_types');
    }

    protected function persistCustomer(string $name, string $ssn, string $note): EncryptCustomer
    {
        $customer       = new EncryptCustomer();
        $customer->name = $name;
        $customer->ssn  = $ssn;
        $customer->note = $note;

        $this->_em->persist($customer);
        $this->_em->flush();

        return $customer;
    }

    protected function persistGroup(string $name, string|null $motto): EncryptGroup
    {
        $group        = new EncryptGroup();
        $group->name  = $name;
        $group->motto = $motto;

        $this->_em->persist($group);
        $this->_em->flush();

        return $group;
    }

    protected function persistCustomerWithGroups(EncryptGroup ...$groups): EncryptCustomer
    {
        $customer = $this->persistCustomer('joe', '123-45-6789', 'top secret');

        foreach ($groups as $group) {
            $customer->groups->add($group);
        }

        $this->_em->flush();

        return $customer;
    }

    /** @return Collection<int, EncryptGroup> */
    protected function loadGroups(EncryptCustomer $customer): Collection
    {
        $reloaded = $this->_em->find(EncryptCustomer::class, $customer->id);

        self::assertFalse($reloaded->groups->isInitialized());

        return $reloaded->groups;
    }

    /** @param list<mixed> $params */
    protected function fetchRawColumn(string $sql, array $params): string
    {
        $value = $this->_em->getConnection()->fetchOne($sql, $params);

        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        self::assertIsString($value);

        return $value;
    }

    protected function decrypt(string $envelope): string
    {
        return (new OpenSSLCipher())->decrypt($envelope, $this->keyProvider);
    }

    /** @return list{EncryptCustomer, EncryptCustomer, EncryptCustomer} */
    protected function initCustomers(): array
    {
        $joe  = $this->persistCustomer('joe', '123-45-6789', 'top secret');
        $jane = $this->persistCustomer('jane', '987-65-4321', 'other secret');
        $jim  = $this->persistCustomer('jim', '555-55-5555', 'third secret');
        $jax  = $this->persistCustomer('jax', '123-45-6789', 'again secret');

        return [$joe, $jane, $jim, $jax];
    }

    protected function assertCustomer(string $name, EncryptCustomer|array $customer): void
    {
        if (is_array($customer)) {
            $customer = (object) $customer;
        }

        self::assertSame($name, $customer->name);

        self::assertSame(match ($name) {
            'joe', 'jax' => '123-45-6789',
            'jane' => '987-65-4321',
            'jim' => '555-55-5555',
        }, $customer->ssn);

        self::assertSame(match ($name) {
            'joe' => 'top secret',
            'jane' => 'other secret',
            'jim' => 'third secret',
            'jax' => 'again secret',
        }, $customer->note);
    }
}
