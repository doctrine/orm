<?php

class Doctrine_DatabasePlatform_MockPlatform extends Doctrine_DatabasePlatform
{
    public function getNativeDeclaration(array $field) {}
    public function getPortableDeclaration(array $field) {}
}

?>