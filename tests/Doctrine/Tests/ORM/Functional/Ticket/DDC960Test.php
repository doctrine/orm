<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

require_once __DIR__ . '/../../../TestInit.php';

class DDC960Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC960Root'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC960Child')
            ));
        } catch(\Exception $e) {

        }
    }

    /**
     * @group DDC-960
     */
    public function testUpdateRootVersion()
    {
        $child = new DDC960Child('Test');
        $this->_em->persist($child);
        $this->_em->flush();

        $child->setName("Test2");

        $this->_em->flush();

        $this->assertEquals(2, $child->getVersion());
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorMap({
 *  "root" = "DDC960Root",
 *  "child" = "DDC960Child"
 * })
 */
class DDC960Root
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     */
    private $id;

    /**
     * @Column(type="integer") @Version
     */
    private $version;

    public function getId()
    {
        return $this->id;
    }

    public function getVersion()
    {
        return $this->version;
    }
}

/**
 * @Entity
 */
class DDC960Child extends DDC960Root
{
    /**
     * @column(type="string")
     * @var string
     */
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}