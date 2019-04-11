<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH5763Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(GH5763Entity::class),
        ]);
    }

    public function testInsertSqlCacheIsBustedOnColumnSetChange()
    {
        $metaData = $this->_em->getClassMetadata(GH5763Entity::class);

        list($originalIdGenerator, $originalIdGeneratorType) = [$metaData->idGenerator, $metaData->generatorType];
        $metaData->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
        $metaData->setIdGenerator(new AssignedGenerator());

        $firstObject = new GH5763Entity('foo', 10);

        $this->_em->persist($firstObject);
        $this->_em->flush();

        static::assertSame(10, $firstObject->id);

        list($metaData->idGenerator, $metaData->generatorType) = [$originalIdGenerator, $originalIdGeneratorType];

        $secondObject = new GH5763Entity('bar');

        $this->_em->persist($secondObject);
        $this->_em->flush();

        $this->assertNotNull($secondObject->id);
    }
}

/**
 * @Entity()
 * @Table()
 */
class GH5763Entity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $something;

    public function __construct($something, $id = null)
    {
        $this->id        = $id;
        $this->something = $something;
    }
}
