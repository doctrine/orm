<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\Tests\Models\NonPublicSchemaJoins\User as NonPublicSchemaUser;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use function sprintf;

/**
 * Doctrine\Tests\ORM\Mapping\DefaultQuoteStrategyTest
 */
class DefaultQuoteStrategyTest extends OrmTestCase
{
    #[Group('DDC-3590')]
    #[Group('DDC-1316')]
    public function testGetJoinTableName(): void
    {
        $em       = $this->getTestEntityManager();
        $metadata = $em->getClassMetadata(NonPublicSchemaUser::class);
        $strategy = new DefaultQuoteStrategy();
        $platform = new SQLitePlatform();

        self::assertSame(
            'readers.author_reader',
            $strategy->getJoinTableName($metadata->associationMappings['readers'], $metadata, $platform),
        );
    }

    #[DataProvider('fullTableNameProvider')]
    public function testGetTableName(string $expectedFullTableName, string $tableName): void
    {
        $classMetadata = new ClassMetadata(self::class);
        $classMetadata->setPrimaryTable(['name' => $tableName]);

        $platform = $this->createStub(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')
            ->willReturnCallback(
                static fn (string $identifier): string => sprintf('✌️%s✌️', $identifier),
            );

        $quotedTableName = (new DefaultQuoteStrategy())->getTableName(
            $classMetadata,
            $platform,
        );

        self::assertSame($expectedFullTableName, $quotedTableName);
    }

    /** @return iterable<string, array{string, string}> */
    public static function fullTableNameProvider(): iterable
    {
        yield 'quoted table name with schema' => [
            '✌️custom✌️.✌️reserved✌️',
            'custom.`reserved`',
        ];

        yield 'unquoted table name with schema' => [
            'custom.non_reserved',
            'custom.non_reserved',
        ];

        yield 'quoted table name without schema' => [
            '✌️reserved✌️',
            '`reserved`',
        ];

        yield 'unquoted table name without schema' => [
            'non_reserved',
            'non_reserved',
        ];
    }
}
