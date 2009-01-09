<?php

require_once 'lib/DoctrineTestInit.php';

/**
 * Description of BasicCRUDTest
 *
 * @author robo
 */
class Orm_Functional_BasicCRUDTest extends Doctrine_OrmFunctionalTestCase {
    public function testSingleEntityCRUD() {
        $em = $this->_getEntityManager();

        $exporter = new Doctrine_ORM_Export_ClassExporter($em);
        $exporter->exportClasses(array(
            $em->getClassMetadata('CmsUser'),
            $em->getClassMetadata('CmsPhonenumber')
        ));

        // Create
        $user = new CmsUser;
        $user->name = 'romanb';
        $em->save($user);
        $this->assertTrue(is_numeric($user->id));
        $this->assertTrue($em->contains($user));

        // Read
        $user2 = $em->find('CmsUser', $user->id);
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
        
    }
}

