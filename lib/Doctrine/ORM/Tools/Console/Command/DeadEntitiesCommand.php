<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools\Console\Command;

use Doctrine\ORM\Mapping\MappingException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Show information about dead entities.
 *
 * Dead entities appear in a database where data has been inserted improperly.
 * For example someone can disable referential integrity checks, delete the
 * parent row in a parent-child relationship, and then enable referential
 * integrity checks.
 *
 * Even worse we could face the nightmare of having to work with a database
 * designed before referential integrity, and data modeling, was conceived by
 * the humar race. And yes, there a countries where this still happens!
 *
 * @link    www.doctrine-project.org
 * @since   2.6
 * @author  Marco Buschini <marco.buschini@gmail.com>
 */
class DeadEntitiesCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('orm:deadentities')
            ->setDescription('Checks for dead entities in the DB')
            ->setHelp(<<<EOT
The <info>%command.name%</info> shows entities that have a reference to a
missing entity, such as children with a parent that has been deleted.
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $entityManager \Doctrine\ORM\EntityManager */
        $entityManager = $this->getHelper('em')->getEntityManager();
        $schema = $entityManager->getConnection()->getSchemaManager();

        $classNames = $entityManager->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();

        if (!$classNames) {
            throw new \Exception(
                'You do not have any mapped Doctrine ORM entities according to the current configuration. '.
                'If you have entities or mapping files you should check your mapping configuration for errors.'
            );
        }

        $output->writeln(sprintf("Found <info>%d</info> mapped entities:", count($classNames)));

        $tableNames = array();
        foreach($classNames as $className) {
            $tableNames[$entityManager->getClassMetadata($className)->getTableName()] = $className;
        }
        $failure = false;
        $entity = array();
        foreach ($classNames as $className) {
            try {
                $tableName = array_search($className, $tableNames);
                $keys = $schema->listTableForeignKeys($tableName);
                $output->writeln('<info>'.$className.'('.$tableName.')'.'</info> '.count($keys).' foreign key(s)');
                $assoc = $entityManager->getClassMetadata($className)->getAssociationMappings();
                foreach($assoc as $a) {
                    if(array_key_exists('sourceToTargetKeyColumns', $a)) {
                        $output->writeln('-> Entity: '.$a['targetEntity'].'('.array_search($a['targetEntity'], $tableNames).')');
                        foreach ($a['joinColumnFieldNames'] as $column => $field) {
                            $output->writeln("\tField: $field($column)");
                        }
                        foreach($a['sourceToTargetKeyColumns'] as $fcolumn => $ffield) {
                            $output->writeln("\t\tForeign key: $ffield($fcolumn)");
                        }
                    }
                }
                continue;
                $output->writeln($tableNames[$tableName].'->'.array_keys($assoc)[0]);

                $foreignCols = array_key_exists($tableName, $assoc) ? $assoc[$tableName]['sourceToTargetKeyColums'] : null;
                $foreignCols = $assoc[$tableName];
                $foreignCols = $foreignCols['sourceToTargetKeyColums'];
                $cols = '';
                $dqlCols = '';
                foreach($foreignCols as $col) {
                    $cols .= $col . ', ';
                    $dqlCols .= '_inner.'.$col . ', ';
                }
                $cols = substr($cols, 0, strlen($cols) - 2);
                $dqlCols = substr($dqlCols, 0, strlen($dqlCols) - 2);
                //$output->writeln("\t".$field.' -> '.$tableNames[$key->getForeignTableName()].'('.$cols.')');
                $dql =
                    "SELECT _inner \n".
                    "  FROM ".$entity[0]['table']." _inner \n".
                    "  LEFT JOIN ".$entity[0]['foreignTable']." _outer \n".
                    "  WITH _inner.".$entity[0]['foreignColumn']." = _outer \n".
                    " WHERE ";
                $where = '';
                //foreach($foreignCols as $col) {
                    $where .= '_outer.'.$entity[0]['foreignColumn'].' IS NULL OR ';
                //}
                $dql .= substr($where, 0, strlen($where) - 4);

                $output->writeln('<warn>'.$dql.'</warn>');
                $query = $entityManager->createQuery($dql);
                $ret = $query->getResult();
                foreach($ret as $key => $val) {
                    foreach(array_keys($val) as $key) {
                        $output->writeln("\t".$className.' id with missing '.$field.': '.$val[$key]);
                    }
                }
            } catch (MappingException $e) {
                $output->writeln("<error>[FAIL]</error> ".$className);
                $output->writeln(sprintf("<comment>%s</comment>", $e->getMessage()));
                $output->writeln('');

                $failure = true;
            }
        }

        return $failure ? 1 : 0;
    }
}

