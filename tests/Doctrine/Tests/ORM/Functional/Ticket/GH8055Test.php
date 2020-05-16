<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH8055
 */
final class GH8055Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH8055BaseClass::class,
            GH8055SubClass::class,
        ]);
    }

    public function testNumericDescriminatorColumn() : void
    {
        $entity        = new GH8055SubClass();
        $entity->value = 'test';
        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        $repository = $this->_em->getRepository(GH8055SubClass::class);
        $hydrated   = $repository->find($entity->id);

        self::assertSame('test', $hydrated->value);
    }
}

/**
 * @Entity()
 * @Table(name="gh8055")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="integer")
 * @DiscriminatorMap({
 *     "1" = GH8055BaseClass::class,
 *     "2" = GH8055SubClass::class
 * })
 */
class GH8055BaseClass
{
    /**
     * @Id @GeneratedValue
     * @Column(type="integer")
     */
    public $id;
}

/**
 * @Entity()
 */
class GH8055SubClass extends GH8055BaseClass
{
    /**
     * @Column(name="test", type="string")
     * @var string
     */
    public $value;
}
