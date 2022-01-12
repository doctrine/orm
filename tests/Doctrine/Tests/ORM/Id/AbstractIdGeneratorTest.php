<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Id;

use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;

class AbstractIdGeneratorTest extends TestCase
{
    use VerifyDeprecations;

    public function testDeprecationLayerForLegacyImplementation(): void
    {
        $generator = new class extends AbstractIdGenerator
        {
            /** @var mixed */
            public $receivedEm;
            /** @var mixed */
            public $receivedEntity;

            /**
             * {@inheritdoc}
             */
            public function generate(EntityManager $em, $entity): string
            {
                $this->receivedEm     = $em;
                $this->receivedEntity = $entity;

                return '4711';
            }
        };

        $em     = $this->createMock(EntityManager::class);
        $entity = new stdClass();

        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/9325');

        self::assertSame('4711', $generator->generateId($em, $entity));
        self::assertSame($em, $generator->receivedEm);
        self::assertSame($entity, $generator->receivedEntity);
    }

    public function testNoEndlessRecursionOnGenerateId(): void
    {
        $generator = new class extends AbstractIdGenerator
        {
        };

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Endless recursion detected in Doctrine\ORM\Id\AbstractIdGenerator@anonymous. Please implement generateId() without calling the parent implementation.');

        $generator->generateId($this->createMock(EntityManager::class), (object) []);
    }

    public function testNoEndlessRecursionOnGenerate(): void
    {
        $generator = new class extends AbstractIdGenerator
        {
        };

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Endless recursion detected in Doctrine\ORM\Id\AbstractIdGenerator@anonymous. Please implement generateId() without calling the parent implementation.');

        $generator->generate($this->createMock(EntityManager::class), (object) []);
    }
}
