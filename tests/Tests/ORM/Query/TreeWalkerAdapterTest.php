<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use PHPUnit\Framework\TestCase;
use stdClass;

class TreeWalkerAdapterTest extends TestCase
{
    use VerifyDeprecations;

    public function testDeprecatedSetQueryComponent(): void
    {
        $walker = new class (
            $this->createMock(AbstractQuery::class),
            $this->createMock(ParserResult::class),
            []
        ) extends TreeWalkerAdapter{
        };

        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/9551');
        $walker->setQueryComponent('foo', [
            'metadata' => new ClassMetadata(stdClass::class),
            'parent' => null,
            'relation' => null,
            'map' => null,
            'nestingLevel' => 0,
            'token' => ['value' => '', 'type' => Lexer::T_NONE, 'position' => 0],
        ]);
    }

    public function testSetQueryComponent(): void
    {
        $walker = new class (
            $this->createMock(AbstractQuery::class),
            $this->createMock(ParserResult::class),
            []
        ) extends TreeWalkerAdapter{
            public function doSetQueryComponent(): void
            {
                $this->setQueryComponent('foo', [
                    'metadata' => new ClassMetadata(stdClass::class),
                    'parent' => null,
                    'relation' => null,
                    'map' => null,
                    'nestingLevel' => 0,
                    'token' => ['value' => '', 'type' => Lexer::T_NONE, 'position' => 0],
                ]);
            }
        };

        $this->expectNoDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/9551');
        $walker->doSetQueryComponent();

        self::assertArrayHasKey('foo', $walker->getQueryComponents());
    }
}
