<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Query\ResultSetMapping;

class JoinColumnIndexNameTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testCreateSchemaJoinColumnExplicitIndexName()
    {
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->_em);
        $sql = $schemaTool->getCreateSchemaSql(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\Car'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\Maker'),
        ));

        $expected = array (
            0 => 'CREATE TABLE Car (id INTEGER NOT NULL, maker_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL, year INTEGER NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_4F70A07D68DA5EC3 FOREIGN KEY (maker_id) REFERENCES Maker (id) NOT DEFERRABLE INITIALLY IMMEDIATE)',
            1 => 'CREATE INDEX idx_maker_id ON Car (maker_id)',
            2 => 'CREATE TABLE Maker (id INTEGER NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))',
        );

        $this->assertEquals(
            $expected,
            $sql
        );
    }

    public function testUpdateSchemaJoinColumnExplicitIndexName()
    {
        $classes = array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\Car'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\Maker'),
        );

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->_em);
        $schemaTool->createSchema($classes);

        $sql = $schemaTool->getUpdateSchemaSql($classes, true);

        $schema = $schemaTool->getSchemaFromMetadata($classes);

        $carTable = $schema->getTable('Car');

        $indexes = $this->_em->getConnection()->getSchemaManager()->listTableIndexes('Car');

        unset($indexes['primary']);

        /** @var \Doctrine\DBAL\Schema\Index $makerIdIndex */
        $makerIdIndex = current($indexes);

        $this->_em->getConnection()->getSchemaManager()->dropIndex($makerIdIndex, $carTable);

        $query = $this->_em->createNativeQuery(
            'CREATE INDEX idx_maker_id ON Car(maker_id)',
            $rsm = new ResultSetMapping()
        );
        $query->execute();

        $sql = $schemaTool->getUpdateSchemaSql($classes, true);

        $this->assertEmpty($sql, "There should not be any changes, all indexes are present.");
    }
}

/**
 * @Table(
 *   indexes={@Index(name="idx_maker_id", columns={"maker_id"})})
 * )
 * @Entity()
 */
class Car
{
    /**
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     * @Column(type="integer", nullable=false)
     */
    protected $id;

    /**
     * @Column(type="string")
     */
    protected $name;

    /**
     * @Column(type="integer", length=4)
     */
    protected $year;

    /**
     * @ManyToOne(targetEntity="Maker")
     * @JoinColumn(name="maker_id", referencedColumnName="id", unique=false)
     */
    protected $maker;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getYear()
    {
        return $this->year;
    }

    public function setYear($year)
    {
        $this->year = $year;
    }

    public function getMaker()
    {
        return $this->maker;
    }

    public function setMaker(Maker $maker)
    {
        $this->maker = $maker;
    }
}

/**
 * @Table()
 * @Entity()
 */
class Maker
{
    /**
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     * @Column(type="integer", nullable=false)
     */
    protected $id;

    /**
     * @Column(type="string")
     */
    protected $name;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
