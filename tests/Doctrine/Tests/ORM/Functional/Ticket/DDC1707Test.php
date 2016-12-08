<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Events;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1707
 */
class DDC1707Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(
                [
                $this->_em->getClassMetadata(DDC1509File::class),
                $this->_em->getClassMetadata(DDC1509Picture::class),
                ]
            );
        } catch (\Exception $ignored) {

        }
    }

    public function testPostLoadOnChild()
    {
        $class  = $this->_em->getClassMetadata(DDC1707Child::class);
        $entity = new DDC1707Child();

        $class->invokeLifecycleCallbacks(Events::postLoad, $entity);

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
