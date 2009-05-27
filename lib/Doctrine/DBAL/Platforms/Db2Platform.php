<?php

namespace Doctrine\DBAL\Platforms;

class Db2Platform extends AbstractPlatform
{
    public function getSequenceNextValSql($sequenceName)
    {
        return 'SELECT NEXTVAL FOR ' . $this->quoteIdentifier($sequenceName)
                . ' FROM SYSIBM.SYSDUMMY1';
    }

    /**
     * Get the platform name for this instance
     *
     * @return string
     */
    public function getName()
    {
        return 'db2';
    }
}