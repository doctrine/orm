<?php
/*
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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools\Console\Command;

use Doctrine\ORM\Mapping\MappingException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Helper\TableHelper;

/**
 * Show information about mapped entities.
 *
 * @link    www.doctrine-project.org
 * @since   2.1
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Daniel Leech <daniel@dantleech.com>
 */
class InfoCommand extends Command
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var array
     */
    private $out;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('orm:info')
            ->addArgument('entityName', InputArgument::OPTIONAL, 'Show detailed information about the given class')
            ->setDescription('Display information about mapped objects')
            ->setHelp(<<<EOT
The <info>%command.name%</info> without arguments shows basic information about
which entities exist and possibly if their mapping information contains errors
or not.

You can display the complete metadata for a given entity by specifying it, e.g.

    <info>%command.full_name%</info> My\Namespace\Entity\MyEntity

You can also specify a partial class name (as a regex):

    <info>%command.full_name%</info> MyEntity
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityName = $input->getArgument('entityName');

        /* @var $entityManager \Doctrine\ORM\EntityManager */
        $entityManager = $this->getHelper('em')->getEntityManager();

        $this->output = $output;
        $this->entityManager = $entityManager;

        if (null === $entityName) {
            return $this->displayAll($output);
        }

        $this->displayEntity($entityName);

        return 0;
    }

    /**
     * List all the mapped classes
     *
     * Returns the exit code, which will be 1 if there are any mapping exceptions
     * encountered when listing the mapped classes.
     *
     * @return integer
     */
    private function displayAll()
    {
        $entityClassNames = $this->getMappedEntities();

        $this->output->writeln(sprintf("Found <info>%d</info> mapped entities:", count($entityClassNames)));

        $failure = false;

        foreach ($entityClassNames as $entityClassName) {
            try {
                $meta = $this->entityManager->getClassMetadata($entityClassName);
                $this->output->writeln(sprintf("<info>[OK]</info>   %s", $entityClassName));
            } catch (MappingException $e) {
                $this->output->writeln("<error>[FAIL]</error> ".$entityClassName);
                $this->output->writeln(sprintf("<comment>%s</comment>", $e->getMessage()));
                $this->output->writeln('');

                $failure = true;
            }
        }

        return $failure ? 1 : 0;
    }

    /**
     * Display all the mapping information for a single Entity.
     *
     * @param string $entityName Full or partial entity class name
     */
    private function displayEntity($entityName)
    {
        $meta = $this->getClassMetadata($entityName);

        $this->formatField('Name', $meta->name);
        $this->formatField('Root entity name', $meta->rootEntityName);
        $this->formatField('Custom generator definition', $meta->customGeneratorDefinition);
        $this->formatField('Custom repository class', $meta->customRepositoryClassName);
        $this->formatField('Mapped super class?', $meta->isMappedSuperclass);
        $this->formatField('Embedded class?', $meta->isEmbeddedClass);
        $this->formatField('Parent classes', $meta->parentClasses);
        $this->formatField('Sub classes', $meta->subClasses);
        $this->formatField('Embedded classes', $meta->subClasses);
        $this->formatField('Named queries', $meta->namedQueries);
        $this->formatField('Named native queries', $meta->namedNativeQueries);
        $this->formatField('SQL result set mappings', $meta->sqlResultSetMappings);
        $this->formatField('Identifier', $meta->identifier);
        $this->formatField('Inheritance type', $meta->inheritanceType);
        $this->formatField('Discriminator column', $meta->discriminatorColumn);
        $this->formatField('Discriminator value', $meta->discriminatorValue);
        $this->formatField('Discriminator map', $meta->discriminatorMap);
        $this->formatField('Generator type', $meta->generatorType);
        $this->formatField('Table', $meta->table);
        $this->formatField('Composite identifier?', $meta->isIdentifierComposite);
        $this->formatField('Foreign identifier?', $meta->containsForeignIdentifier);
        $this->formatField('Sequence generator definition', $meta->sequenceGeneratorDefinition);
        $this->formatField('Table generator definition', $meta->tableGeneratorDefinition);
        $this->formatField('Change tracking policy', $meta->changeTrackingPolicy);
        $this->formatField('Versioned?', $meta->isVersioned);
        $this->formatField('Version field', $meta->versionField);
        $this->formatField('Read only?', $meta->isReadOnly);
        $this->formatField('Foo', array('Foo', 'Bar', 'Boo'));

        $this->formatEntityListeners($meta->entityListeners);
        $this->formatAssociationMappings($meta->associationMappings);
        $this->formatFieldMappings($meta->fieldMappings);

        if (class_exists('Symfony\Component\Console\Helper\TableHelper')) {
            $table = new TableHelper();
            $table->setHeaders(array('Field', 'Value'));
            foreach ($this->out as $tuple) {
                $table->addRow($tuple);
            }
            $table->render($this->output);
        } else {
            foreach ($this->out as $tuple) {
                list($label, $value) = $tuple;
                $this->output->writeln(sprintf('<info>%s</info>: %s', $label, $value));
            }
        }
    }

    /**
     * Return all mapped entity class names
     *
     * @return array
     */
    private function getMappedEntities()
    {
        $entityClassNames = $this->entityManager->getConfiguration()
                                          ->getMetadataDriverImpl()
                                          ->getAllClassNames();

        if (!$entityClassNames) {
            throw new \InvalidArgumentException(
                'You do not have any mapped Doctrine ORM entities according to the current configuration. '.
                'If you have entities or mapping files you should check your mapping configuration for errors.'
            );
        }

        return $entityClassNames;
    }

    /**
     * Return the class metadata for the given entity
     * name
     *
     * @param string $entityName Full or partial entity name
     */
    private function getClassMetadata($entityName)
    {
        try {
            $meta = $this->entityManager->getClassMetadata($entityName);
        } catch (\Doctrine\Common\Persistence\Mapping\MappingException $e) {
            $mappedEntities = $this->getMappedEntities();
            $matches = array_filter($mappedEntities, function ($mappedEntity) use ($entityName) {
                if (preg_match('{' . preg_quote($entityName) . '}', $mappedEntity)) {
                    return true;
                }

                return false;
            });

            if (0 === count($matches)) {
                throw new \InvalidArgumentException(sprintf(
                    'Could not find any mapped Entity classes matching "%s"',
                    $entityName
                ));
            }

            if (1 === count($matches)) {
                $meta = $this->entityManager->getClassMetadata(current($matches));
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'Entity name "%s" is ambigous, possible matches: "%s"',
                    $entityName, implode(', ', $matches)
                ));
            }
        }

        return $meta;
    }

    /**
     * Format the given value for console output
     *
     * @param mixed $value
     */
    private function formatValue($value)
    {
        if ('' === $value) {
            return '';
        }

        if (null === $value) {
            return '<comment>Null</comment>';
        }

        if (is_bool($value)) {
            return '<comment>' . ($value ? 'True' : 'False') . '</comment>';
        }

        if (empty($value)) {
            return '<comment>Empty</comment>';
        }

        if (is_array($value)) {
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                return json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            }

            return json_encode($value);
        }

        if (is_object($value)) {
            return sprintf('<%s>', get_class($value));
        }

        if (is_scalar($value)) {
            return $value;
        }

        throw new \InvalidArgumentException(sprintf('Do not know how to format value "%s"', print_r($value, true)));
    }

    /**
     * Add the given label and value to the two column table
     * output
     *
     * @param string $label Label for the value
     * @param mixed $valueA Value to show
     */
    private function formatField($label, $value)
    {
        if (null === $value) {
            $value = '<comment>None</comment>';
        }

        $this->out[] = array(sprintf('<info>%s</info>', $label), $this->formatValue($value));
    }

    /**
     * Format the association mappings
     *
     * @param array
     */
    private function formatAssociationMappings($associationMappings)
    {
        $this->formatField('Association mappings:', '');
        foreach ($associationMappings as $associationName => $mapping) {
            $this->formatField(sprintf('  %s',$associationName), '');
            foreach ($mapping as $field => $value) {
                $this->formatField(sprintf('    %s', $field), $this->formatValue($value));
            }
        }
    }

    /**
     * Format the entity listeners
     *
     * @param array $entityListeners
     */
    private function formatEntityListeners($entityListeners)
    {
        $entityListenerNames = array();
        foreach ($entityListeners as $entityListener) {
            $entityListenerNames[] = get_class($entityListener);
        }

        $this->formatField('Entity listeners', $entityListenerNames);
    }

    /**
     * Form the field mappings
     *
     * @param array $fieldMappings
     */
    private function formatFieldMappings($fieldMappings)
    {
        $this->formatField('Field mappings:', '');
        foreach ($fieldMappings as $fieldName => $mapping) {
            $this->formatField(sprintf('  %s',$fieldName), '');
            foreach ($mapping as $field => $value) {
                $this->formatField(sprintf('    %s', $field), $this->formatValue($value));
            }
        }
    }
}
