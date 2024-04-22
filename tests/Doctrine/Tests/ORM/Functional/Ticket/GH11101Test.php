<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\Tests\IterableTester;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH11101Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema(
            [
                GH11101Entity::class,
            ]
        );

        $this->_em->persist($e1 = new GH11101Entity(1, 'A#1'));
        $this->_em->persist($e2 = new GH11101Entity(2, 'A#2'));
        $this->_em->persist($e3 = new GH11101Entity(3, 'B#1'));

        $this->_em->flush();
        $this->_em->clear();
    }

    public function testToIterableIfYieldAndBreakBeforeFinish(): void
    {
        $evm = $this->_em->getEventManager();
        
        $this->yieldFromToIterable();
        self::assertCount(0, $evm->getListeners(Events::onClear));
        
        $this->yieldFromToIterable();
        self::assertCount(0, $evm->getListeners(Events::onClear));
    }
    
    private function yieldFromToIterable()
    {
        $q = $this->_em->createQuery('SELECT a.id FROM ' . GH11101Entity::class . ' a')->setMaxResults(2);
        $result = $q->toIterable();
        
        if ($result instanceof \IteratorAggregate) {
            $result = $result->getIterator();
        }

        /* If the result is a MongoCursor, it must be advanced to the first
        * element. Rewinding should have no ill effect if $result is another
        * iterator implementation.
        */
        if ($result instanceof \Iterator) {
            $result->rewind();
            if ($result instanceof \Countable && 1 < \count($result)) {
                $result = [$result->current(), $result->current()];
            } else {
                $result = $result->valid() && null !== $result->current() ? [$result->current()] : [];
            }
        } elseif (\is_array($result)) {
            reset($result);
        } else {
            $result = null === $result ? [] : [$result];
        }
        
        return $result;
    }
}

/** @Entity */
class GH11101Entity
{
    /**
    * @var int
    * @Id
    * @Column(type="integer", name="a_id")
    */
    public $id;

    /**
    * @var string
    * @Column(type="string", length=255)
    */
    public $name;

    public function __construct(int $id, string $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }
}