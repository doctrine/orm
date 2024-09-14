<?php

declare(strict_types=1);

namespace Doctrine\Tests\DbalExtensions;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection as BaseConnection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Logging\Middleware as LoggingMiddleware;

use function class_exists;

class Connection extends BaseConnection
{
    /** @var QueryLog */
    public $queryLog;

    public function __construct(array $params, Driver $driver, ?Configuration $config = null, ?EventManager $eventManager = null)
    {
        $this->queryLog = new QueryLog();
        if (class_exists(LoggingMiddleware::class)) {
            $logging = new LoggingMiddleware(new SqlLogger($this->queryLog));
            $driver  = $logging->wrap($driver);
        } else {
            $config = $config ?? new Configuration();
            $config->setSQLLogger(new LegacySqlLogger($this->queryLog));
        }

        parent::__construct($params, $driver, $config, $eventManager);
    }
}
