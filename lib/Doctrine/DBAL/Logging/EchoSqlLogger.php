<?php

namespace Doctrine\DBAL\Logging;

/**
 * A SQL logger that logs to the standard output using echo/var_dump.
 * 
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class EchoSqlLogger implements SqlLogger
{
    public function logSql($sql, array $params = null)
    {
    	echo $sql . PHP_EOL;
    	if ($params) {
    		var_dump($params);
    	}
    }
}