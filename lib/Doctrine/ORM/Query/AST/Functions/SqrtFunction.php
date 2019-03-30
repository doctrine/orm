<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\AST\TypedExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * "SQRT" "(" SimpleArithmeticExpression ")"
 */
class SqrtFunction extends FunctionNode implements TypedExpression
{
    /** @var SimpleArithmeticExpression */
    public $simpleArithmeticExpression;

    /**
     * @override
     * @inheritdoc
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return $sqlWalker->getConnection()->getDatabasePlatform()->getSqrtExpression(
            $sqlWalker->walkSimpleArithmeticExpression($this->simpleArithmeticExpression)
        );
    }

    /**
     * @override
     * @inheritdoc
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->simpleArithmeticExpression = $parser->SimpleArithmeticExpression();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * @inheritDoc
     */
    public function getReturnType() : Type
    {
        return Type::getType(Type::FLOAT);
    }
}
