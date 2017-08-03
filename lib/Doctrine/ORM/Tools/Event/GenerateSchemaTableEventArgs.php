<?php
declare(strict_types=1);

namespace Doctrine\ORM\Tools\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

/**
 * Event Args used for the Events::postGenerateSchemaTable event.
 *
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 */
class GenerateSchemaTableEventArgs extends EventArgs
{
    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    private $classMetadata;

    /**
     * @var \Doctrine\DBAL\Schema\Schema
     */
    private $schema;

    /**
     * @var \Doctrine\DBAL\Schema\Table
     */
    private $classTable;

    /**
     * @param ClassMetadata $classMetadata
     * @param Schema        $schema
     * @param Table         $classTable
     */
    public function __construct(ClassMetadata $classMetadata, Schema $schema, Table $classTable)
    {
        $this->classMetadata = $classMetadata;
        $this->schema = $schema;
        $this->classTable = $classTable;
    }

    /**
     * @return ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->classMetadata;
    }

    /**
     * @return Schema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @return Table
     */
    public function getClassTable()
    {
        return $this->classTable;
    }
}
