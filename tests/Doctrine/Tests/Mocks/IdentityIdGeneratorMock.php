<?php

namespace Doctrine\Tests\Mocks;

class IdentityIdGeneratorMock extends \Doctrine\ORM\Id\IdentityGenerator
{
    private $_mockPostInsertId;

    public function setMockPostInsertId($id) {
        $this->_mockPostInsertId = $id;
    }
}