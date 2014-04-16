<?php
/*
 * This file is part of the codeliner/doctrine2.
 * (c) Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 24.02.14 - 18:32
 */

namespace Doctrine\Tests\Models\DDC2984;

/**
 * Class DDC2984DomainUserId ValueObject
 *
 * @package Doctrine\Tests\Models\DDC2372
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class DDC2984DomainUserId
{
    /**
     * @var string
     */
    private $userIdString;

    /**
     * @param string $aUserIdString
     */
    public function __construct($aUserIdString)
    {
        $this->userIdString = $aUserIdString;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->userIdString;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @param DDC2984DomainUserId $other
     * @return bool
     */
    public function sameValueAs(DDC2984DomainUserId $other)
    {
        return $this->toString() === $other->toString();
    }
} 