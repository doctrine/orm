<?php

namespace Doctrine\Tests\ORM\Performance;

use Doctrine\Tests\Models\Cache\Country;

require_once __DIR__ . '/../../TestInit.php';

/**
 * @group DDC-2183
 * @group performance
 */
class SencondLevelCacheTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    
    protected function setUp(){}

    public function testFindCountryWithoutCache()
    {
        parent::useModelSet('cache');
        parent::setUp();

        $size           = 500;
        $countries      = array();
        $startPersist   = microtime(true);
        
        for ($i = 0; $i < $size; $i++) {
            $country = new Country("Country $i");
            
            $this->_em->persist($country);

            $countries[] = $country;
        }

        $this->_em->flush();

        printf("NOCACHE - persist %s countries %s\n", number_format(microtime(true) - $startPersist, 6), $size);

        $this->_em->clear();

        $queryCount = count($this->_sqlLoggerStack->queries);
        $startFind  = microtime(true);

        foreach ($countries as $country) {
            $this->_em->find('Doctrine\Tests\Models\Cache\Country', $country->getId());
        }

        $this->assertEquals($queryCount + $size, count($this->_sqlLoggerStack->queries));
        printf("NOCACHE - find %s countries %s\n", number_format(microtime(true) - $startFind, 6), $size);
    }

    public function testFindCountryWithCache()
    {
        parent::enableSecondLevelCache();
        parent::useModelSet('cache');
        parent::setUp();

        $size           = 500;
        $countries      = array();
        $startPersist   = microtime(true);

        for ($i = 0; $i < $size; $i++) {
            $country = new Country("Country $i");

            $this->_em->persist($country);

            $countries[] = $country;
        }

        $this->_em->flush();

        printf("CACHE - persist %s countries %s\n", number_format(microtime(true) - $startPersist, 6), $size);

        $this->_em->clear();

        $queryCount = count($this->_sqlLoggerStack->queries);
        $startFind  = microtime(true);

        foreach ($countries as $country) {
            $this->_em->find('Doctrine\Tests\Models\Cache\Country', $country->getId());
        }

        $this->assertEquals($queryCount, count($this->_sqlLoggerStack->queries));
        printf("CACHE - find %s countries %s\n", number_format(microtime(true) - $startFind, 6), $size);
    }
}