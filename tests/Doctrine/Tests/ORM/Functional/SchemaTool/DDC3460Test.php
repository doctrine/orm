<?php

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\ORM\Tools;

class DDC3460Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * @group DDC-3460
     */
    public function testDetectMyISAM()
    {
        $conn = $this->_em->getConnection();

        if (strpos($conn->getDriver()->getName(), "mysql") === false) {
            $this->markTestSkipped('this test is only relevant for MySQL');
        }

        $conn->exec('CREATE TABLE `tweet_user` (id INT NOT NULL AUTO_INCREMENT, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) ENGINE = MyISAM');
        $conn->exec('CREATE TABLE `tweet_tweet` (id INT NOT NULL AUTO_INCREMENT, content VARCHAR(255) NOT NULL, author_id INT, PRIMARY KEY(id)) ENGINE = MyISAM');

        $fromSchema = $conn->getSchemaManager()->createSchema();

        $classMetadata = [
            $this->_em->getClassMetadata('Doctrine\Tests\Models\Tweet\Tweet'),
            $this->_em->getClassMetadata('Doctrine\Tests\Models\Tweet\User')
        ];
        $schemaTool = new Tools\SchemaTool($this->_em);
        $toSchema = $schemaTool->getSchemaFromMetadata($classMetadata);

        $comparator = new \Doctrine\DBAL\Schema\Comparator();
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);
        $this->assertEquals([], $schemaDiff->toSql($conn->getDatabasePlatform()));
    }
}
