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
 
namespace Doctrine\ORM\Tools\Cli\Task;

use Doctrine\ORM\Tools\Cli\AbstractTask;

class Help extends AbstractTask
{
    public function extendedHelp()
    {
        $this->getPrinter()->write('help extended help' . PHP_EOL, 'HEADER');
        $this->getPrinter()->write('help extended help' . PHP_EOL, 'ERROR');
        $this->getPrinter()->write('help extended help' . PHP_EOL, 'INFO');
        $this->getPrinter()->write('help extended help' . PHP_EOL, 'COMMENT');
        $this->getPrinter()->write('help extended help' . PHP_EOL, 'NONE');
    }

    public function basicHelp()
    {
        $this->getPrinter()->write('help basic help' . PHP_EOL, 'HEADER');
        $this->getPrinter()->write('help basic help' . PHP_EOL, 'ERROR');
        $this->getPrinter()->write('help basic help' . PHP_EOL, 'INFO');
        $this->getPrinter()->write('help basic help' . PHP_EOL, 'COMMENT');
        $this->getPrinter()->write('help basic help' . PHP_EOL, 'NONE');
    }
    
    public function validate()
    {
        return true;
    }

    public function run()
    {
        $this->getPrinter()->write('help run' . PHP_EOL, 'HEADER');
        $this->getPrinter()->write('help run' . PHP_EOL, 'ERROR');
        $this->getPrinter()->write('help run' . PHP_EOL, 'INFO');
        $this->getPrinter()->write('help run' . PHP_EOL, 'COMMENT');
        $this->getPrinter()->write('help run' . PHP_EOL, 'NONE');
    }
}