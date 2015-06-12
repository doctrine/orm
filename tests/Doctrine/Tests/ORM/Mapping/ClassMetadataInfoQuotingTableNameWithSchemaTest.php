<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Events;

class ClassMetadataInfoQuotingTableNameWithSchemaTest extends \Doctrine\Tests\OrmTestCase
{
    public function testIsQuotingTableNameWithSchema()
    {
        // First Assert
        $tableName = 'foo.bar';
        $return = $this->execute($tableName);
        $this->assertEquals($tableName, $return);

        // Secound Assert
        $tableName = '`foo`.`bar`';
        $return = $this->execute($tableName);
        $this->assertEquals($tableName, $return);

        // Third Assert
        $tableName = 'bar';
        $return = $this->execute($tableName);
        $this->assertEquals($tableName, $return);

        // Fourth Assert
        $tableName = '`bar`';
        $return = $this->execute($tableName);
        $this->assertEquals($tableName, $return);
    }

    protected function execute($tableName)
    {
        $quote = preg_match('/`/', $tableName) ? true : false;
        $tableName = preg_replace('/`/', '', $tableName);
        $tableNameExploded = explode('.', $tableName);

        $platform = $this->getPlataformMock();

        $identifier = $tableNameExploded[0];
        $expected = self::quote($tableNameExploded[0], $quote);
        if (isset($tableNameExploded[1])) {
            $identifier .= '.' . $tableNameExploded[1];
            $expected .= '.' . self::quote($tableNameExploded[1], $quote);

            $platform->supportsSchemas()->willReturn(true);
            $platform->canEmulateSchemas()->willReturn(true);
        }

        $platform->quoteIdentifier($identifier)->willReturn($expected);

        return $this->getClassMetadataInfo($expected)->getQuotedTableName(
            $platform->reveal()
        );
    }

    protected static function quote($str, $quote = true)
    {
        return $quote ? '`' . $str . '`' : $str;
    }

    protected function getClassMetadataInfo($tableName)
    {
        $classMetadataInfo = new \Doctrine\ORM\Mapping\ClassMetadataInfo('EntityFoo');
        $classMetadataInfo->setPrimaryTable([
            'name' => $tableName
        ]);

        return $classMetadataInfo;
    }

    protected function getPlataformMock()
    {
        return $this->prophesize('\Doctrine\DBAL\Platforms\AbstractPlatform');
    }
}
