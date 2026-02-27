<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\OneToOneOwningSideMapping;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\ResolveTargetOneToOne\AppVendor;
use Doctrine\Tests\Models\ResolveTargetOneToOne\BaseVendor;
use Doctrine\Tests\Models\ResolveTargetOneToOne\Profile;
use Doctrine\Tests\Models\ResolveTargetOneToOne\VendorInterface;
use Doctrine\Tests\OrmTestCase;
use ReflectionMethod;

/**
 * @see https://github.com/doctrine/orm/issues/12382
 */
class BasicEntityPersisterInverseOneToOneWithResolveTargetTest extends OrmTestCase
{
    private BasicEntityPersister $persister;

    private EntityManagerMock $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em = $this->getTestEntityManager();

        $listener = new ResolveTargetEntityListener();
        $listener->addResolveTargetEntity(VendorInterface::class, AppVendor::class, []);

        $evm = $this->em->getEventManager();
        $evm->addEventListener(Events::loadClassMetadata, $listener);

        $xmlDir = __DIR__ . '/../../Models/ResolveTargetOneToOne/xml';
        $this->em->getConfiguration()->setMetadataDriverImpl(new XmlDriver($xmlDir));

        $this->em->getMetadataFactory()->getMetadataFor(Profile::class);
        $this->em->getMetadataFactory()->getMetadataFor(AppVendor::class);

        $vendorMeta = $this->em->getClassMetadata(AppVendor::class);
        $profileAssoc = $vendorMeta->associationMappings['profile'];
        assert($profileAssoc instanceof OneToOneOwningSideMapping);

        // Simulate the condition where the owning association's sourceEntity
        // retains the mapped-superclass class name. This happens in frameworks
        // like Sylius where the mapped-superclass is defined via XML and the
        // concrete entity via PHP attributes with a different mapping driver.
        $profileAssoc->sourceEntity = BaseVendor::class;

        $this->persister = new BasicEntityPersister($this->em, $this->em->getClassMetadata(Profile::class));
    }

    public function testInverseOneToOneJoinUsesCorrectTableAlias(): void
    {
        $method = new ReflectionMethod($this->persister, 'getSelectSQL');
        $selectSQL = $method->invoke($this->persister, []);

        self::assertStringContainsString('LEFT JOIN', $selectSQL);

        preg_match('/LEFT JOIN vendor (\w+) ON (\w+)\.profile_id/', $selectSQL, $matches);

        self::assertNotEmpty($matches, 'Could not find LEFT JOIN vendor ... ON ...profile_id pattern in SQL: ' . $selectSQL);
        self::assertSame(
            $matches[1],
            $matches[2],
            sprintf(
                'Table alias mismatch in JOIN condition: table aliased as "%s" but ON clause references "%s". SQL: %s',
                $matches[1],
                $matches[2],
                $selectSQL,
            ),
        );
    }
}
