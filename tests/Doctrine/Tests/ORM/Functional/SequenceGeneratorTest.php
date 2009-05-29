<?php

namespace Doctrine\Tests\ORM\Functional;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Description of SequenceGeneratorTest
 *
 * @author robo
 */
class SequenceGeneratorTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testFoo()
    {
        $this->assertEquals(1, 1);
    }
}

/**
 * @Entity
 */
class SeqUser {
    /**
     * @Id
     * @IdGenerator("sequence")
     */
    private $id;

    public function getId() {
        return $this->id;
    }
}

