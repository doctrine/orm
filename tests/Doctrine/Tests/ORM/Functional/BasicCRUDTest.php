<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Export\ClassExporter;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Description of BasicCRUDTest
 *
 * @author robo
 */
class BasicCRUDTest extends \Doctrine\Tests\OrmFunctionalTestCase {

    public function testSingleEntityCRUD() {
        $em = $this->_em;

        $exporter = new ClassExporter($this->_em);
        $exporter->exportClasses(array(
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser'),
            $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsPhonenumber')
        ));

        // Create
        $user = new CmsUser;
        $user->name = 'romanb';
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
        $this->assertTrue($user->phonenumbers instanceof \Doctrine\ORM\Collection);

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

    /*public function testMore() {

        $ph = new CmsPhonenumber;
        $ph->phonenumber = 123456;

        $this->_em->save($ph);

        $this->_em->flush();
    }*/
}

