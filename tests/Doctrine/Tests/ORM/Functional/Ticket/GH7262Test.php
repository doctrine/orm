<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Persistence\PersistentObject;
use Doctrine\Tests\Mocks\QuoteStrategyMock;
use Doctrine\Tests\Models\Hydration\SimpleEntity;

/**
 * Class GH7262Test
 *
 * @author Michael Petri <mpetri@lyska.io>
 * @see https://github.com/doctrine/doctrine2/issues/7262
 *
 * @package Doctrine\Tests\ORM\Functional\Ticket
 */
final class GH7262Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

  /**
   * Setup test case with custom quote strategy.
   */
  public function setUp()
  {
    parent::setUp();

    $this->_em
      ->getConfiguration()
      ->setQuoteStrategy(new QuoteStrategyMock());

    try {
      $this->_schemaTool->createSchema(
        [
          $this->_em->getClassMetadata(SimpleEntity::class),
        ]
      );
    } catch (\Exception $e) {
    }

    PersistentObject::setObjectManager($this->_em);
  }

  /**
   * Tests that the custom quote strategy is set.
   */
  public function testQuoteStrategyIsSet()
  {
    $this->assertEquals(
      QuoteStrategyMock::class,
      get_class($this->_em->getConfiguration()->getQuoteStrategy()),
      'Quote strategy of type ' . QuoteStrategyMock::class . ' is required.'
    );
  }

  /**
   * Tests finding persisted entities while quote strategy is enabled.
   *
   * @throws \Doctrine\ORM\ORMException
   * @throws \Doctrine\ORM\OptimisticLockException
   */
  public function testFindPersistedEntitiesWithActiveQuoteStrategy()
  {
    $entity = new SimpleEntity();

    $this->_em->persist($entity);
    $this->_em->flush();

    $this->assertNotEmpty($entity->id, 'Primary key after persisting an entity expected.');

    $entities = $this->_em->getRepository(SimpleEntity::class)
      ->findAll();

    $this->assertNotEmpty($entities, 'One persisted entity expected.');
  }

}