<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\ASTException;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;

use function assert;
use function is_numeric;
use function strtolower;

/**
 * "DATE_ADD" "(" ArithmeticPrimary "," ArithmeticPrimary "," StringPrimary ")"
 *
 * @link    www.doctrine-project.org
 */
class DateAddFunction extends FunctionNode
{
    /** @var Node */
    public $firstDateExpression = null;

    /** @var Node */
    public $intervalExpression = null;

    /** @var Node */
    public $unit = null;

    /** @inheritdoc */
    public function getSql(SqlWalker $sqlWalker)
    {
        switch (strtolower($this->unit->value)) {
            case 'second':
                return $sqlWalker->getConnection()->getDatabasePlatform()->getDateAddSecondsExpression(
                    $this->firstDateExpression->dispatch($sqlWalker),
                    $this->dispatchIntervalExpression($sqlWalker),
                );

            case 'minute':
                return $sqlWalker->getConnection()->getDatabasePlatform()->getDateAddMinutesExpression(
                    $this->firstDateExpression->dispatch($sqlWalker),
                    $this->dispatchIntervalExpression($sqlWalker),
                );

            case 'hour':
                return $sqlWalker->getConnection()->getDatabasePlatform()->getDateAddHourExpression(
                    $this->firstDateExpression->dispatch($sqlWalker),
                    $this->dispatchIntervalExpression($sqlWalker),
                );

            case 'day':
                return $sqlWalker->getConnection()->getDatabasePlatform()->getDateAddDaysExpression(
                    $this->firstDateExpression->dispatch($sqlWalker),
                    $this->dispatchIntervalExpression($sqlWalker),
                );

            case 'week':
                return $sqlWalker->getConnection()->getDatabasePlatform()->getDateAddWeeksExpression(
                    $this->firstDateExpression->dispatch($sqlWalker),
                    $this->dispatchIntervalExpression($sqlWalker),
                );

            case 'month':
                return $sqlWalker->getConnection()->getDatabasePlatform()->getDateAddMonthExpression(
                    $this->firstDateExpression->dispatch($sqlWalker),
                    $this->dispatchIntervalExpression($sqlWalker),
                );

            case 'year':
                return $sqlWalker->getConnection()->getDatabasePlatform()->getDateAddYearsExpression(
                    $this->firstDateExpression->dispatch($sqlWalker),
                    $this->dispatchIntervalExpression($sqlWalker),
                );

            default:
                throw QueryException::semanticalError(
                    'DATE_ADD() only supports units of type second, minute, hour, day, week, month and year.',
                );
        }
    }

    /**
     * @return numeric-string
     *
     * @throws ASTException
     */
    private function dispatchIntervalExpression(SqlWalker $sqlWalker)
    {
        $sql = $this->intervalExpression->dispatch($sqlWalker);
        assert(is_numeric($sql));

        return $sql;
    }

    /** @inheritdoc */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->firstDateExpression = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->intervalExpression = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->unit = $parser->StringPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
