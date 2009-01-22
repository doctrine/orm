<?php

namespace Doctrine\DBAL\Platforms;

class Db2Platform extends AbstractPlatform
{
    
    public function getSequenceNextValSql($sequenceName) {
        return 'SELECT NEXTVAL FOR ' . $this->quoteIdentifier($sequenceName)
                . ' FROM SYSIBM.SYSDUMMY1';
    }
    
}

