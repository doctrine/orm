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
    Doctrine\Common\Cli\Option,
    Doctrine\Common\Cli\OptionGroup,
    Doctrine\Common\Cli\CliException,
    Doctrine\ORM\Tools\EntityGenerator,
    Doctrine\ORM\Tools\Export\ClassMetadataExporter;

/**
 * CLI Task to generate entity classes and method stubs from your mapping information.
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
class GenerateEntitiesTask extends ConvertMappingTask
{
    /**
     * @inheritdoc
     */
    public function buildDocumentation()
    {
        $options = new OptionGroup(OptionGroup::CARDINALITY_N_N, array(
            new Option('from', '<FROM>', 'The path to mapping information.'),
            new Option('dest', '<DEST>', 'The path to your entities.')
        ));

        $doc = $this->getDocumentation();
        $doc->setName('generate-entities')
            ->setDescription('Generate entity classes and method stubs from your mapping information.')
            ->getOptionGroup()
                ->addOption($options);
    }

    /**
     * @inheritdoc
     */
    public function validate()
    {
        $arguments = $this->getArguments();
        $em = $this->getConfiguration()->getAttribute('em');

        if ( ! isset($arguments['from']) || ! isset($arguments['dest'])) {
            throw new CliException('You must specify a value for --from and --dest');
        }

        return true;
    }

    public function run()
    {
        $printer = $this->getPrinter();
        $arguments = $this->getArguments();
        $from = $arguments['from'];
        $dest = realpath($arguments['dest']);

        $generator = new EntityGenerator();
        $generator->setGenerateAnnotations(false);
        $generator->setGenerateStubMethods(true);
        $generator->setRegenerateEntityIfExists(false);
        $generator->setUpdateEntityIfExists(true);

        if (isset($arguments['extend']) && $arguments['extend']) {
            $generator->setClassToExtend($arguments['extend']);
        }
        
        if (isset($arguments['num-spaces']) && $arguments['extend']) {
            $generator->setNumSpaces($arguments['num-spaces']);
        }

        $type = $this->_determineSourceType($from);

        if ( ! $type) {
            throw new CliException(
                "Invalid mapping source type '$sourceArg'."
            );
        }

        $source = $this->_getSourceByType($type, $from);

        $cme = new ClassMetadataExporter();
        $cme->addMappingSource($source, $type);
        $metadatas = $cme->getMetadatasForMappingSources();

        $printer->writeln(
            sprintf(
                'Generating entity stubs for "%s" mapping information located at "%s" to "%s"', 
                $printer->format($type, 'KEYWORD'),
                $printer->format($from, 'KEYWORD'),
                $printer->format($dest, 'KEYWORD')
            )
        );

        $generator->generate($metadatas, $dest);
    }
}