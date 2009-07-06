<?php

namespace Doctrine\Common\Annotations;

class Annotation
{
    public $value;
    private static $creationStack = array();

    public final function __construct(array $data)
    {
        $reflection = new \ReflectionClass($this);
        $class = $reflection->getName();
        if (isset(self::$creationStack[$class])) {
            trigger_error("Circular annotation reference on '$class'", E_USER_ERROR);
            return;
        }
        self::$creationStack[$class] = true;
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
        unset(self::$creationStack[$class]);
    }

    private function createName($target)
    {
        if ($target instanceof ReflectionMethod) {
            return $target->getDeclaringClass()->getName().'::'.$target->getName();
        } else if ($target instanceof ReflectionProperty) {
            return $target->getDeclaringClass()->getName().'::$'.$target->getName();
        } else {
            return $target->getName();
        }
    }

    //protected function checkConstraints($target) {}
}