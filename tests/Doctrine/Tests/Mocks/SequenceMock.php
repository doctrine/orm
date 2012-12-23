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

    /**
     * @override
     */
    public function nextId($seqName, $ondemand = true)
    {
        return $this->_sequenceNumber++;
    }

    /**
     * @override
     */
    public function lastInsertId($table = null, $field = null)
    {
        return $this->_sequenceNumber - 1;
    }

    /**
     * @override
     */
    public function currId($seqName)
    {
        return $this->_sequenceNumber;
    }

    /* Mock API */

    /**
     * @return void
     */
    public function reset()
    {
        $this->_sequenceNumber = 0;
    }

    /**
     * @return void
     */
    public function autoinc()
    {
        $this->_sequenceNumber++;
    }
}
