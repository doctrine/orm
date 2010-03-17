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
    Doctrine\ORM\Tools\Export\ClassMetadataExporter,
    Doctrine\Common\Cli\CliException,
    Doctrine\Common\Cli\Option,
    Doctrine\Common\Cli\OptionGroup,
    Doctrine\ORM\Tools\ConvertDoctrine1Schema;

/**
 * CLI Task to convert a Doctrine 1 schema to a Doctrine 2 mapping file
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
class ConvertDoctrine1SchemaTask extends AbstractTask
{
    /**
     * @inheritdoc
     */
    public function buildDocumentation()
    {
        $options = new OptionGroup(OptionGroup::CARDINALITY_N_N, array(
            new Option('from', '<FROM>', 'The path to the Doctrine 1 schema.'),
            new Option('to', '<TO>', 'The Doctrine 2 mapping format to convert to.'),
            new Option('dest', '<DEST>', 'The path to export the converted schema.')
        ));

        $doc = $this->getDocumentation();
        $doc->setName('convert-10-schema')
            ->setDescription('Displays the current installed Doctrine version.')
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

        if ( ! isset($arguments['from']) || ! isset($arguments['to']) || ! isset($arguments['dest'])) {
            throw new CliException('You must specify a value for --from, --to and --dest');
        }

        return true;
    }

    public function run()
    {
        $arguments = $this->getArguments();
        $printer = $this->getPrinter();

        $printer->writeln(sprintf(
                'Converting Doctrine 1 schema at "%s" to the "%s" format',
                $printer->format($arguments['from'], 'KEYWORD'),
                $printer->format($arguments['to'], 'KEYWORD')
            )
        );

        $cme = new ClassMetadataExporter();
        $exporter = $cme->getExporter($arguments['to'], $arguments['dest']);

        $converter = new ConvertDoctrine1Schema($arguments['from']);
        $metadatas = $converter->getMetadatasFromSchema();

        foreach ($metadatas as $metadata) {
            $printer->writeln(
                sprintf('Processing entity "%s"', $printer->format($metadata->name, 'KEYWORD'))
            );
        }

        $exporter->setMetadatas($metadatas);
        $exporter->export();

        $printer->writeln(sprintf(
                'Writing Doctrine 2 mapping files to "%s"',
                $printer->format($arguments['dest'], 'KEYWORD')
            )
        );
    }
}