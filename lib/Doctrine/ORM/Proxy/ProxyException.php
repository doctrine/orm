<?php

namespace Doctrine\ORM\Proxy;

class ProxyException extends Doctrine\ORM\ORMException {

    public static function proxyDirectoryRequired() {
        return new self("You must configure a proxy directory. See docs for details");
    }

    public static function proxyNamespaceRequired() {
        return new self("You must configure a proxy namespace. See docs for details");
    }

}