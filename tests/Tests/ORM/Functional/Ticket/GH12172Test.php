<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTime;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;
use stdClass;

#[Group('GH12172')]
class GH12172Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(CmsUser::class);
    }

    public function testInitializeObjectNoOpForNonManagedObjects(): void
    {
        $nonEntity               = new stdClass();
        $nonEntity->someProperty = 'test';

        $this->expectNotToPerformAssertions();
        $this->_em->initializeObject($nonEntity);
    }

    public function testInitializeObjectNoOpForCustomNonEntityClass(): void
    {
        $nonEntity       = new GH12172TestClass();
        $nonEntity->data = 'some data';

        $this->expectNotToPerformAssertions();
        $this->_em->initializeObject($nonEntity);
    }

    public function testIsUninitializedObjectNoExceptionForNonManagedObjects(): void
    {
        $nonEntity = new stdClass();

        $result = $this->_em->isUninitializedObject($nonEntity);

        self::assertFalse($result, 'Non-managed objects should be considered initialized (not uninitialized)');
    }

    public function testInitializeObjectWithValidEntityClassDoesNotBreakMetadataLoading(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Test User';
        $user->username = 'testuser';
        $user->status   = 'active';

        $this->expectNotToPerformAssertions();
        $this->_em->initializeObject($user);
    }

    public function testInitializeObjectWithDifferentTypesOfObjectsConsistentBehavior(): void
    {
        $testObjects = [
            new stdClass(),
            new GH12172TestClass(),
            new DateTime(),
            (object) ['prop' => 'value'],
        ];

        foreach ($testObjects as $object) {
            $this->_em->initializeObject($object);

            $result = $this->_em->isUninitializedObject($object);
            self::assertFalse($result, 'Non-entity objects should not be considered uninitialized');
        }

        $this->addToAssertionCount(1);
    }
}

class GH12172TestClass
{
    public string $data = '';
}
