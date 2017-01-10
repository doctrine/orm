<?php

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use PDO;

class CustomHydratorTest extends HydrationTestCase
{
    public function testCustomHydrator()
    {
        $em = $this->getTestEntityManager();
        $config = $em->getConfiguration();
        $config->addCustomHydrationMode('CustomHydrator', CustomHydrator::class);

        $hydrator = $em->newHydrator('CustomHydrator');
        self::assertInstanceOf(CustomHydrator::class, $hydrator);
        self::assertNull($config->getCustomHydrationMode('does not exist'));
    }
}

class CustomHydrator extends AbstractHydrator
{
    protected function hydrateAllData()
    {
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
