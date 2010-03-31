<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

use DateTime, Doctrine\DBAL\Types\Type;

class DDC425Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC425Entity'),
            //$this->_em->getClassMetadata(__NAMESPACE__ . '\DDC425Other')
        ));
    }

    /**
     * @group DDC-425
     */
    public function testIssue()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);

        $num = $this->_em->createQuery('DELETE '.__NAMESPACE__.'\DDC425Entity e WHERE e.someDatetimeField > ?1')
                ->setParameter(1, new DateTime, Type::DATETIME)
                ->getResult();
        $this->assertEquals(0, $num);
    }
}

/** @Entity */
class DDC425Entity {
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;
    
    /** @Column(type="datetime") */
    public $someDatetimeField;
}
