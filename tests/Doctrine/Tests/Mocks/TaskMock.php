<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\Common\Cli\AbstractNamespace;
use Doctrine\Common\Cli\Tasks\AbstractTask;

/**
 * TaskMock used for testing the CLI interface.
 */
class TaskMock extends AbstractTask
{
    /**
     * Since instances of this class can be created elsewhere all instances
     * register themselves in this array for later inspection.
     *
     * @var array (TaskMock)
     */
    public static $instances = [];

    /** @var int */
    private $runCounter = 0;

    /**
     * Constructor of Task Mock Object.
     * Makes sure the object can be inspected later.
     *
     * @param AbstractNamespace $namespace CLI Namespace, passed to parent constructor.
     */
    public function __construct(AbstractNamespace $namespace)
    {
        self::$instances[] = $this;

        parent::__construct($namespace);
    }

    /**
     * Returns the number of times run() was called on this object.
     */
    public function getRunCounter(): int
    {
        return $this->runCounter;
    }

    /* Mock API */

    /**
     * Method invoked by CLI to run task.
     */
    public function run(): void
    {
        $this->runCounter++;
    }

    /**
     * Method supposed to generate the CLI Task Documentation.
     */
    public function buildDocumentation(): void
    {
    }
}
