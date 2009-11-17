<?php

namespace Doctrine\ORM\Tools\Cli\Tasks;

use Doctrine\Common\DoctrineException,
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
        $toDir = new OptionGroup(OptionGroup::CARDINALITY_0_1, array(
            new Option('to-dir', '<PATH>', 'Generates the classes in the specified directory.')
        ));
        
        $doc = $this->getDocumentation();
        $doc->setName('generate-proxies')
            ->setDescription('Generates proxy classes for entity classes.')
            ->getOptionGroup()
                ->addOption($toDir);
    }
    
    /**
     * @inheritdoc
     */
    public function validate()
    {
        $args = $this->getArguments();
        $printer = $this->getPrinter();
        
        $metadataDriver = $this->getEntityManager()->getConfiguration()->getMetadataDriverImpl();
        
        if ($metadataDriver instanceof \Doctrine\ORM\Mapping\Driver\AnnotationDriver) {
            if ( ! isset($args['class-dir'])) {
                $printer->writeln("The supplied configuration uses the annotation metadata driver."
                        . " The 'class-dir' argument is required for this driver.", 'ERROR');
                return false;
            } else {
                $metadataDriver->setClassDirectory($args['class-dir']);
            }
        }
        
        return true;
    }

    /**
     * Executes the task.
     */
    public function run()
    {
        $args = $this->getArguments();

        $em = $this->getEntityManager();
        $cmf = $em->getMetadataFactory();
        $driver = $em->getConfiguration()->getMetadataDriverImpl();
        
        $classes = array();
        $preloadedClasses = $driver->preload(true);
        
        foreach ($preloadedClasses as $className) {
            $classes[] = $cmf->getMetadataFor($className);
        }

        $printer = $this->getPrinter();
        $factory = $em->getProxyFactory();
        
        if (empty($classes)) {
            $printer->writeln('No classes to process.', 'INFO');
            return;
        }

        $factory->generateProxyClasses($classes, isset($args['to-dir']) ? $args['to-dir'] : null);
        
        $printer->writeln(
            'Proxy classes generated to: ' . 
            (isset($args['to-dir']) ? $args['to-dir'] : $em->getConfiguration()->getProxyDir())
        );
    }
}