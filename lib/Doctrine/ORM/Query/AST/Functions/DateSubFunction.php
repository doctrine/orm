<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\QueryException;

/**
 * "DATE_ADD(date1, interval, unit)"
 *
 * 
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class DateSubFunction extends DateAddFunction
{
    /**
     * @override
     * @inheritdoc
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        switch (strtolower($this->unit->value)) {
            case 'hour':
                return $sqlWalker->getConnection()->getDatabasePlatform()->getDateSubHourExpression(
                    $this->firstDateExpression->dispatch($sqlWalker),
                    $this->intervalExpression->dispatch($sqlWalker)
                );
            case 'day':
                return $sqlWalker->getConnection()->getDatabasePlatform()->getDateSubDaysExpression(
                    $this->firstDateExpression->dispatch($sqlWalker),
                    $this->intervalExpression->dispatch($sqlWalker)
                );

            case 'month':
                return $sqlWalker->getConnection()->getDatabasePlatform()->getDateSubMonthExpression(
                    $this->firstDateExpression->dispatch($sqlWalker),
                    $this->intervalExpression->dispatch($sqlWalker)
                );

            default:
                throw QueryException::semanticalError(
                    'DATE_SUB() only supports units of type hour, day and month.'
                );
        }
    }
}
