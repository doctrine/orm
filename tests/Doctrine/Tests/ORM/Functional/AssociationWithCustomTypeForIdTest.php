<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\AuxiliaryEntity;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedManyToManyCompositeIdEntity;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedManyToManyCompositeIdForeignKeyEntity;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedManyToManyEntity;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedOneToManyCompositeIdEntity;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedOneToManyCompositeIdForeignKeyEntity;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedOneToManyEntity;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedOneToOneCompositeIdEntity;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedOneToOneCompositeIdForeignKeyEntity;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedOneToOneEntity;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\OwningManyToManyCompositeIdEntity;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\OwningManyToManyCompositeIdForeignKeyEntity;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\OwningManyToManyEntity;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\OwningManyToOneCompositeIdEntity;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\OwningManyToOneCompositeIdForeignKeyEntity;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\OwningManyToOneEntity;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\OwningOneToOneCompositeIdEntity;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\OwningOneToOneCompositeIdForeignKeyEntity;
use Doctrine\Tests\Models\AssociationWithCustomTypeForId\OwningOneToOneEntity;
use Doctrine\Tests\OrmFunctionalTestCase;

require_once __DIR__ . '/../../TestInit.php';

class AssociationWithCustomTypeForIdTest extends OrmFunctionalTestCase
{
    public function setUp()
    {
        if (!DBALType::hasType('uuid')) {
            DBALType::addType('uuid', 'Doctrine\Tests\DbalTypes\UuidType');
        }

        $this->useModelSet('act');
        parent::setUp();

        // OneToOne

        $inversedOneToOne = new InversedOneToOneEntity('abcdef01-2345-6789-abcd-ef0123456789', 'some value to be loaded');
        $owningOneToOne = new OwningOneToOneEntity('bcdef012-3456-789a-bcde-f0123456789a');

        $inversedOneToOne->setAssociatedEntity($owningOneToOne);

        $this->_em->persist($inversedOneToOne);
        $this->_em->persist($owningOneToOne);

        // OneToOne, CompositeId

        $inversedOneToOneCompositeId = new InversedOneToOneCompositeIdEntity('cdef0123-4567-89ab-cdef-0123456789ab', 'def01234-5678-9abc-def0-123456789abc', 'some value to be loaded');
        $owningOneToOneCompositeId = new OwningOneToOneCompositeIdEntity('ef012345-6789-abcd-ef01-23456789abcd');

        $inversedOneToOneCompositeId->setAssociatedEntity($owningOneToOneCompositeId);

        $this->_em->persist($inversedOneToOneCompositeId);
        $this->_em->persist($owningOneToOneCompositeId);

        // OneToOne, CompositeId, ForeignKey

        $auxiliaryEntity = new AuxiliaryEntity('f0123456-789a-bcde-f012-3456789abcde');
        $inversedOneToOneCompositeId = new InversedOneToOneCompositeIdForeignKeyEntity('fedcba98-7654-3210-fedc-ba9876543210', $auxiliaryEntity, 'some value to be loaded');
        $owningOneToOneCompositeId = new OwningOneToOneCompositeIdForeignKeyEntity('edcba987-6543-210f-edcb-a9876543210f');

        $inversedOneToOneCompositeId->setAssociatedEntity($owningOneToOneCompositeId);

        $this->_em->persist($auxiliaryEntity);
        $this->_em->persist($inversedOneToOneCompositeId);
        $this->_em->persist($owningOneToOneCompositeId);

        // OneToMany

        $inversedOneToMany = new InversedOneToManyEntity('01234567-89ab-cdef-0123-456789abcdef');
        $owningManyToOne = new OwningManyToOneEntity('12345678-9abc-def0-1234-56789abcdef0');

        $inversedOneToMany->addAssociatedEntity($owningManyToOne);

        $this->_em->persist($inversedOneToMany);
        $this->_em->persist($owningManyToOne);

        // OneToMany, CompositeId

        $inversedOneToManyCompositeId = new InversedOneToManyCompositeIdEntity('456789ab-cdef-0123-4567-89abcdef0123', '56789abc-def0-1234-5678-9abcdef01234');
        $owningManyToOneCompositeId = new OwningManyToOneCompositeIdEntity('6789abcd-ef01-2345-6789-abcdef012345');

        $inversedOneToManyCompositeId->addAssociatedEntity($owningManyToOneCompositeId);

        $this->_em->persist($inversedOneToManyCompositeId);
        $this->_em->persist($owningManyToOneCompositeId);

        // OneToMany, CompositeId, ForeignKey

        $auxiliaryEntity = new AuxiliaryEntity('dcba9876-5432-10fe-dcba-9876543210fe');
        $inversedOneToManyCompositeId = new InversedOneToManyCompositeIdForeignKeyEntity('cba98765-4321-0fed-cba9-876543210fed', $auxiliaryEntity);
        $owningManyToOneCompositeId = new OwningManyToOneCompositeIdForeignKeyEntity('ba987654-3210-fedc-ba98-76543210fedc');

        $inversedOneToManyCompositeId->addAssociatedEntity($owningManyToOneCompositeId);

        $this->_em->persist($auxiliaryEntity);
        $this->_em->persist($inversedOneToManyCompositeId);
        $this->_em->persist($owningManyToOneCompositeId);

        // ManyToMany

        $inversedManyToMany = new InversedManyToManyEntity('23456789-abcd-ef01-2345-6789abcdef01');
        $owningManyToMany = new OwningManyToManyEntity('3456789a-bcde-f012-3456-789abcdef012');

        $inversedManyToMany->addAssociatedEntity($owningManyToMany);

        $this->_em->persist($inversedManyToMany);
        $this->_em->persist($owningManyToMany);

        // ManyToMany, CompositeId

        $inversedManyToManyCompositeId = new InversedManyToManyCompositeIdEntity('789abcde-f012-3456-789a-bcdef0123456', '89abcdef-0123-4567-89ab-cdef01234567');
        $owningManyToManyCompositeId = new OwningManyToManyCompositeIdEntity('9abcdef0-1234-5678-9abc-def012345678');

        $inversedManyToManyCompositeId->addAssociatedEntity($owningManyToManyCompositeId);

        $this->_em->persist($inversedManyToManyCompositeId);
        $this->_em->persist($owningManyToManyCompositeId);

        // ManyToMany, CompositeId, ForeignKey

        $auxiliaryEntity = new AuxiliaryEntity('a9876543-210f-edcb-a987-6543210fedcb');
        $inversedManyToManyCompositeIdForeignKey = new InversedManyToManyCompositeIdForeignKeyEntity('98765432-10fe-dcba-9876-543210fedcba', $auxiliaryEntity);
        $owningManyToManyCompositeIdForeignKey = new OwningManyToManyCompositeIdForeignKeyEntity('87654321-0fed-cba9-8765-43210fedcba9');

        $inversedManyToManyCompositeIdForeignKey->addAssociatedEntity($owningManyToManyCompositeIdForeignKey);

        $this->_em->persist($auxiliaryEntity);
        $this->_em->persist($inversedManyToManyCompositeIdForeignKey);
        $this->_em->persist($owningManyToManyCompositeIdForeignKey);

        // flush & clear

        $this->_em->flush();
        $this->_em->clear();
    }

