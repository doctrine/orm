<?php

namespace Doctrine\ORM\Tools\Cli\Tasks;

/**
 * Task for executing arbitrary SQL that can come from a file or directly from
 * the command line.
 * 
 * @author robo
 * @since 2.0
 */
class RunSqlTask extends AbstractTask
{
    /**
     * @inheritdoc
     */
    public function extendedHelp()
    {
        $printer = $this->getPrinter();
        
        $printer->write('Task: ')->writeln('run-sql', 'KEYWORD')
                ->write('Synopsis: ');
        $this->_writeSynopsis($printer);
        
        $printer->writeln('Description: Executes arbitrary SQL from a file or directly from the command line.')
                ->writeln('Options:')
                ->write('--sql=<SQL>', 'REQ_ARG')
                ->writeln("\tThe SQL to execute.")
                ->writeln("\t\tIf defined, --file can not be requested on same task")
                ->write(PHP_EOL)
                ->write('--file=<path>', 'REQ_ARG')
                ->writeln("\tThe path to the file with the SQL to execute.")
                ->writeln("\t\tIf defined, --sql can not be requested on same task");
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
        $printer->write('run-sql', 'KEYWORD')
                ->writeln(' (--file=<path> | --sql=<SQL>)', 'REQ_ARG');
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
        
        $isSql = isset($args['sql']);
        $isFile = isset($args['file']);
        
        if ( ! ($isSql ^ $isFile)) {
            $printer->writeln("One of --sql or --file required, and only one.", 'ERROR');
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
        
        if (isset($args['file'])) {
            //TODO
        } else if (isset($args['sql'])) {
            if (preg_match('/^select/i', $args['sql'])) {
                $stmt = $this->_em->getConnection()->execute($args['sql']);
                var_dump($stmt->fetchAll(\Doctrine\DBAL\Connection::FETCH_ASSOC));
            } else {
                var_dump($this->_em->getConnection()->executeUpdate($args['sql']));
            }
        }
    }
}