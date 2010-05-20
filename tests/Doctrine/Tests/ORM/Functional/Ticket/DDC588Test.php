<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC588Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC588Site'),
        ));
    }

    public function testIssue()
    {
        $site = new DDC588Site('Foo');

        $this->_em->persist($site);
        $this->_em->flush();
        // Following should not result in exception
        $this->_em->refresh($site);
    }
}

/**
 * @Entity
 */
class DDC588Site
{
    /**
     * @Id
     * @Column(type="integer", name="site_id")
     * @GeneratedValue
     */
    public $id;

    /**
     * @Column(type="string", length=45)
     */
    protected $name = null;

    public function __construct($name = '')
    {
        $this->name = $name;
    }
}