    public static function tearDownAfterClass()
    {
        $conn = static::$_sharedConn;

        $conn->executeUpdate('DROP TABLE act_auxiliary_entities');
        $conn->executeUpdate('DROP TABLE act_inversed_manytomany_compositeid_entities');
        $conn->executeUpdate('DROP TABLE act_inversed_manytomany_compositeid_foreignkey_entities');
        $conn->executeUpdate('DROP TABLE act_inversed_manytomany_entities');
        $conn->executeUpdate('DROP TABLE act_inversed_onetomany_compositeid_entities');
        $conn->executeUpdate('DROP TABLE act_inversed_onetomany_compositeid_foreignkey_entities');
        $conn->executeUpdate('DROP TABLE act_inversed_onetomany_entities');
        $conn->executeUpdate('DROP TABLE act_inversed_onetoone_compositeid_entities');
        $conn->executeUpdate('DROP TABLE act_inversed_onetoone_compositeid_foreignkey_entities');
        $conn->executeUpdate('DROP TABLE act_inversed_onetoone_entities');
        $conn->executeUpdate('DROP TABLE act_owning_manytomany_compositeid_entities');
        $conn->executeUpdate('DROP TABLE act_owning_manytomany_compositeid_foreignkey_entities');
        $conn->executeUpdate('DROP TABLE act_owning_manytomany_entities');
        $conn->executeUpdate('DROP TABLE act_owning_manytoone_compositeid_entities');
        $conn->executeUpdate('DROP TABLE act_owning_manytoone_compositeid_foreignkey_entities');
        $conn->executeUpdate('DROP TABLE act_owning_manytoone_entities');
        $conn->executeUpdate('DROP TABLE act_owning_onetoone_compositeid_entities');
        $conn->executeUpdate('DROP TABLE act_owning_onetoone_compositeid_foreignkey_entities');
        $conn->executeUpdate('DROP TABLE act_owning_onetoone_entities');
        $conn->executeUpdate('DROP TABLE act_xref_manytomany');
        $conn->executeUpdate('DROP TABLE act_xref_manytomany_compositeid');
        $conn->executeUpdate('DROP TABLE act_xref_manytomany_compositeid_foreignkey');
    }

