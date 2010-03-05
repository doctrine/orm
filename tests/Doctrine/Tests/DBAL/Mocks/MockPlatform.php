<?php

namespace Doctrine\Tests\DBAL\Mocks;

use Doctrine\DBAL\Platforms;

class MockPlatform extends \Doctrine\DBAL\Platforms\AbstractPlatform
{
    public function getBooleanTypeDeclarationSQL(array $columnDef) {}
    public function getIntegerTypeDeclarationSQL(array $columnDef) {}
    public function getBigIntTypeDeclarationSQL(array $columnDef) {}
    public function getSmallIntTypeDeclarationSQL(array $columnDef) {}
    public function _getCommonIntegerTypeDeclarationSQL(array $columnDef) {}

    public function getVarcharTypeDeclarationSQL(array $field)
    {
        return "DUMMYVARCHAR()";
    }
    
    /** @override */
    public function getClobTypeDeclarationSQL(array $field)
    {
        return 'DUMMYCLOB';
    }

    public function getVarcharDefaultLength()
    {
        return 255;
    }

    public function getName()
    {
        return 'mock';
    }
}
