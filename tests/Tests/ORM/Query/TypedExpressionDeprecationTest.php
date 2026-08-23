<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\AST\ExpressionWithReturnType;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\TypedExpression;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

class TypedExpressionDeprecationTest extends OrmTestCase
{
    use VerifyDeprecations;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->getTestEntityManager();
    }

    #[IgnoreDeprecations]
    public function testImplementingLegacyTypedExpressionTriggersDeprecation(): void
    {
        $this->entityManager
            ->getConfiguration()
            ->addCustomNumericFunction('LEGACY_TYPED', LegacyTypedFunctionStub::class);

        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/12543');

        $this->entityManager
            ->createQuery('SELECT LEGACY_TYPED(p.phonenumber) FROM ' . CmsPhonenumber::class . ' p')
            ->getSQL();
    }

    public function testImplementingExpressionWithReturnTypeDoesNotTriggerDeprecation(): void
    {
        $this->entityManager
            ->getConfiguration()
            ->addCustomNumericFunction('MODERN_TYPED', ModernTypedFunctionStub::class);

        $this->expectNoDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/12543');

        $this->entityManager
            ->createQuery('SELECT MODERN_TYPED(p.phonenumber) FROM ' . CmsPhonenumber::class . ' p')
            ->getSQL();
    }
}

final class LegacyTypedFunctionStub extends FunctionNode implements TypedExpression
{
    private Node $arithmeticExpression;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'ABS(' . $sqlWalker->walkSimpleArithmeticExpression($this->arithmeticExpression) . ')';
    }

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->arithmeticExpression = $parser->SimpleArithmeticExpression();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getReturnType(): Type
    {
        return Type::getType(Types::INTEGER);
    }
}

final class ModernTypedFunctionStub extends FunctionNode implements ExpressionWithReturnType, TypedExpression
{
    private Node $arithmeticExpression;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'ABS(' . $sqlWalker->walkSimpleArithmeticExpression($this->arithmeticExpression) . ')';
    }

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->arithmeticExpression = $parser->SimpleArithmeticExpression();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getReturnTypeName(): string
    {
        return Types::INTEGER;
    }

    public function getReturnType(): Type
    {
        return Type::getType($this->getReturnTypeName());
    }
}
