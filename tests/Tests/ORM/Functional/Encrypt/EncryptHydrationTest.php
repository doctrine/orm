<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Encrypt;

use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Tests\Models\Encrypt\EncryptCustomer;
use Doctrine\Tests\Models\Encrypt\EncryptGroup;

use function sprintf;

class EncryptHydrationTest extends EncryptFunctionalTestCase
{
    public function testFindReturnsDecryptedFieldValues(): void
    {
        [$joe] = $this->initCustomers();
        $this->_em->clear();

        $found = $this->_em->find(EncryptCustomer::class, $joe->id);

        self::assertNotNull($found);
        $this->assertCustomer('joe', $found);
    }

    public function testDqlObjectHydrationDecryptsEncryptedFields(): void
    {
        $this->initCustomers();
        $this->_em->clear();

        $result = $this->_em->createQuery(sprintf('SELECT c FROM %s c', EncryptCustomer::class))->getResult();

        self::assertCount(4, $result);

        $this->assertCustomer('joe', $result[0]);
        $this->assertCustomer('jane', $result[1]);
        $this->assertCustomer('jim', $result[2]);
        $this->assertCustomer('jax', $result[3]);
    }

    public function testDqlArrayHydrationDecryptsEncryptedFields(): void
    {
        $this->initCustomers();
        $this->_em->clear();

        $result = $this->_em->createQuery(sprintf('SELECT c FROM %s c', EncryptCustomer::class))->getArrayResult();

        self::assertCount(4, $result);

        $this->assertCustomer('joe', $result[0]);
        $this->assertCustomer('jane', $result[1]);
        $this->assertCustomer('jim', $result[2]);
        $this->assertCustomer('jax', $result[3]);
    }

    public function testNullEncryptedFieldHydratesAsNull(): void
    {
        $group = $this->persistGroup('anonymous', null);
        $this->_em->clear();

        $found = $this->_em->find(EncryptGroup::class, $group->id);

        self::assertNotNull($found);
        self::assertNull($found->motto);
    }

    public function testDisableDecryptHydratesCiphertext(): void
    {
        $this->initCustomers();
        $this->_em->clear();

        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm->addRootEntityFromClassMetadata(EncryptCustomer::class, 'c');
        $rsm->disableDecrypt();

        $result = $this->_em->createNativeQuery('SELECT * FROM encrypt_customers', $rsm)->getArrayResult();

        self::assertCount(4, $result);
        self::assertNotSame('123-45-6789', $result[0]['ssn']);
        self::assertSame('123-45-6789', $this->decrypt($result[0]['ssn']));
    }
}
