<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query\BindParameterMapping;
use PHPUnit\Framework\TestCase;

class BindParameterMappingTest extends TestCase
{
    private BindParameterMapping $bpm;

    protected function setUp(): void
    {
        $this->bpm = new BindParameterMapping();
    }

    public function testIsEmptyByDefault(): void
    {
        self::assertTrue($this->bpm->isEmpty());
        self::assertFalse($this->bpm->hasParameter('name'));
        self::assertNull($this->bpm->getParameterType('name'));
    }

    public function testAddParameter(): void
    {
        self::assertSame($this->bpm, $this->bpm->addParameter('name', 'string'));

        self::assertFalse($this->bpm->isEmpty());
        self::assertTrue($this->bpm->hasParameter('name'));
        self::assertSame('string', $this->bpm->getParameterType('name'));
    }

    public function testAddPositionalParameter(): void
    {
        $this->bpm->addParameter(1, 'string');

        self::assertTrue($this->bpm->hasParameter(1));
        self::assertSame('string', $this->bpm->getParameterType(1));
    }

    /**
     * This covers two distinct fields sharing a type as well: they resolve to the very same DBAL
     * type, which converts values the one way, so nothing is ambiguous about inferring it. See
     * BindParameterMappingWalkerTest, where the two fields are actually told apart.
     */
    public function testAddingTheSameTypeTwiceIsANoop(): void
    {
        $this->bpm->addParameter('name', 'string');
        $this->bpm->addParameter('name', 'string');

        self::assertSame('string', $this->bpm->getParameterType('name'));
        self::assertSame([], $this->bpm->ambiguousParameters);
    }

    public function testAddingAnotherTypeMakesTheParameterAmbiguous(): void
    {
        $this->bpm->addParameter('name', 'string');
        $this->bpm->addParameter('name', 'integer');

        self::assertFalse($this->bpm->hasParameter('name'));
        self::assertNull($this->bpm->getParameterType('name'));
        self::assertSame(['name' => true], $this->bpm->ambiguousParameters);
        self::assertTrue($this->bpm->isEmpty());
    }

    public function testAnAmbiguousParameterCannotBeMappedAgain(): void
    {
        $this->bpm->addParameter('name', 'string');
        $this->bpm->addParameter('name', 'integer');
        $this->bpm->addParameter('name', 'string');

        self::assertFalse($this->bpm->hasParameter('name'));
        self::assertSame(['name' => true], $this->bpm->ambiguousParameters);
    }

    public function testAmbiguityIsTrackedPerParameter(): void
    {
        $this->bpm->addParameter('name', 'string');
        $this->bpm->addParameter('name', 'integer');
        $this->bpm->addParameter('other', 'string');

        self::assertFalse($this->bpm->hasParameter('name'));
        self::assertTrue($this->bpm->hasParameter('other'));
        self::assertSame('string', $this->bpm->getParameterType('other'));
    }
}
