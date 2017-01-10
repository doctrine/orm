<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Sequencing\SequenceGenerator;

/**
 * Mock class for SequenceGenerator.
 */
class SequenceMock extends SequenceGenerator
{
    /**
     * @var int
     */
    private $sequenceNumber = 0;

    /**
     * {@inheritdoc}
     */
    public function generate(EntityManager $em, $entity)
    {
        return $this->sequenceNumber++;
    }

    /* Mock API */

    /**
     * @return void
     */
    public function reset()
    {
        $this->sequenceNumber = 0;
    }
}
