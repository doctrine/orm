<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\Persistence\Mapping\StaticReflectionService;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

use function class_exists;
use function serialize;
use function unserialize;

#[CoversClass(ClassMetadata::class)]
#[Group('DDC-3103')]
class DDC3103Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        if (! class_exists(StaticReflectionService::class)) {
            self::markTestSkipped('This test is not supported by the current installed doctrine/persistence version');
        }

        parent::setUp();
    }

    public function testIssue(): void
    {
        $classMetadata = new ClassMetadata(DDC3103ArticleId::class);

        $this->createAttributeDriver()->loadMetadataForClass(DDC3103ArticleId::class, $classMetadata);

        self::assertTrue(
            $classMetadata->isEmbeddedClass,
            'The isEmbeddedClass property should be true from the mapping data.',
        );

        self::assertTrue(
            unserialize(serialize($classMetadata))->isEmbeddedClass,
            'The isEmbeddedClass property should still be true after serialization and unserialization.',
        );
    }
}

#[Embeddable]
class DDC3103ArticleId
{
    #[Column(name: 'name', type: 'string', length: 255)]
    protected string $nameValue;
}
