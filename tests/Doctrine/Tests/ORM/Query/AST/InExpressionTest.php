<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query\AST;

use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\Query\AST\ArithmeticExpression;
use Doctrine\ORM\Query\AST\InExpression;
use Doctrine\ORM\Query\AST\InListExpression;
use Doctrine\ORM\Query\AST\InSubselectExpression;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\AST\Subselect;
use PHPUnit\Framework\TestCase;

class InExpressionTest extends TestCase
{
    use VerifyDeprecations;

    public function testDeprecation(): void
    {
        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/10267');

        new InExpression(new ArithmeticExpression());
    }

    public function testNoDeprecations(): void
    {
        $this->expectNoDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/10267');

        new InListExpression(new ArithmeticExpression(), [new Literal(Literal::STRING, 'foo')]);
        new InSubselectExpression(new ArithmeticExpression(), $this->createMock(Subselect::class));
    }
}
