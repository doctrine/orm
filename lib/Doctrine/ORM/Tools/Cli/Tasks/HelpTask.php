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
 
namespace Doctrine\ORM\Tools\Cli\Tasks;

/**
 * CLI Task to display available commands help
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class HelpTask extends AbstractTask
{
    /**
     * @inheritdoc
     */
    public function extendedHelp()
    {
        $this->getPrinter()->write('help extended help' . PHP_EOL, 'HEADER');
        $this->getPrinter()->write('help extended help' . PHP_EOL, 'ERROR');
        $this->getPrinter()->write('help extended help' . PHP_EOL, 'INFO');
        $this->getPrinter()->write('help extended help' . PHP_EOL, 'COMMENT');
        $this->getPrinter()->write('help extended help' . PHP_EOL, 'NONE');
    }

    /**
     * @inheritdoc
     */
    public function basicHelp()
    {
        $this->getPrinter()->write('help basic help' . PHP_EOL, 'HEADER');
        $this->getPrinter()->write('help basic help' . PHP_EOL, 'ERROR');
        $this->getPrinter()->write('help basic help' . PHP_EOL, 'INFO');
        $this->getPrinter()->write('help basic help' . PHP_EOL, 'COMMENT');
        $this->getPrinter()->write('help basic help' . PHP_EOL, 'NONE');
    }

    /**
     * @inheritdoc
     */    
    public function validate()
    {
        return true;
    }

    /**
     * Exposes the available tasks
     *
     */
    public function run()
    {
        $this->getPrinter()->write('help run' . PHP_EOL, 'HEADER');
        $this->getPrinter()->write('help run' . PHP_EOL, 'ERROR');
        $this->getPrinter()->write('help run' . PHP_EOL, 'INFO');
        $this->getPrinter()->write('help run' . PHP_EOL, 'COMMENT');
        $this->getPrinter()->write('help run' . PHP_EOL, 'NONE');
    }
}