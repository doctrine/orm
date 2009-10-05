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

use Doctrine\ORM\Tools\Export\ClassMetadataExporter;

/**
 * CLI Task to convert your mapping information between the various formats
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class ConvertMappingTask extends AbstractTask
{
    /**
     * @inheritdoc
     */
    public function extendedHelp()
    {
        $printer = $this->getPrinter();
    
        $printer->write('Task: ')->writeln('convert-mapping', 'KEYWORD')
                ->write('Synopsis: ');
        $this->_writeSynopsis($printer);
    
        $printer->writeln('Description: Convert mapping information between supported formats.')
                ->writeln('Options:')
                ->write('--from=<PATH>', 'REQ_ARG')
                ->writeln("\tThe path to the mapping information you are converting from.")
                ->write('--to=<TYPE>', 'REQ_ARG')
                ->writeln("\tThe format to convert to.")
                ->write(PHP_EOL)
                ->write('--dest=<PATH>', 'REQ_ARG')
                ->writeln("\tThe path to write the converted mapping information.");
    }

    /**
     * @inheritdoc
     */
    public function basicHelp()
    {
        $this->_writeSynopsis($this->getPrinter());
    }

    private function _writeSynopsis($printer)
    {
        $printer->write('convert-mapping', 'KEYWORD')
                ->write(' --from=<PATH>', 'REQ_ARG')
                ->write(' --to=<TYPE>', 'REQ_ARG')
                ->writeln(' --dest=<PATH>', 'REQ_ARG');
    }

    /**
     * @inheritdoc
     */    
    public function validate()
    {
        if ( ! parent::validate()) {
            return false;
        }
        
        $args = $this->getArguments();
        $printer = $this->getPrinter();

        if (!(isset($args['from']) && isset($args['to']) && isset($args['dest']))) {
          $printer->writeln('You must include a value for all four options: --from, --to and --dest', 'ERROR');
          return false;
        }
        if ($args['to'] != 'annotation' && $args['extend']) {
            $printer->writeln('You can only use the --extend argument when converting to annoations.');
            return false;
        }
        return true;
    }

    public function run()
    {
        $printer = $this->getPrinter();
        $args = $this->getArguments();

        $cme = new ClassMetadataExporter();
        $from = (array) $args['from'];
        foreach ($from as $path) {
            $type = $this->_determinePathType($path);

            $printer->writeln(sprintf('Adding %s mapping directory: "%s"', $type, $path), 'INFO');

            $cme->addMappingDir($path, $type);
        }

        $exporter = $cme->getExporter($args['to']);
        if (isset($args['extend'])) {
            $exporter->setClassToExtend($args['extend']);
        }
        if (isset($args['num-spaces'])) {
            $exporter->setNumSpaces($args['num-spaces']);
        }
        $exporter->setOutputDir($args['dest']);

        $printer->writeln(sprintf('Exporting %s mapping information to directory: "%s"', $args['to'], $args['dest']), 'INFO');

        $exporter->export();
    }

    protected function _determinePathType($path)
    {
      $files = glob($path . '/*.*');
      if (!$files)
      {
        throw new \InvalidArgumentException(sprintf('No schema mapping files found in "%s"', $path));
      }
      $info = pathinfo($files[0]);
      return $info['extension'];
    }
}