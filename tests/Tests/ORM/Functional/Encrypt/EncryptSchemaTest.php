<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Encrypt;

use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\Tests\Models\Encrypt\EncryptCustomer;

/**
 * Schema generation for encrypted fields: the column uses the cipher's storage type,
 * not the mapped field type.
 */
class EncryptSchemaTest extends EncryptFunctionalTestCase
{
    public function testSchemaGeneratesBlobColumnForEncryptedField(): void
    {
        $table = $this->getSchemaForModels(EncryptCustomer::class)->getTable('encrypt_customers');

        self::assertInstanceOf(BlobType::class, $table->getColumn('ssn')->getType());
        self::assertInstanceOf(BlobType::class, $table->getColumn('note')->getType());
        self::assertInstanceOf(StringType::class, $table->getColumn('name')->getType());
    }
}
