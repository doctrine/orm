<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\StaticPhpDriver;
use Doctrine\ORM\Mapping\Driver\PhpDriver;

/**
 * This is a test to verify a custom column name is accurately mapped when
 * creating an association using Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder::addManyToOne
 */
class DDC2376 extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testIssue()
    {
        $driver = new StaticPHPDriver(__DIR__ . '/../../../Models/DDC2376/');
        $this->_em->getConfiguration()->setMetadataDriverImpl($driver);

        $sql = $this->_schemaTool->getCreateSchemaSql(array(
            $this->_em->getClassMetadata('Doctrine\Tests\Models\DDC2376\User'),
            $this->_em->getClassMetadata('Doctrine\Tests\Models\DDC2376\Reference'),
        ));

        $this->assertEquals('CREATE TABLE User (user_id INTEGER NOT NULL, username VARCHAR(255) NOT NULL, PRIMARY KEY(user_id))', $sql[0]);
        $this->assertEquals('CREATE TABLE Reference (id INTEGER NOT NULL, user_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_2C52CBB0A76ED395 FOREIGN KEY (user_id) REFERENCES User (user_id) NOT DEFERRABLE INITIALLY IMMEDIATE)', $sql[1]);
    }
}
