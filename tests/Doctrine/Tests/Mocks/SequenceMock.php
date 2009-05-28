<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\EntityManager;

class SequenceMock extends \Doctrine\ORM\Id\SequenceGenerator
{
    private $_sequenceNumber = 0;

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

    public function reset()
    {
        $this->_sequenceNumber = 0;
    }

    public function autoinc()
    {
        $this->_sequenceNumber++;
    }
}

