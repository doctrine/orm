<?php

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\ORM\Tools;

class DDC3460Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    public function setUp()
    {
        parent::setUp();
        $this->connection = $this->_em->getConnection();

        if (strpos($this->connection->getDriver()->getName(), "mysql") === false) {
            $this->markTestSkipped('this test is only relevant for MySQL');
        }

        $this->connection->exec('DROP TABLE IF EXISTS `tweet_user`');
        $this->connection->exec('DROP TABLE IF EXISTS `tweet_tweet`');
    }

    /**
     * @group DDC-3460
     */
    public function testDetectMyISAM()
    {

        $this->connection->exec('CREATE TABLE `tweet_user` (id INT NOT NULL AUTO_INCREMENT, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) ENGINE = MyISAM');
        $this->connection->exec('CREATE TABLE `tweet_tweet` (id INT NOT NULL AUTO_INCREMENT, content VARCHAR(255) NOT NULL, author_id INT, PRIMARY KEY(id)) ENGINE = MyISAM');

        $fromSchema = $this->connection->getSchemaManager()->createSchema();
        $options = $fromSchema->getTable('tweet_user')->getOptions();
        $this->assertArrayHasKey('engine', $options);
        $this->assertSame('MyISAM', $options['engine']);

        $classMetadata = [
            $this->_em->getClassMetadata(Doctrine\Tests\Models\Tweet\Tweet::class),
            $this->_em->getClassMetadata(Doctrine\Tests\Models\Tweet\User::class)
        ];
        $schemaTool = new Tools\SchemaTool($this->_em);
        $toSchema = $schemaTool->getSchemaFromMetadata($classMetadata);

        $comparator = new \Doctrine\DBAL\Schema\Comparator();
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);
        $this->assertEquals([], $schemaDiff->toSql($this->connection->getDatabasePlatform()), 'Schema diff should be empty');
    }

    public function tearDown()
    {
        $this->connection->exec('DROP TABLE IF EXISTS `tweet_user`');
        $this->connection->exec('DROP TABLE IF EXISTS `tweet_tweet`');
        parent::tearDown();
    }
}
