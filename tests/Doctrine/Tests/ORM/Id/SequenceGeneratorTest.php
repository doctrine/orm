<?php

namespace Doctrine\Tests\ORM\Id;

use Doctrine\ORM\Id\SequenceGenerator;
use Doctrine\Tests\Mocks\StatementArrayMock;
use Doctrine\Tests\OrmTestCase;

/**
 * Description of SequenceGeneratorTest
 *
 * @author robo
 */
class SequenceGeneratorTest extends OrmTestCase
{
    private $_em;
    private $_seqGen;

    protected function setUp()
    {
        $this->_em = $this->_getTestEntityManager();
        $this->_seqGen = new SequenceGenerator('seq', 10);
    }

    public function testGeneration()
    {
        for ($i=0; $i < 42; ++$i) {
            if ($i % 10 == 0) {
                $nextId = array(array((int)($i / 10) * 10));
                $this->_em->getConnection()->setQueryResult(new StatementArrayMock($nextId));
            }
            $id = $this->_seqGen->generate($this->_em, null);
            $this->assertEquals($i, $id);
            $this->assertEquals((int)($i / 10) * 10 + 10, $this->_seqGen->getCurrentMaxValue());
            $this->assertEquals($i + 1, $this->_seqGen->getNextValue());
        }
    }
}

