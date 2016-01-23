<?php

namespace Shitty\Tests\ORM\Functional\Ticket;

use Shitty\ORM\Event\LifecycleEventArgs;

/**
 * @group DDC-1707
 */
class DDC1707Test extends \Shitty\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1509File'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1509Picture'),
            ));
        } catch (\Exception $ignored) {

        }
    }

    public function testPostLoadOnChild()
    {
        $class  = $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1707Child');
        $entity = new DDC1707Child();
        $event  = new LifecycleEventArgs($entity, $this->_em);

        $class->invokeLifecycleCallbacks(\Shitty\ORM\Events::postLoad, $entity, $event);

        $this->assertTrue($entity->postLoad);
    }
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorMap({"c": "DDC1707Child"})
 * @HasLifecycleCallbacks
 */
abstract class DDC1707Base
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    protected $id;

    public $postLoad = false;

    /**
     * @PostLoad
     */
    public function onPostLoad()
    {
        $this->postLoad = true;
    }
}
/**
 * @Entity
 */
class DDC1707Child extends DDC1707Base
{
}
