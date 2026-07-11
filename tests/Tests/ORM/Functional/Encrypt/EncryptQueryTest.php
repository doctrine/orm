<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Encrypt;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Encrypt\Exception\UnsupportedEncryptedFieldUsage;
use Doctrine\Tests\Models\Encrypt\EncryptCustomer;

/**
 * Query path: filtering by deterministic equality-queryable encrypted fields through the
 * repository APIs, and the rejections for unsupported usages.
 */
class EncryptQueryTest extends EncryptFunctionalTestCase
{
    public function testFindOneByEncryptedEqualityField(): void
    {
        $this->initCustomers();
        $this->_em->clear();

        $found = $this->_em->getRepository(EncryptCustomer::class)->findOneBy(['ssn' => '987-65-4321']);

        self::assertNotNull($found);
        $this->assertCustomer('jane', $found);
    }

    public function testFindByEncryptedFieldWithInList(): void
    {
        $this->initCustomers();
        $this->_em->clear();

        $founds = $this->_em->getRepository(EncryptCustomer::class)->findBy(['ssn' => ['555-55-5555', '987-65-4321']]);

        self::assertCount(2, $founds);
        $this->assertCustomer('jane', $founds[0]);
        $this->assertCustomer('jim', $founds[1]);
    }

    public function testMatchingCriteriaWithDuplicatedEncryptedField(): void
    {
        $this->initCustomers();
        $this->_em->clear();

        $criteria = Criteria::create()->where(
            Criteria::expr()->orX(
                Criteria::expr()->eq('ssn', '555-55-5555'),
                Criteria::expr()->eq('ssn', '987-65-4321'),
            ),
        );

        $founds = $this->_em->getRepository(EncryptCustomer::class)->matching($criteria);

        self::assertCount(2, $founds);
        $this->assertCustomer('jane', $founds[0]);
        $this->assertCustomer('jim', $founds[1]);
    }

    public function testFilteringByNonQueryableEncryptedFieldThrows(): void
    {
        $this->expectException(UnsupportedEncryptedFieldUsage::class);
        $this->expectExceptionMessage(EncryptCustomer::class . '::$note is encrypted; filtering by an encrypted field without queryType set is not supported.');

        $this->_em->getRepository(EncryptCustomer::class)->findOneBy(['note' => 'top secret']);
    }

    public function testMatchingCriteriaWithNonEqualityOperatorOnEncryptedFieldThrows(): void
    {
        $criteria = Criteria::create()->where(Criteria::expr()->gt('ssn', '123-45-6789'));

        $this->expectException(UnsupportedEncryptedFieldUsage::class);
        $this->expectExceptionMessage(EncryptCustomer::class . '::$ssn is encrypted; filtering by an encrypted field only supports equality operators (=, <>, IN, NIN), got ">".');

        $this->_em->getRepository(EncryptCustomer::class)->matching($criteria)->toArray();
    }

    public function testOrderingByEncryptedFieldThrows(): void
    {
        $this->expectException(UnsupportedEncryptedFieldUsage::class);
        $this->expectExceptionMessage(EncryptCustomer::class . '::$ssn is encrypted; ordering by an encrypted field is not supported.');

        $this->_em->getRepository(EncryptCustomer::class)->findBy([], ['ssn' => 'ASC']);
    }
}
