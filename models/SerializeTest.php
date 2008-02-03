<?php
class SerializeTest extends Doctrine_Record 
{
    public static function initMetadata($class)
    {
        $class->setTableName('serialize_test');
    
        $class->setColumn('booltest', 'boolean');
        $class->setColumn('integertest', 'integer', 4, array('unsigned' => true));
        $class->setColumn('floattest', 'float');
        $class->setColumn('stringtest', 'string', 200, array('fixed' => true));
        $class->setColumn('arraytest', 'array', 10000);
        $class->setColumn('objecttest', 'object');
        $class->setColumn('blobtest', 'blob');
        $class->setColumn('clobtest', 'clob');
        $class->setColumn('timestamptest', 'timestamp');
        $class->setColumn('timetest', 'time');
        $class->setColumn('datetest', 'date');
        $class->setColumn('enumtest', 'enum', 4, 
                         array(
                            'values' => array(
                                        'php',
                                        'java',
                                        'python'
                                        )
                               )
        );
        $class->setColumn('gziptest', 'gzip');
    }

}
