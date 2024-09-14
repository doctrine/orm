<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmFunctionalTestCase;

use function method_exists;
use function strlen;

/** @group DDC-451 */
class UUIDGeneratorTest extends OrmFunctionalTestCase
{
    use VerifyDeprecations;

    public function testItIsDeprecated(): void
    {
        if (! method_exists(AbstractPlatform::class, 'getGuidExpression')) {
            self::markTestSkipped('Test valid for doctrine/dbal:2.x only.');
        }

        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/issues/7312');
        $this->_em->getClassMetadata(UUIDEntity::class);
    }

    public function testGenerateUUID(): void
    {
        if (! method_exists(AbstractPlatform::class, 'getGuidExpression')) {
            self::markTestSkipped('Test valid for doctrine/dbal:2.x only.');
        }

        if (! $this->_em->getConnection()->getDatabasePlatform() instanceof MySQLPlatform) {
            self::markTestSkipped('Currently restricted to MySQL platform.');
        }

        $this->createSchemaForModels(UUIDEntity::class);
        $entity = new UUIDEntity();

        $this->_em->persist($entity);
        self::assertNotNull($entity->getId());
        self::assertGreaterThan(0, strlen($entity->getId()));
    }

    public function testItCannotBeInitialised(): void
    {
        if (method_exists(AbstractPlatform::class, 'getGuidExpression')) {
            self::markTestSkipped('Test valid for doctrine/dbal:3.x only.');
        }

        $this->expectException(NotSupported::class);
        $this->_em->getClassMetadata(UUIDEntity::class);
    }
}

/** @Entity */
class UUIDEntity
{
    /**
     * @var string
     * @Id
     * @Column(type="string")
     * @GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * Get id.
     *
     * @return string.
     */
    public function getId(): string
    {
        return $this->id;
    }
}
