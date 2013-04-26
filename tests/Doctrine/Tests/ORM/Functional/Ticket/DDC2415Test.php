<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\Driver\StaticPHPDriver;

/**
 * @group DDC-2415
 */
class DDC2415Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_em->getConfiguration()->setMetadataDriverImpl(new StaticPHPDriver(array()));

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2415ParentEntity'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2415ChildEntity'),
        ));
    }

    public function testTicket()
    {
        $parentMetadata  = $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2415ParentEntity');
        $childMetadata   = $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2415ChildEntity');

        $this->assertEquals($parentMetadata->generatorType, $childMetadata->generatorType);
        $this->assertEquals($parentMetadata->customGeneratorDefinition, $childMetadata->customGeneratorDefinition);
        $this->assertEquals('Doctrine\Tests\ORM\Functional\Ticket\DDC2415Generator', $parentMetadata->customGeneratorDefinition['class']);

        $e1 = new DDC2415ChildEntity("ChildEntity 1");
        $e2 = new DDC2415ChildEntity("ChildEntity 2");

        $this->_em->persist($e1);
        $this->_em->persist($e2);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertEquals(md5($e1->getName()), $e1->getId());
        $this->assertEquals(md5($e2->getName()), $e2->getId());
    }
}

class DDC2415ParentEntity
{
    protected $id;

    public function getId()
    {
        return $this->id;
    }

    public static function loadMetadata(ClassMetadataInfo $metadata)
    {
        $metadata->mapField(array (
            'id'        => true,
            'fieldName' => 'id',
            'type'      => 'string',
        ));

        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_CUSTOM);
        $metadata->setCustomGeneratorDefinition(array(
            'class' => 'Doctrine\Tests\ORM\Functional\Ticket\DDC2415Generator'
        ));

        $metadata->isMappedSuperclass = true;
    }
}

class DDC2415ChildEntity extends DDC2415ParentEntity
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public static function loadMetadata(ClassMetadataInfo $metadata)
    {
        $metadata->mapField(array (
            'fieldName' => 'name',
            'type'      => 'string',
        ));
    }
}

class DDC2415Generator extends AbstractIdGenerator
{
    public function generate(EntityManager $em, $entity)
    {
        return md5($entity->getName());
    }
}