<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('GH-9230')]
class GH9230Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // ensure entity table exists
        $this->setUpEntitySchema([GH9230Entity::class]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $connection = static::$sharedConn;
        if ($connection === null) {
            return;
        }

        // remove persisted entities
        $connection->executeStatement('DELETE FROM GH9230Entity');
    }

    /**
     * This does not work before the fix in PR#9663, but is does work after the fix is applied
     */
    public static function failingValuesBeforeFix(): array
    {
        return [
            'string=""' => ['name', '', 'test name'],
            'string="0"' => ['name', '0', 'test name'],
            'string=null' => ['name', null, 'test name'],

            'int=0' => ['counter', 0, 1],
            'int=null' => ['counter', null, 1],

            'bool=false' => ['enabled', false, true],
            'bool=null' => ['enabled', null, true],

            'float=0.0' => ['price', 0.0, 1.1],
            'float=-0.0' => ['price', -0.0, 1.1],
            'float=null' => ['price', null, 1.1],

            'json=[]' => ['extra', [], 1],
            'json=0' => ['extra', 0, 1],
            'json=0.0' => ['extra', 0.0, 1],
            'json=false' => ['extra', false, 1],
            'json=""' => ['extra', '', 1, ['""', '1']],
            'json=null' => ['enabled', null, 1],
        ];
    }

    /**
     * This already works before the fix in PR#9663 is applied because none of these are falsy values in php
     */
    public static function succeedingValuesBeforeFix(): array
    {
        return [
            'string="test"' => ['name', 'test', 'test2'],
            'int=1' => ['counter', 1, 1],
            'bool=true' => ['enabled', true, 1],
            'json=[null]' => ['extra', [null], 1],
            'json=1' => ['extra', 1, 1],
            'json=1.1' => ['extra', 1.1, 1],
            'json=true' => ['extra', true, 1],
            'json="test"' => ['extra', 'test', 'test'],
            'json="1"' => ['extra', '1', 1],
        ];
    }

    #[DataProvider('failingValuesBeforeFix')]
    #[DataProvider('succeedingValuesBeforeFix')]
    public function testIssue(string $property, $falsyValue, $truthyValue): void
    {
        $counter1            = new GH9230Entity();
        $counter1->$property = $falsyValue;

        $counter2            = new GH9230Entity();
        $counter2->$property = $truthyValue;

        $this->_em->persist($counter1);
        $this->_em->persist($counter2);
        $this->_em->flush();

        $this->_em->clear();

        $persistedCounter1 = $this->_em->find(GH9230Entity::class, $counter1->id);
        $persistedCounter2 = $this->_em->find(GH9230Entity::class, $counter2->id);

        // Assert entities were persisted
        self::assertInstanceOf(GH9230Entity::class, $persistedCounter1);
        self::assertInstanceOf(GH9230Entity::class, $persistedCounter2);
        self::assertEquals($falsyValue, $persistedCounter1->$property);
        self::assertEquals($truthyValue, $persistedCounter2->$property);

        $this->_em->clear();

        $counterRepository = $this->_em->getRepository(GH9230Entity::class);

        $query = $counterRepository->createQueryBuilder('counter')
            ->select('counter.' . $property)
            ->getQuery();

        $values = $query->getSingleColumnResult();

        // Assert that there are 2 values returned.
        // This fails when there is a falsy value in the array,
        // because the first falsy value halts the hydration process (before the fix is applied).
        self::assertCount(2, $values);
    }
}


#[Entity]
class GH9230Entity
{
    /** @var int */
    #[Column(name: 'id', type: 'integer')]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;

    /** @var ?string */
    #[Column(name: 'name', type: 'string', nullable: true)]
    public $name;

    /** @var ?int */
    #[Column(name: 'counter', type: 'integer', nullable: true)]
    public $counter;

    /** @var ?bool */
    #[Column(name: 'enabled', type: 'boolean', nullable: true)]
    public $enabled;

    /** @var ?float */
    #[Column(name: 'price', type: 'decimal', scale: 1, precision: 2, nullable: true)]
    public $price;

    /** @var mixed[] */
    #[Column(name: 'extra', type: 'json', nullable: true)]
    public $extra;
}
