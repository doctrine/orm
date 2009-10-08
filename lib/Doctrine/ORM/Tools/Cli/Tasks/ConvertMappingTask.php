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

use Doctrine\ORM\Tools\Export\ClassMetadataExporter,
    Doctrine\Common\DoctrineException;

if ( ! class_exists('sfYaml', false)) {
    require_once __DIR__ . '/../../../../../vendor/sfYaml/sfYaml.class.php';
    require_once __DIR__ . '/../../../../../vendor/sfYaml/sfYamlDumper.class.php';
    require_once __DIR__ . '/../../../../../vendor/sfYaml/sfYamlInline.class.php';
    require_once __DIR__ . '/../../../../../vendor/sfYaml/sfYamlParser.class.php';
}

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
                ->write('--from=<SOURCE>', 'REQ_ARG')
                ->writeln("\tThe path to the mapping information to convert from (yml, xml, php, annotation).")
                ->write('--from-database', 'REQ_ARG')
                ->writeln("\tUse this option if you wish to reverse engineer your database to a set of Doctrine mapping files.")
                ->write('--to=<TYPE>', 'REQ_ARG')
                ->writeln("\tThe format to convert to (yml, xml, php, annotation).")
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
                ->write(' --from=<SOURCE>', 'REQ_ARG')
                ->write(' --to=<TYPE>', 'REQ_ARG')
                ->write(' --dest=<PATH>', 'REQ_ARG')
                ->writeln(' --from-database', 'OPT_ARG');
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

        if (array_key_exists('from-database', $args)) {
            $args['from'][0] = 'database';
            $this->setArguments($args);
        }

        if (!(isset($args['from']) && isset($args['to']) && isset($args['dest']))) {
          $printer->writeln('You must include a value for all three options: --from, --to and --dest', 'ERROR');
          return false;
        }
        if ($args['to'] != 'annotation' && isset($args['extend'])) {
            $printer->writeln('You can only use the --extend argument when converting to annoations.');
            return false;
        }
        if ($args['from'][0] == 'database') {
            $config = $this->_em->getConfiguration();
            $config->setMetadataDriverImpl(new \Doctrine\ORM\Mapping\Driver\DatabaseDriver($this->_em->getConnection()->getSchemaManager()));
        }
        return true;
    }

    public function run()
    {
        $printer = $this->getPrinter();
        $args = $this->getArguments();

        $cme = new ClassMetadataExporter();

        // Get exporter and configure it
        $exporter = $cme->getExporter($args['to'], $args['dest']);

        if (isset($args['extend'])) {
            $exporter->setClassToExtend($args['extend']);
        }
        if (isset($args['num-spaces'])) {
            $exporter->setNumSpaces($args['num-spaces']);
        }

        $from = (array) $args['from'];

        if ($this->_isDoctrine1Schema($from)) {
            $printer->writeln('Converting Doctrine 1 schema to Doctrine 2 mapping files', 'INFO');

            $converter = new \Doctrine\ORM\Tools\ConvertDoctrine1Schema($from);
            $metadatas = $converter->getMetadatasFromSchema();
        } else {
            foreach ($from as $source) {
                $sourceArg = $source;

                $type = $this->_determineSourceType($sourceArg);
                if ( ! $type) {
                    throw DoctrineException::invalidMappingSourceType($sourceArg);
                }
                $source = $this->_getSourceByType($type, $sourceArg);

                $printer->writeln(
                    sprintf(
                        'Adding "%s" mapping source which contains the "%s" format', 
                        $printer->format($sourceArg, 'KEYWORD'),
                        $printer->format($type, 'KEYWORD')
                    )
                );

                $cme->addMappingSource($source, $type);
            }
            $metadatas = $cme->getMetadatasForMappingSources();
        }

        foreach ($metadatas as $metadata) {
            $printer->writeln(
                sprintf(
                    'Processing entity "%s"',
                    $printer->format($metadata->name, 'KEYWORD')
                )
            );
        }

        $printer->writeln(
            sprintf(
                'Exporting "%s" mapping information to directory "%s"',
                $printer->format($args['to'], 'KEYWORD'),
                $printer->format($args['dest'], 'KEYWORD')
            )
        );

        $exporter->setMetadatas($metadatas);
        $exporter->export();
    }

    private function _isDoctrine1Schema(array $from)
    {
        $files = glob(current($from) . '/*.yml');
        if ($files) {
            $array = \sfYaml::load($files[0]);
            $first = current($array);
            // We're dealing with a Doctrine 1 schema if you have
            // a columns index in the first model array
            return isset($first['columns']);
        } else {
            return false;
        }
    }

    private function _determineSourceType($source)
    {
        // If the --from=<VALUE> is a directory lets determine if it is
        // annotations, yaml, xml, etc.
        if (is_dir($source)) {
            // Find the files in the directory
            $files = glob($source . '/*.*');
            if ( ! $files) {
                throw new \InvalidArgumentException(
                    sprintf('No mapping files found in "%s"', $source)
                );
            }

            // Get the contents of the first file
            $contents = file_get_contents($files[0]);

            // Check if it has a class definition in it for annotations
            if (preg_match("/class (.*)/", $contents)) {
              return 'annotation';
            // Otherwise lets determine the type based on the extension of the 
            // first file in the directory (yml, xml, etc)
            } else {
              $info = pathinfo($files[0]);
              return $info['extension'];
            }
        // Nothing special for database
        } else if ($source == 'database') {
            return 'database';
        }
    }

    private function _getSourceByType($type, $source)
    {
        // If --from==database then the source is an instance of SchemaManager
        // for the current EntityMAnager
        if ($type == 'database') {
            return $this->_em->getConnection()->getSchemaManager();
        } else {
            return $source;
        }
    }
}