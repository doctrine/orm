<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\Common\Collections\ExpressionBuilder as CriteriaBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Persisters\SqlExpressionVisitor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SqlExpressionVisitorTest extends TestCase
{
    private SqlExpressionVisitor $visitor;
    private BasicEntityPersister&MockObject $persister;
    private ClassMetadata $classMetadata;

    protected function setUp(): void
    {
        $this->persister     = $this->createMock(BasicEntityPersister::class);
        $this->classMetadata = new ClassMetadata('Dummy');
        $this->visitor       = new SqlExpressionVisitor($this->persister, $this->classMetadata);
    }

    public function testWalkNotCompositeExpression(): void
    {
        $cb = new CriteriaBuilder();

        $this->persister
            ->expects(self::once())
            ->method('getSelectConditionStatementSQL')
            ->willReturn('dummy expression');

        $expr = $this->visitor->walkCompositeExpression(
            $cb->not(
                $cb->eq('foo', 1),
            ),
        );

        self::assertEquals('NOT (dummy expression)', $expr);
    }
}
