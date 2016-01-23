<?php

namespace Shitty\Tests\Mocks;

use Shitty\ORM\EntityManager;

/**
 * Mock class for SequenceGenerator.
 */
class SequenceMock extends \Shitty\ORM\Id\SequenceGenerator
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
