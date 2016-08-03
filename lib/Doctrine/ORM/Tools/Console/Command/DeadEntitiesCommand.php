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
use Doctrine\ORM\Query\QueryException;

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
                        $sourceFields = array();
                        $sourceColumns = array();
                        foreach ($a['joinColumnFieldNames'] as $column => $field) {
                            $output->writeln("\tField: $field($column)");
                            $sourceFields[] = $field;
                            $sourceColumns[] = $column;
                        }
                        $targetFields = array();
                        $targetColumns = array();
                        foreach($a['sourceToTargetKeyColumns'] as $fcolumn => $ffield) {
                            $output->writeln("\t\tForeign key: $ffield($fcolumn)");
                            $targetFields[] = $ffield;
                            $targetColumns[] = $fcolumn;
                        }
                        $missing = $this->findMissingEntities($a, $output);
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

    private function findMissingEntities($entity, $output) {
        $dql = "SELECT _inner, _outer\n"
              ."  FROM ".$entity['sourceEntity']." _inner\n"
              ."  LEFT JOIN ".$entity['targetEntity']." _outer"
              .$this->with($entity)
              .$this->where($entity);
        
        $entityManager = $this->getHelper('em')->getEntityManager();
        try {
            $query = $entityManager->createQuery($dql);
            $ret = $query->getResult();
        } catch(QueryException $e) {
            $output->writeln("<error>Data model too complex to analyze.</error>");
            return;
        }

        foreach($ret as $key) {
            if($key == null) {
                continue;
            }
            $output->writeln("\t".$entity['targetEntity'].' id with missing reference '.$key->getId());
        }
    }
    
    private function with($entity) {
        if(count($entity['joinColumns']) == 1 || true) {
            return "\n  WITH _inner.".$entity['fieldName']." = _outer";
        }
        $ret = "\n  WITH ";
        foreach($entity['joinColumns'] as $column) {
            $ret .= "_inner.".$column['name']." = _outer.".$column['referencedColumnName']."\n   AND ";
        }
        return substr($ret, 0, strlen($ret) - 6);
    }
    
    private function where($entity) {
        if(count($entity['joinColumns']) != 0) {
            $ret = "\n WHERE ";
            foreach($entity['joinColumns'] as $column) {
                $ret .= "_outer.".$column['referencedColumnName']." IS NULL\n   AND ";
            }
            return substr($ret, 0, strlen($ret) - 6);
        } else {
            return "\n WHERE _outer IS NULL";
        }
    }
}
