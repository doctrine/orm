<?php

namespace Doctrine\DBAL\Platforms;

class InformixPlatform extends AbstractPlatform
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get the platform name for this instance
     *
     * @return string
     */
    public function getName()
    {
        return 'informix';
    }
}