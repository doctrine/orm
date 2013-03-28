<?php

namespace Doctrine\Tests\Models\DDC2376;

use Doctrine\ORM\Mapping\ClassMetadata;

class User
{
    public static function loadMetadata(ClassMetadata $metadata)
    {
        $metadata->mapField(array(
           'id' => true,
           'fieldName' => 'id',
           'type' => 'integer',
           'columnName' => 'user_id',
        ));

        $metadata->mapField(array(
           'fieldName' => 'username',
           'type' => 'string'
        ));
    }

    protected $id;
    protected $username;
}
