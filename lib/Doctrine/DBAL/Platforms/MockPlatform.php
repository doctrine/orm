<?php
#namespace Doctrine::DBAL::Platforms;

class Doctrine_DBAL_Platforms_MockPlatform extends Doctrine_DBAL_Platforms_AbstractPlatform
{
    public function getNativeDeclaration(array $field) {}
    public function getPortableDeclaration(array $field) {}
}

?>