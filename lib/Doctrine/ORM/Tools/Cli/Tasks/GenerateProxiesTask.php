<?php

namespace Doctrine\ORM\Tools\Cli\Tasks;

use Doctrine\Common\Cli\Tasks\AbstractTask,
    Doctrine\Common\Cli\CliException,
    Doctrine\Common\Cli\Option,
    Doctrine\Common\Cli\OptionGroup;

/**
 * Task to (re)generate the proxy classes used by doctrine.
 * 
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision: 3938 $
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class GenerateProxiesTask extends AbstractTask
{
    /**
     * @inheritdoc
     */
    public function buildDocumentation()
    {
        $classDir = new OptionGroup(OptionGroup::CARDINALITY_1_1, array(
            new Option('class-dir', '<PATH>', 'Specified directory where mapping classes are located.')
        ));
        
        $toDir = new OptionGroup(OptionGroup::CARDINALITY_0_1, array(
            new Option('to-dir', '<PATH>', 'Generates the classes in the specified directory.')
        ));
        
        $doc = $this->getDocumentation();
        $doc->setName('generate-proxies')
            ->setDescription('Generates proxy classes for entity classes.')
            ->getOptionGroup()
                ->addOption($classDir)
                ->addOption($toDir);
    }
    
    /**
     * @inheritdoc
     */
    public function validate()
    {
        $arguments = $this->getArguments();
        $em = $this->getConfiguration()->getAttribute('em');
        
        if ($em === null) {
            throw new CliException(
                "Attribute 'em' of CLI Configuration is not defined or it is not a valid EntityManager."
            );
        }
        
        $metadataDriver = $em->getConfiguration()->getMetadataDriverImpl();
        
        if ($metadataDriver instanceof \Doctrine\ORM\Mapping\Driver\AnnotationDriver) {
            if (isset($arguments['class-dir'])) {
                $metadataDriver->addPaths((array) $arguments['class-dir']);
            } else {
                throw new CliException(
                    'The supplied configuration uses the annotation metadata driver. ' .
                    "The 'class-dir' argument is required for this driver."
                );
            }
        }
        
        return true;
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $arguments = $this->getArguments();
        $printer = $this->getPrinter();
        
        $em = $this->getConfiguration()->getAttribute('em');
        $cmf = $em->getMetadataFactory();
        $classes = $cmf->getAllMetadata();
        $factory = $em->getProxyFactory();
        
        if (empty($classes)) {
            $printer->writeln('No classes to process.', 'INFO');
        } else {
            foreach ($classes as $class) {
                $printer->writeln(
                    sprintf('Processing entity "%s"', $printer->format($class->name, 'KEYWORD'))
                );
            }

            $factory->generateProxyClasses(
                $classes, isset($arguments['to-dir']) ? $arguments['to-dir'] : null
            );

            $printer->writeln('');

            $printer->writeln(
                sprintf('Proxy classes generated to "%s"',
                $printer->format(isset($arguments['to-dir']) ? $arguments['to-dir'] : $em->getConfiguration()->getProxyDir(), 'KEYWORD'))
            );
        }
    }
}