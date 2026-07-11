<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Encrypt;

class EncryptWriteTest extends EncryptFunctionalTestCase
{
    public function testInsertStoresCiphertextInDatabase(): void
    {
        [$joe] = $this->initCustomers();

        $rawSsn = $this->fetchRawColumn('SELECT ssn FROM encrypt_customers WHERE id = ?', [$joe->id]);

        self::assertNotSame('123-45-6789', $rawSsn);
        self::assertSame('123-45-6789', $this->decrypt($rawSsn));
        self::assertSame('joe', $this->fetchRawColumn('SELECT name FROM encrypt_customers WHERE id = ?', [$joe->id]));
    }

    public function testUpdateReEncryptsChangedValue(): void
    {
        [$joe] = $this->initCustomers();

        $joe->ssn = '999-99-9999';
        $this->_em->flush();

        $rawSsn = $this->fetchRawColumn('SELECT ssn FROM encrypt_customers WHERE id = ?', [$joe->id]);

        self::assertNotSame('999-99-9999', $rawSsn);
        self::assertSame('999-99-9999', $this->decrypt($rawSsn));
    }

    public function testDeterministicCipherProducesIdenticalCiphertextForEqualPlaintextAndRandomCipherDoesNot(): void
    {
        [$joe, , , $jax] = $this->initCustomers();

        $joeRawSsn = $this->fetchRawColumn('SELECT ssn FROM encrypt_customers WHERE id = ?', [$joe->id]);
        $jaxRawSsn = $this->fetchRawColumn('SELECT ssn FROM encrypt_customers WHERE id = ?', [$jax->id]);

        self::assertSame($joeRawSsn, $jaxRawSsn);

        $joeRawNote = $this->fetchRawColumn('SELECT note FROM encrypt_customers WHERE id = ?', [$joe->id]);
        $jaxRawNote = $this->fetchRawColumn('SELECT note FROM encrypt_customers WHERE id = ?', [$jax->id]);

        self::assertNotSame($joeRawNote, $jaxRawNote);
    }
}
