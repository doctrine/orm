<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\Command;

use Doctrine\ORM\Tools\ConvertDoctrine1Schema;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\Export\ClassMetadataExporter;
use Doctrine\ORM\Tools\Export\Driver\AnnotationExporter;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_merge;
use function file_exists;
use function is_readable;
use function is_writable;
use function realpath;
use function sprintf;

use const PHP_EOL;

/**
 * Command to convert a Doctrine 1 schema to a Doctrine 2 mapping file.
 *
 * @deprecated 2.7 This class is being removed from the ORM and won't have any replacement
 *
 * @link    www.doctrine-project.org
 */
class ConvertDoctrine1SchemaCommand extends Command
{
    /** @var EntityGenerator|null */
    private $entityGenerator = null;

    /** @var ClassMetadataExporter|null */
    private $metadataExporter = null;

    /**
     * @return EntityGenerator
     */
    public function getEntityGenerator()
    {
        if ($this->entityGenerator === null) {
            $this->entityGenerator = new EntityGenerator();
        }

        return $this->entityGenerator;
    }

    /**
     * @return void
     */
    public function setEntityGenerator(EntityGenerator $entityGenerator)
    {
        $this->entityGenerator = $entityGenerator;
    }

    /**
     * @return ClassMetadataExporter
     */
    public function getMetadataExporter()
    {
        if ($this->metadataExporter === null) {
            $this->metadataExporter = new ClassMetadataExporter();
        }

        return $this->metadataExporter;
    }

    /**
     * @return void
     */
    public function setMetadataExporter(ClassMetadataExporter $metadataExporter)
    {
        $this->metadataExporter = $metadataExporter;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('orm:convert-d1-schema')
             ->setAliases(['orm:convert:d1-schema'])
             ->setDescription('Converts Doctrine 1.x schema into a Doctrine 2.x schema')
             ->addArgument('from-path', InputArgument::REQUIRED, 'The path of Doctrine 1.X schema information.')
             ->addArgument('to-type', InputArgument::REQUIRED, 'The destination Doctrine 2.X mapping type.')
             ->addArgument('dest-path', InputArgument::REQUIRED, 'The path to generate your Doctrine 2.X mapping information.')
             ->addOption('from', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Optional paths of Doctrine 1.X schema information.', [])
             ->addOption('extend', null, InputOption::VALUE_OPTIONAL, 'Defines a base class to be extended by generated entity classes.')
             ->addOption('num-spaces', null, InputOption::VALUE_OPTIONAL, 'Defines the number of indentation spaces', 4)
             ->setHelp('Converts Doctrine 1.x schema into a Doctrine 2.x schema.');
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ui = new SymfonyStyle($input, $output);
        $ui->warning('Command ' . $this->getName() . ' is deprecated and will be removed in Doctrine ORM 3.0.');

        // Process source directories
        $fromPaths = array_merge([$input->getArgument('from-path')], $input->getOption('from'));

        // Process destination directory
        $destPath = realpath($input->getArgument('dest-path'));

        $toType    = $input->getArgument('to-type');
        $extend    = $input->getOption('extend');
        $numSpaces = (int) $input->getOption('num-spaces');

        $this->convertDoctrine1Schema($fromPaths, $destPath, $toType, $numSpaces, $extend, $output);

        return 0;
    }

    /**
     * @param mixed[]     $fromPaths
     * @param string      $destPath
     * @param string      $toType
     * @param int         $numSpaces
     * @param string|null $extend
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function convertDoctrine1Schema(array $fromPaths, $destPath, $toType, $numSpaces, $extend, OutputInterface $output)
    {
        foreach ($fromPaths as &$dirName) {
            $dirName = realpath($dirName);

            if (! file_exists($dirName)) {
                throw new InvalidArgumentException(
                    sprintf("Doctrine 1.X schema directory '<info>%s</info>' does not exist.", $dirName)
                );
            }

            if (! is_readable($dirName)) {
                throw new InvalidArgumentException(
                    sprintf("Doctrine 1.X schema directory '<info>%s</info>' does not have read permissions.", $dirName)
                );
            }
        }

        if (! file_exists($destPath)) {
            throw new InvalidArgumentException(
                sprintf("Doctrine 2.X mapping destination directory '<info>%s</info>' does not exist.", $destPath)
            );
        }

        if (! is_writable($destPath)) {
            throw new InvalidArgumentException(
                sprintf("Doctrine 2.X mapping destination directory '<info>%s</info>' does not have write permissions.", $destPath)
            );
        }

        $cme      = $this->getMetadataExporter();
        $exporter = $cme->getExporter($toType, $destPath);

        if ($exporter instanceof AnnotationExporter) {
            $entityGenerator = $this->getEntityGenerator();
            $exporter->setEntityGenerator($entityGenerator);

            $entityGenerator->setNumSpaces($numSpaces);

            if ($extend !== null) {
                $entityGenerator->setClassToExtend($extend);
            }
        }

        $converter = new ConvertDoctrine1Schema($fromPaths);
        $metadata  = $converter->getMetadata();

        if ($metadata) {
            $output->writeln('');

            foreach ($metadata as $class) {
                $output->writeln(sprintf('Processing entity "<info>%s</info>"', $class->name));
            }

            $exporter->setMetadata($metadata);
            $exporter->export();

            $output->writeln(PHP_EOL . sprintf(
                'Converting Doctrine 1.X schema to "<info>%s</info>" mapping type in "<info>%s</info>"',
                $toType,
                $destPath
            ));
        } else {
            $output->writeln('No Metadata Classes to process.');
        }
    }
}
