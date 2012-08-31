<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Generic\DateTimeModel;
use Doctrine\Common\Collections\Criteria;

require_once __DIR__ . '/../../TestInit.php';

/**
 * @author Josiah <josiah@jjs.id.au>
 */
class EntityRepositoryCriteriaTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('generic');
        parent::setUp();
    }

    public function tearDown()
    {
        if ($this->_em) {
            $this->_em->getConfiguration()->setEntityNamespaces(array());
        }
        parent::tearDown();
    }

    public function loadFixture()
    {
        $today = new DateTimeModel();
        $today->datetime =
        $today->date =
        $today->time =
            new \DateTime('today');
        $this->_em->persist($today);

        $tomorrow = new DateTimeModel();
        $tomorrow->datetime =
        $tomorrow->date =
        $tomorrow->time =
            new \DateTime('tomorrow');
        $this->_em->persist($tomorrow);

        $yesterday = new DateTimeModel();
        $yesterday->datetime =
        $yesterday->date =
        $yesterday->time =
            new \DateTime('yesterday');
        $this->_em->persist($yesterday);

        $this->_em->flush();

        unset($today);
        unset($tomorrow);
        unset($yesterday);

        $this->_em->clear();
    }

    public function testLteDateComparison()
    {
        $this->loadFixture();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\Generic\DateTimeModel');
        $dates = $repository->matching(new Criteria(
            Criteria::expr()->lte('datetime', new \DateTime('today'))
        ));

        $this->assertEquals(2, count($dates));
    }
}
