<?php
/**
 * This unittest demonstrates how I would like to implement entity security on
 * an eagerly loaded owner side orm.
 *
 * It fails now because eagerly loaded entites are not loaded by the time
 * postLoad is called.
 *
 * @author Tom Anderson <tanderson@soliantconsulting.com>
 */

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

require_once __DIR__ . '/../../TestInit.php';

class CancelLoadEntityExceptionTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\CLEET_Unit'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\CLEET_Test'),
            ));
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
    }

    /**
     * Cancel Load Entity Exception Test
     */
    public function test()
    {
        $unit1 = new CLEET_Unit;
        $unit1->setMake('49ers');
        $unit1->setName('Tank Carradine');

        $unit2 = new CLEET_Unit;
        $unit2->setMake('Giants');
        $unit2->setName('Brett Pill');

        $u1Test1 = new CLEET_Test;
        $u1Test1->setName('49ers Test 1');
        $u1Test1->setUnit($unit1);

        $u1Test2 = new CLEET_Test;
        $u1Test2->setName('49ers Test 2');
        $u1Test2->setUnit($unit1);

        $u2Test1 = new CLEET_Test;
        $u2Test1->setName('Giants Test 1');
        $u2Test1->setUnit($unit2);

        $u2Test2 = new CLEET_Test;
        $u2Test2->setName('Giants Test 2');
        $u2Test2->setUnit($unit2);

        $this->_em->persist($unit1);
        $this->_em->persist($unit2);
        $this->_em->persist($u1Test1);
        $this->_em->persist($u1Test2);
        $this->_em->persist($u2Test1);
        $this->_em->persist($u2Test2);

        $this->_em->flush();

        $this->_em->clear();


        // Test user can see only thier own units
        $testMake = 'Giants';
        CLEET_User::$admin = false;
        CLEET_User::$make = $testMake;

        $units = $this->_em->getRepository('Doctrine\Tests\ORM\Functional\CLEET_Unit')->findAll();

        $this->assertEquals(1, sizeof($units));
        foreach ($units as $unit) {
            $this->assertEquals($testMake, $unit->getMake());
        }


        $this->_em->clear();


        // Test another user
        $testMake = '49ers';
        CLEET_User::$admin = false;
        CLEET_User::$make = $testMake;

        $units = $this->_em->getRepository('Doctrine\Tests\ORM\Functional\CLEET_Unit')->findAll();

        $this->assertEquals(1, sizeof($units));
        foreach ($units as $unit) {
            $this->assertEquals($testMake, $unit->getMake());
        }


        $this->_em->clear();


        // Test user can see only thier own tests
        $testMake = 'Giants';
        CLEET_User::$admin = false;
        CLEET_User::$make = $testMake;

        $tests = $this->_em->getRepository('Doctrine\Tests\ORM\Functional\CLEET_Test')->findAll();

        $this->assertEquals(2, sizeof($tests));
        foreach ($tests as $test) {
            print_r($test);die();

            $this->assertEquals($testMake, $test->getUnit()->getMake());
        }

        // Test another user
        $testMake = '49ers';
        CLEET_User::$admin = false;
        CLEET_User::$make = $testMake;

        $tests = $this->_em->getRepository('Doctrine\Tests\ORM\Functional\CLEET_Test')->findAll();

        $this->assertEquals(2, sizeof($tests));
        foreach ($tests as $test) {
            $this->assertEquals($testMake, $test->getUnit()->getMake());
        }


        $this->_em->clear();


        // Test admin can see all
        CLEET_User::$admin = true;
        CLEET_User::$make = null;

        if (!CLEET_User::$admin) die('admin unset');

        $tests = $this->_em->getRepository('Doctrine\Tests\ORM\Functional\CLEET_Test')->findAll();

        if (!CLEET_User::$admin) die('admin unset');

#        $this->assertEquals(4, sizeof($tests));
    }
}

class CLEET_User
{
    static $make;
    static $admin;
}

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="cleet_unit")
 */
class CLEET_Unit
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Column(type="string", length=50)
     */
    private $name;

    /**
     * @Column(type="string", length=50)
     */
    private $make;

    /**
     * @OneToMany(targetEntity="CLEET_Test", mappedBy="unit")
     */
    private $tests;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($value)
    {
        $this->name = $value;
        return $this;
    }

    public function getMake()
    {
        return $this->make;
    }

    public function setMake($value)
    {
        $this->make = $value;
        return $this;
    }

    public function getTests()
    {
        return $this->tests;
    }

    /** @PostLoad */
    public function securityCheck(LifecycleEventArgs $args)
    {
        if (CLEET_User::$admin === true) {
            return true;
        }

        if (CLEET_User::$make === $args->getEntity()->getMake()) {
            return true;
        }

        throw new \Doctrine\ORM\CancelLoadEntityException;
    }
}

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(name="cleet_test")
 */
class CLEET_Test
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Column(type="string", length=50)
     */
    private $name;

    /**
     * @ManyToOne(targetEntity="CLEET_Unit", fetch="EAGER")
     * @JoinColumn(name="unit_id", referencedColumnName="id")
     */
    private $unit;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($value)
    {
        $this->name = $value;
        return $this;
    }

    public function getUnit()
    {
        return $this->unit;
    }

    public function setUnit(CLEET_Unit $value)
    {
        $this->unit = $value;
        return $this;
    }

    /** @PostLoad */
    public function securityCheck(LifecycleEventArgs $args)
    {
        if ( ! $args->getEntity()->getUnit()) {
            throw new \Doctrine\ORM\CancelLoadEntityException;
        }
    }
}
