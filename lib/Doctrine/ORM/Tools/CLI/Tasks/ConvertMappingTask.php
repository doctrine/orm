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
 
namespace Doctrine\ORM\Tools\CLI\Tasks;

use Doctrine\Common\CLI\Tasks\AbstractTask,
    Doctrine\Common\CLI\CliException,
    Doctrine\Common\CLI\Option,
    Doctrine\Common\CLI\OptionGroup,
    Doctrine\ORM\Tools\Export\ClassMetadataExporter,
    Doctrine\ORM\Mapping\Driver\DriverChain,
    Doctrine\ORM\Mapping\Driver\AnnotationDriver,
    Doctrine\ORM\Mapping\Driver\Driver;

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
            throw new CLIException(
                'You must include a value for all three options: --from, --to and --dest.'
            );
        }
        
        if (strtolower($arguments['to']) != 'annotation' && isset($arguments['extend'])) {
            throw new CLIException(
                'You can only use the --extend argument when converting to annotations.'
            );
        }
        
        if (strtolower($arguments['from']) == 'database') {
            // Check if we have an active EntityManager
            $em = $this->getConfiguration()->getAttribute('em');
        
            if ($em === null) {
                throw new CLIException(
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
        $cme->setEntityManager($this->getConfiguration()->getAttribute('em'));
        $printer = $this->getPrinter();
        
        // Get exporter and configure it
        $exporter = $cme->getExporter($arguments['to'], $arguments['dest']);

        if ($arguments['to'] === 'annotation') {
            $entityGenerator = new EntityGenerator();
            $exporter->setEntityGenerator($entityGenerator);

            if (isset($arguments['extend']) && $arguments['extend']) {
                $entityGenerator->setClassToExtend($arguments['extend']);
            }

            if (isset($arguments['num-spaces']) && $arguments['extend']) {
                $entityGenerator->setNumSpaces($arguments['num-spaces']);
            }
        }

        $from = (array) $arguments['from'];

        foreach ($from as $source) {
            $cme->addMappingSource($source);
        }

        $metadatas = $cme->getMetadatas();

        foreach ($metadatas as $metadata) {
            $printer->writeln(
                sprintf('Processing entity "%s"', $printer->format($metadata->name, 'KEYWORD'))
            );
        }

        $printer->writeln('');
        $printer->writeln(
            sprintf(
                'Exporting "%s" mapping information to "%s"',
                $printer->format($arguments['to'], 'KEYWORD'), 
                $printer->format($arguments['dest'], 'KEYWORD')
            )
        );

        $exporter->setMetadatas($metadatas);
        $exporter->export();
    }
}