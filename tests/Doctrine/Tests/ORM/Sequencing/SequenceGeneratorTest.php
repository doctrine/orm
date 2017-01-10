<?php

namespace Doctrine\Tests\ORM\Sequencing;

use Doctrine\ORM\Sequencing\SequenceGenerator;
use Doctrine\Tests\OrmTestCase;

/**
 * Description of SequenceGeneratorTest
 *
 * @author robo
 */
class SequenceGeneratorTest extends OrmTestCase
{
    private $em;
    private $seqGen;

    protected function setUp()
    {
        $this->em = $this->getTestEntityManager();
        $this->seqGen = new SequenceGenerator('seq', 10);
    }

    public function testGeneration()
    {
        for ($i=0; $i < 42; ++$i) {
            if ($i % 10 == 0) {
                $this->em->getConnection()->setFetchOneResult((int)($i / 10) * 10);
            }

            $id = $this->seqGen->generate($this->em, null);

            self::assertEquals($i, $id);
            self::assertEquals((int)($i / 10) * 10 + 10, $this->seqGen->getCurrentMaxValue());
            self::assertEquals($i + 1, $this->seqGen->getNextValue());
        }
    }
}

