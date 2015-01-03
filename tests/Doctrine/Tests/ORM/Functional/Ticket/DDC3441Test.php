<?php


namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\DDC3441\Car;
use Doctrine\Tests\Models\DDC3441\Person;

/**
 * @Group DDC3441
 */
class DDC3441Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('ddc3441');
        parent::setUp();

        $car    = new Car();
        $person = new Person();

        $person->setCar($car);

        $this->_em->persist($car);
        $this->_em->persist($person);
        $this->_em->flush();
        $this->_em->clear();
    }

    /**
     * I'm not sure if this is an issue, but in my code I've noticed that lazy-loaded entities that cause problems are
     * always an instance of Proxy, while entities that behave as expected are not. Both pass class type hinting though.
     */
    public function testLazyLoadDoesNotGetProxy()
    {
        $person = $this->_em->find('Doctrine\Tests\Models\DDC3441\Person', 1);
        $car = $person->getCar();

        $this->assertNotInstanceOf('\Doctrine\ORM\Proxy\Proxy', $car);
        $this->assertInstanceOf('Doctrine\Tests\Models\DDC3441\Car', $car);
    }

    /**
     * Properties obtained from lazy loaded entities do no actually match the entity definition.
     */
    public function testGetCorrectPropertiesThroughReflection()
    {
        $person = $this->_em->find('Doctrine\Tests\Models\DDC3441\Person', 1);
        $car    = $person->getCar();

        //create the reflection
        $reflection = new \ReflectionClass($car);
        //get its properties
        $properties = $reflection->getProperties();

        //an array of expected property values
        $checkValues = array('model', 'make', 'year');
        foreach ($properties as $property) {
            //make sure that each property from the reflection class is expected
            $this->assertContains($property->getName(), $checkValues);
            //remove matched elements from the array, they should only appear once
            $key = array_search($property->name, $checkValues);
            unset($checkValues[$key]);
        }
        //ensure that all properties got matched
        $this->assertCount(0, $checkValues);
    }

    /**
     * Trying to access properties from the class definition returns null instead of the value because those properties
     * do not exist on the lazy loaded class. See Above test case.
     */
    public function testCanGetValuesThroughReflection()
    {
        $person = $this->_em->find('Doctrine\Tests\Models\DDC3441\Person', 1);
        $car    = $person->getCar();

        $make = new \ReflectionProperty('Doctrine\Tests\Models\DDC3441\Car', 'make');
        $make->setAccessible(true);
        $this->assertEquals('dodge', $make->getValue($car));

        $model = new \ReflectionProperty('Doctrine\Tests\Models\DDC3441\Car', 'model');
        $model->setAccessible(true);
        $this->assertEquals('neon', $model->getValue($car));

        $year = new \ReflectionProperty('Doctrine\Tests\Models\DDC3441\Car', 'year');
        $year->setAccessible(true);
        $this->assertEquals(2003, $year->getValue($car));
    }

    /**
     * Make sure that all class methods continue to get loaded correctly
     */
    public function testCanAccessCorrectReflectionMethods()
    {
        $person = $this->_em->find('Doctrine\Tests\Models\DDC3441\Person', 1);
        $car    = $person->getCar();

        $reflection = new \ReflectionClass($car);
        $methods    = $reflection->getMethods();

        //check values for all associated arrays
        $checkValues = array('getId', 'setMake', 'getMake', 'setModel', 'getModel', 'setYear', 'getYear');
        foreach ($methods as $method) {
            //skip magic methods
            if (preg_match('/^__/', $method->name) == 1) {
                return;
            }
            //make sure non magic methods exist in my expected array
            $this->assertContains($method->name, $checkValues);
            // remove matched methods from the expected array
            $key = array_search($method->name, $checkValues);
            unset($checkValues[$key]);
        }

        //ensures that all expected methods got matched
        $this->assertCount(0, $checkValues);
    }
}