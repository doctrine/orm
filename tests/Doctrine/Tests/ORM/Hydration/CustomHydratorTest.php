<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use PDO;

class CustomHydratorTest extends HydrationTestCase
{
    public function testCustomHydrator(): void
    {
        $em     = $this->getTestEntityManager();
        $config = $em->getConfiguration();
        $config->addCustomHydrationMode('CustomHydrator', CustomHydrator::class);

        $hydrator = $em->newHydrator('CustomHydrator');
        $this->assertInstanceOf(CustomHydrator::class, $hydrator);
        $this->assertNull($config->getCustomHydrationMode('does not exist'));
    }
}

class CustomHydrator extends AbstractHydrator
{
    /**
     * {@inheritDoc}
     */
    protected function hydrateAllData()
    {
        return $this->_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
