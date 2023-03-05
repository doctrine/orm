<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

use function serialize;
use function unserialize;

#[CoversClass(ClassMetadata::class)]
#[Group('DDC-3103')]
class DDC3103Test extends OrmFunctionalTestCase
{
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
    /** @var string */
    #[Column(name: 'name', type: 'string', length: 255)]
    protected $nameValue;
}
