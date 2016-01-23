<?php

namespace Shitty\Tests\ORM\Id;

use Shitty\ORM\Id\SequenceGenerator;

/**
 * Description of SequenceGeneratorTest
 *
 * @author robo
 */
class SequenceGeneratorTest extends \Shitty\Tests\OrmTestCase
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
                $this->_em->getConnection()->setFetchOneResult((int)($i / 10) * 10);
            }
            $id = $this->_seqGen->generate($this->_em, null);
            $this->assertEquals($i, $id);
            $this->assertEquals((int)($i / 10) * 10 + 10, $this->_seqGen->getCurrentMaxValue());
            $this->assertEquals($i + 1, $this->_seqGen->getNextValue());
        }


    }
}

