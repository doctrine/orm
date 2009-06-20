<?php

namespace Doctrine\Tests\DBAL\Mocks;

use Doctrine\DBAL\Platforms;

class MockPlatform extends \Doctrine\DBAL\Platforms\AbstractPlatform
{
    public function getIntegerTypeDeclarationSql(array $columnDef) {}
    public function getBigIntTypeDeclarationSql(array $columnDef) {}
    public function getSmallIntTypeDeclarationSql(array $columnDef) {}
    public function _getCommonIntegerTypeDeclarationSql(array $columnDef) {}
    public function getVarcharTypeDeclarationSql(array $field) {}
    public function getName()
    {
        return 'mock';
    }
}