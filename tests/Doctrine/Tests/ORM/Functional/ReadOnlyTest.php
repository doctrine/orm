<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;
use function get_class;

/**
 * Functional Query tests.
 *
 * @group DDC-692
 */
class ReadOnlyTest extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(ReadOnlyEntity::class),
                ]
            );
        } catch (Exception $e) {
        }
    }

    public function testReadOnlyEntityNeverChangeTracked() : void
    {
        $readOnly = new ReadOnlyEntity('Test1', 1234);
        $this->em->persist($readOnly);
        $this->em->flush();

        $readOnly->name         = 'Test2';
        $readOnly->numericValue = 4321;

        $this->em->flush();
        $this->em->clear();

        $dbReadOnly = $this->em->find(ReadOnlyEntity::class, $readOnly->id);
        self::assertEquals('Test1', $dbReadOnly->name);
        self::assertEquals(1234, $dbReadOnly->numericValue);
    }

    /**
     * @group DDC-1659
     */
    public function testClearReadOnly() : void
    {
        $readOnly = new ReadOnlyEntity('Test1', 1234);
        $this->em->persist($readOnly);
        $this->em->flush();
        $this->em->getUnitOfWork()->markReadOnly($readOnly);

        $this->em->clear();

        self::assertFalse($this->em->getUnitOfWork()->isReadOnly($readOnly));
    }

    /**
     * @group DDC-1659
     */
    public function testClearEntitiesReadOnly() : void
    {
        $readOnly = new ReadOnlyEntity('Test1', 1234);
        $this->em->persist($readOnly);
        $this->em->flush();
        $this->em->getUnitOfWork()->markReadOnly($readOnly);

        $this->em->clear(get_class($readOnly));

        self::assertFalse($this->em->getUnitOfWork()->isReadOnly($readOnly));
    }
}

/**
 * @ORM\Entity(readOnly=true)
 */
class ReadOnlyEntity
{
    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;
    /** @ORM\Column(type="string") */
    public $name;
    /** @ORM\Column(type="integer") */
    public $numericValue;

    public function __construct($name, $number)
    {
        $this->name         = $name;
        $this->numericValue = $number;
    }
}
