<?php

class Doctrine_DatabasePlatformMock extends Doctrine_DatabasePlatform
{
    public function getNativeDeclaration($field) {}
    public function getPortableDeclaration(array $field) {}
}

?>