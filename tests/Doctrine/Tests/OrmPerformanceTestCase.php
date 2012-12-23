<?php

namespace Doctrine\Tests;

/**
 * Description of DoctrinePerformanceTestCase.
 *
 * @author robo
 */
class OrmPerformanceTestCase extends OrmFunctionalTestCase
{
    /**
     * @var integer
     */
    protected $maxRunningTime = 0;

    /**
     * @return void
     */
    protected function runTest()
    {
        $s = microtime(true);
        parent::runTest();
        $time = microtime(true) - $s;

        if ($this->maxRunningTime != 0 && $time > $this->maxRunningTime) {
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
     * @param integer $maxRunningTime
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     *
     * @since Method available since Release 2.3.0
     */
    public function setMaxRunningTime($maxRunningTime)
    {
        if (is_integer($maxRunningTime) && $maxRunningTime >= 0) {
            $this->maxRunningTime = $maxRunningTime;
        } else {
            throw new \InvalidArgumentException;
        }
    }

    /**
     * @return integer
     *
     * @since Method available since Release 2.3.0
     */
    public function getMaxRunningTime()
    {
        return $this->maxRunningTime;
    }
}
