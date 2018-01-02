<?php

declare(strict_types=1);

namespace Doctrine\ORM;

/**
 * Pessimistic Lock Exception
 *
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class PessimisticLockException extends ORMException
{
    /**
     * @return PessimisticLockException
     */
    public static function lockFailed()
    {
        return new self("The pessimistic lock failed.");
    }
}
