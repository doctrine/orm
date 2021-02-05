<?php

declare(strict_types=1);

namespace Doctrine\Tests;

use InvalidArgumentException;

use function is_integer;
use function microtime;
use function sprintf;

/**
 * Description of DoctrinePerformanceTestCase.
 */
class OrmPerformanceTestCase extends OrmFunctionalTestCase
{
    /** @var int */
    protected $maxRunningTime = 0;

    protected function runTest(): void
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
     * @throws InvalidArgumentException
     */
    public function setMaxRunningTime(int $maxRunningTime): void
    {
        if (is_integer($maxRunningTime) && $maxRunningTime >= 0) {
            $this->maxRunningTime = $maxRunningTime;
        } else {
            throw new InvalidArgumentException();
        }
    }

    public function getMaxRunningTime(): int
    {
        return $this->maxRunningTime;
    }
}
