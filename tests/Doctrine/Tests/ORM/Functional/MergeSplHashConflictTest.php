<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Query;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

class MergeSplHashConflictTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }


    /**
     * @test
     * @covers Doctrine\ORM\UnitOfWork::forgetEntity
     */
    public function testForgetEntity()
    {
        $user = new CmsUser;
        $user->username = 'bilouwan';
        $user->name = 'moroine';
        $hash = spl_object_hash($user);
        $this->_em->persist($user);
        $this->_em->flush();

        $uow = $this->_em->getUnitOfWork();
        $reflexion = new \ReflectionClass(get_class($uow));
        $method = $reflexion->getMethod('forgetEntity');
        $method->setAccessible(true);
        $method->invoke($uow, $user);

        $propertyNames = [
            'entityInsertions',
            'entityUpdates',
            'entityDeletions',
            'entityIdentifiers',
            'entityStates',
            'originalEntityData',
        ];

        foreach ($propertyNames as $propertyName) {
            $property = $reflexion->getProperty($propertyName);
            $property->setAccessible(true);
            $originalEntityData = $property->getValue($uow);

            // As $user is unset, $hash could be reused by spl_object_hash
            $this->assertArrayNotHasKey($hash, $originalEntityData);
        }
    }

    /**
     * @test
     * @covers Doctrine\ORM\UnitOfWork::merge
     */
    public function testMergeSplHashConflict()
    {
        // Create
        $user = new CmsUser;
        $hash = spl_object_hash($user);

        $mergedUser = $this->_em->merge($user);
        unset($user);

        $uow = $this->_em->getUnitOfWork();
        $reflexion = new \ReflectionClass(get_class($uow));
        $property = $reflexion->getProperty('originalEntityData');
        $property->setAccessible(true);
        $originalEntityData = $property->getValue($uow);

        // As $user is unset, $hash could be reused by spl_object_hash
        $this->assertArrayNotHasKey($hash, $originalEntityData);
    }
}
