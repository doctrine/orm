<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Tests\ORM\Functional\DatabaseDriverTestCase;

class GH5551Test extends DatabaseDriverTestCase
{
    public function testLoadMetadataFromManualTableWithoutPrimaryKey()
    {
        $table = new \Doctrine\DBAL\Schema\Table("dbdriver_foo");
        $table->addColumn('id', 'integer');
        $table->addColumn('bar', 'string', array('notnull' => false, 'length' => 200));

        $sm = $this->_em->getConnection()->getSchemaManager();
        $driver = new \Doctrine\ORM\Mapping\Driver\DatabaseDriver($sm);
        $driver->setTables(array($table), array());

        foreach ($driver->getAllClassNames() as $className) {
            $class = new ClassMetadataInfo($className);
            $driver->loadMetadataForClass($className, $class);
        }

        // At this point a fatal error would be thrown without this fix
        // Fatal error: Call to a member function getColumns() on null in .../lib/Doctrine/ORM/Mapping/Driver/DatabaseDriver.php on line 511
    }
}
