<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

/**
 * Interface ClassMetadataResolver
 *
 * @package Doctrine\ORM\Mapping\Factory
 *
 * @since 3.0
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
interface ClassMetadataResolver
{
    /**
     * @param string $className
     *
     * @return string
     */
    public function resolveMetadataClassName(string $className) : string;

    /**
     * @param string $className
     *
     * @return string
     */
    public function resolveMetadataClassPath(string $className) : string;
}
