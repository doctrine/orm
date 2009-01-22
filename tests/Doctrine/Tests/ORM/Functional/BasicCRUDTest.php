<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Export\ClassExporter;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;

require_once dirname(__FILE__) . '/../../TestInit.php';

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

        // Update
        $user->name = 'guilherme';
        $em->flush();
        $this->assertEquals('guilherme', $user->name);

        // Delete
        $em->delete($user);
        $this->assertTrue($em->getUnitOfWork()->isRegisteredRemoved($user));
        $em->flush();
        $this->assertFalse($em->getUnitOfWork()->isRegisteredRemoved($user));
    }

    public function testMore() {
        #echo PHP_EOL . "SECOND" . PHP_EOL;
        /*$user = new CmsUser;
        $user->name = 'jon';
        $user->*/

        $ph = new CmsPhonenumber;
        $ph->phonenumber = 123456;

        $this->_em->save($ph);

        $this->_em->flush();
    }
}

