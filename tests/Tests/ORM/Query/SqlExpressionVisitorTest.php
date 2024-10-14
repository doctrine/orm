<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\Common\Collections\ExpressionBuilder as CriteriaBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Persisters\SqlExpressionVisitor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function method_exists;

class SqlExpressionVisitorTest extends TestCase
{
    /** @var SqlExpressionVisitor */
    private $visitor;

    /** @var BasicEntityPersister&MockObject */
    private $persister;
    /** @var ClassMetadata */
    private $classMetadata;

    protected function setUp(): void
    {
        $this->persister     = $this->createMock(BasicEntityPersister::class);
        $this->classMetadata = new ClassMetadata('Dummy');
        $this->visitor       = new SqlExpressionVisitor($this->persister, $this->classMetadata);
    }

    public function testWalkNotCompositeExpression(): void
    {
        if (! method_exists(CriteriaBuilder::class, 'not')) {
            self::markTestSkipped('doctrine/collections in version ^2.1 is required for this test to run.');
        }

        $cb = new CriteriaBuilder();

        $this->persister
            ->expects(self::once())
            ->method('getSelectConditionStatementSQL')
            ->willReturn('dummy expression');

        $expr = $this->visitor->walkCompositeExpression(
            $cb->not(
                $cb->eq('foo', 1)
            )
        );

        self::assertEquals('NOT (dummy expression)', $expr);
    }
}
