<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Decorator;

use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class EntityManagerDecoratorTest extends TestCase
{
    use VerifyDeprecations;

    public const VOID_METHODS = [
        'persist',
        'remove',
        'clear',
        'detach',
        'refresh',
        'flush',
        'initializeObject',
        'beginTransaction',
        'commit',
        'rollback',
        'close',
        'lock',
    ];

    private EntityManagerInterface&MockObject $wrapped;

    protected function setUp(): void
    {
        $this->wrapped = $this->createMock(EntityManagerInterface::class);
    }

    public function testGetPartialReferenceIsDeprecated(): void
    {
        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/10987');
        $decorator = new class ($this->wrapped) extends EntityManagerDecorator {
        };
        $decorator->getPartialReference(stdClass::class, 1);
    }
}
