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
 
namespace Doctrine\Common\Cli\Tasks;

use Doctrine\Common\Cli\CliException;

/**
 * CLI Task to display available commands help
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class HelpTask extends AbstractTask
{
    /**
     * @inheritdoc
     */
    public function buildDocumentation()
    {
        // Does nothing
    }
    
    /**
     * @inheritdoc
     */
    public function extendedHelp()
    {
        $this->run();
    }

    /**
     * @inheritdoc
     */
    public function basicHelp()
    {
        $this->run();
    }

    /**
     * Exposes the available tasks
     *
     */
    public function run()
    {
        $this->getPrinter()->writeln('Available Tasks:', 'HEADER')->write(PHP_EOL);
        
        // Find the CLI Controller
        $cliController = $this->getNamespace()->getParentNamespace();
        
        // Switch between ALL available tasks and display the basic Help of each one
        $availableTasks = $cliController->getAvailableTasks();
        unset($availableTasks['Core:Help']);
        
        ksort($availableTasks);
        
        foreach (array_keys($availableTasks) as $taskName) {
            $cliController->runTask($taskName, array('basic-help' => true));
        }
    }
}