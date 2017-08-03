<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Doctrine\ORM\Tools\Console\MetadataFilter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Command to (re)generate the proxy classes used by doctrine.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class GenerateProxiesCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
        ->setName('orm:generate-proxies')
        ->setAliases(['orm:generate:proxies'])
        ->setDescription('Generates proxy classes for entity classes.')
        ->setDefinition(
            [
                new InputOption(
                    'filter', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                    'A string pattern used to match entities that should be processed.'
                ),
                new InputArgument(
                    'dest-path', InputArgument::OPTIONAL,
                    'The path to generate your proxy classes. If none is provided, it will attempt to grab from configuration.'
                ),
            ]
        )
        ->setHelp(<<<EOT
Generates proxy classes for entity classes.
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getHelper('em')->getEntityManager();

        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        $metadatas = MetadataFilter::filter($metadatas, $input->getOption('filter'));

        // Process destination directory
        if (($destPath = $input->getArgument('dest-path')) === null) {
            $destPath = $em->getConfiguration()->getProxyDir();
        }

        if ( ! is_dir($destPath)) {
            mkdir($destPath, 0775, true);
        }

        $destPath = realpath($destPath);

        if ( ! file_exists($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Proxies destination directory '<info>%s</info>' does not exist.", $em->getConfiguration()->getProxyDir())
            );
        }

        if ( ! is_writable($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Proxies destination directory '<info>%s</info>' does not have write permissions.", $destPath)
            );
        }

        if ( count($metadatas)) {
            foreach ($metadatas as $metadata) {
                $output->writeln(
                    sprintf('Processing entity "<info>%s</info>"', $metadata->getClassName())
                );
            }

            // Generating Proxies
            $em->getProxyFactory()->generateProxyClasses($metadatas, $destPath);

            // Outputting information message
            $output->writeln(PHP_EOL . sprintf('Proxy classes generated to "<info>%s</INFO>"', $destPath));
        } else {
            $output->writeln('No Metadata Classes to process.');
        }
    }
}
