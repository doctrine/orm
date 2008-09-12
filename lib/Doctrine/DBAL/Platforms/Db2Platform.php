<?php

#namespace Doctrine::DBAL::Platforms;

class Doctrine_DBAL_Platforms_Db2Platform extends Doctrine_DBAL_Platforms_AbstractPlatform
{
    
    public function getSequenceNextValSql($sequenceName) {
        return 'SELECT NEXTVAL FOR ' . $this->quoteIdentifier($sequenceName)
                . ' FROM SYSIBM.SYSDUMMY1';
    }
    
}

?>