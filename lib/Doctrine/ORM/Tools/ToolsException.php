<?php

namespace Doctrine\ORM\Tools;

class ToolsException extends ORMException {
    public static function couldNotMapDoctrine1Type($type) {
        return new self("Could not map doctrine 1 type '$type'!");
    }
}