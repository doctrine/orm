<?php

namespace Doctrine\DBAL\Logging;

/**
 * Interface for SQL loggers.
 * 
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
interface SqlLogger
{
	function logSql($sql, array $params = null);
}