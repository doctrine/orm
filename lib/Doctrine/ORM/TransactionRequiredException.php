<?php

declare(strict_types=1);

namespace Doctrine\ORM;

/**
 * Is thrown when a transaction is required for the current operation, but there is none open.
 *
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class TransactionRequiredException extends ORMException
{
    /**
     * @return TransactionRequiredException
     */
    static public function transactionRequired()
    {
        return new self('An open transaction is required for this operation.');
    }
}
