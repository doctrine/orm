<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

/**
 * Expression class for building DQL OR clauses.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class Orx extends Composite
{
    /**
     * @var string
     */
    protected $separator = ' OR ';

    /**
     * @var array
     */
    protected $allowedClasses = [
        Comparison::class,
        Func::class,
        Andx::class,
        Orx::class,
    ];

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }
}
