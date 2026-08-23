<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EntityRepository::class)]
class EntityRepositoryTest extends TestCase
{
    public function testExpr(): void
    {
        $expr = static::createStub(Expr::class);

        $em = static::createStub(EntityManagerInterface::class);
        $em->method('getExpressionBuilder')->willReturn($expr);

        $metadata = new ClassMetadata('foo');

        $repository = new class ($em, $metadata) extends EntityRepository {
            public function testExpr(): void
            {
                TestCase::assertSame($this->getEntityManager()->getExpressionBuilder(), $this->expr());
            }
        };

        $repository->testExpr();
    }
}
