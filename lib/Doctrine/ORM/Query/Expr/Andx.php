<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

/**
 * Expression class for building DQL and parts.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class Andx extends Composite
{
    /**
     * @var string
     */
    protected $separator = ' AND ';

    /**
     * @var array
     */
    protected $allowedClasses = [
        Comparison::class,
        Func::class,
        Orx::class,
        Andx::class,
    ];

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
}
