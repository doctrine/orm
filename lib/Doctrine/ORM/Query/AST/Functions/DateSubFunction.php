<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\ASTException;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;

use function assert;
use function is_numeric;
use function strtolower;

/**
 * "DATE_SUB(date1, interval, unit)"
 *
 * @link    www.doctrine-project.org
 */
class DateSubFunction extends DateAddFunction
{
    /** @inheritdoc */
    public function getSql(SqlWalker $sqlWalker)
    {
        switch (strtolower($this->unit->value)) {
            case 'second':
                return $sqlWalker->getConnection()->getDatabasePlatform()->getDateSubSecondsExpression(
                    $this->firstDateExpression->dispatch($sqlWalker),
                    $this->dispatchIntervalExpression($sqlWalker),
                );

            case 'minute':
                return $sqlWalker->getConnection()->getDatabasePlatform()->getDateSubMinutesExpression(
                    $this->firstDateExpression->dispatch($sqlWalker),
                    $this->dispatchIntervalExpression($sqlWalker),
                );

            case 'hour':
                return $sqlWalker->getConnection()->getDatabasePlatform()->getDateSubHourExpression(
                    $this->firstDateExpression->dispatch($sqlWalker),
                    $this->dispatchIntervalExpression($sqlWalker),
                );

            case 'day':
                return $sqlWalker->getConnection()->getDatabasePlatform()->getDateSubDaysExpression(
                    $this->firstDateExpression->dispatch($sqlWalker),
                    $this->dispatchIntervalExpression($sqlWalker),
                );

            case 'week':
                return $sqlWalker->getConnection()->getDatabasePlatform()->getDateSubWeeksExpression(
                    $this->firstDateExpression->dispatch($sqlWalker),
                    $this->dispatchIntervalExpression($sqlWalker),
                );

            case 'month':
                return $sqlWalker->getConnection()->getDatabasePlatform()->getDateSubMonthExpression(
                    $this->firstDateExpression->dispatch($sqlWalker),
                    $this->dispatchIntervalExpression($sqlWalker),
                );

            case 'year':
                return $sqlWalker->getConnection()->getDatabasePlatform()->getDateSubYearsExpression(
                    $this->firstDateExpression->dispatch($sqlWalker),
                    $this->dispatchIntervalExpression($sqlWalker),
                );

            default:
                throw QueryException::semanticalError(
                    'DATE_SUB() only supports units of type second, minute, hour, day, week, month and year.',
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
}
