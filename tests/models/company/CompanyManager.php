<?php

class CompanyManager extends CompanyEmployee
{
    public static function initMetadata($mapping)
    {
        $mapping->mapColumn(array(
            'fieldName' => 'title',
            'type' => 'string'
        ));
    }
}

?>