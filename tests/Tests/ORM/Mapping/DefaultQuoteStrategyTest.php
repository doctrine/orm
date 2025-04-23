<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Name\UnquotedIdentifierFolding;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\Tests\Models\NonPublicSchemaJoins\User as NonPublicSchemaUser;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\Group;

use function assert;
use function enum_exists;

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
        $platform = $this->getMockForAbstractClass(AbstractPlatform::class, enum_exists(UnquotedIdentifierFolding::class) ? [UnquotedIdentifierFolding::UPPER] : []);
        assert($platform instanceof AbstractPlatform);

        self::assertSame(
            'readers.author_reader',
            $strategy->getJoinTableName($metadata->associationMappings['readers'], $metadata, $platform),
        );
    }
}
