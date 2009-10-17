<?php

namespace Doctrine\ORM\Tools\Cli\Tasks;

use Doctrine\Common\DoctrineException,
    Doctrine\ORM\Tools\SchemaTool,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\ORM\Mapping\Driver\AnnotationDriver,
    Doctrine\ORM\Mapping\Driver\XmlDriver,
    Doctrine\ORM\Mapping\Driver\YamlDriver;

/**
 * Task to create the database schema for a set of classes based on their mappings.
 * 
 * This task has the following arguments:
 * 
 * <tt>--class-dir=<path></tt>
 * Specifies the directory where to start looking for mapped classes.
 * This argument is required when the annotation metadata driver is used,
 * otherwise it has no effect.
 * 
 * <tt>--dump-sql</tt>
 * Specifies that instead of directly executing the SQL statements,
 * they should be printed to the standard output.
 * 
 * <tt>--create</tt>
 * Specifies that the schema of the classes should be created.
 * 
 * <tt>--drop</tt>
 * Specifies that the schema of the classes should be dropped.
 * 
 * <tt>--update</tt>
 * Specifies that the schema of the classes should be updated.
 * 
 * 
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision: 3938 $
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class SchemaToolTask extends AbstractTask
{
    /**
     * @inheritdoc
     */
    public function extendedHelp()
    {
        $printer = $this->getPrinter();
        
        $printer->write('Task: ')->writeln('schema-tool', 'KEYWORD')
                ->write('Synopsis: ');
        $this->_writeSynopsis($printer);
        
        $printer->writeln('Description: Processes the schema and either apply it directly on EntityManager or generate the SQL output.')
                ->writeln('Options:')
                ->write('--create', 'REQ_ARG')
                ->writeln("\t\tCreates the schema in EntityManager (create tables on Database)")
                ->writeln("\t\t\tIf defined, --drop and --update can not be requested on same task")
                ->write(PHP_EOL)
                ->write('--drop', 'REQ_ARG')
                ->writeln("\t\t\tDrops the schema of EntityManager (drop tables on Database)")
                ->writeln("\t\t\tIf defined, --create and --update can not be requested on same task")
                ->write(PHP_EOL)
                ->write('--update', 'REQ_ARG')
                ->writeln("\t\tUpdates the schema in EntityManager (update tables on Database)")
                ->writeln("\t\t\tIf defined, --create and --drop can not be requested on same task")
                ->write(PHP_EOL)
                ->write('--re-create', 'REQ_ARG')
                ->writeln("\t\tRuns --drop then --create to re-create the database.")
                ->write(PHP_EOL)
                ->write('--dump-sql', 'OPT_ARG')
                ->writeln("\t\tInstead of try to apply generated SQLs into EntityManager, output them.")
                ->write(PHP_EOL)
                ->write('--class-dir=<path>', 'OPT_ARG')
                ->writeln("\tOptional class directory to fetch for Entities.")
                ->write(PHP_EOL);
    }

    /**
     * @inheritdoc
     */
    public function basicHelp()
    {
        $this->_writeSynopsis($this->getPrinter());
    }
    
    private function _writeSynopsis($printer)
    {
        $printer->write('schema-tool', 'KEYWORD')
                ->write(' (--create | --drop | --update | --re-create)', 'REQ_ARG')
                ->writeln(' [--dump-sql] [--class-dir=<path>]', 'OPT_ARG');
    }
    
    /**
     * @inheritdoc
     */
    public function validate()
    {
        if ( ! parent::validate()) {
            return false;
        }
        
        $args = $this->getArguments();
        $printer = $this->getPrinter();
        
        if ( ! $this->_requireEntityManager()) {
            return false;
        }

        if (array_key_exists('re-create', $args)) {
            $args['drop'] = true;
            $args['create'] = true;
            $this->setArguments($args);
        }
        
        $isCreate = isset($args['create']);
        $isDrop = isset($args['drop']);
        $isUpdate = isset($args['update']);
        
        if ($isUpdate && ($isCreate || $isDrop)) {
            $printer->writeln("You can't use --update with --create or --drop", 'ERROR');
            return false;
        }

        if ( ! ($isCreate || $isDrop || $isUpdate)) {
            $printer->writeln('You must specify at a minimum one of the options (--create, --drop, --update, --re-create).', 'ERROR');
            return false;
        }
        
        $metadataDriver = $this->_em->getConfiguration()->getMetadataDriverImpl();
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

        $isCreate = isset($args['create']);
        $isDrop = isset($args['drop']);
        $isUpdate = isset($args['update']);

        $cmf = $this->_em->getMetadataFactory();
        $driver = $this->_em->getConfiguration()->getMetadataDriverImpl();
        
        $classes = array();
        $preloadedClasses = $driver->preload(true);
        foreach ($preloadedClasses as $className) {
            $classes[] = $cmf->getMetadataFor($className);
        }

        $printer = $this->getPrinter();
        $tool = new SchemaTool($this->_em);
        
        if (empty($classes)) {
            $printer->writeln('No classes to process.', 'INFO');
            return;
        }

        if ($isDrop) {
            if (isset($args['dump-sql'])) {
                foreach ($tool->getDropSchemaSql($classes) as $sql) {
                    $printer->writeln($sql);
                }
            } else {
                $printer->writeln('Dropping database schema...', 'INFO');
                
                try {
                    $tool->dropSchema($classes);
                    $printer->writeln('Database schema dropped successfully.', 'INFO');
                } catch (\Exception $ex) {
                    throw new DoctrineException($ex);
                }
            }
        }

        if ($isCreate) {
            if (isset($args['dump-sql'])) {
                foreach ($tool->getCreateSchemaSql($classes) as $sql) {
                    $printer->writeln($sql);
                }
            } else {
                $printer->writeln('Creating database schema...', 'INFO');
                
                try {
                    $tool->createSchema($classes);
                    $printer->writeln('Database schema created successfully.', 'INFO');
                } catch (\Exception $ex) {
                    throw new DoctrineException($ex);
                }
            }
        }

        if ($isUpdate) {            
            if (isset($args['dump-sql'])) {
                foreach ($tool->getUpdateSchemaSql($classes) as $sql) {
                    $printer->writeln($sql);
                }
            } else {
                $printer->writeln('Updating database schema...', 'INFO');
                
                try {
                    $tool->updateSchema($classes);
                    $printer->writeln('Database schema updated successfully.', 'INFO');
                } catch (\Exception $ex) {
                    throw new DoctrineException($ex);
                }
            }
        }
    }
}