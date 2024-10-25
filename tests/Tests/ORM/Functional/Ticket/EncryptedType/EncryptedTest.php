<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\EncryptedType;

use Doctrine\DBAL\Types\Type;
use Doctrine\Tests\OrmFunctionalTestCase;

use function implode;
use function sprintf;
use function str_replace;

final class EncryptedTest extends OrmFunctionalTestCase
{
    private const PASSWORD = '#password#';

    public function setUp(): void
    {
        parent::setUp();

        if (! Type::hasType(EncryptedType::NAME)) {
            Type::addType(EncryptedType::NAME, EncryptedType::class);
        }

        $this->setUpEntitySchema([
            Credential::class,
        ]);
    }

    private function prepareData(): void
    {
        $credential = new Credential('root', self::PASSWORD);

        $this->_em->persist($credential);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testUseQueryBuilderWhereExpression(): void
    {
        $this->prepareData();

        $credential = $this->_em->createQueryBuilder()
            ->select('credential')
            ->from(Credential::class, 'credential')
            ->where('credential.password = :password')
            ->setParameter('password', self::PASSWORD)
            ->getQuery()
            ->getOneOrNullResult();

        self::assertInstanceOf(Credential::class, $credential, $this->generateMessage('object not found'));
    }

    public function testUseQueryBuilderSubSelect(): void
    {
        $this->prepareData();

        $subSelect = $this->_em->createQueryBuilder()
            ->select('credential.password')
            ->from(Credential::class, 'credential')
            ->where('credential.password = :password');

        $password = $this->_em->createQueryBuilder()
            ->addSelect(sprintf('(%s)', $subSelect))
            ->from(Credential::class, 'c')
            ->setParameter('password', self::PASSWORD)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();

        self::assertEquals(self::PASSWORD, $password, $this->generateMessage('object not found'));
    }

    public function testUseQueryBuilderSubSelectWhere(): void
    {
        $this->prepareData();

        $subSelect = $this->_em->createQueryBuilder()
            ->select('credential.id')
            ->from(Credential::class, 'credential')
            ->where('credential.password = :password');

        $password = $this->_em->createQueryBuilder()
            ->select('c')
            ->from(Credential::class, 'c')
            ->where(sprintf('c.id in(%s)', $subSelect))
            ->setParameter('password', self::PASSWORD)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        self::assertInstanceOf(Credential::class, $password, $this->generateMessage('object not found'));
    }

    public function testUseCriteria(): void
    {
        $this->prepareData();

        $credential = $this->_em->getRepository(Credential::class)->findOneBy(['password' => self::PASSWORD]);

        self::assertInstanceOf(Credential::class, $credential, $this->generateMessage('object not found'));
    }

    private function generateMessage(string $message): string
    {
        $sqlTrace = [];
        foreach ($this->getQueryLog()->queries as $key => $log) {
            $sql        = sprintf(str_replace('?', '"%s"', $log['sql']), ...($log['params'] ?? []));
            $sqlTrace[] = sprintf('#%s %s', $key, str_replace("\n", '', $sql));
        }

        return sprintf("%s\nSQL Trace:\n\t%s", $message, implode("\n\t", $sqlTrace));
    }
}
