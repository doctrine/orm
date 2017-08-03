<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\Command;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\ColumnMetadata;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\TableMetadata;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Show information about mapped entities.
 *
 * @link    www.doctrine-project.org
 * @since   2.4
 * @author  Daniel Leech <daniel@dantleech.com>
 */
final class MappingDescribeCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('orm:mapping:describe')
             ->addArgument('entityName', InputArgument::REQUIRED, 'Full or partial name of entity')
             ->setDescription('Display information about mapped objects')
             ->setHelp(<<<EOT
The %command.full_name% command describes the metadata for the given full or partial entity class name.

    <info>%command.full_name%</info> My\Namespace\Entity\MyEntity

Or:

    <info>%command.full_name%</info> MyEntity
EOT
             );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ui = new SymfonyStyle($input, $output);

        /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
        $entityManager = $this->getHelper('em')->getEntityManager();

        $this->displayEntity($input->getArgument('entityName'), $entityManager, $ui);

        return 0;
    }

    /**
     * Display all the mapping information for a single Entity.
     *
     * @param string                 $entityName    Full or partial entity class name
     * @param EntityManagerInterface $entityManager
     * @param SymfonyStyle           $ui
     */
    private function displayEntity($entityName, EntityManagerInterface $entityManager, SymfonyStyle $ui)
    {
        $metadata = $this->getClassMetadata($entityName, $entityManager);

        $ui->table(
            ['Field', 'Value'],
            array_merge(
                [
                    $this->formatField('Name', $metadata->getClassName()),
                    $this->formatField('Root entity name', $metadata->getRootClassName()),
                    $this->formatField('Custom repository class', $metadata->getCustomRepositoryClassName()),
                    $this->formatField('Mapped super class?', $metadata->isMappedSuperclass),
                    $this->formatField('Embedded class?', $metadata->isEmbeddedClass),
                    $this->formatField('Parent classes', $metadata->getParentClasses()),
                    $this->formatField('Sub classes', $metadata->getSubClasses()),
                    $this->formatField('Embedded classes', $metadata->getSubClasses()),
                    $this->formatField('Named queries', $metadata->getNamedQueries()),
                    $this->formatField('Named native queries', $metadata->getNamedNativeQueries()),
                    $this->formatField('SQL result set mappings', $metadata->getSqlResultSetMappings()),
                    $this->formatField('Identifier', $metadata->getIdentifier()),
                    $this->formatField('Inheritance type', $metadata->inheritanceType),
                    $this->formatField('Discriminator column', '')
                ],
                $this->formatColumn($metadata->discriminatorColumn),
                [
                    $this->formatField('Discriminator value', $metadata->discriminatorValue),
                    $this->formatField('Discriminator map', $metadata->discriminatorMap),
                    $this->formatField('Table', ''),
                ],
                $this->formatTable($metadata->table),
                [
                    $this->formatField('Composite identifier?', $metadata->isIdentifierComposite()),
                    $this->formatField('Change tracking policy', $metadata->changeTrackingPolicy),
                    $this->formatField('Versioned?', $metadata->isVersioned()),
                    $this->formatField('Version field', ($metadata->isVersioned() ? $metadata->versionProperty->getName() : '')),
                    $this->formatField('Read only?', $metadata->isReadOnly()),

                    $this->formatEntityListeners($metadata->entityListeners),
                ],
                [$this->formatField('Property mappings:', '')],
                $this->formatPropertyMappings($metadata->getProperties())
            )
        );
    }

    /**
     * Return all mapped entity class names
     *
     * @param EntityManagerInterface $entityManager
     *
     * @return string[]
     */
    private function getMappedEntities(EntityManagerInterface $entityManager)
    {
        $entityClassNames = $entityManager->getConfiguration()
                                          ->getMetadataDriverImpl()
                                          ->getAllClassNames();

        if ( ! $entityClassNames) {
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
     * @param string                 $entityName    Full or partial entity name
     * @param EntityManagerInterface $entityManager
     *
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    private function getClassMetadata($entityName, EntityManagerInterface $entityManager)
    {
        try {
            return $entityManager->getClassMetadata($entityName);
        } catch (MappingException $e) {
        }

        $matches = array_filter(
            $this->getMappedEntities($entityManager),
            function ($mappedEntity) use ($entityName) {
                return preg_match('{' . preg_quote($entityName) . '}', $mappedEntity);
            }
        );

        if ( ! $matches) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find any mapped Entity classes matching "%s"',
                $entityName
            ));
        }

        if (count($matches) > 1) {
            throw new \InvalidArgumentException(sprintf(
                'Entity name "%s" is ambiguous, possible matches: "%s"',
                $entityName, implode(', ', $matches)
            ));
        }

        return $entityManager->getClassMetadata(current($matches));
    }

    /**
     * Format the given value for console output
     *
     * @param mixed $value
     *
     * @return string
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
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
     * Add the given label and value to the two column table output
     *
     * @param string $label Label for the value
     * @param mixed  $value A Value to show
     *
     * @return array
     */
    private function formatField($label, $value)
    {
        if (null === $value) {
            $value = '<comment>None</comment>';
        }

        return [sprintf('<info>%s</info>', $label), $this->formatValue($value)];
    }

    /**
     * Format the association mappings
     *
     * @param array $propertyMappings
     *
     * @return array
     */
    private function formatAssociationMappings(array $propertyMappings)
    {
        $output = [];

        foreach ($propertyMappings as $propertyName => $mapping) {
            $output[] = $this->formatField(sprintf('  %s', $propertyName), '');

            foreach ($mapping as $field => $value) {
                $output[] = $this->formatField(sprintf('    %s', $field), $this->formatValue($value));
            }
        }

        return $output;
    }


    /**
     * Format the property mappings
     *
     * @param array $propertyMappings
     *
     * @return array
     */
    private function formatPropertyMappings(array $propertyMappings)
    {
        $output = [];

        foreach ($propertyMappings as $propertyName => $property) {
            $output[] = $this->formatField(sprintf('  %s', $propertyName), '');

            if ($property instanceof FieldMetadata) {
                $output = array_merge($output, $this->formatColumn($property));
            }  else if ($property instanceof AssociationMetadata) {
                // @todo guilhermeblanco Fix me! We are trying to iterate through an AssociationMetadata instance
                foreach ($property as $field => $value) {
                    $output[] = $this->formatField(sprintf('    %s', $field), $this->formatValue($value));
                }

            }
        }

        return $output;
    }

    /**
     * @param ColumnMetadata|null $columnMetadata
     *
     * @return array|string
     */
    private function formatColumn(ColumnMetadata $columnMetadata = null)
    {
        $output = [];

        if (null === $columnMetadata) {
            $output[] = '<comment>Null</comment>';

            return $output;
        }

        $output[] = $this->formatField('    type', $this->formatValue($columnMetadata->getTypeName()));
        $output[] = $this->formatField('    tableName', $this->formatValue($columnMetadata->getTableName()));
        $output[] = $this->formatField('    columnName', $this->formatValue($columnMetadata->getColumnName()));
        $output[] = $this->formatField('    columnDefinition', $this->formatValue($columnMetadata->getColumnDefinition()));
        $output[] = $this->formatField('    isPrimaryKey', $this->formatValue($columnMetadata->isPrimaryKey()));
        $output[] = $this->formatField('    isNullable', $this->formatValue($columnMetadata->isNullable()));
        $output[] = $this->formatField('    isUnique', $this->formatValue($columnMetadata->isUnique()));
        $output[] = $this->formatField('    options', $this->formatValue($columnMetadata->getOptions()));

        if ($columnMetadata instanceof FieldMetadata) {
            $output[] = $this->formatField('    Generator type', $this->formatValue($columnMetadata->getValueGenerator()->getType()));
            $output[] = $this->formatField('    Generator definition', $this->formatValue($columnMetadata->getValueGenerator()->getDefinition()));
        }

        return $output;
    }

    /**
     * Format the entity listeners
     *
     * @param array $entityListeners
     *
     * @return array
     */
    private function formatEntityListeners(array $entityListeners)
    {
        return $this->formatField('Entity listeners', array_map('get_class', $entityListeners));
    }

    /**
     * @param TableMetadata|null $tableMetadata
     *
     * @return array|string
     */
    private function formatTable(TableMetadata $tableMetadata = null)
    {
        $output = [];

        if (null === $tableMetadata) {
            $output[] = '<comment>Null</comment>';

            return $output;
        }

        $output[] = $this->formatField('    schema', $this->formatValue($tableMetadata->getSchema()));
        $output[] = $this->formatField('    name', $this->formatValue($tableMetadata->getName()));
        $output[] = $this->formatField('    indexes', $this->formatValue($tableMetadata->getIndexes()));
        $output[] = $this->formatField('    uniqueConstaints', $this->formatValue($tableMetadata->getUniqueConstraints()));
        $output[] = $this->formatField('    options', $this->formatValue($tableMetadata->getOptions()));

        return $output;
    }
}
