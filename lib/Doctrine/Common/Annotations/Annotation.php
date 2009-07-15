<?php

namespace Doctrine\Common\Annotations;

class Annotation
{
    public $value;

    public final function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }
}