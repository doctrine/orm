<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Doctrine\Tests\Models\CMS\CmsEmail;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-2965
 */
class DDC2965Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsEmail'),
            ));
        } catch (\Exception $e) {
            // no action needed - schema seems to be already in place
        }
    }

    public function testPersistEntityAndThenSwitchToAssignedIdGenerator()
    {
        $email        = new CmsEmail();
        $email->email = 'example5@example.com';

        $this->_em->persist($email);
        $this->_em->flush();

        $classMetadata = $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsEmail');
        $classMetadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_NONE);
        $classMetadata->setIdGenerator(new AssignedGenerator());

        $this->_em->clear();

        $newEmail        = new CmsEmail();
        $newEmail->email = 'example@example.com';
        $newEmail->id    = 13;

        $this->_em->persist($newEmail);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertSame(13, $newEmail->id);
    }
}
