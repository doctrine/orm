<?php

#namespace Doctrine\Tests\Mocks;

/**
 * Description of Doctrine_IdentityIdGeneratorMock
 *
 * @author robo
 */
class Doctrine_IdentityIdGeneratorMock extends Doctrine_ORM_Id_IdentityGenerator
{
    private $_mockPostInsertId;

    public function setMockPostInsertId($id) {
        $this->_mockPostInsertId = $id;
    }
}

