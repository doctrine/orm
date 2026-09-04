<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query\QuerySetMapping;
use PHPUnit\Framework\TestCase;

class QuerySetMappingTest extends TestCase
{
    private QuerySetMapping $qsm;

    protected function setUp(): void
    {
        $this->qsm = new QuerySetMapping();
    }

    public function testIsEmptyByDefault(): void
    {
        self::assertTrue($this->qsm->isEmpty());
        self::assertFalse($this->qsm->hasParameter('name'));
        self::assertNull($this->qsm->getParameterType('name'));
    }

    public function testAddParameter(): void
    {
        self::assertSame($this->qsm, $this->qsm->addParameter('name', 'string'));

        self::assertFalse($this->qsm->isEmpty());
        self::assertTrue($this->qsm->hasParameter('name'));
        self::assertSame('string', $this->qsm->getParameterType('name'));
    }

    public function testAddPositionalParameter(): void
    {
        $this->qsm->addParameter(1, 'string');

        self::assertTrue($this->qsm->hasParameter(1));
        self::assertSame('string', $this->qsm->getParameterType(1));
    }

    /**
     * This covers two distinct fields sharing a type as well: they resolve to the very same DBAL
     * type, which converts values the one way, so nothing is ambiguous about inferring it. See
     * QuerySetMappingWalkerTest, where the two fields are actually told apart.
     */
    public function testAddingTheSameTypeTwiceIsANoop(): void
    {
        $this->qsm->addParameter('name', 'string');
        $this->qsm->addParameter('name', 'string');

        self::assertSame('string', $this->qsm->getParameterType('name'));
        self::assertSame([], $this->qsm->ambiguousParameters);
    }

    public function testAddingAnotherTypeMakesTheParameterAmbiguous(): void
    {
        $this->qsm->addParameter('name', 'string');
        $this->qsm->addParameter('name', 'integer');

        self::assertFalse($this->qsm->hasParameter('name'));
        self::assertNull($this->qsm->getParameterType('name'));
        self::assertSame(['name' => true], $this->qsm->ambiguousParameters);
        self::assertTrue($this->qsm->isEmpty());
    }

    public function testAnAmbiguousParameterCannotBeMappedAgain(): void
    {
        $this->qsm->addParameter('name', 'string');
        $this->qsm->addParameter('name', 'integer');
        $this->qsm->addParameter('name', 'string');

        self::assertFalse($this->qsm->hasParameter('name'));
        self::assertSame(['name' => true], $this->qsm->ambiguousParameters);
    }

    public function testAmbiguityIsTrackedPerParameter(): void
    {
        $this->qsm->addParameter('name', 'string');
        $this->qsm->addParameter('name', 'integer');
        $this->qsm->addParameter('other', 'string');

        self::assertFalse($this->qsm->hasParameter('name'));
        self::assertTrue($this->qsm->hasParameter('other'));
        self::assertSame('string', $this->qsm->getParameterType('other'));
    }
}
