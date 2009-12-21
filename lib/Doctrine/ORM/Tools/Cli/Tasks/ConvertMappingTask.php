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

use Doctrine\Common\Cli\Tasks\AbstractTask,
    Doctrine\Common\Cli\CliException,
    Doctrine\Common\Cli\Option,
    Doctrine\Common\Cli\OptionGroup,
    Doctrine\ORM\Tools\Export\ClassMetadataExporter;

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
    public function buildDocumentation()
    {
        $convertOptions = new OptionGroup(OptionGroup::CARDINALITY_N_N, array(
            new OptionGroup(OptionGroup::CARDINALITY_1_1, array(
                new Option('from', '<SOURCE>', 'The path to the mapping information to convert from (yml, xml, php, annotation).'),
                new Option('from-database', null, 'Use this option if you wish to reverse engineer your database to a set of Doctrine mapping files.')
            )),
            new Option('to', '<TYPE>', 'The format to convert to (yml, xml, php, annotation).'),
            new Option('dest', '<PATH>', 'The path to write the converted mapping information.')
        ));
        
        $doc = $this->getDocumentation();
        $doc->setName('convert-mapping')
            ->setDescription('Convert mapping information between supported formats.')
            ->getOptionGroup()
                ->addOption($convertOptions);
    }
    
    /**
     * @inheritdoc
     */    
    public function validate()
    {
        $arguments = $this->getArguments();
        
        if (isset($arguments['from-database']) && $arguments['from-database']) {
            $arguments['from'] = 'database';
            
            $this->setArguments($arguments);
        }

        if (!(isset($arguments['from']) && isset($arguments['to']) && isset($arguments['dest']))) {
            throw new CliException(
                'You must include a value for all three options: --from, --to and --dest.'
            );
        }
        
        if (strtolower($arguments['to']) != 'annotation' && isset($arguments['extend'])) {
            throw new CliException(
                'You can only use the --extend argument when converting to annotations.'
            );
        }
        
        if (strtolower($arguments['from']) == 'database') {
            // Check if we have an active EntityManager
            $em = $this->getConfiguration()->getAttribute('em');
        
            if ($em === null) {
                throw new CliException(
                    "Attribute 'em' of CLI Configuration is not defined or it is not a valid EntityManager."
                );
            }
    
            $config = $em->getConfiguration();
            $config->setMetadataDriverImpl(
                new \Doctrine\ORM\Mapping\Driver\DatabaseDriver(
                    $em->getConnection()->getSchemaManager()
                )
            );
        }
        
        return true;
    }

    public function run()
    {
        $arguments = $this->getArguments();
        $cme = new ClassMetadataExporter();
        $printer = $this->getPrinter();
        
        // Get exporter and configure it
        $exporter = $cme->getExporter($arguments['to'], $arguments['dest']);

        if (isset($arguments['extend']) && $arguments['extend']) {
            $exporter->setClassToExtend($arguments['extend']);
        }
        
        if (isset($arguments['num-spaces']) && $arguments['extend']) {
            $exporter->setNumSpaces($arguments['num-spaces']);
        }

        $from = (array) $arguments['from'];

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
                        $printer->format($sourceArg, 'KEYWORD'), $printer->format($type, 'KEYWORD')
                    )
                );

                $cme->addMappingSource($source, $type);
            }
            
            $metadatas = $cme->getMetadatasForMappingSources();
        }

        foreach ($metadatas as $metadata) {
            $printer->writeln(
                sprintf('Processing entity "%s"', $printer->format($metadata->name, 'KEYWORD'))
            );
        }

        $printer->writeln(
            sprintf(
                'Exporting "%s" mapping information to directory "%s"',
                $printer->format($arguments['to'], 'KEYWORD'), 
                $printer->format($arguments['dest'], 'KEYWORD')
            )
        );

        $exporter->setMetadatas($metadatas);
        $exporter->export();
    }

    private function _isDoctrine1Schema(array $from)
    {
        if ( ! class_exists('sfYaml', false)) {
            require_once __DIR__ . '/../../../../../vendor/sfYaml/sfYaml.class.php';
            require_once __DIR__ . '/../../../../../vendor/sfYaml/sfYamlDumper.class.php';
            require_once __DIR__ . '/../../../../../vendor/sfYaml/sfYamlInline.class.php';
            require_once __DIR__ . '/../../../../../vendor/sfYaml/sfYamlParser.class.php';
        }
        
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
            $em = $this->getConfiguration->getAttribute('em');
            
            return $em->getConnection()->getSchemaManager();
        } else {
            return $source;
        }
    }
}