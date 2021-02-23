<?php

declare(strict_types=1);

namespace Doctrine\Tests;

use InvalidArgumentException;
use function is_int;
use function microtime;
use function sprintf;

/**
 * Description of DoctrinePerformanceTestCase.
 */
class OrmPerformanceTestCase extends OrmFunctionalTestCase
{
    /** @var int */
    protected $maxRunningTime = 0;

    protected function runTest()
    {
        $s = microtime(true);
        parent::runTest();
        $time = microtime(true) - $s;

        if ($this->maxRunningTime !== 0 && $time > $this->maxRunningTime) {
            $this->fail(
                sprintf(
                    'expected running time: <= %s but was: %s',
                    $this->maxRunningTime,
                    $time
                )
            );
        }
    }

    /**
     * @param int $maxRunningTime
     *
     * @throws InvalidArgumentException
     */
    public function setMaxRunningTime($maxRunningTime)
    {
        if (! (is_int($maxRunningTime) && $maxRunningTime >= 0)) {
            throw new InvalidArgumentException();
        }

        $this->maxRunningTime = $maxRunningTime;
    }

    /**
     * @return int
     */
    public function getMaxRunningTime()
    {
        return $this->maxRunningTime;
    }
}