    public function testOneToOneAssociationIsLoaded()
    {
        $entity = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\OwningOneToOneEntity',
            'bcdef012-3456-789a-bcde-f0123456789a'
        );

        $associatedEntity = $entity->getAssociatedEntity();

        $this->assertEquals('some value to be loaded', $associatedEntity->getProxyLoadTrigger());
    }

    public function testOneToOneAssociationWithCompositeIdIsLoaded()
    {
        $entity = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\OwningOneToOneCompositeIdEntity',
            'ef012345-6789-abcd-ef01-23456789abcd'
        );

        $associatedEntity = $entity->getAssociatedEntity();

        $this->assertEquals('some value to be loaded', $associatedEntity->getProxyLoadTrigger());
    }

    public function testOneToOneAssociationWithCompositeIdIncludingForeignKeyIsLoaded()
    {
        $entity = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\OwningOneToOneCompositeIdForeignKeyEntity',
            'edcba987-6543-210f-edcb-a9876543210f'
        );

        $associatedEntity = $entity->getAssociatedEntity();

        $this->assertEquals('some value to be loaded', $associatedEntity->getProxyLoadTrigger());
    }

    public function testOneToManyAssociationIsLoaded()
    {
        $entity = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedOneToManyEntity',
            '01234567-89ab-cdef-0123-456789abcdef'
        );

        $associatedEntities = $entity->getAssociatedEntities();

        $this->assertCount(1, $associatedEntities);
    }

    public function testOneToManyAssociationWithCompositeIdIsLoaded()
    {
        $entity = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedOneToManyCompositeIdEntity',
            array('id1' => '456789ab-cdef-0123-4567-89abcdef0123', 'id2' => '56789abc-def0-1234-5678-9abcdef01234')
        );

        $associatedEntities = $entity->getAssociatedEntities();

        $this->assertCount(1, $associatedEntities);
    }

    public function testOneToManyAssociationWithCompositeIdIncludingForeignKeyIsLoaded()
    {
        $auxiliary = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\AuxiliaryEntity',
            'dcba9876-5432-10fe-dcba-9876543210fe'
        );
        $entity = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedOneToManyCompositeIdForeignKeyEntity',
            array('id' => 'cba98765-4321-0fed-cba9-876543210fed', 'foreignEntity' => $auxiliary->getId())
        );

        $associatedEntities = $entity->getAssociatedEntities();

        $this->assertCount(1, $associatedEntities);
    }

    public function testManyToManyAssociationIsLoaded()
    {
        $entity = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedManyToManyEntity',
            '23456789-abcd-ef01-2345-6789abcdef01'
        );

        $associatedEntities = $entity->getAssociatedEntities();

        $this->assertCount(1, $associatedEntities);
    }

    public function testManyToManyAssociationWithCompositeIdIsLoaded()
    {
        $entity = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedManyToManyCompositeIdEntity',
            array('id1' => '789abcde-f012-3456-789a-bcdef0123456', 'id2' => '89abcdef-0123-4567-89ab-cdef01234567')
        );

        $associatedEntities = $entity->getAssociatedEntities();

        $this->assertCount(1, $associatedEntities);
    }

    public function testManyToManyAssociationWithCompositeIdForeignKeyIsLoaded()
    {
        $auxiliary = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\AuxiliaryEntity',
            'a9876543-210f-edcb-a987-6543210fedcb'
        );
        $entity = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedManyToManyCompositeIdForeignKeyEntity',
            array('id' => '98765432-10fe-dcba-9876-543210fedcba', 'foreignEntity' => $auxiliary->getId())
        );

        $associatedEntities = $entity->getAssociatedEntities();

        $this->assertCount(1, $associatedEntities);
    }

    public function testManyToManyAssociationIsRemoved()
    {
        // remove the associated entity from collection

        $entity = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedManyToManyEntity',
            '23456789-abcd-ef01-2345-6789abcdef01'
        );

        foreach ($entity->getAssociatedEntities() as $associatedEntity) {
            $entity->removeAssociatedEntity($associatedEntity);
        }

        $this->_em->flush();
        $this->_em->clear();

        // test that it is removed

        $entity = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedManyToManyEntity',
            '23456789-abcd-ef01-2345-6789abcdef01'
        );

        $associatedEntities = $entity->getAssociatedEntities();

        $this->assertCount(0, $associatedEntities);
    }

    public function testManyToManyAssociationWithCompositeIdIsRemoved()
    {
        // remove the associated entity from collection

        $entity = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedManyToManyCompositeIdEntity',
            array('id1' => '789abcde-f012-3456-789a-bcdef0123456', 'id2' => '89abcdef-0123-4567-89ab-cdef01234567')
        );

        foreach ($entity->getAssociatedEntities() as $associatedEntity) {
            $entity->removeAssociatedEntity($associatedEntity);
        }

        $this->_em->flush();
        $this->_em->clear();

        // test that it is removed

        $entity = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedManyToManyCompositeIdEntity',
            array('id1' => '789abcde-f012-3456-789a-bcdef0123456', 'id2' => '89abcdef-0123-4567-89ab-cdef01234567')
        );

        $associatedEntities = $entity->getAssociatedEntities();

        $this->assertCount(0, $associatedEntities);
    }

    public function testManyToManyAssociationWithCompositeIdForeignKeyIsRemoved()
    {
        // remove the associated entity from collection

        $auxiliary = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\AuxiliaryEntity',
            'a9876543-210f-edcb-a987-6543210fedcb'
        );
        $entity = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedManyToManyCompositeIdForeignKeyEntity',
            array('id' => '98765432-10fe-dcba-9876-543210fedcba', 'foreignEntity' => $auxiliary->getId())
        );

        foreach ($entity->getAssociatedEntities() as $associatedEntity) {
            $entity->removeAssociatedEntity($associatedEntity);
        }

        $this->_em->flush();
        $this->_em->clear();

        // test that it is removed

        $auxiliary = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\AuxiliaryEntity',
            'a9876543-210f-edcb-a987-6543210fedcb'
        );
        $entity = $this->_em->find(
            'Doctrine\Tests\Models\AssociationWithCustomTypeForId\InversedManyToManyCompositeIdForeignKeyEntity',
            array('id' => '98765432-10fe-dcba-9876-543210fedcba', 'foreignEntity' => $auxiliary->getId())
        );

        $associatedEntities = $entity->getAssociatedEntities();

        $this->assertCount(0, $associatedEntities);
    }
}
