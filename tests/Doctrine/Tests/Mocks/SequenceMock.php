<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\SequenceGenerator;

/**
 * Mock class for SequenceGenerator.
 */
class SequenceMock extends SequenceGenerator
{
    /** @var int */
    private $_sequenceNumber = 0;

    /**
     * {@inheritdoc}
     */
    public function generate(EntityManager $em, $entity)
    {
        return $this->_sequenceNumber++;
    }

    /* Mock API */

    public function reset(): void
    {
        $this->_sequenceNumber = 0;
    }
}
