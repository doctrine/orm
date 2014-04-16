<?php
/*
 * This file is part of the codeliner/doctrine2.
 * (c) Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 24.02.14 - 18:39
 */

namespace Doctrine\Tests\Models\DDC2984;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\StringType;

/**
 * Class DDC2984UserIdCustomDbalType
 *
 * @package Doctrine\Tests\Models\DDC2984
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class DDC2984UserIdCustomDbalType extends StringType
{
    public function getName()
    {
        return 'ddc2984_domain_user_id';
    }
    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (empty($value)) {
            return null;
        }

        return new DDC2984DomainUserId($value);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (empty($value)) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (!$value instanceof DDC2984DomainUserId) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }

        return $value->toString();
    }
} 