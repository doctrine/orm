<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\EntityManager;

/**
 * Mock class for SequenceGenerator.
 */
class SequenceMock extends \Doctrine\ORM\Id\SequenceGenerator
{
    /**
     * @var int
     */
    private $_sequenceNumber = 0;

    /**
     * {@inheritdoc}
     */
    public function generate(EntityManager $em, $entity)
    {
        return $this->_sequenceNumber++;
    }

    /* Mock API */

    /**
     * @return void
     */
    public function reset()
    {
        $this->_sequenceNumber = 0;
    }
}
