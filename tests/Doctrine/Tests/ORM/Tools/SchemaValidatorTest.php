<?php

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\Tools\SchemaValidator;

require_once __DIR__ . '/../../TestInit.php';

class SchemaValidatorTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @var EntityManager
     */
    private $em = null;

    /**
     * @var SchemaValidator
     */
    private $validator = null;

    public function setUp()
    {
        $this->em = $this->_getTestEntityManager();
        $this->validator = new SchemaValidator($this->em);
    }

    public function testCmsModelSet()
    {
        $this->em->getConfiguration()->getMetadataDriverImpl()->addPaths(array(
            __DIR__ . "/../../Models/CMS"
        ));
        $this->validator->validateMapping();
    }

    public function testCompanyModelSet()
    {
        $this->em->getConfiguration()->getMetadataDriverImpl()->addPaths(array(
            __DIR__ . "/../../Models/Company"
        ));
        $this->validator->validateMapping();
    }

    public function testECommerceModelSet()
    {
        $this->em->getConfiguration()->getMetadataDriverImpl()->addPaths(array(
            __DIR__ . "/../../Models/ECommerce"
        ));
        $this->validator->validateMapping();
    }

    public function testForumModelSet()
    {
        $this->em->getConfiguration()->getMetadataDriverImpl()->addPaths(array(
            __DIR__ . "/../../Models/Forum"
        ));
        $this->validator->validateMapping();
    }

    public function testNavigationModelSet()
    {
        $this->em->getConfiguration()->getMetadataDriverImpl()->addPaths(array(
            __DIR__ . "/../../Models/Navigation"
        ));
        $this->validator->validateMapping();
    }

    public function testRoutingModelSet()
    {
        $this->em->getConfiguration()->getMetadataDriverImpl()->addPaths(array(
            __DIR__ . "/../../Models/Routing"
        ));
        $this->validator->validateMapping();
    }
}