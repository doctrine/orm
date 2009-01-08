<?php

require_once 'lib/DoctrineTestInit.php';

/**
 * Description of BasicCRUDTest
 *
 * @author robo
 */
class Orm_Functional_BasicCRUDTest extends Doctrine_OrmFunctionalTestCase {
    public function testFoo() {
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

        $user2 = new CmsUser;
        $user2->name = 'jwage';
        $em->save($user2);
        $this->assertTrue(is_numeric($user2->id));
        $this->assertTrue($em->contains($user2));

        // Read
        $user3 = $em->find('CmsUser', $user->id);
        $this->assertTrue($user === $user3);

        $user4 = $em->find('CmsUser', $user2->id);
        $this->assertTrue($user2 === $user4);

        $ph = new CmsPhonenumber;
        $ph->phonenumber = "12345";

        $user->phonenumbers[] = $ph;

        //var_dump($em->getUnitOfWork())
    }
}

