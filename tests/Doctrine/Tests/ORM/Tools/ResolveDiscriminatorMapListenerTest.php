<?php

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Tools\ResolveDiscriminatorMapListener;

class ResolveDiscriminatorMapListenerTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @var EntityManager
     */
    private $em = null;

    /**
     * @var ClassMetadataFactory
     */
    private $factory = null;

    public function setUp()
    {
        $annotationDriver = $this->createAnnotationDriver();

        $this->em = $this->_getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $this->factory = new ClassMetadataFactory;
        $this->factory->setEntityManager($this->em);
    }

    /**
     * @group DDC-3300
     */
    public function testResolveDiscriminatorMapListenerTestCanResolveDiscriminatorMap()
    {
        $evm = $this->em->getEventManager();
        $listener = new ResolveDiscriminatorMapListener(array(
            'Doctrine\Tests\ORM\Tools\BossInterface' => 'Doctrine\Tests\ORM\Tools\Boss',
            'Doctrine\Tests\ORM\Tools\EmployeeInterface' => 'Doctrine\Tests\ORM\Tools\Employee',
        ));

        $evm->addEventListener(Events::loadClassMetadata, $listener);
        $cm = $this->factory->getMetadataFor('Doctrine\Tests\ORM\Tools\Person');
        $meta = $cm->discriminatorMap;
        $this->assertSame('Doctrine\Tests\ORM\Tools\Boss', $meta['boss']);
        $this->assertSame('Doctrine\Tests\ORM\Tools\Employee', $meta['employee']);
    }

    /**
     * @group DDC-3300
     */
    public function testResolveDiscriminatorMapListenerTestCannotResolveWrongDiscriminatorMap()
    {
        $evm = $this->em->getEventManager();
        $listener = new ResolveDiscriminatorMapListener(array(
            'Doctrine\Tests\ORM\Tools\EmployeeInterface' => 'Doctrine\Tests\ORM\Tools\Employee',
        ));

        $evm->addEventListener(Events::loadClassMetadata, $listener);
        $cm = $this->factory->getMetadataFor('Doctrine\Tests\ORM\Tools\Person');
        $meta = $cm->discriminatorMap;
        $this->assertSame('Doctrine\Tests\ORM\Tools\BossInterface', $meta['boss']);
        $this->assertSame('Doctrine\Tests\ORM\Tools\Employee', $meta['employee']);
    }
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *      "boss" = "\Doctrine\Tests\ORM\Tools\BossInterface",
 *      "employee" = "\Doctrine\Tests\ORM\Tools\EmployeeInterface"
 * })
 */
abstract class Person
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
}

interface BossInterface
{

}

class Boss extends Person implements BossInterface
{
    /**
     * @Column(type="integer")
     */
    private $earnedMoneyInEuros;
}

interface EmployeeInterface
{

}

class Employee extends Person implements EmployeeInterface
{
    /**
     * @Column(type="integer")
     */
    private $daysOfLifeReducedInDays;
}
