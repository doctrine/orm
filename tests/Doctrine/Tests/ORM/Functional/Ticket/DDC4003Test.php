<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Cache\Bar;
use Doctrine\Tests\ORM\Functional\SecondLevelCacheFunctionalTestCase;

use function assert;
use function uniqid;

class DDC4003Test extends SecondLevelCacheFunctionalTestCase
{
    public function testReadsThroughRepositorySameDataThatItWroteInCache(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();

        // Get the id of the first bar
        $id = $this->attractions[0]->getId();

        $repository = $this->_em->getRepository(Bar::class);

        /**
         * This instance is fresh new, no QueryCache, so the full entity gets loaded from DB.
         * It will be saved in the WRONG KEY (notice the cache.bar at the end):
         * doctrine_tests_models_cache_attraction[doctrine_tests_models_cache_attraction_doctrine.tests.models.cache.bar_1][1]
         */
        $bar = $repository->findOneBy(['id' => $id]);
        assert($bar instanceof Bar);

        // Let's change it so that we can compare its state
        $bar->setName($newName = uniqid());
        $this->_em->persist($bar);
        $this->_em->flush();

        /**
         * Flush did 2 important things for us:
         * 1: saved the entity in its right key (notice the cache.attraction at the end):
         *     doctrine_tests_models_cache_attraction[doctrine_tests_models_cache_attraction_doctrine.tests.models.cache.attraction_1][1]
         * 2: Updated the TimestampRegion cache, so that the QueryCache is actually discarded.
         *
         * So, first findOneBy will hit DB, and its entity will not be loaded from cache.
         */
        $repository->findOneBy(['id' => $id]);

        // Lets clear EM so that we don't hit IdentityMap at all.
        $this->_em->clear();

        /**
         * Here's the failing step:
         * Right now QueryCache will HIT, as nothing changed between the last one and now.
         * QueryCache holds a reference to the WRONG KEY, as we saw was formed in line 24 of this test.
         * So this instance won't be updated and will have the original name ("Boteco São Bento"), and not the uniqid().
         */
        $cached = $repository->findOneBy(['id' => $id]);
        assert($cached instanceof Bar);

        self::assertEquals($newName, $cached->getName());
    }
}
