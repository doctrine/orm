<?php

namespace Doctrine\ORM\Tools\Cli\Tasks;

use Doctrine\ORM\Tools\SchemaTool,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\ORM\Mapping\Driver\AnnotationDriver,
    Doctrine\ORM\Mapping\Driver\XmlDriver,
    Doctrine\ORM\Mapping\Driver\YamlDriver;

/**
 * Task to create the database schema for a set of classes based on their mappings.
 * 
 * This task has the following arguments:
 * 
 * <tt>--classdir=<path></tt>
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
 * @author robo
 * @since 2.0
 */
class SchemaToolTask extends AbstractTask
{
    /**
     * @inheritdoc
     */
    public function extendedHelp()
    {
        $this->basicHelp();
    }

    /**
     * @inheritdoc
     */
    public function basicHelp()
    {
        $this->getPrinter()->write('schema-tool', 'KEYWORD');
        $this->getPrinter()->writeln(
            ' (--create | --drop | --update) [--dump-sql] [--classdir=<path>]',
            'INFO');
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
        
        $isCreate = isset($args['create']);
        $isDrop = isset($args['drop']);
        $isUpdate = isset($args['update']);
        
        if ( ! ($isCreate ^ $isDrop ^ $isUpdate)) {
            $printer->writeln("One of --create, --drop or --update required, and only one.", 'ERROR');
            return false;
        }
        
        if ($this->_em->getConfiguration()->getMetadataDriverImpl() instanceof \Doctrine\ORM\Mapping\Driver\AnnotationDriver
                && ! isset($args['classdir'])) {
            $printer->writeln("The supplied configuration uses the annotation metadata driver."
                    . " The 'classdir' argument is required for this driver.", 'ERROR');
            return false;     
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
        
        if ($driver instanceof \Doctrine\ORM\Mapping\Driver\AnnotationDriver) {
            $iter = new \FilesystemIterator($args['classdir']);
            
            $declared = get_declared_classes();          
            foreach ($iter as $item) {
                $baseName = $item->getBaseName();
                if ($baseName[0] == '.') {
                    continue;
                }
                require_once $item->getPathName();
            }
            $declared = array_diff(get_declared_classes(), $declared);
            
            foreach ($declared as $className) {                 
                if ( ! $driver->isTransient($className)) {
                    $classes[] = $cmf->getMetadataFor($className);
                }
            }
        } else {
            $preloadedClasses = $driver->preload(true);
            foreach ($preloadedClasses as $className) {
                $classes[] = $cmf->getMetadataFor($className);
            }
        }
        
        $printer = $this->getPrinter();
        $tool = new SchemaTool($this->_em);
        
        if ($isCreate) {
            if (isset($args['dump-sql'])) {
                foreach ($tool->getCreateSchemaSql($classes) as $sql) {
                    $printer->writeln($sql);
                }
            } else {
                $printer->writeln('Creating database schema...', 'INFO');
                $tool->createSchema($classes);
                $printer->writeln('Database schema created successfully.', 'INFO');
            }
        } else if ($isDrop) {
            if (isset($args['dump-sql'])) {
                foreach ($tool->getDropSchemaSql($classes) as $sql) {
                    $printer->writeln($sql);
                }
            } else {
                $printer->writeln('Dropping database schema...', 'INFO');
                $tool->dropSchema($classes);
                $printer->writeln('Database schema dropped successfully.', 'INFO');
            }
        } else if ($isUpdate) {
            if (isset($args['dump-sql'])) {
                foreach ($tool->getUpdateSchemaSql($classes) as $sql) {
                    $printer->writeln($sql);
                }
            } else {
                $printer->writeln('Updating database schema...', 'INFO');
                $tool->updateSchema($classes);
                $printer->writeln('Database schema updated successfully.', 'INFO');
            }
        }
    }
}