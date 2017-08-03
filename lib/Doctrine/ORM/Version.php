<?php

declare(strict_types=1);

namespace Doctrine\ORM;

/**
 * Class to store and retrieve the version of Doctrine
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class Version
{
    /**
     * Current Doctrine Version
     */
    const VERSION = '3.0.0-DEV';

    /**
     * Compares a Doctrine version with the current one.
     *
     * @param string $version Doctrine version to compare.
     *
     * @return int Returns -1 if older, 0 if it is the same, 1 if version
     *             passed as argument is newer.
     */
    public static function compare($version)
    {
        $currentVersion = str_replace(' ', '', strtolower(self::VERSION));
        $version        = str_replace(' ', '', $version);

        return version_compare($version, $currentVersion);
    }
}
