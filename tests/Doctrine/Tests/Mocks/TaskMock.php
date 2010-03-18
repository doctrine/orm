<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests\Mocks;

use Doctrine\Common\Cli\AbstractNamespace;

/**
 * TaskMock used for testing the CLI interface.
 * @author Nils Adermann <naderman@naderman.de>
 */
class TaskMock extends \Doctrine\Common\Cli\Tasks\AbstractTask
{
    /**
     * Since instances of this class can be created elsewhere all instances
     * register themselves in this array for later inspection.
     *
     * @var array(TaskMock)
     */
    static public $instances = array();

    private $runCounter = 0;

    /**
     * Constructor of Task Mock Object.
     * Makes sure the object can be inspected later.
     *
     * @param AbstractNamespace CLI Namespace, passed to parent constructor
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
     */
    public function run()
    {
        $this->runCounter++;
    }

    /**
     * Method supposed to generate the CLI Task Documentation
     */
    public function buildDocumentation()
    {
    }
}
