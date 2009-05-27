<?php

namespace Doctrine\DBAL\Platforms;

/**
 * Enter description here...
 *
 * @since 2.0
 */
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