<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\Common\Cli\AbstractNamespace;

/**
 * TaskMock used for testing the CLI interface.
 *
 * @author Nils Adermann <naderman@naderman.de>
 */
class TaskMock extends \Doctrine\Common\Cli\Tasks\AbstractTask
{
    /**
     * Since instances of this class can be created elsewhere all instances
     * register themselves in this array for later inspection.
     *
     * @var array (TaskMock)
     */
    static public $instances = [];

    /**
     * @var int
     */
    private $runCounter = 0;

    /**
     * Constructor of Task Mock Object.
     * Makes sure the object can be inspected later.
     *
     * @param AbstractNamespace $namespace CLI Namespace, passed to parent constructor.
     */
    function __construct(AbstractNamespace $namespace)
    {
        self::$instances[] = $this;

        parent::__construct($namespace);
    }

    /**
     * Returns the number of times run() was called on this object.
     *
     * @return int
     */
    public function getRunCounter()
    {
        return $this->runCounter;
    }

    /* Mock API */

    /**
     * Method invoked by CLI to run task.
     *
     * @return void
     */
    public function run()
    {
        $this->runCounter++;
    }

    /**
     * Method supposed to generate the CLI Task Documentation.
     *
     * @return void
     */
    public function buildDocumentation()
    {
    }
}
