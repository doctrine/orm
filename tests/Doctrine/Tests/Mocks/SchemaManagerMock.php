<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\Tests\Mocks;

/**
 * Description of SchemaManagerMock
 *
 * @author robo
 */
class SchemaManagerMock extends \Doctrine\DBAL\Schema\AbstractSchemaManager
{
    public function __construct(\Doctrine\DBAL\Connection $conn) {
        parent::__construct($conn);
    }
}

