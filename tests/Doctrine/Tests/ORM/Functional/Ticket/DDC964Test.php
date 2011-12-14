<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Query;

require_once __DIR__ . '/../../../TestInit.php';

use Doctrine\Tests\Models\DDC964\DDC964Address;
use Doctrine\Tests\Models\DDC964\DDC964Group;
use Doctrine\Tests\Models\DDC964\DDC964Admin;
use Doctrine\Tests\Models\DDC964\DDC964Guest;
use Doctrine\Tests\Models\DDC964\DDC964User;

/**
 * @group DDC-964
 */
class DDC964Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    const NS = 'Doctrine\Tests\Models\DDC964';

    public function testAssociationOverridesMapping()
    {
        $adminMetadata = $this->_em->getClassMetadata('Doctrine\Tests\Models\DDC964\DDC964Admin');
        $guestMetadata = $this->_em->getClassMetadata('Doctrine\Tests\Models\DDC964\DDC964Guest');


        // assert groups association mappings
        $this->assertArrayHasKey('groups', $guestMetadata->associationMappings);
        $this->assertArrayHasKey('groups', $adminMetadata->associationMappings);

        $guestGroups = $guestMetadata->associationMappings['groups'];
        $adminGroups = $adminMetadata->associationMappings['groups'];

        $this->assertEquals('ddc964_users_groups', $guestGroups['joinTable']['name']);
        $this->assertEquals('user_id', $guestGroups['joinTable']['joinColumns'][0]['name']);
        $this->assertEquals('group_id', $guestGroups['joinTable']['inverseJoinColumns'][0]['name']);

        $this->assertEquals(array('user_id'=>'id'), $guestGroups['relationToSourceKeyColumns']);
        $this->assertEquals(array('group_id'=>'id'), $guestGroups['relationToTargetKeyColumns']);
        $this->assertEquals(array('user_id','group_id'), $guestGroups['joinTableColumns']);


        $this->assertEquals('ddc964_users_admingroups', $adminGroups['joinTable']['name']);
        $this->assertEquals('adminuser_id', $adminGroups['joinTable']['joinColumns'][0]['name']);
        $this->assertEquals('admingroup_id', $adminGroups['joinTable']['inverseJoinColumns'][0]['name']);

        $this->assertEquals(array('adminuser_id'=>'id'), $adminGroups['relationToSourceKeyColumns']);
        $this->assertEquals(array('admingroup_id'=>'id'), $adminGroups['relationToTargetKeyColumns']);
        $this->assertEquals(array('adminuser_id','admingroup_id'), $adminGroups['joinTableColumns']);


        // assert address association mappings
        $this->assertArrayHasKey('address', $guestMetadata->associationMappings);
        $this->assertArrayHasKey('address', $adminMetadata->associationMappings);

        $guestAddress = $guestMetadata->associationMappings['address'];
        $adminAddress = $adminMetadata->associationMappings['address'];

        $this->assertEquals('address_id', $guestAddress['joinColumns'][0]['name']);
        $this->assertEquals(array('address_id'=>'id'), $guestAddress['sourceToTargetKeyColumns']);
        $this->assertEquals(array('address_id'=>'address_id'), $guestAddress['joinColumnFieldNames']);
        $this->assertEquals(array('id'=>'address_id'), $guestAddress['targetToSourceKeyColumns']);


        $this->assertEquals('adminaddress_id', $adminAddress['joinColumns'][0]['name']);
        $this->assertEquals(array('adminaddress_id'=>'id'), $adminAddress['sourceToTargetKeyColumns']);
        $this->assertEquals(array('adminaddress_id'=>'adminaddress_id'), $adminAddress['joinColumnFieldNames']);
        $this->assertEquals(array('id'=>'adminaddress_id'), $adminAddress['targetToSourceKeyColumns']);
    }

    public function testShouldCreateAndRetrieveOverriddenAssociation()
    {
        list($admin1,$admin2, $guest1,$guest2) = $this->loadFixtures();
        
        $this->_em->clear();

        $this->assertNotNull($admin1->getId());
        $this->assertNotNull($admin2->getId());

        $this->assertNotNull($guest1->getId());
        $this->assertNotNull($guest1->getId());


        $adminCount = $this->_em
                    ->createQuery('SELECT COUNT(a) FROM ' . self::NS . '\DDC964Admin a')
                    ->getSingleScalarResult();

        $guestCount = $this->_em
                    ->createQuery('SELECT COUNT(g) FROM ' . self::NS . '\DDC964Guest g')
                    ->getSingleScalarResult();

        
        $this->assertEquals(2, $adminCount);
        $this->assertEquals(2, $guestCount);


        $admin1 = $this->_em->find(self::NS . '\DDC964Admin', $admin1->getId());
        $admin2 = $this->_em->find(self::NS . '\DDC964Admin', $admin2->getId());
        
        $guest1 = $this->_em->find(self::NS . '\DDC964Guest', $guest1->getId());
        $guest2 = $this->_em->find(self::NS . '\DDC964Guest', $guest2->getId());

        
        $this->assertUser($admin1, self::NS . '\DDC964Admin', '11111-111', 2);
        $this->assertUser($admin2, self::NS . '\DDC964Admin', '22222-222', 2);

        $this->assertUser($guest1, self::NS . '\DDC964Guest', '33333-333', 2);
        $this->assertUser($guest2, self::NS . '\DDC964Guest', '44444-444', 1);
    }


    /**
     * @param DDC964User    $user
     * @param string        $addressZip
     * @param integer       $groups
     */
    private function assertUser(DDC964User $user, $className, $addressZip, $groups)
    {
        $this->assertInstanceOf($className, $user);
        $this->assertInstanceOf(self::NS . '\DDC964User', $user);
        $this->assertInstanceOf(self::NS . '\DDC964Address', $user->getAddress());
        $this->assertEquals($addressZip, $user->getAddress()->getZip());
        $this->assertEquals($groups, $user->getGroups()->count());
    }

    private function createSchemaDDC964()
    {
        try {

            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(self::NS . '\DDC964Address'),
                $this->_em->getClassMetadata(self::NS . '\DDC964Group'),
                $this->_em->getClassMetadata(self::NS . '\DDC964Guest'),
                $this->_em->getClassMetadata(self::NS . '\DDC964Admin'),
            ));
        } catch (\Exception $exc) {

        }
    }

    /**
     * @return array
     */
    private function loadFixtures()
    {
        $this->createSchemaDDC964();

        $group1 = new DDC964Group('Foo Admin Group');
        $group2 = new DDC964Group('Bar Admin Group');
        $group3 = new DDC964Group('Foo Guest Group');
        $group4 = new DDC964Group('Bar Guest Group');

        $this->_em->persist($group1);
        $this->_em->persist($group2);
        $this->_em->persist($group3);
        $this->_em->persist($group4);

        $this->_em->flush();


        $admin1 = new DDC964Admin('Admin 1');
        $admin2 = new DDC964Admin('Admin 2');
        $guest1 = new DDC964Guest('Guest 1');
        $guest2 = new DDC964Guest('Guest 2');


        $admin1->setAddress(new DDC964Address('11111-111', 'Some Country', 'Some City', 'Some Street'));
        $admin2->setAddress(new DDC964Address('22222-222', 'Some Country', 'Some City', 'Some Street'));
        $guest1->setAddress(new DDC964Address('33333-333', 'Some Country', 'Some City', 'Some Street'));
        $guest2->setAddress(new DDC964Address('44444-444', 'Some Country', 'Some City', 'Some Street'));


        $admin1->addGroup($group1);
        $admin1->addGroup($group2);
        $admin2->addGroup($group1);
        $admin2->addGroup($group2);

        $guest1->addGroup($group3);
        $guest1->addGroup($group4);
        $guest2->addGroup($group2);

        $this->_em->persist($admin1);
        $this->_em->persist($admin2);
        $this->_em->persist($guest1);
        $this->_em->persist($guest2);

        $this->_em->flush();

        return array($admin1,$admin2, $guest1,$guest2);
    }

}