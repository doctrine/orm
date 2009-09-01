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
        $this->getPrinter()->writeln('run-sql extended help.', 'INFO');
    }

    /**
     * @inheritdoc
     */
    public function basicHelp()
    {
        $this->getPrinter()->write('run-sql', 'KEYWORD');
        $this->getPrinter()->writeln(
            ' --file=<path> | --sql=<SQL>',
            'INFO');
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