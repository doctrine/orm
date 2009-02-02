<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Export\ClassExporter;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\Forum\ForumUser;
use Doctrine\Tests\Models\Forum\ForumAvatar;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Description of BasicCRUDTest
 *
 * @author robo
 */
class BasicCRUDTest extends \Doctrine\Tests\OrmFunctionalTestCase {

    public function testBasicUnitsOfWorkWithOneToManyAssociation() {
        $em = $this->_em;

        $exporter = new ClassExporter($this->_em);
        $exporter->exportClasses(array(
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser'),
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsPhonenumber'),
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress')
        ));

        // Create
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'developer';
        $em->save($user);
        $this->assertTrue(is_numeric($user->id));
        $this->assertTrue($em->contains($user));

        // Read
        $user2 = $em->find('Doctrine\Tests\Models\CMS\CmsUser', $user->id);
        $this->assertTrue($user === $user2);

        // Add a phonenumber
        $ph = new CmsPhonenumber;
        $ph->phonenumber = "12345";
        $user->addPhonenumber($ph);
        $em->flush();
        $this->assertTrue($em->contains($ph));
        $this->assertTrue($em->contains($user));
        $this->assertTrue($user->phonenumbers instanceof \Doctrine\ORM\PersistentCollection);

        // Update name
        $user->name = 'guilherme';
        $em->flush();
        $this->assertEquals('guilherme', $user->name);

        // Add another phonenumber
        $ph2 = new CmsPhonenumber;
        $ph2->phonenumber = "6789";
        $user->addPhonenumber($ph2);
        $em->flush();
        $this->assertTrue($em->contains($ph2));

        // Delete
        $em->delete($user);
        $this->assertTrue($em->getUnitOfWork()->isRegisteredRemoved($user));
        $this->assertTrue($em->getUnitOfWork()->isRegisteredRemoved($ph));
        $this->assertTrue($em->getUnitOfWork()->isRegisteredRemoved($ph2));
        $em->flush();
        $this->assertFalse($em->getUnitOfWork()->isRegisteredRemoved($user));
        $this->assertFalse($em->getUnitOfWork()->isRegisteredRemoved($ph));
        $this->assertFalse($em->getUnitOfWork()->isRegisteredRemoved($ph2));
    }

    public function testOneToManyAssociationModification() {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'developer';

        $ph1 = new CmsPhonenumber;
        $ph1->phonenumber = "0301234";
        $ph2 = new CmsPhonenumber;
        $ph2->phonenumber = "987654321";

        $user->addPhonenumber($ph1);
        $user->addPhonenumber($ph2);

        $this->_em->save($user);
        $this->_em->flush();

        $this->assertTrue($user->phonenumbers instanceof \Doctrine\ORM\PersistentCollection);

        // Remove the first element from the collection
        unset($user->phonenumbers[0]);
        $ph1->user = null; // owning side!

        $this->_em->flush();

        $this->assertEquals(1, count($user->phonenumbers));
        $this->assertNull($ph1->user);
    }

    public function testBasicOneToOne()
    {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'developer';

        $address = new CmsAddress;
        $address->country = 'Germany';
        $address->city = 'Berlin';
        $address->zip = '12345';

        $user->address = $address; // inverse side
        $address->user = $user; // owning side!

        $this->_em->save($user);
        $this->_em->flush();

        // Check that the foreign key has been set
        $userId = $this->_em->getConnection()->execute("SELECT user_id FROM cms_addresses WHERE id=?",
                array($address->id))->fetchColumn();
        $this->assertTrue(is_numeric($userId));
    }
}

