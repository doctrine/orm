<?php

namespace Doctrine\ORM\Tools\Cli\Tasks;

use Doctrine\Common\DoctrineException,
    Doctrine\Common\Cli\Option,
    Doctrine\Common\Cli\OptionGroup,
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
    public function buildDocumentation()
    {
        $schemaOption = new OptionGroup(OptionGroup::CARDINALITY_1_1, array(
            new Option(
                'create', null, 
                'Creates the schema in EntityManager (create tables on Database).' . PHP_EOL .
                'If defined, --drop, --update and --re-create can not be requested on same task.'
            ),
            new Option(
                'drop', null,
                'Drops the schema of EntityManager (drop tables on Database).' . PHP_EOL .
                'Beware that the complete database is dropped by this command, '.PHP_EOL.
                'even tables that are not relevant to your metadata model.' . PHP_EOL .
                'If defined, --create, --update and --re-create can not be requested on same task.'
            ),
            new Option(
                'update', null,
                'Updates the schema in EntityManager (update tables on Database).' . PHP_EOL .
                'This command does a save update, which does not delete any tables, sequences or affected foreign keys.' . PHP_EOL .
                'If defined, --create, --drop and --complete-update --re-create can not be requested on same task.'
            ),
            new Option(
                'complete-update', null,
                'Updates the schema in EntityManager (update tables on Database).' . PHP_EOL .
                'Beware that all assets of the database which are not relevant to the current metadata are dropped by this command.'.PHP_EOL.
                'If defined, --create, --drop and --update --re-create can not be requested on same task.'
            ),
            new Option(
                're-create', null, 
                'Runs --drop then --create to re-create the database.' . PHP_EOL .
                'If defined, --create, --update and --drop can not be requested on same task.'
            )
        ));
        
        $optionalOptions = new OptionGroup(OptionGroup::CARDINALITY_0_N, array(
            new Option('dump-sql', null, 'Instead of try to apply generated SQLs into EntityManager, output them.'),
            new Option('class-dir', '<PATH>', 'Optional class directory to fetch for Entities.')
        ));
        
        $doc = $this->getDocumentation();
        $doc->setName('schema-tool')
            ->setDescription('Processes the schema and either apply it directly on EntityManager or generate the SQL output.')
            ->getOptionGroup()
                ->addOption($schemaOption)
                ->addOption($optionalOptions);
    }
    
    /**
     * @inheritdoc
     */
    public function validate()
    {
        $args = $this->getArguments();
        $printer = $this->getPrinter();
        
        if (array_key_exists('re-create', $args)) {
            $args['drop'] = true;
            $args['create'] = true;
            $this->setArguments($args);
        }
        
        $isCreate = isset($args['create']);
        $isDrop = isset($args['drop']);
        $isUpdate = isset($args['update']);
        $isCompleteUpdate = isset($args['complete-update']);
        
        if ($isUpdate && ($isCreate || $isDrop || $isCompleteUpdate)) {
            $printer->writeln("You can't use --update with --create, --drop or --complete-update", 'ERROR');
            return false;
        }

        if ($isCompleteUpdate && ($isCreate || $isDrop || $isUpdate)) {
            $printer->writeln("You can't use --update with --create, --drop or --update", 'ERROR');
            return false;
        }

        if ( ! ($isCreate || $isDrop || $isUpdate || $isCompleteUpdate)) {
            $printer->writeln('You must specify at a minimum one of the options (--create, --drop, --update, --re-create, --complete-update).', 'ERROR');
            return false;
        }
        
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

        $isCreate = isset($args['create']);
        $isDrop = isset($args['drop']);
        $isUpdate = isset($args['update']);
        $isCompleteUpdate = isset($args['complete-update']);

        $em = $this->getEntityManager();
        $cmf = $em->getMetadataFactory();
        $driver = $em->getConfiguration()->getMetadataDriverImpl();

        $classes = array();
        $preloadedClasses = $driver->preload(true);

        foreach ($preloadedClasses as $className) {
            $classes[] = $cmf->getMetadataFor($className);
        }

        $printer = $this->getPrinter();
        
        if (empty($classes)) {
            $printer->writeln('No classes to process.', 'INFO');
            return;
        }

        $tool = new SchemaTool($em);
        
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

        if ($isUpdate || $isCompleteUpdate) {
            $saveMode = $isUpdate?true:false;
            if (isset($args['dump-sql'])) {
                foreach ($tool->getUpdateSchemaSql($classes, $saveMode) as $sql) {
                    $printer->writeln($sql);
                }
            } else {
                $printer->writeln('Updating database schema...', 'INFO');
                
                try {
                    $tool->updateSchema($classes, $saveMode);
                    $printer->writeln('Database schema updated successfully.', 'INFO');
                } catch (\Exception $ex) {
                    throw new DoctrineException($ex);
                }
            }
        }
    }
}